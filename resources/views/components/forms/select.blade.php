@props([
    'name',
    'label' => null,
    'options' => [],     // ['value' => 'label']
    'value' => null,
    'placeholder' => 'Sélectionner...',
    'required' => false,
    'disabled' => false,
    'hint' => null,
    'col' => null,
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
    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        {{ $attributes->merge(['class' => 'form-input form-select '.($hasError ? 'is-invalid' : '')]) }}
    >
        @if($placeholder)
            <option value="" {{ $val === null || $val === '' ? 'selected' : '' }}>{{ $placeholder }}</option>
        @endif
        @if(! empty($slot) && ! $slot->isEmpty())
            {{ $slot }}
        @else
            @foreach($options as $optValue => $optLabel)
                <option value="{{ $optValue }}" {{ (string) $val === (string) $optValue ? 'selected' : '' }}>{{ $optLabel }}</option>
            @endforeach
        @endif
    </select>
    @if($hint && ! $hasError)
        <p class="form-hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="form-error">⚠ {{ $message }}</p>
    @enderror
</div>
