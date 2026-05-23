@extends('layouts.app')
@section('title', 'Espace parent')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8 space-y-6">

    {{-- En-tête --}}
    <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-700 flex items-center justify-center text-white font-extrabold text-2xl shadow-lg flex-shrink-0">
            {{ strtoupper(substr($parent->prenom ?? '?', 0, 1)) }}
        </div>
        <div>
            <h1 class="font-display text-2xl font-extrabold text-gray-900">
                Bonjour, {{ $parent->prenom }} {{ strtoupper($parent->nom) }}
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Espace parent · {{ $enfants->count() }} enfant{{ $enfants->count() > 1 ? 's' : '' }} suivi{{ $enfants->count() > 1 ? 's' : '' }}
                @if(isset($profils) && $profils->count() > 1)
                    · {{ $profils->count() }} établissement{{ $profils->count() > 1 ? 's' : '' }}
                @endif
            </p>
        </div>
    </div>

    @if($enfants->isEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-amber-700 text-sm">
            Aucun enfant n'est associé à votre compte pour le moment. Contactez l'administration de l'école.
        </div>
    @endif

    {{-- Carte par enfant --}}
    @foreach($enfants as $enfant)
        @php $stats = $statsParEnfant[$enfant->id] ?? []; @endphp
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">

            {{-- Header enfant --}}
            <div class="flex items-center justify-between px-6 py-4 bg-gradient-to-r from-emerald-50 to-teal-50 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                        {{ strtoupper(substr($enfant->prenom ?? '?', 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-bold text-gray-900">{{ $enfant->prenom }} {{ strtoupper($enfant->nom) }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $enfant->etablissement?->nom ?? 'Établissement' }}
                            · {{ $enfant->classe?->nom ?? 'Sans classe' }}
                            @if($enfant->statut_eleve)
                                · <span class="font-semibold {{ $enfant->statut_eleve === 'AFF' ? 'text-emerald-600' : 'text-amber-600' }}">{{ $enfant->statut_eleve }}</span>
                            @endif
                            @if($enfant->matricule_desps) · <span class="font-mono">{{ $enfant->matricule_desps }}</span>@endif
                        </p>
                    </div>
                </div>
                <span class="text-xs font-medium px-2.5 py-1 rounded-full
                    {{ $enfant->statut === 'actif' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ ucfirst($enfant->statut ?? 'actif') }}
                </span>
            </div>

            {{-- Stats rapides --}}
            <div class="grid grid-cols-3 divide-x divide-gray-100 border-b border-gray-100">
                <div class="px-5 py-4 text-center">
                    <p class="text-2xl font-extrabold text-gray-900">
                        {{ $stats['moyenneTotale'] ? number_format($stats['moyenneTotale'], 2) : '—' }}
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Moyenne {{ $stats['trimestre']?->libelle ?? 'période' }}
                    </p>
                </div>
                <div class="px-5 py-4 text-center">
                    <p class="text-2xl font-extrabold {{ ($stats['nbAbsences'] ?? 0) > 3 ? 'text-red-600' : 'text-gray-900' }}">
                        {{ $stats['nbAbsences'] ?? 0 }}
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">Absence{{ ($stats['nbAbsences'] ?? 0) > 1 ? 's' : '' }}</p>
                </div>
                <div class="px-5 py-4 text-center">
                    <p class="text-2xl font-extrabold {{ ($stats['resteAPayer'] ?? 0) > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                        {{ number_format($stats['resteAPayer'] ?? 0, 0, ',', ' ') }}
                        <span class="text-sm font-normal">FCFA</span>
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">Reste à payer</p>
                </div>
            </div>

            {{-- Liens rapides --}}
            <div class="grid grid-cols-3 gap-3 p-4">
                <a href="{{ route('mon-espace-parent.notes', $enfant) }}"
                   class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-indigo-50 hover:bg-indigo-100 transition-colors">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    <span class="text-xs font-semibold text-indigo-700">Notes</span>
                </a>
                <a href="{{ route('mon-espace-parent.paiements', $enfant) }}"
                   class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-amber-50 hover:bg-amber-100 transition-colors">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span class="text-xs font-semibold text-amber-700">Paiements</span>
                </a>
                <a href="{{ route('mon-espace-parent.presences', $enfant) }}"
                   class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-red-50 hover:bg-red-100 transition-colors">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-xs font-semibold text-red-700">Présences</span>
                </a>
            </div>
        </div>
    @endforeach

</div>
@endsection
