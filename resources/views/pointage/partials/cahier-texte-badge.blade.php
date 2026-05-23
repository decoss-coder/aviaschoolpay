@php
    $valide = (bool) $pointage->cahier_texte_validated;
    $envoye = $pointage->aCahierTexte();
    $confidence = $pointage->cahier_texte_confidence;
@endphp

@if($valide)
    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-emerald-800 bg-emerald-50 border border-emerald-200/80 px-2.5 py-1 rounded-lg ring-1 ring-emerald-600/10" title="Cahier validé par intelligence artificielle">
        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
        Cahier validé
        @if($confidence)
            <span class="text-emerald-600/80 font-semibold">{{ $confidence }}%</span>
        @endif
    </span>
@elseif($envoye)
    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-amber-800 bg-amber-50 border border-amber-200/80 px-2.5 py-1 rounded-lg" title="Photo reçue depuis l'app — validation IA non conforme">
        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Cahier à revoir
    </span>
@else
    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-gray-500 bg-gray-50 border border-gray-200/80 px-2.5 py-1 rounded-lg" title="Aucune photo de cahier envoyée">
        <svg class="w-3.5 h-3.5 shrink-0 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        Sans cahier
    </span>
@endif
