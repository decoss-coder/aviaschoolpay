@extends('layouts.app')

@section('title', 'Rapport de génération')
@section('page-title', 'Rapport de génération')
@section('page-subtitle', 'Résultat, conformité et application à la grille')

@section('content')
@include('partials.rh-admin-nav')

@php
    $issues = collect($issues ?? []);
    $summary = is_array($run->summary_json) ? $run->summary_json : (json_decode($run->summary_json ?? '[]', true) ?: []);
    $conformite = is_array($run->conformite_json) ? $run->conformite_json : (json_decode($run->conformite_json ?? '[]', true) ?: []);
    $perClasse = collect($conformite['per_classe'] ?? []);
    $issuesSummary = $conformite['issues_summary'] ?? [];
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
            <p class="text-xs font-bold uppercase text-gray-500">Run</p>
            <p class="mt-2 text-2xl font-extrabold text-gray-900">#{{ $run->id }}</p>
        </div>

        <div class="bg-white border border-brand-100 rounded-2xl p-4 shadow-card-brand">
            <p class="text-xs font-bold uppercase text-gray-500">Statut</p>
            <p class="mt-2 text-2xl font-extrabold text-brand-700">{{ $run->status }}</p>
        </div>

        <div class="bg-white border border-emerald-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-emerald-600">Score global</p>
            <p class="mt-2 text-2xl font-extrabold text-emerald-700">{{ $run->score_global ?? ($conformite['score_global'] ?? 0) }}</p>
        </div>

        <div class="bg-white border border-amber-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-amber-600">Warnings</p>
            <p class="mt-2 text-2xl font-extrabold text-amber-700">{{ $issuesSummary['warnings'] ?? 0 }}</p>
        </div>

        <div class="bg-white border border-red-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-red-600">Erreurs</p>
            <p class="mt-2 text-2xl font-extrabold text-red-700">{{ $issuesSummary['errors'] ?? 0 }}</p>
        </div>
    </div>

    <div class="bg-white border border-brand-100 rounded-2xl p-6 shadow-card-brand">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-extrabold text-gray-900">Résumé du run</h3>
                <p class="text-sm text-gray-500">
                    Scénario : <span class="font-bold">{{ $scenario?->nom ?? '—' }}</span>
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('emploi-du-temps.assistant.scenarios.show', $scenario?->id ?? $run->scenario_id) }}"
                   class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 transition">
                    ← Retour scénario
                </a>

                @if($run->status !== 'applied')
                    <form method="POST" action="{{ route('emploi-du-temps.assistant.runs.apply', $run->id) }}">
                        @csrf
                        <button class="px-5 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white rounded-xl text-sm font-bold shadow-brand-glow">
                            Appliquer à la grille
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-5">
            <div class="rounded-xl bg-gray-50 p-4">
                <p class="text-[10px] uppercase font-extrabold text-gray-500">Assignments</p>
                <p class="mt-1 text-2xl font-extrabold text-gray-900">{{ $summary['assignments_count'] ?? 0 }}</p>
            </div>

            <div class="rounded-xl bg-gray-50 p-4">
                <p class="text-[10px] uppercase font-extrabold text-gray-500">Issues</p>
                <p class="mt-1 text-2xl font-extrabold text-gray-900">{{ $summary['issues_count'] ?? $issues->count() }}</p>
            </div>

            <div class="rounded-xl bg-gray-50 p-4">
                <p class="text-[10px] uppercase font-extrabold text-gray-500">UUID</p>
                <p class="mt-1 text-sm font-extrabold text-gray-900 break-all">{{ $run->run_uuid }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white border border-brand-100 rounded-2xl p-6 shadow-card-brand">
            <h3 class="text-lg font-extrabold text-gray-900">Conformité par classe</h3>
            <p class="text-sm text-gray-500 mt-1">Synthèse de la génération par classe.</p>

            @if($perClasse->isEmpty())
                <div class="mt-4 rounded-2xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-400">
                    Aucune donnée de conformité.
                </div>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-brand-100">
                                <th class="px-4 py-3 text-left font-extrabold text-gray-600 uppercase text-[11px]">Classe</th>
                                <th class="px-4 py-3 text-left font-extrabold text-gray-600 uppercase text-[11px]">Unités</th>
                                <th class="px-4 py-3 text-left font-extrabold text-gray-600 uppercase text-[11px]">Matières</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-50">
                            @foreach($perClasse as $row)
                                <tr>
                                    <td class="px-4 py-3 font-bold text-gray-900">{{ $row['classe_nom'] ?? ('Classe #' . ($row['classe_id'] ?? '—')) }}</td>
                                    <td class="px-4 py-3">{{ $row['generated_units'] ?? 0 }}</td>
                                    <td class="px-4 py-3">{{ $row['matieres'] ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="bg-white border border-brand-100 rounded-2xl p-6 shadow-card-brand">
            <h3 class="text-lg font-extrabold text-gray-900">Issues détectées</h3>
            <p class="text-sm text-gray-500 mt-1">Erreurs, alertes et informations du run.</p>

            @if($issues->isEmpty())
                <div class="mt-4 rounded-2xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-400">
                    Aucune issue enregistrée.
                </div>
            @else
                <div class="mt-4 space-y-3">
                    @foreach($issues as $issue)
                        @php
                            $badge = match($issue->niveau) {
                                'error' => 'bg-red-100 text-red-700',
                                'warning' => 'bg-amber-100 text-amber-700',
                                default => 'bg-blue-100 text-blue-700',
                            };
                        @endphp

                        <div class="rounded-xl border border-brand-100 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $badge }}">
                                            {{ $issue->niveau }}
                                        </span>

                                        <span class="text-[11px] font-mono text-gray-400">{{ $issue->issue_code }}</span>
                                    </div>

                                    <p class="mt-2 text-sm font-semibold text-gray-800">{{ $issue->message }}</p>

                                    @if(!empty($issue->details_json))
                                        <pre class="mt-2 text-xs text-gray-500 whitespace-pre-wrap break-all">{{ is_array($issue->details_json) ? json_encode($issue->details_json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : $issue->details_json }}</pre>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection