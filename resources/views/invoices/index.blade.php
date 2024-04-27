<!-- resources/views/invoices/index.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Prepared invoices') }}
        </h1>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto mt-6 lg:mt-8 flex justify-end">
            <a href="{{ url('/create-invoice') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">{{ __('New invoice') }}</a>
        </div>

        <div class="max-w-7xl mx-auto mt-6 lg:mt-8">
            <div>
                <x-input-label for="search" :value="__('Search')" />
                <x-text-input id="search" name="search" type="text" class="mt-1 block w-full"
                    placeholder="{{ __('Search') }}" autofocus autocomplete="name" value="{{ $kw }}" />
                <x-input-error class="mt-2" :messages="$errors->get('search')" />
            </div>

            <div class="overflow-x-auto">
                <table id="allInvoicesTbl"
                    class="mx-auto min-w-full bg-white dark:bg-gray-800 rounded-md overflow-hidden text-gray-800 dark:text-gray-200 leading-tight mt-6 lg:mt-8">
                    <thead class="bg-gray-200 dark:bg-gray-700">
                        <tr>
                            <th class="py-2 px-4">{{ __('Invoice id') }}</th>
                            <th class="py-2 px-4">{{ __('Customer name') }}</th>
                            <th class="py-2 px-4">{{ __('Customer email') }}</th>
                            <th class="py-2 px-4">{{ __('Service(s)') }}</th>
                            <th class="py-2 px-4">{{ __('Open invoice') }}</th>
                            <!-- Add other columns as needed -->
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($allInvoices as $invoice)
                            <tr>
                                <td class="py-2 px-4 text-center">{{ $invoice->invoice_id }}</td>
                                <td class="py-2 px-4 text-center">{{ $invoice->customer_name }}</td>
                                <td class="py-2 px-4 text-center">{{ $invoice->customer_email }}</td>
                                <td class="py-2 px-4 text-center">{{ $invoice->service }}</td>
                                <td class="py-2 px-4 text-center">
                                    <a href="{{ route('invoices.show', ['invoice_id' => $invoice->invoice_id]) }}"
                                        target="_blank"
                                        class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                        {{ __('Open invoice') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="search-message" class="py-2 px-4 text-center text-gray-800 dark:text-gray-200"></div>
            <div id="pagination-links" class="py-2 px-4">
                {{ $allInvoices->onEachSide(2)->links() }}
            </div>
        </div>
    </div>
</x-app-layout>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    // Ajax változó
    var search_ajax;
    $(document).ready(function() {
        // 'Live Searching' végrehajtása, hogyha megváltozik a keresés mezőnek az értéke
        $('#search').on('input change', function(e) {
            e.preventDefault()
            // Aktuális keresési érték
            var searchTxt = $(this).val()
            // URL paraméterek definiálása
            var urlParams = new URLSearchParams(location.search);
            // Új paraméterek tömbje
            var newParams = []
            urlParams.forEach((v, k) => {
                if (k == 'q') {
                    // Keresési érték frissítése
                    v = searchTxt
                }
                // Paraméter hozzáadása az új paraméterhez
                if (searchTxt != "" && k == 'q')
                    newParams.push(`${k}=${encodeURIComponent(v)}`);
            })
            // URL frissítése az oldal újratöltése nélkül
            if (newParams.length > 0) {
                // Új URL strukturálása
                var newLink = `{{ URL::to('/invoices') }}?` + (newParams.join('&'));
                // URL frissítése
                history.pushState({}, "", newLink)
            } else {
                if (searchTxt != "") {
                    // URL frissítése
                    history.pushState({}, "",
                        `{{ URL::to('/invoices') }}?q=${encodeURIComponent(searchTxt)}`)
                } else {
                    // URL frissítése
                    history.pushState({}, "", `{{ URL::to('/invoices') }}`)
                }

            }

            if (search_ajax != undefined && search_ajax != null) {
                // Az előző keresési Ajax folyamat megszakítása, ha létezik
                search_ajax.abort();
            }
            // Ajax folyamat keresésének elínditása
            search_ajax = $.ajax({
                url: `{{ URL::to('invoice-search') }}?q=${searchTxt}`,
                dataType: 'json',
                error: err => {
                    console.log(err)
                    if (err.statusText != 'abort')
                        alert('An error occurred');
                },
                success: function(resp) {
                    console.log('AJAX Response:', resp);
                    if (!!resp.allInvoices) {
                        // Tábla 'body' eleme
                        var tblBody = $('#allInvoicesTbl tbody')
                        // 'Links' elem
                        var paginationLink = $('#pagination-links')
                        // Jelenlegi adatok eltávolítása
                        tblBody.html('')
                        // Linkek eltávolítása
                        paginationLink.html('')
                        if (!!resp.allInvoices.data) {
                            // Keresési adatok iterálása
                            Object.values(resp.allInvoices.data).map(invoice => {
                                // Új tábla sor generálása
                                var tr = $('<tr>');

                                // A sor új oszlopainak és adatainak létrehozása
                                $('<td>').addClass('py-2 px-4 text-center').text(
                                    invoice.invoice_id).appendTo(tr);
                                $('<td>').addClass('py-2 px-4 text-center').text(
                                    invoice.customer_name).appendTo(tr);
                                $('<td>').addClass('py-2 px-4 text-center').text(
                                    invoice.customer_email).appendTo(tr);
                                $('<td>').addClass('py-2 px-4 text-center').text(
                                    invoice.service).appendTo(tr);

                                // 'Számla megnyítása' gomb létrehozása
                                var link = $('<a>')
                                    .attr('href', invoice.invoice_id)
                                    .attr('target', '_blank')
                                    .addClass(
                                        'inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150'
                                    )
                                    .text('{{ __('Open invoice') }}');

                                // Link hozzásadása a táblázat celláihoz
                                $('<td>').addClass('py-2 px-4 text-center').append(
                                    link).appendTo(tr);

                                // A sor hozzáadása a táblázathoz
                                tblBody.append(tr);
                            });

                            if (Object.keys(resp.allInvoices.data).length <= 0) {
                                // Üzenet megjelenítése, ha nem található a kulcsszónak megfelelő adat
                                var tr = $('<tr>');
                                tr.append(
                                    $('<td>').attr('colspan', '5').addClass(
                                        'text-center').text(
                                        '{{ __('No such invoice') }}')
                                );
                                tblBody.append(tr);
                            }
                        }
                        // Oldal link frissítése
                        if (!!resp.allInvoices.pagination_links)
                            paginationLink.html(resp.allInvoices.pagination_links)
                    }
                }
            })
        })
    })
</script>
