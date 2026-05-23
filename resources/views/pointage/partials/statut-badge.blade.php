@php
    $statut = $pointage->statut ?? 'unknown';
    $class = match($statut) {
        'present' => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20',
        'retard' => 'bg-amber-100 text-amber-800 ring-amber-600/20',
        'absent' => 'bg-red-100 text-red-800 ring-red-600/20',
        'hors_zone' => 'bg-orange-100 text-orange-800 ring-orange-600/20',
        'fraude_detectee' => 'bg-rose-100 text-rose-800 ring-rose-600/20',
        default => 'bg-gray-100 text-gray-700 ring-gray-500/20',
    };
@endphp
<span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold ring-1 ring-inset {{ $class }}">
    {{ $pointage->statut_libelle }}
</span>
