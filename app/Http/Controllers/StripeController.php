<?php

namespace App\Http\Controllers;

use App\Mail\InvoicePaid;
use App\Models\Invoice as InvoiceModel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class StripeController extends Controller
{
    public function index()
    {
        return view('index');
    }

    public function checkout($invoice_id)
    {
        $invoiceData = InvoiceModel::where('invoice_id', $invoice_id)->firstOrFail();

        $services = explode(', ', $invoiceData->service);
        $prices = explode(',', $invoiceData->price);
        $discounts = explode(',', $invoiceData->discount);

        $numericPrices = array_map(function ($price) {
            return (float) str_replace(',', '.', $price);
        }, $prices);

        // Apply discounts to prices
        $discountedPrices = [];
        for ($i = 0; $i < count($numericPrices); $i++) {
            $discount = isset($discounts[$i]) ? (float) $discounts[$i] : 0;
            $discountedPrices[] = $numericPrices[$i] * (1 - $discount / 100);
        }

        $totalAmount = array_sum($discountedPrices);
        $tax = $totalAmount * 0.27;
        $totalAmountWithTax = $totalAmount + $tax;

        \Stripe\Stripe::setApiKey(config('stripe.sk'));

        $session = \Stripe\Checkout\Session::create([
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'huf',
                        'product_data' => [
                            'name' => 'Szolgáltatás: ' . $invoiceData->service,
                            'description' => 'Számla azonosító: ' . $invoiceData->invoice_id,
                        ],
                        'unit_amount' => $totalAmountWithTax * 100,
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => route('success', ['invoice_id' => $invoice_id]),
            'cancel_url' => route('invoice.show', ['invoice_id' => $invoice_id]),
        ]);

        return redirect()->away($session->url);
    }

    public function success($invoice_id)
    {
        InvoiceModel::where('invoice_id', $invoice_id)->update([
            'is_paid' => '1',
        ]);

        $invoiceData = InvoiceModel::where('invoice_id', $invoice_id)->firstOrFail();

        // Létrehozzuk a számla linkjét
        $link = URL::route('invoice.show', ['invoice_id' => $invoice_id], false);
        $fullLink = url($link);
        $customerEmail = $invoiceData->customer_email;
        $invoiceMail = new InvoicePaid($customerEmail, $fullLink);

        // Elküldjük a számlát az ügyfélnek
        Mail::to($customerEmail)->send($invoiceMail);

        return redirect()->route('invoice.show', ['invoice_id' => $invoice_id]);
    }
}
