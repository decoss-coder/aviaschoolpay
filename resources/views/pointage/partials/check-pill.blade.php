@php
    $ok = (bool) ($ok ?? false);
    $label = $label ?? '';
    $pillClass = 'inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md border ' .
        ($ok
            ? 'text-emerald-800 bg-emerald-50 border-emerald-200/80'
            : 'text-red-800 bg-red-50 border-red-200/80');
@endphp

<span class="{{ $pillClass }}">
    <span class="w-1.5 h-1.5 rounded-full {{ $ok ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
    {{ $label }}
</span>
