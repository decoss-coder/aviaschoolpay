@extends('layouts.app')

@section('title', 'Paramètres IA Emploi du temps')
@section('page-title', 'Paramètres IA Emploi du temps')
@section('page-subtitle', 'Réglages globaux avant génération')

@section('content')

@php
    $anneeLabel = $anneeActive?->libelle ?? '—';
@endphp

<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800 shadow-sm">
            <div class="font-extrabold mb-1">Veuillez corriger les erreurs suivantes :</div>
            <ul class="space-y-1 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Bandeau navigation --}}
    <div class="bg-white border border-brand-100 rounded-2xl p-5 shadow-card-brand">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-[10px] font-extrabold uppercase text-gray-500 mb-1">
                    Année scolaire
                </label>
                <form method="GET" action="{{ route('emploi-du-temps.parametres.edit') }}">
                    <select name="annee_scolaire_id"
                            onchange="this.form.submit()"
                            class="px-3 py-2 text-sm border border-brand-100 rounded-xl bg-white">
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id }}" @selected($annee->id == $anneeActive?->id)>
                                {{ $annee->libelle }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="ml-auto flex flex-wrap gap-2">
                <a href="{{ route('emploi-du-temps.create', ['annee_scolaire_id' => $anneeActive?->id]) }}"
                   class="px-4 py-2 text-sm font-bold text-violet-700 border border-violet-200 rounded-xl bg-violet-50 hover:bg-violet-100 transition">
                    Génération IA
                </a>

                <a href="{{ route('emploi-du-temps.grille', ['annee_scolaire_id' => $anneeActive?->id]) }}"
                   class="px-4 py-2 text-sm font-bold text-brand-700 border border-brand-200 rounded-xl bg-brand-50 hover:bg-brand-100 transition">
                    Ouvrir la grille
                </a>
            </div>
        </div>
    </div>

    {{-- Synthèse rapide --}}
    <div class="grid grid-cols-2 xl:grid-cols-5 gap-4">
        <div class="bg-white border border-brand-100 rounded-2xl p-4 shadow-card-brand">
            <p class="text-xs font-bold uppercase text-gray-500">Année active</p>
            <p class="mt-2 text-2xl font-extrabold text-gray-900">{{ $anneeLabel }}</p>
        </div>

        <div class="bg-white border border-violet-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-violet-600">Mode IA par défaut</p>
            <p class="mt-2 text-base font-extrabold text-violet-700">
                @switch($parametre->mode_generation_defaut)
                    @case('strict_officiel') Officiel strict @break
                    @case('prive_equilibre') Privé équilibré @break
                    @case('prive_contraint') Privé contraint @break
                    @case('provisoire_vacataires') Provisoire vacataires @break
                    @default {{ str_replace('_', ' ', $parametre->mode_generation_defaut) }}
                @endswitch
            </p>
        </div>

        <div class="bg-white border border-amber-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-amber-600">Jours autorisés</p>
            <p class="mt-2 text-base font-extrabold text-amber-700">
                {{ count($parametre->jours_autorises_json ?? []) ?: 0 }}
            </p>
        </div>

        <div class="bg-white border border-emerald-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-emerald-600">Créneaux autorisés</p>
            <p class="mt-2 text-base font-extrabold text-emerald-700">
                {{ count($parametre->creneaux_autorises_json ?? []) ?: 0 }}
            </p>
        </div>

        <div class="bg-white border border-brand-100 rounded-2xl p-4 shadow-card-brand">
            <p class="text-xs font-bold uppercase text-gray-500">Salles autorisées</p>
            <p class="mt-2 text-base font-extrabold text-gray-900">
                {{ count($parametre->salles_autorisees_json ?? []) ?: 0 }}
            </p>
        </div>
    </div>

    {{-- Message produit --}}
    <div class="rounded-2xl border border-violet-200 bg-violet-50 px-5 py-4 text-sm text-violet-800 shadow-sm">
        <span class="font-extrabold">Système unifié :</span>
        ces paramètres pilotent directement le seul mode IA de génération.
        Ensuite, les ajustements se font dans la grille.
    </div>

    <form method="POST" action="{{ route('emploi-du-temps.parametres.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <input type="hidden" name="annee_scolaire_id" value="{{ $anneeActive?->id }}">

        {{-- Réglages généraux + vacataires --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white border border-brand-100 rounded-2xl p-6 shadow-card-brand space-y-4">
                <h3 class="text-base font-extrabold text-gray-900">Réglages généraux</h3>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Mode de génération par défaut</label>
                    <select name="mode_generation_defaut" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                        <option value="strict_officiel" @selected($parametre->mode_generation_defaut === 'strict_officiel')>
                            Officiel strict
                        </option>
                        <option value="prive_equilibre" @selected($parametre->mode_generation_defaut === 'prive_equilibre')>
                            Privé équilibré
                        </option>
                        <option value="prive_contraint" @selected($parametre->mode_generation_defaut === 'prive_contraint')>
                            Privé contraint
                        </option>
                        <option value="provisoire_vacataires" @selected($parametre->mode_generation_defaut === 'provisoire_vacataires')>
                            Provisoire vacataires
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Politique privée par défaut</label>
                    <select name="policy_id" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                        <option value="">Aucune</option>
                        @foreach($policies as $policy)
                            <option value="{{ $policy->id }}" @selected($parametre->policy_id == $policy->id)>
                                {{ $policy->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Jours autorisés</label>
                    <div class="flex flex-wrap gap-4">
                        @foreach($jours as $jour)
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox"
                                       name="jours_autorises_json[]"
                                       value="{{ $jour }}"
                                       @checked(in_array($jour, $parametre->jours_autorises_json ?? []))>
                                <span class="text-sm text-gray-700">{{ ucfirst($jour) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Créneaux autorisés</label>
                    <select name="creneaux_autorises_json[]" multiple size="8" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                        @foreach($creneaux as $creneau)
                            <option value="{{ $creneau->id }}"
                                    @selected(collect($parametre->creneaux_autorises_json ?? [])->contains($creneau->id))>
                                {{ $creneau->libelle ?? (($creneau->heure_debut ?? '') . ' - ' . ($creneau->heure_fin ?? '')) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Salles autorisées</label>
                    <select name="salles_autorisees_json[]" multiple size="8" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                        @foreach($salles as $salle)
                            <option value="{{ $salle->id }}"
                                    @selected(collect($parametre->salles_autorisees_json ?? [])->contains($salle->id))>
                                {{ $salle->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="bg-white border border-brand-100 rounded-2xl p-6 shadow-card-brand space-y-4">
                <h3 class="text-base font-extrabold text-gray-900">Vacataires et priorités</h3>

                @foreach([
                    'attendre_horaires_vacataires' => 'Attendre les horaires des vacataires',
                    'bloquer_si_vacataire_sans_horaire' => 'Bloquer si un vacataire n’a pas encore d’horaire',
                    'respecter_imports_vacataires' => 'Respecter les imports vacataires validés',
                    'regrouper_heures_vacataires' => 'Regrouper les heures des vacataires',
                    'prioriser_classes_examen' => 'Prioriser les classes d’examen',
                    'prioriser_permanents' => 'Prioriser les permanents',
                    'equilibrer_journees_classes' => 'Équilibrer les journées des classes',
                    'equilibrer_journees_profs' => 'Équilibrer les journées des professeurs',
                ] as $field => $label)
                    <label class="flex items-center gap-3">
                        <input type="hidden" name="{{ $field }}" value="0">
                        <input type="checkbox" name="{{ $field }}" value="1" @checked($parametre->{$field})>
                        <span class="text-sm text-gray-700 font-semibold">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Politique privée + contraintes pédagogiques --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white border border-brand-100 rounded-2xl p-6 shadow-card-brand space-y-4">
                <h3 class="text-base font-extrabold text-gray-900">Politique privée</h3>

                <label class="flex items-center gap-3">
                    <input type="hidden" name="autoriser_reduction_heures" value="0">
                    <input type="checkbox" name="autoriser_reduction_heures" value="1" @checked($parametre->autoriser_reduction_heures)>
                    <span class="text-sm text-gray-700 font-semibold">Autoriser la réduction d’heures</span>
                </label>

                <label class="flex items-center gap-3">
                    <input type="hidden" name="autoriser_matieres_facultatives" value="0">
                    <input type="checkbox" name="autoriser_matieres_facultatives" value="1" @checked($parametre->autoriser_matieres_facultatives)>
                    <span class="text-sm text-gray-700 font-semibold">Autoriser les matières facultatives</span>
                </label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Réduction max / classe (minutes)</label>
                        <input type="number"
                               name="max_reduction_minutes_par_classe"
                               value="{{ $parametre->max_reduction_minutes_par_classe }}"
                               class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Réduction max / matière (minutes)</label>
                        <input type="number"
                               name="max_reduction_minutes_par_matiere"
                               value="{{ $parametre->max_reduction_minutes_par_matiere }}"
                               class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                    </div>
                </div>
            </div>

            <div class="bg-white border border-brand-100 rounded-2xl p-6 shadow-card-brand space-y-4">
                <h3 class="text-base font-extrabold text-gray-900">Contraintes pédagogiques</h3>

                @foreach([
                    'respecter_tp_consecutifs' => 'Respecter les TP / blocs consécutifs',
                    'eviter_eps_heures_chaudes' => 'Éviter EPS aux heures chaudes',
                    'limiter_niveaux_prof' => 'Limiter le nombre de niveaux par professeur',
                    'limiter_heures_creuses' => 'Limiter les heures creuses professeur',
                    'autoriser_trous' => 'Autoriser des trous',
                    'tolerer_surcharge_legere' => 'Tolérer une légère surcharge',
                ] as $field => $label)
                    <label class="flex items-center gap-3">
                        <input type="hidden" name="{{ $field }}" value="0">
                        <input type="checkbox" name="{{ $field }}" value="1" @checked($parametre->{$field})>
                        <span class="text-sm text-gray-700 font-semibold">{{ $label }}</span>
                    </label>
                @endforeach

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Max niveaux / prof</label>
                        <input type="number"
                               name="max_niveaux_par_prof"
                               value="{{ $parametre->max_niveaux_par_prof }}"
                               class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Max heures creuses / prof</label>
                        <input type="number"
                               name="max_heures_creuses_prof"
                               value="{{ $parametre->max_heures_creuses_prof }}"
                               class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                    </div>
                </div>
            </div>
        </div>

        {{-- Apprentissage --}}
        <div class="bg-white border border-brand-100 rounded-2xl p-6 shadow-card-brand space-y-4">
            <h3 class="text-base font-extrabold text-gray-900">Apprentissage et comportement</h3>

            @foreach([
                'activer_apprentissage_ajustements' => 'Apprendre des ajustements manuels',
                'verrouiller_ajustements_manuels_par_defaut' => 'Verrouiller les ajustements manuels par défaut',
                'actif' => 'Activer ces paramètres',
            ] as $field => $label)
                <label class="flex items-center gap-3">
                    <input type="hidden" name="{{ $field }}" value="0">
                    <input type="checkbox" name="{{ $field }}" value="1" @checked($parametre->{$field})>
                    <span class="text-sm text-gray-700 font-semibold">{{ $label }}</span>
                </label>
            @endforeach

            <div>
                <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Notes de génération</label>
                <textarea name="notes_generation"
                          rows="4"
                          class="w-full px-3 py-2.5 border border-brand-100 rounded-xl"
                          placeholder="Ex : commencer par les classes d’examen, regrouper les vacataires sur 2 jours, éviter l’EPS après 14h...">{{ $parametre->notes_generation }}</textarea>
            </div>
        </div>

        <div class="flex flex-wrap justify-between items-center gap-3">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('emploi-du-temps.parametres.plages', ['annee_scolaire_id' => $anneeActive?->id]) }}"
                   class="px-5 py-2.5 bg-white border border-violet-300 rounded-xl text-sm font-bold text-violet-700 hover:bg-violet-50 transition">
                    ⏱ Plages horaires par classe →
                </a>
                <a href="{{ route('emploi-du-temps.creneaux.index') }}"
                   class="px-5 py-2.5 bg-white border border-brand-300 rounded-xl text-sm font-bold text-brand-700 hover:bg-brand-50 transition">
                    🕐 Créneaux horaires →
                </a>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('emploi-du-temps.create', ['annee_scolaire_id' => $anneeActive?->id]) }}"
                   class="px-5 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 transition">
                    Annuler
                </a>
                <button class="px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white rounded-xl text-sm font-bold shadow-brand-glow">
                    Enregistrer les paramètres
                </button>
            </div>
        </div>
    </form>
</div>
@endsection