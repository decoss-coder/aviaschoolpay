@extends('layouts.app')

@section('title', 'Plages horaires par classe')
@section('page-title', 'Plages horaires par classe')
@section('page-subtitle', 'Définissez si chaque classe a cours le matin, l\'après-midi, ou toute la journée')

@section('content')
@php
    $jours = $jours ?? ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'];
    $joursLabels = [
        'lundi'    => 'Lun',
        'mardi'    => 'Mar',
        'mercredi' => 'Mer',
        'jeudi'    => 'Jeu',
        'vendredi' => 'Ven',
    ];
    $options = [
        'libre'      => 'Journée complète',
        'matin'      => 'Matin seulement',
        'apres_midi' => 'Après-midi seul.',
        'aucun'      => 'Aucun cours',
    ];
    $optionsJour = ['defaut' => '(Hérité)'] + $options;
@endphp

<div class="max-w-5xl mx-auto space-y-6">

    {{-- Alertes --}}
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Sélecteur d'année --}}
    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm flex flex-wrap items-center gap-4">
        <span class="text-sm font-semibold text-gray-700">Année scolaire :</span>
        @foreach($annees as $a)
            <a href="{{ route('emploi-du-temps.parametres.plages', ['annee_scolaire_id' => $a->id]) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium border
                      {{ $anneeActive?->id === $a->id
                          ? 'bg-violet-600 text-white border-violet-600'
                          : 'text-gray-700 border-gray-300 hover:border-violet-400' }}">
                {{ $a->libelle }}
            </a>
        @endforeach
        <a href="{{ route('emploi-du-temps.parametres.edit', ['annee_scolaire_id' => $anneeActive?->id]) }}"
           class="ml-auto text-sm text-violet-600 hover:underline">
            ← Retour aux paramètres IA
        </a>
    </div>

    {{-- Aide --}}
    <div class="bg-blue-50 border border-blue-200 rounded-2xl px-5 py-4 text-sm text-blue-800 space-y-1">
        <p class="font-semibold">Comment configurer les plages ?</p>
        <ul class="list-disc list-inside space-y-0.5">
            <li><strong>Journée complète</strong> — la classe peut avoir cours matin ET après-midi (comportement par défaut).</li>
            <li><strong>Matin seulement</strong> — aucun cours après 13h pour cette classe.</li>
            <li><strong>Après-midi seulement</strong> — aucun cours avant 13h pour cette classe.</li>
            <li><strong>Aucun cours</strong> — aucun cours ce jour (utile pour les exceptions par jour).</li>
            <li>Les <strong>exceptions par jour</strong> permettent de dire par exemple "matin toute la semaine, sauf mercredi : journée complète".</li>
        </ul>
    </div>

    {{-- Formulaire --}}
    <form method="POST" action="{{ route('emploi-du-temps.parametres.plages.update') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="annee_scolaire_id" value="{{ $anneeActive?->id }}">

        <div class="space-y-6">
            @foreach($classes as $niveauLabel => $classesNiveau)
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="bg-gray-50 border-b border-gray-200 px-5 py-3">
                        <h3 class="text-sm font-bold text-gray-600 uppercase tracking-wide">{{ $niveauLabel }}</h3>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @foreach($classesNiveau as $classe)
                            @php
                                $modeActuel = $modesActuels[$classe->id] ?? 'libre';
                                $joursClasse = $joursActuels[$classe->id] ?? [];
                                $hasException = collect($joursClasse)->filter(fn($v) => $v !== 'defaut')->isNotEmpty();
                            @endphp

                            <div class="px-5 py-4" x-data="{ open: {{ $hasException ? 'true' : 'false' }} }">
                                <div class="flex flex-wrap items-center gap-4">
                                    {{-- Nom classe --}}
                                    <span class="w-24 shrink-0 text-sm font-semibold text-gray-800">
                                        {{ $classe->nom }}
                                    </span>

                                    {{-- Mode global --}}
                                    <div class="flex-1 min-w-48">
                                        <label class="block text-xs text-gray-500 mb-1">Plage par défaut (tous les jours)</label>
                                        <select name="plages[{{ $classe->id }}][defaut]"
                                                class="w-full rounded-lg border border-gray-300 text-sm px-3 py-2 focus:ring-2 focus:ring-violet-400 focus:border-violet-400">
                                            @foreach($options as $val => $label)
                                                <option value="{{ $val }}" {{ $modeActuel === $val ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Bouton exceptions --}}
                                    <button type="button" @click="open = !open"
                                            class="text-xs text-violet-600 hover:underline shrink-0">
                                        <span x-text="open ? 'Masquer exceptions ▲' : 'Exceptions par jour ▼'"></span>
                                    </button>
                                </div>

                                {{-- Exceptions par jour --}}
                                <div x-show="open" x-transition class="mt-3 grid grid-cols-2 sm:grid-cols-5 gap-3">
                                    @foreach($jours as $jour)
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">
                                                {{ $joursLabels[$jour] ?? ucfirst($jour) }}
                                            </label>
                                            <select name="plages[{{ $classe->id }}][jours][{{ $jour }}]"
                                                    class="w-full rounded-lg border border-gray-300 text-xs px-2 py-1.5 focus:ring-2 focus:ring-violet-400">
                                                @foreach($optionsJour as $val => $label)
                                                    <option value="{{ $val }}"
                                                        {{ ($joursClasse[$jour] ?? 'defaut') === $val ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('emploi-du-temps.create') }}"
               class="px-5 py-2.5 rounded-xl text-sm font-medium border border-gray-300 text-gray-700 hover:bg-gray-50">
                Annuler
            </a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl text-sm font-semibold bg-violet-600 text-white hover:bg-violet-700 shadow-sm">
                Enregistrer les plages
            </button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
// Alpine.js is expected from the main layout
</script>
@endpush
