@extends('layouts.app')

@section('title', 'Scénario de génération')
@section('page-title', 'Scénario de génération')
@section('page-subtitle', 'Vacataires, contraintes, génération et historique des runs')

@section('content')
@include('partials.rh-admin-nav')

@php
    $constraints = collect($constraints ?? []);
    $scopes = collect($scopes ?? []);
    $runs = collect($runs ?? []);
    $imports = collect($imports ?? []);

    $scopeClasses = $scopes->where('scope_type', 'classe');
    $scopeEnseignants = $scopes->where('scope_type', 'enseignant');

    $modeLabels = [
        'strict_officiel' => 'Officiel strict',
        'prive_equilibre' => 'Privé équilibré',
        'prive_contraint' => 'Privé contraint',
        'provisoire_vacataires' => 'Provisoire vacataires',
    ];
@endphp

<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800 shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-2 xl:grid-cols-5 gap-4">
        <div class="bg-white border border-brand-100 rounded-2xl p-4 shadow-card-brand">
            <p class="text-xs font-bold uppercase text-gray-500">Scénario</p>
            <p class="mt-2 text-lg font-extrabold text-gray-900">{{ $scenario->nom }}</p>
        </div>

        <div class="bg-white border border-brand-100 rounded-2xl p-4 shadow-card-brand">
            <p class="text-xs font-bold uppercase text-gray-500">Mode</p>
            <p class="mt-2 text-lg font-extrabold text-brand-700">
                {{ $modeLabels[$scenario->mode_generation] ?? $scenario->mode_generation }}
            </p>
        </div>

        <div class="bg-white border border-brand-100 rounded-2xl p-4 shadow-card-brand">
            <p class="text-xs font-bold uppercase text-gray-500">Portée</p>
            <p class="mt-2 text-lg font-extrabold text-gray-900">{{ ucfirst(str_replace('_', ' ', $scenario->portee)) }}</p>
        </div>

        <div class="bg-white border border-amber-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-amber-600">Contraintes</p>
            <p class="mt-2 text-3xl font-extrabold text-amber-700">{{ $constraints->count() }}</p>
        </div>

        <div class="bg-white border border-violet-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-violet-600">Runs</p>
            <p class="mt-2 text-3xl font-extrabold text-violet-700">{{ $runs->count() }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <div class="bg-white border border-brand-100 rounded-2xl p-6 shadow-card-brand">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-gray-900">Paramètres du scénario</h3>
                        <p class="text-sm text-gray-500">Résumé du cadre de génération choisi.</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('emploi-du-temps.assistant.index') }}"
                           class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 transition">
                            ← Retour assistant
                        </a>

                        <form method="POST" action="{{ route('emploi-du-temps.assistant.generate', $scenario) }}">
                            @csrf
                            <button class="px-5 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white rounded-xl text-sm font-bold shadow-brand-glow">
                                Lancer la génération
                            </button>
                        </form>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
                    <div class="rounded-xl bg-gray-50 p-4">
                        <p class="text-[10px] uppercase font-extrabold text-gray-500">Année scolaire</p>
                        <p class="mt-1 text-base font-extrabold text-gray-900">{{ $scenario->annee_scolaire_id }}</p>
                    </div>

                    <div class="rounded-xl bg-gray-50 p-4">
                        <p class="text-[10px] uppercase font-extrabold text-gray-500">Politique privée</p>
                        <p class="mt-1 text-base font-extrabold text-gray-900">{{ $scenario->policy?->nom ?? 'Aucune' }}</p>
                    </div>

                    <div class="rounded-xl bg-gray-50 p-4">
                        <p class="text-[10px] uppercase font-extrabold text-gray-500">Jours autorisés</p>
                        <p class="mt-1 text-sm font-bold text-gray-800">
                            {{ collect($scenario->jours_json ?? [])->map(fn($j) => ucfirst($j))->implode(', ') ?: 'Tous / non définis' }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-gray-50 p-4">
                        <p class="text-[10px] uppercase font-extrabold text-gray-500">Créneaux filtrés</p>
                        <p class="mt-1 text-sm font-bold text-gray-800">
                            {{ count($scenario->creneaux_json ?? []) ?: 'Tous / non définis' }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
                    <div class="rounded-2xl border border-brand-100 p-4">
                        <p class="text-xs font-extrabold uppercase text-brand-600 mb-3">Classes ciblées</p>
                        @if($scopeClasses->isEmpty())
                            <p class="text-sm text-gray-400">Portée globale ou aucune classe spécifique.</p>
                        @else
                            <div class="flex flex-wrap gap-2">
                                @foreach($scopeClasses as $scope)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-brand-50 text-brand-700 text-xs font-bold">
                                        Classe #{{ $scope->scope_id }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="rounded-2xl border border-violet-100 p-4">
                        <p class="text-xs font-extrabold uppercase text-violet-600 mb-3">Enseignants ciblés</p>
                        @if($scopeEnseignants->isEmpty())
                            <p class="text-sm text-gray-400">Portée globale ou aucun enseignant spécifique.</p>
                        @else
                            <div class="flex flex-wrap gap-2">
                                @foreach($scopeEnseignants as $scope)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-violet-50 text-violet-700 text-xs font-bold">
                                        Prof #{{ $scope->scope_id }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white border border-brand-100 rounded-2xl p-6 shadow-card-brand">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-gray-900">Contraintes sélectionnables</h3>
                        <p class="text-sm text-gray-500">Active, désactive et pondère les contraintes du scénario.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('emploi-du-temps.assistant.constraints.save', $scenario) }}" class="space-y-4">
                    @csrf

                    <div class="space-y-3">
                        @foreach($constraints as $i => $constraint)
                            @php
                                $enabled = (int) ($constraint->enabled ?? 0);
                                $isMandatory = (int) ($constraint->is_mandatory ?? 0) === 1;
                            @endphp

                            <div class="rounded-2xl border border-brand-100 p-4">
                                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="text-sm font-extrabold text-gray-900">{{ $constraint->libelle }}</h4>

                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-[10px] font-bold uppercase">
                                                {{ $constraint->categorie }}
                                            </span>

                                            @if($isMandatory)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold uppercase">
                                                    Obligatoire
                                                </span>
                                            @endif
                                        </div>

                                        <p class="mt-1 text-sm text-gray-500">{{ $constraint->description }}</p>
                                        <p class="mt-1 text-[11px] text-gray-400 font-mono">{{ $constraint->code }}</p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-4">
                                        <div>
                                            <input type="hidden" name="constraints[{{ $i }}][constraint_id]" value="{{ $constraint->constraint_id }}">
                                            <input type="hidden" name="constraints[{{ $i }}][enabled]" value="0">

                                            <label class="inline-flex items-center gap-2">
                                                <input type="checkbox"
                                                       name="constraints[{{ $i }}][enabled]"
                                                       value="1"
                                                       @checked($enabled)
                                                       @disabled($isMandatory)>
                                                <span class="text-sm font-semibold text-gray-700">Activée</span>
                                            </label>
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-extrabold uppercase text-gray-500 mb-1">Poids</label>
                                            <input type="number"
                                                   step="0.01"
                                                   min="0"
                                                   max="1000"
                                                   name="constraints[{{ $i }}][weight]"
                                                   value="{{ $constraint->weight ?? 100 }}"
                                                   class="w-28 px-3 py-2 text-sm border border-brand-100 rounded-xl">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end">
                        <button class="px-5 py-2.5 bg-white border border-brand-100 text-brand-700 rounded-xl text-sm font-bold hover:bg-brand-50 transition">
                            Enregistrer les contraintes
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white border border-brand-100 rounded-2xl p-6 shadow-card-brand">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-gray-900">Historique des runs</h3>
                        <p class="text-sm text-gray-500">Suivi des générations déjà lancées.</p>
                    </div>
                </div>

                @if($runs->isEmpty())
                    <div class="rounded-2xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-400">
                        Aucun run pour ce scénario.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-brand-100">
                                    <th class="px-4 py-3 text-left font-extrabold text-gray-600 uppercase text-[11px]">#</th>
                                    <th class="px-4 py-3 text-left font-extrabold text-gray-600 uppercase text-[11px]">Statut</th>
                                    <th class="px-4 py-3 text-left font-extrabold text-gray-600 uppercase text-[11px]">Score</th>
                                    <th class="px-4 py-3 text-left font-extrabold text-gray-600 uppercase text-[11px]">Créé le</th>
                                    <th class="px-4 py-3 text-right font-extrabold text-gray-600 uppercase text-[11px]">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brand-50">
                                @foreach($runs as $run)
                                    <tr>
                                        <td class="px-4 py-3 font-bold text-gray-900">#{{ $run->id }}</td>
                                        <td class="px-4 py-3">{{ $run->status }}</td>
                                        <td class="px-4 py-3">{{ $run->score_global ?? '—' }}</td>
                                        <td class="px-4 py-3">{{ $run->created_at ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('emploi-du-temps.assistant.runs.report', $run->id) }}"
                                               class="inline-flex items-center px-3 py-2 rounded-xl bg-brand-50 text-brand-700 text-xs font-bold hover:bg-brand-100 transition">
                                                Voir le rapport
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white border border-brand-100 rounded-2xl p-6 shadow-card-brand">
                <h3 class="text-lg font-extrabold text-gray-900">Imports vacataires</h3>
                <p class="text-sm text-gray-500 mt-1">Téléverse les horaires externes des vacataires.</p>

                <form method="POST"
                      action="{{ route('emploi-du-temps.assistant.vacataires.import', $scenario) }}"
                      enctype="multipart/form-data"
                      class="space-y-4 mt-5">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Enseignant</label>
                        <input type="number" name="enseignant_id" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl" placeholder="ID enseignant">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Type de source</label>
                        <select name="source_type" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                            <option value="pdf">PDF</option>
                            <option value="photo">Photo</option>
                            <option value="image">Image</option>
                            <option value="scan">Scan</option>
                            <option value="manuel">Manuel</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Fichier</label>
                        <input type="file" name="fichier" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl bg-white">
                    </div>

                    <button class="w-full px-4 py-2.5 bg-violet-600 text-white rounded-xl text-sm font-bold hover:bg-violet-700 transition">
                        Importer
                    </button>
                </form>
            </div>

            <div class="bg-white border border-brand-100 rounded-2xl p-6 shadow-card-brand">
                <h3 class="text-lg font-extrabold text-gray-900">Derniers imports</h3>
                <p class="text-sm text-gray-500 mt-1">Analyse et validation des disponibilités.</p>

                <div class="mt-4 space-y-3">
                    @forelse($imports as $import)
                        <div class="rounded-xl border border-brand-100 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-extrabold text-gray-900">Import #{{ $import->id }}</p>
                                    <p class="text-xs text-gray-500">{{ $import->source_type }} · {{ $import->status }}</p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('emploi-du-temps.assistant.vacataires.parse', $import->id) }}">
                                        @csrf
                                        <button class="px-3 py-2 rounded-xl bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200 hover:bg-amber-100 transition">
                                            Analyser
                                        </button>
                                    </form>
                                </div>
                            </div>

                            @if(!empty($import->resume_extraction))
                                <p class="mt-3 text-sm text-gray-600">{{ $import->resume_extraction }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-400">
                            Aucun import vacataire pour le moment.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection