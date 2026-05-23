@php
    $label = $label ?? '';
    $value = $value ?? '—';
    $hint = $hint ?? null;
    $accent = $accent ?? 'brand';
    $extraClass = $extraClass ?? '';

    $accents = [
        'brand' => 'from-white to-brand-50/80 border-brand-100/70',
        'blue' => 'from-white to-blue-50/80 border-blue-100/70',
        'emerald' => 'from-white to-emerald-50/80 border-emerald-100/70',
        'amber' => 'from-white to-amber-50/80 border-amber-100/70',
        'red' => 'from-white to-red-50/80 border-red-100/70',
        'orange' => 'from-white to-orange-50/80 border-orange-100/70',
        'violet' => 'from-white to-violet-50/80 border-violet-100/70',
        'slate' => 'from-white to-slate-50/80 border-slate-200/70',
    ];
    $accentClass = $accents[$accent] ?? $accents['brand'];
@endphp

<div class="relative overflow-hidden bg-gradient-to-br border rounded-2xl p-4 shadow-sm hover:shadow-md transition-shadow {{ $accentClass }} {{ $extraClass }}">
    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-gray-500">{{ $label }}</p>
    <p class="font-display text-2xl sm:text-3xl font-extrabold text-gray-900 mt-1.5 tabular-nums">{{ $value }}</p>
    @if($hint)
        <p class="text-[10px] text-gray-500 mt-1">{{ $hint }}</p>
    @endif
</div>
