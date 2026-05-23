@php
    $nbAlertes = $pointage->alertes->count();
    $isAnomalie = $pointage->statut === 'hors_zone'
        || $pointage->statut === 'fraude_detectee'
        || $pointage->spoofing_detecte
        || ! $pointage->gps_valide
        || ! $pointage->token_valide
        || ! $pointage->conforme_emploi_temps;
    $showUrl = route('pointages.show', $pointage);
@endphp

<a href="{{ $showUrl }}" class="block bg-white border border-gray-200/80 rounded-2xl p-4 shadow-sm hover:shadow-md hover:border-brand-200/80 transition-all">
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            @if(!empty($pointage->enseignant->photo_path))
                <img src="{{ route('enseignants.photo', $pointage->enseignant) }}" alt="" class="w-12 h-12 rounded-xl object-cover shrink-0">
            @else
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-sm font-extrabold shrink-0 bg-gradient-to-br from-brand-500 to-brand-700 text-white">
                    {{ strtoupper(substr($pointage->enseignant->prenom ?? 'X', 0, 1)) }}{{ strtoupper(substr($pointage->enseignant->nom ?? 'X', 0, 1)) }}
                </div>
            @endif
            <div class="min-w-0">
                <p class="font-bold text-gray-900 truncate">{{ $pointage->enseignant->nom_complet ?? '—' }}</p>
                <p class="text-xs text-gray-500">{{ $pointage->heure_scan ? substr((string) $pointage->heure_scan, 0, 5) : '—' }} · {{ $pointage->type_scan_libelle }}</p>
            </div>
        </div>
        @include('pointage.partials.statut-badge', ['pointage' => $pointage])
    </div>

    <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
        <div class="rounded-lg bg-gray-50 px-3 py-2">
            <span class="text-gray-500">Salle</span>
            <p class="font-bold text-gray-900 truncate">{{ $pointage->salle->nom ?? '—' }}</p>
        </div>
        <div class="rounded-lg bg-gray-50 px-3 py-2">
            <span class="text-gray-500">Distance</span>
            <p class="font-bold text-gray-900 tabular-nums">{{ number_format((float) ($pointage->distance_ecole_metres ?? 0), 0, ',', ' ') }} m</p>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2">
        @include('pointage.partials.cahier-texte-badge', ['pointage' => $pointage])
        @if($nbAlertes > 0)
            <span class="text-[10px] font-bold text-red-700">{{ $nbAlertes }} alerte(s)</span>
        @elseif($isAnomalie)
            <span class="text-[10px] font-bold text-amber-700">Anomalie</span>
        @endif
    </div>
</a>
