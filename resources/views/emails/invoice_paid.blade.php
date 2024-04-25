@props([
    'align' => 'center',
    'actionText' => __('Open invoice'),
    'color' => 'primary',
])
<x-mail::message>
{{-- Greeting --}}

# @lang('Hello!')


{{-- Intro Lines --}}
@lang('Invoice successfully paid')

<x-mail::button :url="$invoiceLink" :color="$color">
@lang('Open invoice')
</x-mail::button>

@lang('Regards'),<br>
{{ __('Bitecode') }}

<x-slot:subcopy>
@lang(
    "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
    'into your web browser:',
    [
        'actionText' => $actionText,
    ]
) <span class="break-all">[{{ $invoiceLink }}]({{ $invoiceLink }})</span>
</x-slot:subcopy>

</x-mail::message>
