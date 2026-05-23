@extends('layouts.app')

@section('title', 'Supervision pointages')
@section('page-title', 'Pointages enseignants')
@section('page-subtitle', 'Données temps réel depuis l\'application mobile · QR, GPS et cahier de texte IA')

@section('content')
@php
    $buildDateUrl = function (string $d) use ($filterQuery) {
        return route('pointage.index', array_merge($filterQuery, ['date' => $d]));
    };
@endphp

<div class="space-y-6" x-data="{ filtersOpen: {{ $activeFilterCount > 0 ? 'true' : 'false' }}, viewMode: 'table' }">

    {{-- En-tête --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-brand-900 to-brand-800 text-white shadow-xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.04\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-50"></div>
        <div class="relative px-6 py-6 sm:px-8 sm:py-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-[11px] font-bold uppercase tracking-wider text-brand-100 mb-3">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Flux mobile synchronisé
                    </div>
                    <h2 class="font-display text-2xl sm:text-3xl font-extrabold tracking-tight">Centre de supervision</h2>
                    <p class="text-brand-100/90 text-sm mt-2 max-w-xl">
                        Consultez les pointages QR, la géolocalisation et les preuves cahier de texte analysées par IA.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <div class="flex items-center bg-white/10 backdrop-blur border border-white/20 rounded-xl p-1">
                        <a href="{{ $buildDateUrl($dateCarbon->copy()->subDay()->toDateString()) }}"
                           class="p-2.5 rounded-lg hover:bg-white/10 transition-colors" title="Jour précédent">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                        <form method="GET" class="px-2">
                            @foreach($filterQuery as $key => $val)
                                @if($val !== null && $val !== '')
                                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                                @endif
                            @endforeach
                            <input type="date" name="date" value="{{ $date }}"
                                   onchange="this.form.submit()"
                                   class="bg-transparent border-0 text-white text-sm font-bold text-center min-w-[130px] focus:ring-0 cursor-pointer [color-scheme:dark]">
                        </form>
                        <a href="{{ $buildDateUrl($dateCarbon->copy()->addDay()->toDateString()) }}"
                           class="p-2.5 rounded-lg hover:bg-white/10 transition-colors" title="Jour suivant">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                    @if(!$dateCarbon->isToday())
                        <a href="{{ $buildDateUrl(today()->toDateString()) }}"
                           class="inline-flex items-center justify-center px-4 py-2.5 bg-white text-brand-800 text-sm font-bold rounded-xl hover:bg-brand-50 transition-colors shadow-lg">
                            Aujourd'hui
                        </a>
                    @endif
                    @if(Route::has('pointage.parametres.edit'))
                        <a href="{{ route('pointage.parametres.edit') }}"
                           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white/10 border border-white/25 text-sm font-bold rounded-xl hover:bg-white/15 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                            GPS école
                        </a>
                    @endif
                    @if(Route::has('pointage.rapport'))
                        <a href="{{ route('pointage.rapport', ['periode' => 30]) }}"
                           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white/10 border border-white/25 text-sm font-bold rounded-xl hover:bg-white/15 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            Rapport
                        </a>
                    @endif
                    @if(Route::has('alertes-pointage.index'))
                        <a href="{{ route('alertes-pointage.index', ['date' => $date]) }}"
                           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white/10 border border-white/25 text-sm font-bold rounded-xl hover:bg-white/15 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            Alertes
                        </a>
                    @endif
                </div>
            </div>

            <p class="mt-4 text-sm text-brand-100/80 font-medium">
                {{ $dateCarbon->isoFormat('dddd D MMMM YYYY') }}
                @if($dateCarbon->isToday())<span class="ml-2 px-2 py-0.5 rounded-md bg-emerald-500/30 text-emerald-100 text-xs font-bold">Aujourd'hui</span>@endif
            </p>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
        @include('pointage.partials.kpi-card', ['label' => 'Scans', 'value' => $stats['total'] ?? 0, 'hint' => 'Arrivées + départs', 'accent' => 'brand'])
        @include('pointage.partials.kpi-card', ['label' => 'Présents', 'value' => $stats['presents'] ?? 0, 'accent' => 'emerald'])
        @include('pointage.partials.kpi-card', ['label' => 'Retards', 'value' => $stats['retards'] ?? 0, 'accent' => 'amber'])
        @include('pointage.partials.kpi-card', ['label' => 'Anomalies', 'value' => $stats['anomalies'] ?? 0, 'hint' => 'GPS, EDT, zone…', 'accent' => 'orange'])
        @include('pointage.partials.kpi-card', [
            'label' => 'Cahiers IA',
            'value' => ($stats['cahier_valides'] ?? 0) . '/' . ($stats['cahier_envoyes'] ?? 0),
            'hint' => $tauxCahier !== null ? "Taux validation {$tauxCahier}%" : 'Aucune photo',
            'accent' => 'violet',
        ])
        @include('pointage.partials.kpi-card', ['label' => 'Alertes', 'value' => $stats['alertes'] ?? 0, 'accent' => 'red'])
    </div>

    {{-- Filtres --}}
    <div class="bg-white border border-gray-200/80 rounded-2xl shadow-sm overflow-hidden">
        <button type="button" @click="filtersOpen = !filtersOpen"
                class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-gray-50/80 transition-colors">
            <div class="flex items-center gap-3">
                <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-brand-50 text-brand-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                </span>
                <div>
                    <p class="text-sm font-bold text-gray-900">Filtres avancés</p>
                    <p class="text-xs text-gray-500">
                        @if($activeFilterCount > 0)
                            {{ $activeFilterCount }} filtre(s) actif(s) · {{ $pointages->total() }} résultat(s)
                        @else
                            Affiner la liste des pointages
                        @endif
                    </p>
                </div>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="filtersOpen && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div x-show="filtersOpen" x-cloak class="border-t border-gray-100">
            <form method="GET" class="p-5 space-y-4">
                <input type="hidden" name="date" value="{{ $date }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="sm:col-span-2 relative">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Recherche</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, téléphone, token, observation…"
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 bottom-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Enseignant</label>
                        <select name="enseignant_id" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white">
                            <option value="">Tous</option>
                            @foreach($enseignants as $enseignant)
                                <option value="{{ $enseignant->id }}" @selected(request('enseignant_id') == $enseignant->id)>{{ $enseignant->nom_complet }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Statut</label>
                        <select name="statut" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white">
                            <option value="">Tous</option>
                            <option value="present" @selected(request('statut') === 'present')>Présent</option>
                            <option value="retard" @selected(request('statut') === 'retard')>Retard</option>
                            <option value="absent" @selected(request('statut') === 'absent')>Absent</option>
                            <option value="hors_zone" @selected(request('statut') === 'hors_zone')>Hors zone</option>
                            <option value="fraude_detectee" @selected(request('statut') === 'fraude_detectee')>Fraude GPS</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Type scan</label>
                        <select name="type_scan" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white">
                            <option value="">Tous</option>
                            <option value="arrivee" @selected(request('type_scan') === 'arrivee')>Arrivée</option>
                            <option value="depart" @selected(request('type_scan') === 'depart')>Départ</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Cahier de texte (app)</label>
                        <select name="cahier" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white">
                            <option value="">Tous</option>
                            <option value="valide" @selected(request('cahier') === 'valide')>Validé par IA</option>
                            <option value="non_valide" @selected(request('cahier') === 'non_valide')>Photo sans validation</option>
                            <option value="manquant" @selected(request('cahier') === 'manquant')>Cahier en attente</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Validation</label>
                        <select name="validation_finale" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white">
                            <option value="">Toutes</option>
                            <option value="valide" @selected(request('validation_finale') === 'valide')>Validé</option>
                            <option value="provisoire" @selected(request('validation_finale') === 'provisoire')>Provisoire</option>
                            <option value="incomplet" @selected(request('validation_finale') === 'incomplet')>Incomplet</option>
                            <option value="rejete" @selected(request('validation_finale') === 'rejete')>Rejeté</option>
                            <option value="anomalie" @selected(request('validation_finale') === 'anomalie')>Anomalie EDT</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Méthode</label>
                        <select name="methode" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white">
                            <option value="">Toutes</option>
                            <option value="qr_gps" @selected(request('methode') === 'qr_gps')>QR + GPS</option>
                            <option value="pin_gps" @selected(request('methode') === 'pin_gps')>PIN + GPS</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Situation</label>
                        <select name="anomalie" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white">
                            <option value="">Toutes</option>
                            <option value="oui" @selected(request('anomalie') === 'oui')>Anomalies uniquement</option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 pt-1">
                    <button type="submit" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold rounded-xl shadow-sm transition-colors">
                        Appliquer
                    </button>
                    <a href="{{ route('pointage.index', ['date' => $date]) }}"
                       class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-xl transition-colors">
                        Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Raccourcis filtres --}}
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('pointage.index', array_merge($filterQuery, ['date' => $date, 'cahier' => 'valide'])) }}"
           class="px-3 py-1.5 text-xs font-bold rounded-lg border {{ request('cahier') === 'valide' ? 'bg-violet-100 border-violet-300 text-violet-800' : 'bg-white border-gray-200 text-gray-600 hover:border-violet-200' }}">
            Cahiers validés
        </a>
        <a href="{{ route('pointage.index', array_merge($filterQuery, ['date' => $date, 'cahier' => 'non_valide'])) }}"
           class="px-3 py-1.5 text-xs font-bold rounded-lg border {{ request('cahier') === 'non_valide' ? 'bg-amber-100 border-amber-300 text-amber-800' : 'bg-white border-gray-200 text-gray-600 hover:border-amber-200' }}">
            Cahiers à revoir
        </a>
        <a href="{{ route('pointage.index', array_merge($filterQuery, ['date' => $date, 'anomalie' => 'oui'])) }}"
           class="px-3 py-1.5 text-xs font-bold rounded-lg border {{ request('anomalie') === 'oui' ? 'bg-orange-100 border-orange-300 text-orange-800' : 'bg-white border-gray-200 text-gray-600 hover:border-orange-200' }}">
            Anomalies
        </a>
    </div>

    {{-- Liste --}}
    <div class="bg-white border border-gray-200/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-4 border-b border-gray-100 bg-gray-50/50">
            <p class="text-sm font-bold text-gray-900">
                <span class="tabular-nums">{{ $pointages->total() }}</span> pointage(s)
            </p>
            <div class="flex items-center gap-1 p-1 bg-gray-100 rounded-lg">
                <button type="button" @click="viewMode = 'table'"
                        :class="viewMode === 'table' ? 'bg-white shadow text-brand-700' : 'text-gray-500'"
                        class="px-3 py-1.5 text-xs font-bold rounded-md transition-all">Tableau</button>
                <button type="button" @click="viewMode = 'cards'"
                        :class="viewMode === 'cards' ? 'bg-white shadow text-brand-700' : 'text-gray-500'"
                        class="px-3 py-1.5 text-xs font-bold rounded-md transition-all">Cartes</button>
            </div>
        </div>

        @if($pointages->isEmpty())
            <div class="px-6 py-20 text-center">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-brand-50 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-brand-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4"/></svg>
                </div>
                <p class="font-display text-lg font-bold text-gray-800">Aucun pointage pour cette journée</p>
                <p class="text-sm text-gray-500 mt-2 max-w-md mx-auto">Modifiez la date ou les filtres. Les enseignants pointent via l'application mobile (scan QR puis photo du cahier).</p>
            </div>
        @else
            <div x-show="viewMode === 'table'" class="overflow-x-auto">
                <table class="w-full min-w-[960px]">
                    <thead>
                        <tr class="bg-gray-50 text-left">
                            <th class="px-5 py-3.5 text-[10px] font-extrabold text-gray-500 uppercase tracking-wider">Enseignant</th>
                            <th class="px-4 py-3.5 text-[10px] font-extrabold text-gray-500 uppercase tracking-wider">Heure</th>
                            <th class="px-4 py-3.5 text-[10px] font-extrabold text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="px-4 py-3.5 text-[10px] font-extrabold text-gray-500 uppercase tracking-wider">Lieu</th>
                            <th class="px-4 py-3.5 text-[10px] font-extrabold text-gray-500 uppercase tracking-wider">Contrôles</th>
                            <th class="px-4 py-3.5 text-[10px] font-extrabold text-gray-500 uppercase tracking-wider">Cahier app</th>
                            <th class="px-4 py-3.5 text-[10px] font-extrabold text-gray-500 uppercase tracking-wider">Alertes</th>
                            <th class="px-4 py-3.5"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pointages as $pointage)
                            @include('pointage.partials.pointage-row', ['pointage' => $pointage])
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div x-show="viewMode === 'cards'" x-cloak class="p-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($pointages as $pointage)
                    @include('pointage.partials.pointage-card', ['pointage' => $pointage])
                @endforeach
            </div>
        @endif

        @if($pointages->hasPages())
            <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/30">
                {{ $pointages->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
