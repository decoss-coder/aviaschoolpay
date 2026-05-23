@extends('layouts.app')

@section('title', 'Élèves')
@section('page-title', 'Gestion des élèves')
@section('page-subtitle', ($stats['total'] ?? $eleves->total()) . ' élèves — ' . ($annee->libelle ?? 'Année scolaire en cours'))

@section('content')
<div x-data="{
    showFilters: false,
    openDeleteModal: false,
    deleteUrl: '',
    deleteNom: '',
    deleteMatricule: '',
    openDeleteStudentModal(url, nom, matricule) {
        this.deleteUrl = url;
        this.deleteNom = nom;
        this.deleteMatricule = matricule;
        this.openDeleteModal = true;
    },
    closeDeleteStudentModal() {
        this.openDeleteModal = false;
        this.deleteUrl = '';
        this.deleteNom = '';
        this.deleteMatricule = '';
    }
}">

    {{-- ════════════════════════════════════════════════════ --}}
    {{-- HEADER BAR : Actions + Recherche --}}
    {{-- ════════════════════════════════════════════════════ --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3 flex-wrap">
            @editable
            <a href="{{ route('eleves.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nouvelle inscription
            </a>

            <a href="{{ route('eleves.import.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-gold-300 via-gold-400 to-gold-500 text-brand-900 text-[13px] font-extrabold rounded-xl shadow-gold-glow ring-1 ring-gold-200/60 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Importer une liste
            </a>
            @endeditable

            <a href="{{ route('eleves.export') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-brand-100 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-brand-50 hover:border-brand-200 hover:text-brand-700 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Exporter
            </a>

            <button @click="showFilters = !showFilters"
                    class="lg:hidden inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-brand-100 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filtres
            </button>
        </div>

        <form method="GET" class="flex items-center gap-2 flex-wrap">
            <div class="relative flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Nom, prénom, matricule..."
                       class="w-full lg:w-72 pl-10 pr-4 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-300 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                <svg class="w-4 h-4 text-brand-400 absolute left-3 top-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <select name="classe_id" onchange="this.form.submit()"
                    class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm text-gray-700 focus:border-brand-300 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                <option value="">Toutes classes</option>
                @foreach($classes ?? [] as $classe)
                    <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>
                        {{ $classe->nom }}
                        @if($classe->niveau)
                            — {{ $classe->niveau->libelle ?? $classe->niveau->code }}
                        @endif
                    </option>
                @endforeach
            </select>

            <select name="statut_eleve" onchange="this.form.submit()"
                    class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm text-gray-700 focus:border-brand-300 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                <option value="">Tous statuts élève</option>
                <option value="AFF" {{ request('statut_eleve') == 'AFF' ? 'selected' : '' }}>Affecté</option>
                <option value="NAFF" {{ request('statut_eleve') == 'NAFF' ? 'selected' : '' }}>Non affecté</option>
            </select>
        </form>
    </div>

    {{-- ════════════════════════════════════════════════════ --}}
    {{-- CARTES RÉSUMÉ --}}
    {{-- ════════════════════════════════════════════════════ --}}
    @php
        $totalGarcons = (int) (($stats['garcons_aff'] ?? 0) + ($stats['garcons_naff'] ?? 0));
        $totalFilles = (int) (($stats['filles_aff'] ?? 0) + ($stats['filles_naff'] ?? 0));
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="relative overflow-hidden bg-gradient-to-br from-white to-brand-50/50 border border-brand-100/60 rounded-2xl p-5 shadow-card-brand">
            <div class="absolute -top-6 -right-6 w-24 h-24 bg-brand-200/30 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-11 h-11 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center shadow-brand-glow flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[11px] text-gray-500 font-semibold uppercase tracking-[0.12em]">Effectif total</p>
                        <p class="font-display text-3xl font-extrabold text-gray-900 leading-none mt-1">{{ $stats['total'] ?? 0 }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-blue-100 bg-blue-50/70 px-3 py-3">
                        <p class="text-[10px] uppercase tracking-[0.12em] font-bold text-blue-600">Garçons</p>
                        <p class="mt-1 text-xl font-extrabold text-blue-700">{{ $totalGarcons }}</p>
                    </div>
                    <div class="rounded-xl border border-pink-100 bg-pink-50/70 px-3 py-3">
                        <p class="text-[10px] uppercase tracking-[0.12em] font-bold text-pink-600">Filles</p>
                        <p class="mt-1 text-xl font-extrabold text-pink-700">{{ $totalFilles }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-emerald-50/60 border border-emerald-100/60 rounded-2xl p-5 shadow-sm">
            <div class="absolute -top-6 -right-6 w-24 h-24 bg-emerald-200/30 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-xl flex items-center justify-center shadow-sm shadow-emerald-500/30 flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[11px] text-gray-500 font-semibold uppercase tracking-[0.12em]">Affectés</p>
                        <p class="font-display text-3xl font-extrabold text-emerald-700 leading-none mt-1">{{ $stats['affectes'] ?? 0 }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-blue-100 bg-blue-50/70 px-3 py-3">
                        <p class="text-[10px] uppercase tracking-[0.12em] font-bold text-blue-600">Garçons</p>
                        <p class="mt-1 text-xl font-extrabold text-blue-700">{{ $stats['garcons_aff'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl border border-pink-100 bg-pink-50/70 px-3 py-3">
                        <p class="text-[10px] uppercase tracking-[0.12em] font-bold text-pink-600">Filles</p>
                        <p class="mt-1 text-xl font-extrabold text-pink-700">{{ $stats['filles_aff'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-amber-50/60 border border-amber-100/60 rounded-2xl p-5 shadow-sm">
            <div class="absolute -top-6 -right-6 w-24 h-24 bg-amber-200/30 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-400 to-amber-600 rounded-xl flex items-center justify-center shadow-sm shadow-amber-500/30 flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[11px] text-gray-500 font-semibold uppercase tracking-[0.12em]">Non affectés</p>
                        <p class="font-display text-3xl font-extrabold text-amber-700 leading-none mt-1">{{ $stats['non_affectes'] ?? 0 }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-blue-100 bg-blue-50/70 px-3 py-3">
                        <p class="text-[10px] uppercase tracking-[0.12em] font-bold text-blue-600">Garçons</p>
                        <p class="mt-1 text-xl font-extrabold text-blue-700">{{ $stats['garcons_naff'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl border border-pink-100 bg-pink-50/70 px-3 py-3">
                        <p class="text-[10px] uppercase tracking-[0.12em] font-bold text-pink-600">Filles</p>
                        <p class="mt-1 text-xl font-extrabold text-pink-700">{{ $stats['filles_naff'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════ --}}
    {{-- TABLE --}}
    {{-- ════════════════════════════════════════════════════ --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/20 rounded-2xl border border-brand-100/60 shadow-card-brand">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gradient-to-r from-brand-50/60 via-white to-gold-50/30 border-b border-brand-100/60">
                        <th class="px-6 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Matricule</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Élève</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Classe</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Statut élève</th>
                        <th class="px-4 py-3.5 text-right text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Inscription</th>
                        <th class="px-4 py-3.5 text-right text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Scolarité</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Paiement</th>
                        <th class="px-4 py-3.5 text-center text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-50/60">
                    @forelse($eleves as $eleve)
                    @php
                        $classeCourante = $eleve->classe ?? $eleve->inscriptionEnCours?->classe;
                        $statutEleve = strtoupper(trim((string) ($eleve->statut_eleve ?? '')));
                        $showUrl = route('eleves.show', $eleve);

                        // Nouveau système : finances pré-calculées par le controller
                        $fin = $finances[$eleve->id] ?? [
                            'du_inscription' => 0, 'du_scolarite' => 0, 'total_du' => 0,
                            'paye' => 0, 'reste' => 0, 'taux' => 0, 'statut' => 'indefini',
                        ];
                        $net = $fin['total_du'];
                        $paye = $fin['paye'];
                        $taux = $fin['taux'];

                        $classeLabel = $classeCourante
                            ? $classeCourante->nom . ($classeCourante->niveau ? ' · ' . ($classeCourante->niveau->libelle ?? $classeCourante->niveau->code ?? '') : '')
                            : '—';
                    @endphp
                    <tr class="hover:bg-brand-50/30 transition-colors cursor-pointer"
                        onclick="window.location='{{ $showUrl }}'">
                        <td class="px-6 py-3.5">
                            @if($eleve->matricule_desps)
                                <p class="text-[12px] font-mono font-bold text-gray-900">{{ $eleve->matricule_desps }}</p>
                                <p class="text-[10px] text-gray-400 font-medium flex items-center gap-1 mt-0.5">
                                    <span class="w-1 h-1 bg-gray-400 rounded-full"></span>
                                    Int. {{ $eleve->matricule_interne }}
                                </p>
                            @else
                                <p class="text-[12px] font-mono font-bold text-gray-800">{{ $eleve->matricule_interne }}</p>
                                <p class="text-[10px] text-red-500 font-bold flex items-center gap-1 mt-0.5">
                                    ⚠ Sans DESPS
                                </p>
                            @endif
                        </td>

                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-[11px] font-extrabold shadow-sm ring-2 ring-white flex-shrink-0
                                    {{ $eleve->sexe === 'F' ? 'bg-gradient-to-br from-pink-400 to-pink-600 text-white' : 'bg-gradient-to-br from-blue-400 to-blue-600 text-white' }}">
                                    {{ strtoupper(substr($eleve->prenom, 0, 1)) }}{{ strtoupper(substr($eleve->nom, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[13px] font-bold text-gray-900 truncate">{{ $eleve->nom }} {{ $eleve->prenom }}</p>
                                    <p class="text-[11px] text-gray-400">{{ $eleve->sexe }} • {{ $eleve->age }} ans</p>
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center text-[11px] font-bold text-brand-700 bg-gradient-to-br from-brand-50 to-brand-100/50 border border-brand-200/60 px-2.5 py-1 rounded-full">
                                {{ $classeLabel }}
                            </span>
                        </td>

                        <td class="px-4 py-3.5">
                            @if($statutEleve === 'AFF')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200/60">
                                    AFF
                                </span>
                            @elseif($statutEleve === 'NAFF')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200/60">
                                    NAFF
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold text-gray-500 bg-gray-50 border border-gray-200/60">
                                    —
                                </span>
                            @endif
                        </td>

                        {{-- COLONNE INSCRIPTION --}}
                        <td class="px-4 py-3.5 text-right">
                            @if($fin['du_inscription'] > 0)
                                @php
                                    $payeIns = min($fin['paye'], $fin['du_inscription']);
                                    $resteIns = max(0, $fin['du_inscription'] - $payeIns);
                                @endphp
                                <p class="text-[12px] font-bold text-gray-900">{{ number_format($fin['du_inscription'], 0, ',', ' ') }} <span class="text-[9px] text-gray-400">F</span></p>
                                <p class="text-[10px] text-emerald-700 font-semibold mt-0.5">Payé {{ number_format($payeIns, 0, ',', ' ') }} F</p>
                                @if($resteIns > 0)
                                    <p class="text-[10px] text-amber-700 font-bold">Reste {{ number_format($resteIns, 0, ',', ' ') }} F</p>
                                @else
                                    <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-emerald-100 text-emerald-700">Soldé</span>
                                @endif
                            @else
                                <span class="text-[11px] text-gray-300 italic">—</span>
                            @endif
                        </td>

                        {{-- COLONNE SCOLARITÉ --}}
                        <td class="px-4 py-3.5 text-right">
                            @if($fin['du_scolarite'] > 0)
                                @php
                                    $payeScol = max(0, $fin['paye'] - $fin['du_inscription']);
                                    $payeScol = min($payeScol, $fin['du_scolarite']);
                                    $resteScol = max(0, $fin['du_scolarite'] - $payeScol);
                                @endphp
                                <p class="text-[12px] font-bold text-gray-900">{{ number_format($fin['du_scolarite'], 0, ',', ' ') }} <span class="text-[9px] text-gray-400">F</span></p>
                                <p class="text-[10px] text-emerald-700 font-semibold mt-0.5">Payé {{ number_format($payeScol, 0, ',', ' ') }} F</p>
                                @if($resteScol > 0)
                                    <p class="text-[10px] text-amber-700 font-bold">Reste {{ number_format($resteScol, 0, ',', ' ') }} F</p>
                                @else
                                    <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-emerald-100 text-emerald-700">Soldé</span>
                                @endif
                            @elseif($statutEleve === 'AFF')
                                <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-blue-50 text-blue-700">N/A · AFF</span>
                            @elseif($fin['total_du'] === 0)
                                <a href="{{ route('finances.tarifs') }}" class="text-[10px] text-brand-600 hover:underline italic" onclick="event.stopPropagation()">Tarifs ?</a>
                            @else
                                <span class="text-[11px] text-gray-300 italic">—</span>
                            @endif
                        </td>

                        {{-- COLONNE STATUT PAIEMENT (badge + barre + lien fiche financière) --}}
                        <td class="px-4 py-3.5">
                            <div class="flex flex-col gap-1.5" onclick="event.stopPropagation()">
                                @php
                                    $badgeMap = [
                                        'a_jour'    => ['À jour',  'bg-emerald-100 text-emerald-700 ring-emerald-200'],
                                        'partiel'   => ['Partiel', 'bg-amber-100 text-amber-700 ring-amber-200'],
                                        'impaye'    => ['Impayé',  'bg-red-100 text-red-700 ring-red-200'],
                                        'indefini'  => ['—',       'bg-gray-100 text-gray-500 ring-gray-200'],
                                    ];
                                    [$badgeLabel, $badgeCls] = $badgeMap[$fin['statut']] ?? $badgeMap['indefini'];
                                @endphp
                                <span class="inline-flex items-center justify-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider ring-1 w-fit {{ $badgeCls }}">
                                    {{ $badgeLabel }}
                                </span>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="h-1.5 rounded-full shadow-sm {{ $taux >= 100 ? 'bg-gradient-to-r from-emerald-400 to-emerald-600' : ($taux >= 50 ? 'bg-gradient-to-r from-gold-300 to-gold-500' : 'bg-gradient-to-r from-red-400 to-red-600') }}"
                                             style="width: {{ min($taux, 100) }}%"></div>
                                    </div>
                                    <span class="text-[10px] font-extrabold {{ $taux >= 100 ? 'text-emerald-600' : ($taux >= 50 ? 'text-gold-600' : 'text-red-600') }}">{{ $taux }}%</span>
                                </div>
                                <a href="{{ route('finances.eleve', $eleve) }}" class="text-[10px] text-brand-600 hover:underline font-bold mt-0.5">
                                    Fiche financière →
                                </a>
                            </div>
                        </td>

                        <td class="px-4 py-3.5">
                            <div class="flex items-center justify-center gap-1" onclick="event.stopPropagation()">
                                <a href="{{ route('eleves.show', $eleve) }}"
                                   class="p-2 text-gray-500 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors"
                                   title="Voir">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>

                                @editable
                                <a href="{{ route('eleves.edit', $eleve) }}"
                                   class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                   title="Modifier">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>

                                <a href="{{ route('paiements.create', ['classe_id' => $eleve->classe_id, 'eleve_id' => $eleve->id]) }}"
                                   class="p-2 {{ $fin['reste'] > 0 ? 'text-gold-600 hover:text-gold-700 hover:bg-gold-50' : 'text-gray-300 hover:text-gray-500 hover:bg-gray-50' }} rounded-lg transition-colors"
                                   title="{{ $fin['reste'] > 0 ? 'Enregistrer un paiement' : 'Aucun montant dû — voir historique' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                    </svg>
                                </a>

                                <button type="button"
                                        @click="openDeleteStudentModal('{{ route('eleves.destroy', $eleve) }}', '{{ e($eleve->nom . ' ' . $eleve->prenom) }}', '{{ e($eleve->matricule_interne) }}')"
                                        class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Supprimer / radier">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-7 0h8"/>
                                    </svg>
                                </button>
                                @endeditable
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-20 h-20 bg-gradient-to-br from-brand-100 to-brand-50 rounded-full flex items-center justify-center mb-4 shadow-card-brand">
                                    <svg class="w-10 h-10 text-brand-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <p class="font-display text-base font-bold text-gray-700">Aucun élève trouvé</p>
                                <p class="text-sm text-gray-400 mt-1">Essayez de modifier vos filtres ou ajoutez un nouvel élève.</p>
                                <a href="{{ route('eleves.create') }}" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-brand-500 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Inscrire un élève
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($eleves->hasPages())
        <div class="px-6 py-4 border-t border-brand-100/60 bg-gradient-to-r from-brand-50/40 to-transparent">
            {{ $eleves->links() }}
        </div>
        @endif
    </div>

    {{-- MODAL SUPPRESSION --}}
    <template x-teleport="body">
        <div x-show="openDeleteModal"
             x-cloak
             class="fixed inset-0 z-[9999] bg-black/50 backdrop-blur-[2px]"
             @click.self="closeDeleteStudentModal()"
             @keydown.escape.window="closeDeleteStudentModal()">

            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div x-show="openDeleteModal"
                     class="w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-red-100 overflow-hidden">

                    <div class="px-6 py-4 border-b border-red-100 bg-gradient-to-r from-red-50 via-white to-red-50/40 flex items-center justify-between">
                        <div>
                            <h3 class="font-display text-lg font-extrabold text-gray-900">Confirmer la suppression</h3>
                            <p class="text-[12px] text-gray-500 mt-0.5">Cette action radie l’élève de la liste active</p>
                        </div>

                        <button type="button"
                                @click="closeDeleteStudentModal()"
                                class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6">
                        <div class="flex items-start gap-3 mb-5">
                            <div class="w-11 h-11 rounded-xl bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 4h.01m-7.938 4h15.876c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L2.33 17c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>

                            <div>
                                <p class="text-sm text-gray-700">Vous êtes sur le point de radier l’élève :</p>
                                <p class="mt-1 text-base font-extrabold text-gray-900" x-text="deleteNom"></p>
                                <p class="text-[12px] text-gray-500">
                                    Matricule : <span class="font-bold" x-text="deleteMatricule"></span>
                                </p>
                            </div>
                        </div>

                        <div class="rounded-xl border border-red-100 bg-red-50/70 px-4 py-3 text-[12px] text-red-700">
                            L’élève ne sera plus visible dans la liste active. Cette opération est une radiation logique.
                        </div>

                        <form :action="deleteUrl" method="POST" class="mt-6 flex items-center justify-end gap-3">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="confirm_delete" value="1">

                            <button type="button"
                                    @click="closeDeleteStudentModal()"
                                    class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">
                                Annuler
                            </button>

                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white text-[13px] font-extrabold rounded-xl shadow-sm hover:shadow-md transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V4h6v3m-7 4v6m4-6v6"/>
                                </svg>
                                Supprimer l’élève
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </template>
</div>
@endsection