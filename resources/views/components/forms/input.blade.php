@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'hint' => null,
    'col' => null, // ex: 'md:col-span-2' ou 'form-grid-full'
    'icon' => null, // svg path stroke
])

@php
    $id = $attributes->get('id') ?: $name;
    $val = old($name, $value);
    $hasError = $errors->has($name);
@endphp

<div class="form-field {{ $col ?? '' }}">
    @if($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}
            @if($required) <span class="text-rose-500">*</span> @endif
        </label>
    @endif
    <div class="relative">
        @if($icon)
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
            </span>
        @endif
        <input
            type="{{ $type }}"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ $val }}"
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            {{ $attributes->merge(['class' => 'form-input '.($hasError ? 'is-invalid' : '').($icon ? ' pl-11' : '')]) }}
        />
    </div>
    @if($hint && ! $hasError)
        <p class="form-hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="form-error">⚠ {{ $message }}</p>
    @enderror
</div>
