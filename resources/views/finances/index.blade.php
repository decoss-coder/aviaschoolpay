@extends('layouts.app')

@section('title', 'Finances')
@section('page-title', 'Finances & Scolarité')
@section('page-subtitle', 'Pilotage des encaissements — ' . ($annee->libelle ?? ''))

@section('content')
<div class="space-y-6" x-data="{}">

    {{-- ──────────────  FLASH  ────────────── --}}
    @if(session('success'))
        <div class="px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            @foreach($errors->all() as $e) <p>• {{ $e }}</p> @endforeach
        </div>
    @endif

    {{-- ──────────────  HERO HEADER  ────────────── --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-700 to-emerald-800 text-white p-6 sm:p-8 shadow-xl">
        <div class="absolute -top-12 -right-12 w-72 h-72 bg-gradient-to-br from-white/10 to-transparent rounded-full blur-3xl"></div>
        <div class="absolute -bottom-12 -left-12 w-72 h-72 bg-gradient-to-tr from-white/5 to-transparent rounded-full blur-3xl"></div>

        <div class="relative flex flex-col lg:flex-row lg:items-end justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-sm text-xs font-bold uppercase tracking-wider mb-3">
                    <span class="w-2 h-2 rounded-full bg-emerald-300 animate-pulse"></span>
                    Année {{ $annee->libelle ?? '—' }} · {{ $etab->nom ?? '' }}
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Tableau de bord financier</h1>
                <p class="text-white/80 text-sm mt-2 max-w-xl">
                    Suivez les encaissements, identifiez les soldes en retard et configurez les grilles tarifaires AFF / NAFF.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('paiements.create') }}" class="px-4 py-2.5 rounded-xl bg-white text-brand-700 text-sm font-bold hover:bg-brand-50 transition shadow-md flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Nouveau paiement
                </a>
                <a href="{{ route('finances.tarifs') }}" class="px-4 py-2.5 rounded-xl bg-white/15 backdrop-blur-sm border border-white/20 text-white text-sm font-bold hover:bg-white/25 transition">
                    Grilles tarifaires
                </a>
                <a href="{{ route('finances.wave') }}" class="px-4 py-2.5 rounded-xl bg-white/15 backdrop-blur-sm border border-white/20 text-white text-sm font-bold hover:bg-white/25 transition flex items-center gap-2">
                    <span class="w-5 h-5 rounded bg-blue-500 flex items-center justify-center text-[10px] font-extrabold">W</span>
                    Wave
                </a>
                <form method="POST" action="{{ route('finances.synchroniser') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-white/10 backdrop-blur-sm border border-white/20 text-white text-sm font-bold hover:bg-white/20 transition">
                        Recalculer
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ──────────────  KPI CARDS  ────────────── --}}
    @php
        $taux = (float) ($recouvrement['taux'] ?? 0);
        $tauxColor = $taux >= 80 ? 'emerald' : ($taux >= 50 ? 'amber' : 'red');
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Total attendu --}}
        <div class="group relative overflow-hidden bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md hover:border-brand-200 transition-all">
            <div class="absolute -top-6 -right-6 w-24 h-24 bg-brand-50 rounded-full blur-xl opacity-60 group-hover:opacity-100 transition"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-brand-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-brand-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 0v6m0-6L9 17M5 12a7 7 0 1014 0 7 7 0 00-14 0z"/></svg>
                    </div>
                    <span class="text-[10px] font-bold uppercase text-brand-700 bg-brand-50 px-2 py-0.5 rounded-full">Annuel</span>
                </div>
                <p class="text-xs text-gray-500 uppercase font-bold">Total attendu</p>
                <p class="text-2xl font-extrabold text-gray-900 mt-1 tracking-tight">{{ number_format($recouvrement['total_du'] ?? 0, 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-400 mt-0.5">FCFA</p>
            </div>
        </div>

        {{-- Encaissé --}}
        <div class="group relative overflow-hidden bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md hover:border-emerald-200 transition-all">
            <div class="absolute -top-6 -right-6 w-24 h-24 bg-emerald-50 rounded-full blur-xl opacity-60 group-hover:opacity-100 transition"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <span class="text-[10px] font-bold uppercase text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">Confirmé</span>
                </div>
                <p class="text-xs text-gray-500 uppercase font-bold">Encaissé</p>
                <p class="text-2xl font-extrabold text-emerald-600 mt-1 tracking-tight">{{ number_format($recouvrement['total_paye'] ?? 0, 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-400 mt-0.5">FCFA</p>
            </div>
        </div>

        {{-- Reste à percevoir --}}
        <div class="group relative overflow-hidden bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md hover:border-amber-200 transition-all">
            <div class="absolute -top-6 -right-6 w-24 h-24 bg-amber-50 rounded-full blur-xl opacity-60 group-hover:opacity-100 transition"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[10px] font-bold uppercase text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full">À recouvrer</span>
                </div>
                <p class="text-xs text-gray-500 uppercase font-bold">Reste à percevoir</p>
                <p class="text-2xl font-extrabold text-amber-600 mt-1 tracking-tight">{{ number_format($recouvrement['reste'] ?? 0, 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-400 mt-0.5">FCFA</p>
            </div>
        </div>

        {{-- Taux recouvrement --}}
        <div class="group relative overflow-hidden bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md hover:border-{{ $tauxColor }}-200 transition-all">
            <div class="absolute -top-6 -right-6 w-24 h-24 bg-{{ $tauxColor }}-50 rounded-full blur-xl opacity-60 group-hover:opacity-100 transition"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-{{ $tauxColor }}-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-{{ $tauxColor }}-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                    <span class="text-[10px] font-bold uppercase text-{{ $tauxColor }}-700 bg-{{ $tauxColor }}-50 px-2 py-0.5 rounded-full">Performance</span>
                </div>
                <p class="text-xs text-gray-500 uppercase font-bold">Taux recouvrement</p>
                <p class="text-2xl font-extrabold text-{{ $tauxColor }}-600 mt-1 tracking-tight">{{ $taux }}%</p>
                <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2 overflow-hidden">
                    <div class="h-1.5 rounded-full bg-gradient-to-r from-{{ $tauxColor }}-400 to-{{ $tauxColor }}-600 transition-all" style="width: {{ min(100, $taux) }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ──────────────  ANALYSE  ────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Reste par statut --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h2 class="font-bold text-gray-900">Reste par statut élève</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Distribution AFF / NAFF</p>
                </div>
                <div class="w-9 h-9 bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>

            @php
                $aff = (int) ($recouvrement['par_statut']['AFF'] ?? 0);
                $naff = (int) ($recouvrement['par_statut']['NAFF'] ?? 0);
                $totalStatut = max(1, $aff + $naff);
                $pAff = round(($aff / $totalStatut) * 100, 1);
                $pNaff = round(($naff / $totalStatut) * 100, 1);
            @endphp

            <div class="space-y-3">
                <div>
                    <div class="flex justify-between items-baseline text-sm mb-1">
                        <span class="font-semibold text-emerald-700">AFF</span>
                        <span class="font-bold text-gray-900">{{ number_format($aff, 0, ',', ' ') }} F</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600" style="width: {{ $pAff }}%"></div>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $pAff }}% du reste</p>
                </div>
                <div>
                    <div class="flex justify-between items-baseline text-sm mb-1">
                        <span class="font-semibold text-amber-700">NAFF</span>
                        <span class="font-bold text-gray-900">{{ number_format($naff, 0, ',', ' ') }} F</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-gradient-to-r from-amber-400 to-amber-600" style="width: {{ $pNaff }}%"></div>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $pNaff }}% du reste</p>
                </div>
            </div>

            <p class="text-[11px] text-gray-400 mt-4 leading-relaxed border-t pt-3">
                <strong>AFF</strong> = inscription seule · <strong>NAFF</strong> = inscription + scolarité annuelle.
            </p>
        </div>

        {{-- Élèves avec solde (top 15) --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-gray-900 flex items-center gap-2">
                        Élèves avec solde
                        <span class="text-xs font-medium text-gray-400">({{ $retards->count() }})</span>
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">Triés par montant restant</p>
                </div>
                <a href="{{ route('paiements.index') }}" class="text-xs text-brand-600 font-semibold hover:underline">
                    Voir tous les paiements →
                </a>
            </div>

            @if($retards->isEmpty())
                <div class="px-5 py-10 text-center">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-100 flex items-center justify-center mb-3">
                        <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <p class="font-bold text-gray-900">Tout est à jour</p>
                    <p class="text-sm text-gray-500 mt-1">Aucun élève n'a de solde en attente.</p>
                </div>
            @else
                <ul class="divide-y divide-gray-50 max-h-[420px] overflow-y-auto">
                    @foreach($retards as $r)
                        @php
                            $eleve = $r['eleve'];
                            $reste = $r['reste'] ?? 0;
                            $du = $r['du']['montant_total_du'] ?? 0;
                            $pourcent = $du > 0 ? min(100, round((($du - $reste) / $du) * 100, 1)) : 0;
                            $isAff = $eleve->statut_eleve === 'AFF';
                        @endphp
                        <li>
                            <a href="{{ route('finances.eleve', $eleve) }}" class="px-5 py-3 flex items-center gap-3 hover:bg-gray-50 transition group">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold {{ $isAff ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ strtoupper(substr($eleve->prenom ?? '', 0, 1) . substr($eleve->nom ?? '', 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-baseline justify-between gap-2">
                                        <p class="text-sm font-semibold text-gray-900 truncate">
                                            {{ $eleve->prenom }} {{ strtoupper($eleve->nom) }}
                                        </p>
                                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full {{ $isAff ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                            {{ $eleve->statut_eleve }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <div class="flex-1 bg-gray-100 rounded-full h-1 overflow-hidden">
                                            <div class="h-1 rounded-full bg-gradient-to-r from-brand-400 to-brand-600" style="width: {{ $pourcent }}%"></div>
                                        </div>
                                        <span class="text-[10px] text-gray-400 font-medium">{{ $pourcent }}%</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-extrabold text-amber-600">{{ number_format($reste, 0, ',', ' ') }} F</p>
                                    <p class="text-[10px] text-gray-400">restants</p>
                                </div>
                                <svg class="w-4 h-4 text-gray-300 group-hover:text-brand-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- ──────────────  ACCÈS RAPIDE  ────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <a href="{{ route('paiements.create') }}" class="group bg-white rounded-2xl border border-gray-100 p-4 hover:border-brand-300 hover:shadow-sm transition flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-brand-100 group-hover:bg-brand-500 flex items-center justify-center transition">
                <svg class="w-5 h-5 text-brand-700 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </div>
            <div class="min-w-0">
                <p class="font-bold text-gray-900 text-sm">Nouveau paiement</p>
                <p class="text-[11px] text-gray-500">Manuel ou Wave</p>
            </div>
        </a>

        <a href="{{ route('finances.tarifs') }}" class="group bg-white rounded-2xl border border-gray-100 p-4 hover:border-purple-300 hover:shadow-sm transition flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-purple-100 group-hover:bg-purple-500 flex items-center justify-center transition">
                <svg class="w-5 h-5 text-purple-700 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="font-bold text-gray-900 text-sm">Grilles tarifaires</p>
                <p class="text-[11px] text-gray-500">Collège · Lycée</p>
            </div>
        </a>

        <a href="{{ route('finances.wave') }}" class="group bg-white rounded-2xl border border-gray-100 p-4 hover:border-blue-300 hover:shadow-sm transition flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-blue-100 group-hover:bg-blue-500 flex items-center justify-center transition">
                <span class="text-blue-700 group-hover:text-white text-base font-extrabold transition">W</span>
            </div>
            <div class="min-w-0">
                <p class="font-bold text-gray-900 text-sm">Paramètres Wave</p>
                <p class="text-[11px] text-gray-500">Activation · Lien marchand</p>
            </div>
        </a>

        <a href="{{ route('paiements.index') }}" class="group bg-white rounded-2xl border border-gray-100 p-4 hover:border-gray-300 hover:shadow-sm transition flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-gray-100 group-hover:bg-gray-700 flex items-center justify-center transition">
                <svg class="w-5 h-5 text-gray-700 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="font-bold text-gray-900 text-sm">Historique paiements</p>
                <p class="text-[11px] text-gray-500">Filtres · Export CSV</p>
            </div>
        </a>
    </div>

    {{-- ──────────────  DERNIERS PAIEMENTS  ────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
            <div>
                <h2 class="font-bold text-gray-900">Derniers paiements</h2>
                <p class="text-xs text-gray-500 mt-0.5">{{ $paiements->total() }} paiement(s) au total</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('paiements.export') }}" class="px-3 py-1.5 rounded-lg border border-gray-200 text-gray-700 text-xs font-bold hover:bg-gray-50 transition">
                    Export CSV
                </a>
                <a href="{{ route('paiements.index') }}" class="px-3 py-1.5 rounded-lg bg-brand-600 text-white text-xs font-bold hover:bg-brand-700 transition">
                    Voir tout →
                </a>
            </div>
        </div>

        @if($paiements->isEmpty())
            <div class="px-5 py-12 text-center">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-gray-100 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <p class="font-bold text-gray-900">Aucun paiement enregistré</p>
                <p class="text-sm text-gray-500 mt-1">Commencez par <a href="{{ route('paiements.create') }}" class="text-brand-600 underline">enregistrer un paiement</a>.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50/50 text-left text-[11px] uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-5 py-3 font-bold">Date</th>
                            <th class="px-4 py-3 font-bold">Élève</th>
                            <th class="px-4 py-3 font-bold">Classe</th>
                            <th class="px-4 py-3 text-right font-bold">Montant</th>
                            <th class="px-4 py-3 font-bold">Mode</th>
                            <th class="px-4 py-3 font-bold">Statut</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($paiements as $p)
                            @php
                                $modeColors = [
                                    'orange_money'=>['orange','OM'],
                                    'mtn_money'=>['yellow','MTN'],
                                    'moov_money'=>['indigo','MV'],
                                    'wave'=>['blue','W'],
                                    'especes'=>['gray','€'],
                                    'cheque'=>['purple','CH'],
                                    'virement'=>['teal','VR'],
                                    'carte_bancaire'=>['pink','CB'],
                                ];
                                [$c, $abbr] = $modeColors[$p->mode] ?? ['gray', '?'];
                                $statutPill = match($p->statut) {
                                    'confirme' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                                    'en_attente' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
                                    'annule' => 'bg-gray-100 text-gray-600 ring-1 ring-gray-200',
                                    'echoue' => 'bg-red-50 text-red-700 ring-1 ring-red-200',
                                    default => 'bg-gray-50 text-gray-600 ring-1 ring-gray-200',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50/60 transition">
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <p class="font-medium text-gray-900">{{ $p->date_paiement?->format('d/m/Y') }}</p>
                                    @if($p->numero_recu)
                                        <p class="text-[10px] text-emerald-600 font-mono font-bold">{{ $p->numero_recu }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-gray-900">{{ $p->eleve?->prenom }} {{ $p->eleve?->nom }}</p>
                                    <p class="text-[11px] text-gray-400 font-mono">{{ $p->eleve?->matricule_interne }}</p>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $p->inscription?->classe?->nom ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="font-extrabold text-gray-900">{{ number_format($p->montant, 0, ',', ' ') }}</span>
                                    <span class="text-[10px] text-gray-400 ml-0.5">F</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="inline-flex items-center gap-1.5">
                                        <span class="w-6 h-6 rounded-md bg-{{ $c }}-100 text-{{ $c }}-700 text-[10px] font-extrabold flex items-center justify-center">{{ $abbr }}</span>
                                        <span class="text-xs text-gray-700">{{ str_replace('_', ' ', ucfirst($p->mode)) }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center text-[11px] font-bold uppercase px-2 py-0.5 rounded-full {{ $statutPill }}">
                                        {{ str_replace('_', ' ', $p->statut) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('paiements.show', $p) }}" class="text-xs text-brand-600 font-bold hover:underline">Détail</a>
                                    @if($p->statut === 'confirme')
                                        <span class="text-gray-200 mx-1">·</span>
                                        <a href="{{ route('paiements.recu', $p) }}" class="text-xs text-emerald-600 font-bold hover:underline">Reçu</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-gray-50 bg-gray-50/30">
                {{ $paiements->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
