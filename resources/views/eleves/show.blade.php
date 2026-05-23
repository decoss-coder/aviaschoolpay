@extends('layouts.app')

@section('title', $eleve->prenom . ' ' . $eleve->nom)
@section('page-title', $eleve->nom . ' ' . $eleve->prenom)
@section('page-subtitle', 'Matricule ' . $eleve->matricule_interne . ' — ' . ($eleve->classe?->nom ?? 'Non inscrit'))

@section('content')
@php
    $classeCourante = $eleve->classe;
    $inscription = $eleve->inscriptionEnCours;
    $paye = $inscription ? $inscription->montantPaye() : 0;
    $net = $inscription->montant_net ?? 0;
    $taux = $net > 0 ? round(($paye / $net) * 100) : 0;
    $reste = max(0, $net - $paye);
    $derniereMoyenne = $eleve->moyennesGenerales->sortByDesc('trimestre_id')->first();
    $parent = $eleve->parents->first();

    $classeLabel = $classeCourante ? $classeCourante->nom : 'Non inscrit';
    $niveauLabel = $classeCourante?->niveau ? ($classeCourante->niveau->libelle ?? $classeCourante->niveau->code) : null;
@endphp

<div x-data="{ activeTab: 'infos' }">

    {{-- ════════════════════════════════════════════════════ --}}
    {{-- HERO HEADER --}}
    {{-- ════════════════════════════════════════════════════ --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-brand-800 rounded-2xl shadow-brand-glow mb-6">
        <div class="absolute -top-20 -right-20 w-72 h-72 bg-gold-400/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-10 -left-10 w-60 h-60 bg-brand-400/30 rounded-full blur-3xl"></div>
        <div class="absolute top-0 right-0 bottom-0 w-1 bg-gradient-to-b from-gold-300 via-gold-400 to-gold-500"></div>

        <div class="relative p-6 lg:p-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center gap-6">
                <div class="relative flex-shrink-0">
                    <div class="w-24 h-24 lg:w-28 lg:h-28 rounded-2xl overflow-hidden ring-4 ring-white/20 shadow-2xl">
                        @if($eleve->photo_path)
                            <img src="{{ asset('storage/' . $eleve->photo_path) }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full bg-gradient-to-br {{ $eleve->sexe === 'F' ? 'from-pink-400 to-pink-600' : 'from-blue-400 to-blue-600' }} flex items-center justify-center">
                                <span class="font-display text-4xl font-extrabold text-white">
                                    {{ strtoupper(substr($eleve->prenom, 0, 1)) }}{{ strtoupper(substr($eleve->nom, 0, 1)) }}
                                </span>
                            </div>
                        @endif
                    </div>
                    <span class="absolute -bottom-2 -right-2 px-2 py-1 bg-gradient-to-br from-gold-300 to-gold-500 text-brand-900 text-[10px] font-extrabold rounded-lg shadow-gold-glow">
                        {{ $eleve->sexe === 'F' ? 'F' : 'G' }} · {{ $eleve->age }} ans
                    </span>
                </div>

                <div class="flex-1 min-w-0">
                    <h1 class="font-display text-2xl lg:text-3xl font-extrabold text-white tracking-tight leading-tight">
                        {{ $eleve->prenom }} {{ $eleve->nom }}
                    </h1>
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white/15 backdrop-blur text-white text-[11px] font-bold rounded-full border border-white/20">
                            <span class="w-1 h-1 bg-gold-300 rounded-full"></span>
                            {{ $eleve->matricule_interne }}
                        </span>
                        @if($eleve->matricule_desps)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gold-400/20 backdrop-blur text-gold-100 text-[11px] font-bold rounded-full border border-gold-300/40">
                                DESPS · {{ $eleve->matricule_desps }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-red-500/30 backdrop-blur text-red-100 text-[11px] font-bold rounded-full border border-red-300/40">
                                ⚠ Sans matricule DESPS
                            </span>
                        @endif
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white/15 backdrop-blur text-white text-[11px] font-bold rounded-full border border-white/20">
                            {{ $classeLabel }}
                            @if($niveauLabel)
                                <span class="text-brand-100">· {{ $niveauLabel }}</span>
                            @endif
                        </span>
                    </div>
                    <p class="text-[12px] text-brand-100 mt-3">
                        Né{{ $eleve->sexe === 'F' ? 'e' : '' }} le
                        <span class="font-bold text-white">{{ $eleve->date_naissance?->format('d/m/Y') ?? '—' }}</span>
                        @if($eleve->lieu_naissance)
                            à <span class="font-bold text-white">{{ $eleve->lieu_naissance }}</span>
                        @endif
                        @if($eleve->nationalite)
                            · <span class="font-bold text-white">{{ $eleve->nationalite }}</span>
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 lg:flex-col lg:items-stretch">
                    <a href="{{ route('paiements.create', ['eleve_id' => $eleve->id]) }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-gold-300 to-gold-500 text-brand-900 text-[13px] font-extrabold rounded-xl shadow-gold-glow hover:shadow-lg transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Enregistrer paiement
                    </a>
                    <a href="{{ route('eleves.edit', $eleve) }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white/15 backdrop-blur text-white text-[13px] font-bold rounded-xl border border-white/20 hover:bg-white/25 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Modifier
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════ --}}
    {{-- QUICK STATS --}}
    {{-- ════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="relative overflow-hidden bg-gradient-to-br from-white to-violet-50/50 rounded-2xl border border-violet-100/60 shadow-card-violet p-5">
            <div class="absolute -top-6 -right-6 w-24 h-24 bg-violet-200/30 rounded-full blur-2xl"></div>
            <div class="relative flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-gradient-to-br from-violet-400 to-purple-600 rounded-lg flex items-center justify-center shadow-sm shadow-violet-500/30">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                </div>
                @if($derniereMoyenne)
                    <span class="text-[10px] font-bold text-violet-700 bg-violet-100 border border-violet-200/60 px-2 py-0.5 rounded-full">{{ $derniereMoyenne->trimestre->libelle ?? 'T?' }}</span>
                @endif
            </div>
            <p class="font-display text-2xl font-extrabold {{ $derniereMoyenne && $derniereMoyenne->moyenne_generale >= 14 ? 'text-brand-600' : ($derniereMoyenne && $derniereMoyenne->moyenne_generale >= 10 ? 'text-blue-600' : 'text-gray-400') }}">
                {{ $derniereMoyenne ? number_format($derniereMoyenne->moyenne_generale, 2) : '—' }}<span class="text-sm text-gray-400 font-medium">/20</span>
            </p>
            <p class="text-[11px] text-gray-500 font-medium mt-1">Moyenne actuelle{{ $derniereMoyenne ? ' · Rang ' . $derniereMoyenne->rang . 'e' : '' }}</p>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-gold-50/50 rounded-2xl border border-gold-200/60 shadow-card-gold p-5">
            <div class="absolute -top-6 -right-6 w-24 h-24 bg-gold-200/30 rounded-full blur-2xl"></div>
            <div class="relative flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-lg flex items-center justify-center shadow-gold-glow">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/></svg>
                </div>
                <span class="text-[10px] font-bold {{ $taux >= 100 ? 'text-brand-700 bg-brand-100' : ($taux >= 50 ? 'text-gold-700 bg-gold-100' : 'text-red-700 bg-red-100') }} border border-current/20 px-2 py-0.5 rounded-full">{{ $taux }}%</span>
            </div>
            <p class="font-display text-2xl font-extrabold text-gray-900">{{ number_format($paye, 0, ',', ' ') }}<span class="text-xs text-gray-400 font-medium"> / {{ number_format($net, 0, ',', ' ') }} F</span></p>
            <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2 overflow-hidden">
                <div class="h-1.5 rounded-full {{ $taux >= 100 ? 'bg-gradient-to-r from-brand-400 to-brand-600' : ($taux >= 50 ? 'bg-gradient-to-r from-gold-300 to-gold-500' : 'bg-gradient-to-r from-red-400 to-red-600') }}" style="width: {{ min($taux, 100) }}%"></div>
            </div>
            <p class="text-[11px] text-gray-500 font-medium mt-2">Scolarité payée</p>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white {{ $reste > 0 ? 'to-red-50/50 border-red-100/60' : 'to-brand-50/50 border-brand-100/60' }} rounded-2xl border shadow-card p-5">
            <div class="absolute -top-6 -right-6 w-24 h-24 {{ $reste > 0 ? 'bg-red-200/30' : 'bg-brand-200/30' }} rounded-full blur-2xl"></div>
            <div class="relative flex items-center justify-between mb-3">
                <div class="w-10 h-10 {{ $reste > 0 ? 'bg-gradient-to-br from-red-400 to-red-600' : 'bg-gradient-to-br from-brand-400 to-brand-600' }} rounded-lg flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="font-display text-2xl font-extrabold {{ $reste > 0 ? 'text-red-600' : 'text-brand-600' }}">{{ number_format($reste, 0, ',', ' ') }}<span class="text-xs text-gray-400 font-medium"> F</span></p>
            <p class="text-[11px] text-gray-500 font-medium mt-1">{{ $reste > 0 ? 'Reste à payer' : 'Scolarité complète' }}</p>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-blue-50/50 rounded-2xl border border-blue-100/60 shadow-card-blue p-5">
            <div class="absolute -top-6 -right-6 w-24 h-24 bg-blue-200/30 rounded-full blur-2xl"></div>
            <div class="relative flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center shadow-sm shadow-blue-500/30">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <p class="font-display text-2xl font-extrabold text-gray-900">{{ $eleve->paiements->count() }}</p>
            <p class="text-[11px] text-gray-500 font-medium mt-1">Paiements enregistrés</p>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════ --}}
    {{-- ONGLETS --}}
    {{-- ════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl border border-brand-100/60 shadow-card-brand overflow-hidden">
        <div class="flex items-center gap-1 px-4 py-2 border-b border-brand-100/60 bg-gradient-to-r from-brand-50/60 via-white to-gold-50/30 overflow-x-auto">
            @php
                $tabs = [
                    ['id' => 'infos', 'label' => 'Informations', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['id' => 'parents', 'label' => 'Parents', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                    ['id' => 'moyennes', 'label' => 'Moyennes', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10'],
                    ['id' => 'paiements', 'label' => 'Paiements', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z'],
                ];
            @endphp
            @foreach($tabs as $tab)
                <button @click="activeTab = '{{ $tab['id'] }}'" type="button"
                        :class="activeTab === '{{ $tab['id'] }}' ? 'bg-gradient-to-r from-brand-500 to-brand-700 text-white shadow-brand-glow' : 'text-gray-600 hover:bg-brand-50 hover:text-brand-700'"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-[13px] font-bold transition-all whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}"/></svg>
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </div>

        <div x-show="activeTab === 'infos'" class="p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                <div class="flex items-start gap-3 p-3 bg-brand-50/30 border border-brand-100/60 rounded-xl">
                    <div class="w-8 h-8 bg-gradient-to-br from-brand-400 to-brand-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm"><svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div>
                    <div class="min-w-0"><dt class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Nom complet</dt><dd class="text-sm font-bold text-gray-900 mt-0.5">{{ $eleve->nom }} {{ $eleve->prenom }}</dd></div>
                </div>

                <div class="flex items-start gap-3 p-3 bg-gold-50/30 border border-gold-100/60 rounded-xl">
                    <div class="w-8 h-8 bg-gradient-to-br from-gold-300 to-gold-500 rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Classe</dt>
                        <dd class="text-sm font-bold text-gray-900 mt-0.5">
                            {{ $classeLabel }}
                            @if($niveauLabel)
                                <span class="text-brand-600">· {{ $niveauLabel }}</span>
                            @endif
                        </dd>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-3 bg-blue-50/30 border border-blue-100/60 rounded-xl">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm"><svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
                    <div class="min-w-0"><dt class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Date de naissance</dt><dd class="text-sm font-bold text-gray-900 mt-0.5">{{ $eleve->date_naissance?->format('d/m/Y') ?? '—' }}{{ $eleve->age ? ' (' . $eleve->age . ' ans)' : '' }}</dd></div>
                </div>

                <div class="flex items-start gap-3 p-3 bg-gold-50/30 border border-gold-100/60 rounded-xl">
                    <div class="w-8 h-8 bg-gradient-to-br from-gold-300 to-gold-500 rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm"><svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
                    <div class="min-w-0"><dt class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Lieu de naissance</dt><dd class="text-sm font-bold text-gray-900 mt-0.5">{{ $eleve->lieu_naissance ?? '—' }}</dd></div>
                </div>

                <div class="flex items-start gap-3 p-3 bg-violet-50/30 border border-violet-100/60 rounded-xl">
                    <div class="w-8 h-8 bg-gradient-to-br from-violet-400 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm"><svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/></svg></div>
                    <div class="min-w-0"><dt class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Nationalité</dt><dd class="text-sm font-bold text-gray-900 mt-0.5">{{ $eleve->nationalite ?? '—' }}</dd></div>
                </div>

                <div class="flex items-start gap-3 p-3 bg-gray-50 border border-gray-200/60 rounded-xl md:col-span-2">
                    <div class="w-8 h-8 bg-gradient-to-br from-gray-400 to-gray-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm"><svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg></div>
                    <div class="min-w-0 flex-1"><dt class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Adresse</dt><dd class="text-sm font-bold text-gray-900 mt-0.5">{{ $eleve->adresse ?? '—' }}</dd></div>
                </div>

                <div class="flex items-start gap-3 p-3 bg-brand-50/30 border border-brand-100/60 rounded-xl">
                    <div class="w-8 h-8 bg-gradient-to-br from-brand-400 to-brand-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm"><svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg></div>
                    <div class="min-w-0"><dt class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Téléphone</dt><dd class="text-sm font-bold text-gray-900 mt-0.5">{{ $eleve->telephone ?? '—' }}</dd></div>
                </div>

                <div class="flex items-start gap-3 p-3 bg-blue-50/30 border border-blue-100/60 rounded-xl">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm"><svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div>
                    <div class="min-w-0"><dt class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Email</dt><dd class="text-sm font-bold text-gray-900 mt-0.5 truncate">{{ $eleve->email ?? '—' }}</dd></div>
                </div>
            </dl>
        </div>

        <div x-show="activeTab === 'parents'" x-cloak class="p-6">
            @if($eleve->parents->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($eleve->parents as $p)
                    <div class="relative overflow-hidden bg-gradient-to-br from-white to-violet-50/40 border border-violet-100/60 rounded-2xl p-5 shadow-card-violet">
                        <div class="absolute -top-6 -right-6 w-20 h-20 bg-violet-200/30 rounded-full blur-2xl"></div>
                        <div class="relative">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-violet-400 to-purple-600 rounded-xl flex items-center justify-center shadow-sm shadow-violet-500/30 text-white font-extrabold">
                                    {{ strtoupper(substr($p->nom_complet ?? 'P', 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-display text-base font-extrabold text-gray-900 truncate">{{ $p->nom_complet ?? '—' }}</p>
                                    <span class="inline-flex text-[10px] font-bold text-violet-700 bg-violet-100 border border-violet-200/60 px-2 py-0.5 rounded-full mt-1 capitalize">{{ $p->lien ?? 'Tuteur' }}</span>
                                </div>
                            </div>
                            <div class="space-y-2 text-sm">
                                @if($p->telephone)
                                <p class="flex items-center gap-2 text-gray-700"><svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg><a href="tel:{{ $p->telephone }}" class="font-bold hover:text-violet-700">{{ $p->telephone }}</a></p>
                                @endif
                                @if($p->email)
                                <p class="flex items-center gap-2 text-gray-700"><svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg><a href="mailto:{{ $p->email }}" class="font-medium hover:text-violet-700 truncate">{{ $p->email }}</a></p>
                                @endif
                                @if($p->profession)
                                <p class="flex items-center gap-2 text-gray-700"><svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg><span class="font-medium">{{ $p->profession }}</span></p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gradient-to-br from-violet-100 to-violet-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-violet-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857"/></svg>
                    </div>
                    <p class="font-display text-base font-bold text-gray-700">Aucun parent enregistré</p>
                    <a href="{{ route('eleves.edit', $eleve) }}" class="inline-flex items-center gap-2 mt-3 text-sm font-bold text-violet-600 hover:text-violet-700">
                        Ajouter un parent →
                    </a>
                </div>
            @endif
        </div>

        <div x-show="activeTab === 'moyennes'" x-cloak class="p-6">
            @if($eleve->moyennesGenerales->count() > 0)
                <div class="space-y-3">
                    @foreach($eleve->moyennesGenerales->sortBy('trimestre_id') as $moy)
                    <div class="flex items-center gap-4 p-4 bg-gradient-to-r from-violet-50/50 via-white to-transparent border border-violet-100/60 rounded-xl shadow-sm">
                        <div class="w-12 h-12 bg-gradient-to-br from-violet-400 to-purple-600 rounded-xl flex items-center justify-center shadow-sm shadow-violet-500/30 text-white font-extrabold flex-shrink-0">
                            T{{ $moy->trimestre->numero ?? '?' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-display text-sm font-extrabold text-gray-900">{{ $moy->trimestre->libelle ?? 'Trimestre' }}</p>
                            <p class="text-[11px] text-gray-500 mt-0.5">Rang : <span class="font-bold text-gray-700">{{ $moy->rang }}<sup>e</sup></span> sur {{ $moy->effectif_classe ?? '?' }} · @if($moy->mention) <span class="font-bold text-violet-700">{{ $moy->mention }}</span> @endif</p>
                        </div>
                        <div class="text-right">
                            <p class="font-display text-2xl font-extrabold {{ $moy->moyenne_generale >= 14 ? 'text-brand-600' : ($moy->moyenne_generale >= 10 ? 'text-blue-600' : 'text-red-600') }}">
                                {{ number_format($moy->moyenne_generale, 2) }}<span class="text-sm text-gray-400 font-medium">/20</span>
                            </p>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gradient-to-br from-violet-100 to-violet-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-violet-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10"/></svg>
                    </div>
                    <p class="font-display text-base font-bold text-gray-700">Aucune moyenne enregistrée</p>
                    <p class="text-sm text-gray-400 mt-1">Les moyennes apparaîtront après saisie des notes.</p>
                </div>
            @endif
        </div>

        <div x-show="activeTab === 'paiements'" x-cloak class="p-6">
            @if($eleve->paiements->count() > 0)
                <div class="space-y-2">
                    @foreach($eleve->paiements->sortByDesc('date_paiement') as $p)
                    @php $modeIcons = ['orange_money' => 'OM', 'mtn_money' => 'MTN', 'wave' => 'W', 'especes' => 'ESP', 'carte_bancaire' => 'CB', 'moov_money' => 'MV']; @endphp
                    <div class="flex items-center gap-4 p-3 bg-gradient-to-r from-gold-50/30 via-white to-transparent border border-gold-100/60 rounded-xl hover:shadow-card-gold transition-all">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-[10px] font-extrabold flex-shrink-0 shadow-sm
                            {{ $p->mode === 'orange_money' ? 'bg-gradient-to-br from-orange-100 to-orange-200 text-orange-700' : ($p->mode === 'mtn_money' ? 'bg-gradient-to-br from-yellow-100 to-yellow-200 text-yellow-700' : ($p->mode === 'wave' ? 'bg-gradient-to-br from-blue-100 to-blue-200 text-blue-700' : 'bg-gradient-to-br from-gray-100 to-gray-200 text-gray-600')) }}">
                            {{ $modeIcons[$p->mode] ?? '?' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-900 truncate">{{ $p->reference ?? 'Paiement' }}</p>
                            <p class="text-[11px] text-gray-500">{{ $p->date_paiement?->format('d/m/Y H:i') }} · {{ str_replace('_', ' ', $p->mode) }}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="font-display text-base font-extrabold text-brand-700">{{ number_format($p->montant, 0, ',', ' ') }}<span class="text-xs text-gray-400 font-medium"> F</span></p>
                            @if($p->statut === 'valide' || $p->statut === 'success')
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-brand-700 bg-brand-100 border border-brand-200/60 px-1.5 py-0.5 rounded-full mt-1">✓ Validé</span>
                            @elseif($p->statut === 'pending')
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-gold-700 bg-gold-100 border border-gold-200/60 px-1.5 py-0.5 rounded-full mt-1">En attente</span>
                            @else
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-gray-600 bg-gray-100 border border-gray-200/60 px-1.5 py-0.5 rounded-full mt-1">{{ $p->statut }}</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gradient-to-br from-gold-100 to-gold-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gold-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                    </div>
                    <p class="font-display text-base font-bold text-gray-700">Aucun paiement</p>
                    <a href="{{ route('paiements.create', ['eleve_id' => $eleve->id]) }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-gradient-to-r from-gold-300 to-gold-500 text-brand-900 text-[13px] font-bold rounded-xl shadow-gold-glow">
                        Enregistrer le premier paiement
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection