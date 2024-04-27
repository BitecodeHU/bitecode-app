<?php

namespace App\Http\Controllers;

use App\Models\Invoice as InvoiceModel;
use Illuminate\Http\Request;
use LaravelDaily\Invoices\Invoice;
use LaravelDaily\Invoices\Classes\Party;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceGenerated;
use Illuminate\Support\Facades\URL;

// ...

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        // Keresési feltétel
        $kw = $request->q;

        if (empty($kw)) {
            // Ha nincsen keresési feltétel, akkor az összes adat mejelenítése max 10/oldal
            $allInvoices = InvoiceModel::paginate(10);
        } else {
            // Csak a keresési értéknek megfelelő adatok megjelítése
            $allInvoices = InvoiceModel::where('invoice_id', 'LIKE', "%{$kw}%")
                ->orwhere('customer_name', 'LIKE', "%{$kw}%")
                ->orwhere('customer_email', 'LIKE', "%{$kw}%")
                ->paginate(10)
                ->appends(['q' => "{$kw}"])
                ->withPath('/invoices')
                ->withQueryString();
        }
        // Oldal megjelenítése
        return view('invoices.index', ['allInvoices' => $allInvoices, 'kw' => $kw]);
    }

    public function createInvoice()
    {
        return view('invoices.create');
    }

    public function store(Request $request)
    {
        // Számla azonosító létrehozása
        $invoice_id = Str::uuid();

        // Számla sorszámának lekérése, ha létezik növeljük 1-el
        $lastSerial = InvoiceModel::orderBy('serial_number', 'desc')->first();
        $nextSerial = $lastSerial ? $lastSerial->serial_number + 1 : 1;

        // Szolgáltatások és Árak lekérdezése
        $services = implode(', ', $request->input('service'));
        $prices = implode(', ', $request->input('price'));
        $discount = implode(', ', $request->input('discount'));

        // Létrehozzuk az 'InvoiceModel'-t és elmentjük az adatokat az adatbázisba
        $invoice = new InvoiceModel([
            'invoice_id' => $invoice_id,
            'serial_number' => $nextSerial,
            'customer_name' => $request->input('customer_name'),
            'customer_email' => strtolower($request->input('customer_email')),
            'customer_tax_number' => $request->input('customer_tax_number'),
            'customer_location' => $request->input('customer_location'),
            'service' => $services,
            'price' => $prices,
            'discount' => $discount,
            'invoice_date' => now(),
        ]);

        $invoice->save();

        // Lekérünk minden adatot az adatbázisból
        $invoiceData = InvoiceModel::where('invoice_id', $invoice_id)->firstOrFail();

        // A szolgálatasások és árak tömbből stringeket csinálunk
        $services = explode(', ', $invoiceData->service);
        $prices = explode(',', $invoiceData->price);
        $discount = explode(',', $invoiceData->discount);
        $prices = array_map(function ($price) {
            return floatval(str_replace(',', '.', $price));
        }, $prices);

        // Definiáljuk a számla kiállításának dátumát
        $date = $invoiceData->invoice_date;

        // Átkonvertájuk a dátumot
        $carbon_date = Carbon::parse($date);

        // Létrehozzuk az eladót
        $client = new Party([
            'name' => 'RATIO EGÉSZSÉGÜGYI Kft.',
            'custom_fields' => [
                'cím' => '4025 Debrecen, Miklós utca 50/A. földszint 3.',
                'adószám' => '24367855-1-09',
                'email' => 'hello@bitecode.hu',
                'Telefonszám:' => '+36 20 289 1551',
            ],
        ]);

        // Létrehozunk egy tömböt amiben majd azokat az adatokat tároljuk, amiket extraként szeretnénk megjeleníteni a számlán
        $custom_fields = [];

        if (!empty($invoiceData->customer_location)) {
            $custom_fields['cím'] = $invoiceData->customer_location;
        }

        if (!empty($invoiceData->customer_tax_number)) {
            $custom_fields['adószám'] = $invoiceData->customer_tax_number;
        }

        $custom_fields['email'] = $invoiceData->customer_email;
        $custom_fields['számla azonosító'] = $invoiceData->invoice_id;

        // Létrehozzuk a vevőt
        $customer = new Party([
            'name' => $invoiceData->customer_name,
            'custom_fields' => $custom_fields,
        ]);

        // Megvizsgáljuk, hogy a számlát kifizették-e
        if ($invoiceData->is_paid == 1) {
            $paid = __('invoices::invoice.paid');
        } else {
            $paid = '';
        }

        $items = [];

        for ($i = 0; $i < count($services); $i++) {
            // Létrehozzunk minden számlatételt, és hozzáadjuk az $items tömbhöz
            $items[] = InvoiceItem::make($services[$i])
                ->pricePerUnit($prices[$i])
                ->discountByPercent($discount[$i])
                ->taxByPercent(27);
        }

        $customData = [
            'invoice_id' => $invoiceData->invoice_id,
            'paid' => $invoiceData->paid,
        ];

        // Legeneráljuk a számlát
        $invoice = Invoice::make()
            ->sequence($invoiceData->serial_number)
            ->seller($client)
            ->buyer($customer)
            ->addItems($items)
            ->payUntilDays(14)
            ->dateFormat('Y/m/d')
            ->date($carbon_date)
            ->currencySymbol('Ft')
            ->currencyCode('HUF')
            ->currencyDecimalPoint(',')
            ->currencyThousandsSeparator(' ')
            ->logo(public_path('img/B-black.webp'))
            ->status($paid)
            ->setCustomData($customData)
            ->filename($client->name . '-' . $customer->name . '-' . $invoice_id . '-' . $date)
            ->save('public');

        // Létrehozzuk a számla linkjét
        $link = URL::route('invoice.show', ['invoice_id' => $invoice_id], false);
        $fullLink = url($link);
        $customerEmail = $invoiceData->customer_email;
        $invoiceMail = new InvoiceGenerated($customerEmail, $fullLink);

        // Elküldjük a számlát az ügyfélnek
        Mail::to($customerEmail)->send($invoiceMail);

        return redirect('/invoices')->with('success', 'Invoice created successfully!');
    }

    public function show($invoice_id)
    {
        // Lekérünk minden adatot az adatbázisból
        $invoiceData = InvoiceModel::where('invoice_id', $invoice_id)->firstOrFail();

        $invoice_id = $invoiceData->invoice_id;

        // A szolgálatasások és árak tömbből stringeket csinálunk
        $services = explode(', ', $invoiceData->service);
        $prices = explode(',', $invoiceData->price);
        $discount = explode(',', $invoiceData->discount);
        $prices = array_map(function ($price) {
            return floatval(str_replace(',', '.', $price));
        }, $prices);

        // Definiáljuk a számla kiállításának dátumát
        $date = $invoiceData->invoice_date;

        // Átkonvertájuk a dátumot
        $carbon_date = Carbon::parse($date);

        // Létrehozzuk az eladót
        $client = new Party([
            'name' => 'RATIO EGÉSZSÉGÜGYI Kft.',
            'custom_fields' => [
                'cím' => '4025 Debrecen, Miklós utca 50/A. földszint 3.',
                'adószám' => '24367855-1-09',
                'email' => 'hello@bitecode.hu',
                'Telefonszám:' => '+36 20 289 1551',
            ],
        ]);

        // Létrehozunk egy tömböt amiben majd azokat az adatokat tároljuk, amiket extraként szeretnénk megjeleníteni a számlán
        $custom_fields = [];

        if (!empty($invoiceData->customer_location)) {
            $custom_fields['cím'] = $invoiceData->customer_location;
        }

        if (!empty($invoiceData->customer_tax_number)) {
            $custom_fields['adószám'] = $invoiceData->customer_tax_number;
        }

        $custom_fields['email'] = $invoiceData->customer_email;
        $custom_fields['számla azonosító'] = $invoiceData->invoice_id;

        // Létrehozzuk a vevőt
        $customer = new Party([
            'name' => $invoiceData->customer_name,
            'custom_fields' => $custom_fields,
        ]);

        // Megvizsgáljuk, hogy a számlát kifizették-e
        if ($invoiceData->is_paid == 1) {
            $paid = __('invoices::invoice.paid');
        } else {
            $paid = '';
        }

        $items = [];

        for ($i = 0; $i < count($services); $i++) {
            // Létrehozzunk minden számlatételt, és hozzáadjuk az $items tömbhöz
            $items[] = InvoiceItem::make($services[$i])
                ->pricePerUnit($prices[$i])
                ->discountByPercent($discount[$i])
                ->taxByPercent(27);
        }

        $customData = [
            'invoice_id' => $invoiceData->invoice_id,
            'paid' => $invoiceData->is_paid,
        ];

        // Legeneráljuk a számlát
        $invoice = Invoice::make()
            ->sequence($invoiceData->serial_number)
            ->seller($client)
            ->buyer($customer)
            ->addItems($items)
            ->payUntilDays(14)
            ->dateFormat('Y/m/d')
            ->date($carbon_date)
            ->currencySymbol('Ft')
            ->currencyCode('HUF')
            ->currencyDecimalPoint(',')
            ->currencyThousandsSeparator(' ')
            ->logo(public_path('img/B-black.webp'))
            ->status($paid)
            ->setCustomData($customData)
            ->filename($client->name . '-' . $customer->name . '-' . $invoice_id . '-' . $date)
            ->save('public');

        $link = $invoice->url();

        return $invoice->stream();
    }

    public function search(Request $request)
    {
        // Keresési feltétel
        $kw = $request->q;
        if (empty($kw)) {
            // Ha nincsen keresési feltétel, akkor az összes adat mejelenítése max 10/oldal
            $allInvoices = InvoiceModel::paginate(10);
        } else {
            // Csak a keresési értéknek megfelelő adatok megjelítése
            $allInvoices = InvoiceModel::where('invoice_id', 'LIKE', "%{$kw}%")
                ->orwhere('customer_name', 'LIKE', "%{$kw}%")
                ->orwhere('customer_email', 'LIKE', "%{$kw}%")
                ->paginate(10)
                ->appends(['q' => "{$kw}"])
                ->withPath('/invoices')
                ->withQueryString();
        }

        // Tömb átalakítása Laravel collection-né
        $allInvoicesCollection = collect($allInvoices);
        // A lekérdezett adatok egyesítése oldalszámozási hivatkozásokkal HTML-ben
        $allInvoicesCollection = $allInvoicesCollection->merge(['pagination_links' => (string) $allInvoices->onEachSide(2)->links()]);

        // Visszaadjuk az adatokat JSON string formában
        return collect(['allInvoices' => $allInvoicesCollection->all()])->toJson();
    }
}
