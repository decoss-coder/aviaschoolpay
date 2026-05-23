@props([
    'name',
    'label' => null,
    'options' => [],     // ['M' => ['label' => 'Garçon', 'icon' => '♂', 'color' => 'blue']]
    'value' => null,
    'required' => false,
    'col' => null,
    'columns' => 2,
    'hint' => null,
])

@php
    $val = old($name, $value);
    $hasError = $errors->has($name);
    $gridCols = match((int)$columns) {
        3 => 'grid-cols-3',
        4 => 'grid-cols-2 md:grid-cols-4',
        default => 'grid-cols-2',
    };
@endphp

<div class="form-field {{ $col ?? '' }}">
    @if($label)
        <label class="form-label">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif
    <div class="grid {{ $gridCols }} gap-2">
        @foreach($options as $optValue => $opt)
            @php
                $optLabel = is_array($opt) ? ($opt['label'] ?? $optValue) : $opt;
                $icon = is_array($opt) ? ($opt['icon'] ?? null) : null;
                $color = is_array($opt) ? ($opt['color'] ?? 'brand') : 'brand';
                $isChecked = (string) $val === (string) $optValue;
                $hoverClass = "hover:border-{$color}-300";
                $checkedClass = "has-[:checked]:bg-gradient-to-br has-[:checked]:from-{$color}-50 has-[:checked]:to-{$color}-100/50 has-[:checked]:border-{$color}-300 has-[:checked]:shadow-sm";
                $textClass = "peer-checked:text-{$color}-700";
            @endphp
            <label class="relative flex items-center justify-center gap-2 px-3 py-2.5 bg-white border border-brand-100 rounded-xl cursor-pointer transition-all {{ $hoverClass }} {{ $checkedClass }}">
                <input type="radio" name="{{ $name }}" value="{{ $optValue }}"
                       @if($isChecked) checked @endif
                       @if($required) required @endif
                       class="sr-only peer no-default-style" />
                @if($icon)
                    <span class="w-5 h-5 rounded-full bg-gradient-to-br from-{{ $color }}-400 to-{{ $color }}-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">{{ $icon }}</span>
                @endif
                <span class="text-sm font-semibold text-gray-700 {{ $textClass }}">{{ $optLabel }}</span>
            </label>
        @endforeach
    </div>
    @if($hint && ! $hasError)
        <p class="form-hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="form-error">⚠ {{ $message }}</p>
    @enderror
</div>
