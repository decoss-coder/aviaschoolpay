@extends('layouts.app')

@section('title', 'Rapport de génération IA')
@section('page-title', 'Rapport de génération IA')
@section('page-subtitle', 'Relis, ajuste, enregistre puis applique les propositions')

@section('content')
@include('partials.rh-admin-nav')

@php
    $summary = is_array($run->summary_json ?? null)
        ? ($run->summary_json ?? [])
        : (json_decode($run->summary_json ?? '[]', true) ?: []);

    $proposals = collect($proposals ?? []);
@endphp

<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 shadow-sm">
            {{ session('warning') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800 shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800 shadow-sm">
            <div class="font-extrabold mb-2">Veuillez corriger les erreurs suivantes :</div>
            <ul class="space-y-1 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white border border-brand-100 rounded-2xl p-6 shadow-card-brand">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-extrabold text-gray-900">Run #{{ $run->id }}</h3>
                <p class="text-sm text-gray-500 mt-1">
                    UUID : <span class="font-mono">{{ $run->run_uuid }}</span>
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('emploi-du-temps.create', ['annee_scolaire_id' => $run->annee_scolaire_id]) }}"
                   class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 transition">
                    Retour
                </a>

                <a href="{{ route('emploi-du-temps.grille', ['annee_scolaire_id' => $run->annee_scolaire_id]) }}"
                   class="px-4 py-2.5 bg-white border border-brand-100 rounded-xl text-sm font-bold text-brand-700 hover:bg-brand-50 transition">
                    Ouvrir la grille
                </a>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('emploi-du-temps.ia.proposals.save', $run) }}" class="space-y-6">
        @csrf

        <div class="bg-white border border-brand-100 rounded-2xl p-6 shadow-card-brand">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-lg font-extrabold text-gray-900">Propositions générées</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Modifie les lignes si besoin, puis enregistre avant application.
                    </p>
                </div>

                <div class="text-sm font-bold text-gray-600">
                    {{ $proposals->count() }} proposition(s)
                </div>
            </div>

            @if($proposals->isEmpty())
                <div class="rounded-2xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-400">
                    Aucune proposition disponible dans ce run.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[980px]">
                        <thead>
                            <tr class="border-b border-brand-100">
                                <th class="px-3 py-3 text-left font-extrabold text-gray-600 uppercase text-[11px]">Jour</th>
                                <th class="px-3 py-3 text-left font-extrabold text-gray-600 uppercase text-[11px]">Créneau</th>
                                <th class="px-3 py-3 text-left font-extrabold text-gray-600 uppercase text-[11px]">Classe</th>
                                <th class="px-3 py-3 text-left font-extrabold text-gray-600 uppercase text-[11px]">Matière</th>
                                <th class="px-3 py-3 text-left font-extrabold text-gray-600 uppercase text-[11px]">Prof</th>
                                <th class="px-3 py-3 text-left font-extrabold text-gray-600 uppercase text-[11px]">Salle</th>
                                <th class="px-3 py-3 text-left font-extrabold text-gray-600 uppercase text-[11px]">Score IA</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-50">
                            @foreach($proposals as $i => $row)
                                <tr>
                                    <td class="px-3 py-2">
                                        <input type="text" name="proposals[{{ $i }}][jour]" value="{{ $row['jour'] ?? '' }}" class="w-full px-3 py-2 border border-gray-200 rounded-xl">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" name="proposals[{{ $i }}][creneau_id]" value="{{ $row['creneau_id'] ?? '' }}" class="w-full px-3 py-2 border border-gray-200 rounded-xl">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" name="proposals[{{ $i }}][classe_id]" value="{{ $row['classe_id'] ?? '' }}" class="w-full px-3 py-2 border border-gray-200 rounded-xl">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" name="proposals[{{ $i }}][matiere_id]" value="{{ $row['matiere_id'] ?? '' }}" class="w-full px-3 py-2 border border-gray-200 rounded-xl">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" name="proposals[{{ $i }}][enseignant_id]" value="{{ $row['enseignant_id'] ?? '' }}" class="w-full px-3 py-2 border border-gray-200 rounded-xl">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" name="proposals[{{ $i }}][salle_id]" value="{{ $row['salle_id'] ?? '' }}" class="w-full px-3 py-2 border border-gray-200 rounded-xl">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" name="proposals[{{ $i }}][ia_score]" value="{{ $row['ia_score'] ?? '' }}" class="w-full px-3 py-2 border border-gray-200 rounded-xl">
                                        <input type="hidden" name="proposals[{{ $i }}][valide_du]" value="{{ $row['valide_du'] ?? '' }}">
                                        <input type="hidden" name="proposals[{{ $i }}][valide_au]" value="{{ $row['valide_au'] ?? '' }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 flex flex-wrap gap-3">
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl border border-brand-200 bg-white text-brand-700 text-sm font-extrabold hover:bg-brand-50 transition">
                        Enregistrer les propositions
                    </button>
                </div>
            @endif
        </div>
    </form>

    @if($proposals->isNotEmpty())
        <div class="bg-white border border-brand-100 rounded-2xl p-6 shadow-card-brand">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-extrabold text-gray-900">Application finale</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Une fois les propositions enregistrées, applique-les à la grille.
                    </p>
                </div>

                <form method="POST" action="{{ route('emploi-du-temps.ia.apply', $run) }}">
                    @csrf
                    <button class="px-5 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-extrabold hover:bg-brand-700 transition">
                        Appliquer à la grille
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection