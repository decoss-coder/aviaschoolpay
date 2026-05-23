@props([
    'name',
    'label' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'hint' => null,
    'rows' => 4,
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
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($readonly) readonly @endif
        {{ $attributes->merge(['class' => 'form-textarea '.($hasError ? 'is-invalid' : '')]) }}
    >{{ $val }}</textarea>
    @if($hint && ! $hasError)
        <p class="form-hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="form-error">⚠ {{ $message }}</p>
    @enderror
</div>
