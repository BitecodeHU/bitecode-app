<!-- resources/views/invoices/create.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('New invoice') }}
        </h1>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto p-6 lg:p-8">
            <form action="{{ route('invoices.store') }}" method="POST">
                @csrf
                <div>
                    <x-input-label for="customer_name" :value="__('Customer name')" />
                    <x-text-input id="customer_name" name="customer_name" type="text" class="mt-1 block w-full"
                        placeholder="{{ __('Customer name') }}" required autofocus autocomplete="name" />
                    <x-input-error class="mt-2" :messages="$errors->get('customer_name')" />
                </div>
                <div class="mt-4">
                    <x-input-label for="customer_email" :value="__('Email')" />
                    <x-text-input id="customer_email" name="customer_email" type="text" class="mt-1 block w-full"
                        placeholder="{{ __('Email') }}" required autofocus autocomplete="email" />
                    <x-input-error class="mt-2" :messages="$errors->get('customer_email')" />
                </div>
                <div class="mt-4">
                    <x-input-label for="customer_tax_number" :value="__('Tax number')" />
                    <x-text-input id="customer_tax_number" name="customer_tax_number" type="text"
                        class="mt-1 block w-full" placeholder="{{ __('Tax number') }}" autofocus />
                    <x-input-error class="mt-2" :messages="$errors->get('customer_tax_number')" />
                </div>
                <div class="mt-4">
                    <x-input-label for="customer_location" :value="__('Address')" />
                    <x-text-input id="customer_location" name="customer_location" type="text"
                        class="mt-1 block w-full" placeholder="{{ __('Address') }}" autofocus />
                    <x-input-error class="mt-2" :messages="$errors->get('customer_location')" />
                </div>
                <div class="mt-4" id="dynamicServiceFields">
                    <div id="serviceContainer" class="grid md:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="service" :value="__('Service')" />
                            <x-select-input id="service" name="service[]" class="mt-1 block w-full" required
                                autofocus>
                                <option value="Weboldal készítés">Weboldal készítés</option>
                                <option value="Weboldal üzemeltetés">Weboldal üzemeltetés</option>
                            </x-select-input>
                            <x-input-error class="mt-2" :messages="$errors->get('service')" />
                        </div>
                        <div>
                            <x-input-label for="price" :value="__('Price')" />
                            <x-text-input id="price" name="price[]" type="text" class="mt-1 block w-full"
                                placeholder="{{ __('Price') }}" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('price')" />
                        </div>
                        <div>
                            <x-input-label for="discount" :value="__('Discount') . ' (%)'" />
                            <x-text-input id="discount" name="discount[]" type="text" class="mt-1 block w-full"
                                placeholder="{{ __('Discount') }} (%)" value="0" autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('discount')" />
                        </div>
                    </div>
                    <div class="flex justify-end mt-4">
                        <button type="button" id="removeServiceButton"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                            {{ __('Service removal') }}
                        </button>
                        <button type="button" id="addServiceButton"
                            class="ms-3 inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            {{ __('Add service') }}
                        </button>
                    </div>
                </div>
                <div class="max-w-7xl mx-auto mt-4 lg:mt-4 flex justify-end">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">{{ __('New invoice') }}</button>
                </div>
            </form>
        </div>
    </div>

</x-app-layout>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var addServiceButton = document.getElementById('addServiceButton');
        var removeServiceButton = document.getElementById('removeServiceButton');
        var serviceContainer = document.getElementById('serviceContainer');
        var originalServiceSelect = document.getElementById('service');
        var originalPriceInput = document.getElementById('price');
        var originalDiscountInput = document.getElementById('discount');

        addServiceButton.addEventListener('click', function() {
            // Az eredeti szolgáltatások mező leklónozása
            var newServiceSelect = originalServiceSelect.cloneNode(true);

            // Egyedi azonosító és név generálása a klónozott mezőnek
            var newServiceId = 'service_' + Math.floor(Math.random() * 1000);
            newServiceSelect.id = newServiceId;
            newServiceSelect.name = 'service[]';

            // Az eredeti kedvezmény mező leklónozása
            var newDiscountInput = originalDiscountInput.cloneNode(true);

            // Egyedi azonosító és név generálása a klónozott mezőnek
            var newDiscountId = 'discount_' + Math.floor(Math.random() * 1000);
            newDiscountInput.id = newDiscountId;
            newDiscountInput.name = 'discount[]';

            // Az eredeti ár mező leklónozása
            var newPriceInput = originalPriceInput.cloneNode(true);

            // Egyedi azonosító és név generálása a klónozott mezőnek
            var newPriceId = 'price_' + Math.floor(Math.random() * 1000);
            newPriceInput.id = newPriceId;
            newPriceInput.name = 'price[]';

            // Hozzáfűzzük a klónozott mezőket a 'container'-hez
            serviceContainer.appendChild(newServiceSelect);
            serviceContainer.appendChild(newPriceInput);
            serviceContainer.appendChild(newDiscountInput);
        });

        removeServiceButton.addEventListener('click', function() {
            // Az eltávolítás előtt győződjön meg arról, hogy van legalább egy mező
            if (serviceContainer.children.length > 3) {
                // Távolítsa el az utolsó 3 'children'-t
                for (let i = 0; i < 3; i++) {
                    serviceContainer.removeChild(serviceContainer.lastElementChild);
                }
            }
        });
    });
</script>
