@php
    $vf = $pointage->validation_finale ?? 'provisoire';
    $class = match ($vf) {
        \App\Models\Pointage::VALIDATION_VALIDE => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20',
        \App\Models\Pointage::VALIDATION_PROVISOIRE => 'bg-amber-100 text-amber-800 ring-amber-600/20',
        \App\Models\Pointage::VALIDATION_INCOMPLET => 'bg-orange-100 text-orange-800 ring-orange-600/20',
        \App\Models\Pointage::VALIDATION_REJETE => 'bg-red-100 text-red-800 ring-red-600/20',
        \App\Models\Pointage::VALIDATION_ANOMALIE => 'bg-violet-100 text-violet-800 ring-violet-600/20',
        default => 'bg-gray-100 text-gray-700 ring-gray-500/20',
    };
@endphp
<span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold ring-1 ring-inset {{ $class }}">
    {{ $pointage->validation_finale_libelle }}
</span>
