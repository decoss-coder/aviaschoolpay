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

<tr class="group hover:bg-brand-50/40 transition-colors cursor-pointer border-b border-gray-100/80 last:border-0"
    onclick="window.location='{{ $showUrl }}'">
    <td class="px-5 py-4">
        <div class="flex items-center gap-3 min-w-[200px]">
            @if(!empty($pointage->enseignant->photo_path))
                <img src="{{ route('enseignants.photo', $pointage->enseignant) }}"
                     alt=""
                     class="w-11 h-11 rounded-xl object-cover ring-2 ring-white shadow-sm shrink-0">
            @else
                <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xs font-extrabold shrink-0 bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-sm">
                    {{ strtoupper(substr($pointage->enseignant->prenom ?? 'X', 0, 1)) }}{{ strtoupper(substr($pointage->enseignant->nom ?? 'X', 0, 1)) }}
                </div>
            @endif
            <div class="min-w-0">
                <p class="text-sm font-bold text-gray-900 truncate group-hover:text-brand-700 transition-colors">
                    {{ $pointage->enseignant->nom_complet ?? '—' }}
                </p>
                <p class="text-[11px] text-gray-400 truncate">{{ $pointage->enseignant->telephone ?? '—' }}</p>
            </div>
        </div>
    </td>
    <td class="px-4 py-4 whitespace-nowrap">
        <p class="text-sm font-semibold text-gray-800">{{ $pointage->heure_scan ? substr((string) $pointage->heure_scan, 0, 5) : '—' }}</p>
        <p class="text-[10px] text-gray-400 uppercase tracking-wide">{{ $pointage->type_scan_libelle }}</p>
    </td>
    <td class="px-4 py-4">
        @include('pointage.partials.statut-badge', ['pointage' => $pointage])
    </td>
    <td class="px-4 py-4 min-w-[140px]">
        <p class="text-sm font-semibold text-gray-800">{{ $pointage->salle->nom ?? '—' }}</p>
        <p class="text-[11px] text-gray-500 tabular-nums">{{ number_format((float) ($pointage->distance_ecole_metres ?? 0), 0, ',', ' ') }} m</p>
    </td>
    <td class="px-4 py-4">
        <div class="flex flex-wrap gap-1 max-w-[200px]">
            @include('pointage.partials.check-pill', ['ok' => $pointage->gps_valide, 'label' => 'GPS'])
            @include('pointage.partials.check-pill', ['ok' => $pointage->token_valide, 'label' => 'Token'])
            @include('pointage.partials.check-pill', ['ok' => $pointage->conforme_emploi_temps, 'label' => 'EDT'])
            @if($pointage->spoofing_detecte)
                <span class="text-[10px] font-bold text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-md">Spoofing</span>
            @endif
        </div>
    </td>
    <td class="px-4 py-4">
        @include('pointage.partials.cahier-texte-badge', ['pointage' => $pointage])
        <div class="mt-1.5">@include('pointage.partials.validation-finale-badge', ['pointage' => $pointage])</div>
    </td>
    <td class="px-4 py-4">
        @if($nbAlertes > 0)
            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-red-800 bg-red-50 border border-red-200/80 px-2.5 py-1 rounded-lg">
                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                {{ $nbAlertes }}
            </span>
        @elseif($isAnomalie)
            <span class="text-[11px] font-bold text-amber-800 bg-amber-50 border border-amber-200/80 px-2.5 py-1 rounded-lg">À surveiller</span>
        @else
            <span class="text-[11px] font-semibold text-gray-400">RAS</span>
        @endif
    </td>
    <td class="px-4 py-4 text-right">
        <a href="{{ $showUrl }}"
           onclick="event.stopPropagation()"
           class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200/60 rounded-xl transition-colors">
            Détail
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
    </td>
</tr>
