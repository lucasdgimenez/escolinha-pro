@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
])

@php
$base = 'inline-flex items-center justify-center font-medium rounded-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

$variants = [
    'primary'   => 'bg-primary-600 text-white border-transparent hover:bg-primary-700 focus:ring-primary-500',
    'secondary' => 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 focus:ring-primary-500',
    'danger'    => 'bg-error-500 text-white border-transparent hover:bg-error-700 focus:ring-error-500',
];

$sizes = [
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base',
];
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => $base.' '.$variants[$variant].' '.$sizes[$size]]) }}
>
    {{ $slot }}
</button>
