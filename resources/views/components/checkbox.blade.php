@props([
    'label' => null,
    'name'  => null,
    'id'    => null,
])

@php
$checkboxId = $id ?? $name;
$errors     = $errors ?? new \Illuminate\Support\ViewErrorBag;
@endphp

<div class="flex items-start gap-2">
    <input
        type="checkbox"
        id="{{ $checkboxId }}"
        name="{{ $name }}"
        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 focus:ring-offset-0 cursor-pointer"
        {{ $attributes }}
    />

    @if ($label)
        <label for="{{ $checkboxId }}" class="text-sm text-gray-700 cursor-pointer select-none">
            {{ $label }}
        </label>
    @endif

    @if ($name)
        @error($name)
            <p class="text-xs text-error-700 mt-1">{{ $message }}</p>
        @enderror
    @endif
</div>
