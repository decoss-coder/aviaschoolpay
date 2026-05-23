@extends('layouts.app')
@section('title', 'Bilan des présences — Direction')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- En-tête --}}
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="font-display text-2xl font-extrabold text-gray-900">Bilan des présences</h1>
            <p class="text-sm text-gray-500 mt-1">
                Analyse globale des absences et retards par trimestre ou année.
            </p>
        </div>
        <a href="{{ route('admin.rh.presences.dashboard') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-brand-600 px-3 py-2 rounded-lg border border-gray-200 hover:border-brand-300 bg-white">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour dashboard
        </a>
    </div>

    {{-- Filtres période --}}
    <form method="GET" action="{{ route('admin.rh.presences.bilan') }}" class="bg-white rounded-2xl shadow-card border border-gray-100 p-4">
        <div class="flex items-end gap-3 flex-wrap">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Trimestre</label>
                <select name="trimestre_id" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    @foreach($trimestres as $t)
                        <option value="{{ $t->id }}" @selected($trimestre_id === $t->id)>
                            T{{ $t->numero }} — {{ $t->libelle }}
                            @if($t->en_cours) (en cours) @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Du</label>
                <input type="date" name="date_debut" value="{{ request('date_debut') }}"
                       class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Au</label>
                <input type="date" name="date_fin" value="{{ request('date_fin') }}"
                       class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
            </div>
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-5 py-2 rounded-lg text-sm">
                Filtrer
            </button>
        </div>
        <div class="text-xs text-gray-500 mt-2">
            Période : <span class="font-bold">{{ $periode['debut']->format('d/m/Y') }} → {{ $periode['fin']->format('d/m/Y') }}</span>
            ({{ $periode['label'] }})
        </div>
    </form>

    {{-- Stats globales --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-card p-5 border-l-4 border-blue-400">
            <p class="text-xs uppercase font-bold text-gray-500">Total appels</p>
            <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ number_format($bilan['totaux']['total_appels']) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card p-5 border-l-4 border-red-400">
            <p class="text-xs uppercase font-bold text-gray-500">Absences</p>
            <p class="text-2xl font-extrabold text-red-600 mt-1">{{ $bilan['totaux']['absences'] }}</p>
            <p class="text-xs text-gray-500 mt-1">
                {{ $bilan['totaux']['justifies'] }} just. · {{ $bilan['totaux']['non_justifies'] }} non
            </p>
        </div>
        <div class="bg-white rounded-2xl shadow-card p-5 border-l-4 border-amber-400">
            <p class="text-xs uppercase font-bold text-gray-500">Retards</p>
            <p class="text-2xl font-extrabold text-amber-600 mt-1">{{ $bilan['totaux']['retards'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card p-5 border-l-4 border-purple-400">
            <p class="text-xs uppercase font-bold text-gray-500">Heures d'absence</p>
            <p class="text-2xl font-extrabold text-purple-700 mt-1">{{ number_format($bilan['totaux']['heures_absence'], 1) }}h</p>
        </div>
    </div>

    {{-- Top classes & top élèves --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top classes --}}
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-bold text-gray-800">Top 10 — Classes avec le plus d'absences</h2>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($bilan['top_classes'] as $i => $c)
                    <a href="{{ route('admin.rh.presences.bilan.classe', $c['classe_id']) }}?{{ http_build_query(request()->only(['trimestre_id', 'date_debut', 'date_fin'])) }}"
                       class="flex items-center justify-between px-5 py-3 hover:bg-gray-50">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center text-sm font-bold">{{ $i + 1 }}</span>
                            <span class="font-semibold text-gray-800">{{ $c['classe'] }}</span>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-red-600">{{ $c['nb_absences'] }} absences</div>
                            <div class="text-xs text-gray-500">{{ number_format($c['heures_absence'], 1) }}h perdues</div>
                        </div>
                    </a>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-gray-400">Aucune absence sur la période.</p>
                @endforelse
            </div>
        </div>

        {{-- Top élèves --}}
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-bold text-gray-800">Top 20 — Élèves avec le plus d'absences</h2>
            </div>
            <div class="divide-y divide-gray-50 max-h-[600px] overflow-y-auto">
                @forelse($bilan['top_eleves'] as $i => $e)
                    <a href="{{ route('admin.rh.presences.bilan.eleve', $e['eleve_id']) }}?{{ http_build_query(request()->only(['trimestre_id', 'date_debut', 'date_fin'])) }}"
                       class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50">
                        <span class="w-7 h-7 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-sm font-bold flex-shrink-0">{{ $i + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-gray-800 truncate">{{ $e['prenom'] }} {{ strtoupper($e['nom']) }}</div>
                            <div class="text-xs text-gray-500 truncate">
                                {{ $e['classe'] }}
                                @if($e['matricule_desps']) · DSPS {{ $e['matricule_desps'] }} @endif
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <div class="text-sm font-bold text-red-600">{{ $e['nb_absences'] }}</div>
                            <div class="text-xs text-gray-500">{{ number_format($e['heures_absence'], 1) }}h</div>
                            @if($e['nb_non_justifies'] > 0)
                                <span class="text-xs bg-red-100 text-red-700 rounded px-1.5 py-0.5 font-bold">
                                    {{ $e['nb_non_justifies'] }} non just.
                                </span>
                            @endif
                        </div>
                    </a>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-gray-400">Aucun élève absent sur la période.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Lien vers les classes --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-5">
        <h2 class="font-bold text-gray-800 mb-3">Bilan par classe</h2>
        <p class="text-sm text-gray-500 mb-4">Cliquez sur une classe pour voir le bilan détaillé.</p>
        <div class="flex flex-wrap gap-2">
            @foreach($classes as $c)
                <a href="{{ route('admin.rh.presences.bilan.classe', $c->id) }}?{{ http_build_query(request()->only(['trimestre_id', 'date_debut', 'date_fin'])) }}"
                   class="px-3 py-2 rounded-lg bg-gray-50 hover:bg-brand-50 border border-gray-200 hover:border-brand-300 text-sm font-medium text-gray-700 hover:text-brand-700 transition">
                    {{ $c->nom }}
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
