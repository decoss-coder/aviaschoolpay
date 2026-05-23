@extends('layouts.app')
@section('title', 'Présences élèves — Tableau de bord')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-5">

    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('admin.rh.dashboard') }}" class="hover:text-brand-600 font-medium">RH</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">Présences élèves</span>
    </div>

    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="font-display text-2xl font-extrabold text-gray-900">Présences & Absences</h1>
            <p class="text-sm text-gray-500 mt-1">Vue d'ensemble de la vie scolaire — {{ \Carbon\Carbon::parse($date)->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <label class="text-xs font-bold text-gray-500 uppercase">Date :</label>
            <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                   class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm">
            <a href="{{ route('admin.rh.presences.index') }}"
               class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold px-4 py-1.5 rounded-lg">
                Voir la liste complète
            </a>
        </form>
    </div>

    {{-- Stats du jour --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="bg-white rounded-2xl shadow-card border border-green-100 p-4">
            <p class="text-xs font-bold text-green-600 uppercase">Présents</p>
            <p class="text-3xl font-extrabold text-green-700 mt-1">{{ $statsJour['present'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-red-100 p-4">
            <p class="text-xs font-bold text-red-600 uppercase">Absents</p>
            <p class="text-3xl font-extrabold text-red-700 mt-1">{{ $statsJour['absent'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-amber-100 p-4">
            <p class="text-xs font-bold text-amber-600 uppercase">Retards</p>
            <p class="text-3xl font-extrabold text-amber-700 mt-1">{{ $statsJour['retard'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-blue-100 p-4">
            <p class="text-xs font-bold text-blue-600 uppercase">Excusés</p>
            <p class="text-3xl font-extrabold text-blue-700 mt-1">{{ $statsJour['excuse'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border-2 border-orange-200 p-4">
            <p class="text-xs font-bold text-orange-600 uppercase">⚠ À traiter</p>
            <p class="text-3xl font-extrabold text-orange-700 mt-1">{{ $aTraiter }}</p>
            <a href="{{ route('admin.rh.presences.index', ['statut'=>'absent','traite'=>'0']) }}"
               class="text-xs font-bold text-orange-600 hover:underline mt-1 inline-block">Traiter →</a>
        </div>
    </div>

    {{-- Évolution 7 jours --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-5">
        <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide mb-4">Évolution sur 7 jours</h2>
        <div class="grid grid-cols-7 gap-2">
            @foreach($derniers7 as $j)
            <div class="text-center {{ $j['date'] === today()->toDateString() ? 'ring-2 ring-brand-300 rounded-lg p-2' : 'p-2' }}">
                <p class="text-[10px] font-bold text-gray-400 uppercase">{{ $j['libelle'] }}</p>
                <p class="text-xl font-extrabold text-red-600 mt-1">{{ $j['absents'] }}</p>
                <p class="text-[10px] text-gray-400">{{ $j['retards'] }} retards</p>
            </div>
            @endforeach
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-5">
        {{-- Top classes avec absences (semaine) --}}
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Top classes — absences cette semaine</h2>
            </div>
            @if($absencesParClasse->isEmpty())
                <div class="px-5 py-8 text-center text-sm text-gray-400">Aucune absence cette semaine 🎉</div>
            @else
            <ul class="divide-y divide-gray-50">
                @foreach($absencesParClasse as $row)
                <li class="flex items-center justify-between px-5 py-3">
                    <span class="font-semibold text-gray-800">{{ $row->classe_nom }}</span>
                    <div class="flex items-center gap-3">
                        <div class="w-24 h-2 bg-red-100 rounded-full overflow-hidden">
                            @php $max = $absencesParClasse->max('nb_abs'); $w = $max > 0 ? round($row->nb_abs / $max * 100) : 0; @endphp
                            <div class="h-2 bg-red-500 rounded-full" style="width: {{ $w }}%"></div>
                        </div>
                        <span class="font-bold text-red-700 text-sm w-10 text-right">{{ $row->nb_abs }}</span>
                    </div>
                </li>
                @endforeach
            </ul>
            @endif
        </div>

        {{-- Top élèves absents --}}
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Élèves les plus absents — semaine</h2>
            </div>
            @if($topElevesAbsents->isEmpty())
                <div class="px-5 py-8 text-center text-sm text-gray-400">Aucun élève absent 🎉</div>
            @else
            <ul class="divide-y divide-gray-50">
                @foreach($topElevesAbsents as $row)
                <li class="flex items-center justify-between px-5 py-2.5">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-mono font-bold text-gray-700">{{ $row->matricule_desps ?: $row->matricule_interne }}</p>
                        <p class="font-semibold text-sm text-gray-800 truncate">{{ strtoupper($row->nom) }} {{ $row->prenom }}</p>
                        <p class="text-[10px] text-gray-400">{{ $row->classe_nom }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold bg-red-100 text-red-700 px-2 py-0.5 rounded-full">{{ $row->nb_abs }} abs</span>
                        @if($row->nb_justifiees > 0)
                            <span class="text-[10px] font-semibold bg-green-50 text-green-700 px-1.5 py-0.5 rounded-full">{{ $row->nb_justifiees }} just.</span>
                        @endif
                        <a href="{{ route('admin.rh.presences.eleve', $row->id) }}"
                           class="text-xs font-bold text-brand-600 hover:text-brand-800">→</a>
                    </div>
                </li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>
</div>
@endsection
