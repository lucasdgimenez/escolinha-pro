@props([
    'label'    => null,
    'name'     => null,
    'id'       => null,
    'required' => false,
])

@php
$selectId  = $id ?? $name;
$errors    = $errors ?? new \Illuminate\Support\ViewErrorBag;
$hasError  = $name && $errors->has($name);
$selectClass = 'block w-full rounded-lg border px-3 py-2 text-sm text-gray-900 bg-white '
    . 'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent '
    . 'disabled:bg-gray-100 disabled:cursor-not-allowed transition-colors '
    . ($hasError ? 'border-error-500 bg-error-50' : 'border-gray-300');
@endphp

<div class="flex flex-col gap-1">
    @if ($label)
        <label for="{{ $selectId }}" class="text-sm font-medium text-gray-700">
            {{ $label }}
            @if ($required)
                <span class="text-error-500" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <select
        id="{{ $selectId }}"
        name="{{ $name }}"
        {{ $attributes->merge(['class' => $selectClass]) }}
    >
        {{ $slot }}
    </select>

    @if ($name)
        @error($name)
            <p class="text-xs text-error-700">{{ $message }}</p>
        @enderror
    @endif
</div>
