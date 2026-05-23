@extends('layouts.app')

@section('title', 'Classes')
@section('page-title', 'Gestion des classes')
@section('page-subtitle', $stats['total_classes'] . ' classes — Année scolaire ' . ($annee->libelle ?? '2025-2026'))

@section('content')
<div x-data="{ openTarifsModal: false, modeCiblage: 'niveau' }">
    @php
        $classesPourModal = isset($classes) && $classes
            ? collect($classes)
            : collect($classesParNiveau ?? [])->flatten(1)->values();
    @endphp

    {{-- ════════════════════════════════════════════════════ --}}
    {{-- HEADER : Actions + Filtres --}}
    {{-- ════════════════════════════════════════════════════ --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3 flex-wrap">
            @editable
                <a href="{{ route('classes.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Nouvelle classe
                </a>

                <button type="button"
                        @click="openTarifsModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-gold-300 via-gold-400 to-gold-500 text-brand-900 text-[13px] font-extrabold rounded-xl shadow-gold-glow ring-1 ring-gold-200/60 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Ajuster les tarifs
                </button>
            @endeditable
        </div>

        <form method="GET" class="flex items-center gap-2 flex-wrap">
            <div class="relative flex-1 min-w-[180px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Nom de la classe..."
                       class="w-full lg:w-60 pl-10 pr-4 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-300 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm">
                <svg class="w-4 h-4 text-brand-400 absolute left-3 top-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <select name="niveau_id" onchange="this.form.submit()"
                    class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm text-gray-700 focus:border-brand-300 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                <option value="">Tous niveaux</option>
                @foreach($niveaux as $niv)
                    <option value="{{ $niv->id }}" {{ request('niveau_id') == $niv->id ? 'selected' : '' }}>
                        {{ $niv->nom }}
                    </option>
                @endforeach
            </select>

            <select name="statut" onchange="this.form.submit()"
                    class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm text-gray-700 focus:border-brand-300 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                <option value="">Tous statuts</option>
                <option value="disponible" {{ request('statut') == 'disponible' ? 'selected' : '' }}>Places disponibles</option>
                <option value="pleine" {{ request('statut') == 'pleine' ? 'selected' : '' }}>Classes pleines</option>
                <option value="vide" {{ request('statut') == 'vide' ? 'selected' : '' }}>Classes vides</option>
            </select>
        </form>
    </div>

    {{-- ════════════════════════════════════════════════════ --}}
    {{-- STATS GLOBALES --}}
    {{-- ════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="relative overflow-hidden bg-gradient-to-br from-white to-brand-50/50 border border-brand-100/60 rounded-xl p-4 shadow-card-brand">
            <div class="absolute -top-4 -right-4 w-16 h-16 bg-brand-200/30 rounded-full blur-xl"></div>
            <div class="relative flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-lg flex items-center justify-center shadow-brand-glow flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <p class="font-display text-2xl font-extrabold text-gray-900 leading-none">{{ $stats['total_classes'] }}</p>
                    <p class="text-[11px] text-gray-500 font-medium mt-1">Classes actives</p>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-blue-50/50 border border-blue-100/60 rounded-xl p-4 shadow-card-blue">
            <div class="absolute -top-4 -right-4 w-16 h-16 bg-blue-200/30 rounded-full blur-xl"></div>
            <div class="relative flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center shadow-sm shadow-blue-500/30 flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-display text-2xl font-extrabold text-gray-900 leading-none">
                        {{ $stats['total_effectif'] }}<span class="text-sm text-gray-400">/{{ $stats['total_capacite'] }}</span>
                    </p>
                    <p class="text-[11px] text-gray-500 font-medium mt-1">Élèves inscrits</p>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-gold-50/50 border border-gold-200/60 rounded-xl p-4 shadow-card-gold">
            <div class="absolute -top-4 -right-4 w-16 h-16 bg-gold-200/30 rounded-full blur-xl"></div>
            <div class="relative flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-lg flex items-center justify-center shadow-gold-glow flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <div>
                    <p class="font-display text-2xl font-extrabold text-gray-900 leading-none">{{ $stats['taux_remplissage'] }}<span class="text-sm text-gray-400">%</span></p>
                    <p class="text-[11px] text-gray-500 font-medium mt-1">Taux remplissage</p>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white {{ $stats['classes_pleines'] > 0 ? 'to-red-50/50 border-red-100/60' : 'to-brand-50/50 border-brand-100/60' }} border rounded-xl p-4 shadow-card">
            <div class="absolute -top-4 -right-4 w-16 h-16 {{ $stats['classes_pleines'] > 0 ? 'bg-red-200/30' : 'bg-brand-200/30' }} rounded-full blur-xl"></div>
            <div class="relative flex items-center gap-3">
                <div class="w-10 h-10 {{ $stats['classes_pleines'] > 0 ? 'bg-gradient-to-br from-red-400 to-red-600' : 'bg-gradient-to-br from-brand-400 to-brand-600' }} rounded-lg flex items-center justify-center shadow-sm flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-display text-2xl font-extrabold text-gray-900 leading-none">{{ $stats['classes_pleines'] }}</p>
                    <p class="text-[11px] {{ $stats['classes_pleines'] > 0 ? 'text-red-600' : 'text-gray-500' }} font-medium mt-1">Classes pleines</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════ --}}
    {{-- CLASSES PAR NIVEAU --}}
    {{-- ════════════════════════════════════════════════════ --}}
    @forelse($classesParNiveau as $niveauNom => $classesDuNiveau)
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="flex items-center gap-2 px-4 py-1.5 bg-gradient-to-r from-brand-500 to-brand-700 text-white text-[13px] font-extrabold rounded-full shadow-brand-glow">
                    <span class="w-1.5 h-1.5 bg-gold-300 rounded-full"></span>
                    {{ $niveauNom ?? 'Sans niveau' }}
                </div>
                <div class="flex-1 h-px bg-gradient-to-r from-brand-200 via-brand-100 to-transparent"></div>
                <span class="text-[11px] font-bold text-gray-400">{{ $classesDuNiveau->count() }} classe(s)</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($classesDuNiveau as $classe)
                    @php
                        $taux = $classe->capacite > 0 ? round(($classe->effectif / $classe->capacite) * 100) : 0;
                        $plein = $classe->effectif >= $classe->capacite;
                    @endphp

                    <a href="{{ route('classes.show', $classe) }}"
                       class="group relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300 p-5">
                        <div class="absolute -top-8 -right-8 w-32 h-32 bg-gradient-to-br from-brand-200/30 to-brand-300/10 rounded-full blur-2xl group-hover:scale-110 transition-transform duration-500"></div>
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-brand-400 via-brand-500 to-gold-400"></div>

                        <div class="relative">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="font-display text-xl font-extrabold text-gray-900 leading-tight">{{ $classe->nom }}</h3>
                                    @if($classe->serie)
                                        <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 bg-gold-100 border border-gold-200/60 text-gold-700 text-[10px] font-bold rounded-full">
                                            Série {{ $classe->serie->nom }}
                                        </span>
                                    @endif
                                </div>

                                @if($plein)
                                    <span class="inline-flex items-center text-[10px] font-bold text-red-700 bg-red-100 border border-red-200/60 px-2 py-0.5 rounded-full">PLEINE</span>
                                @elseif($classe->effectif === 0)
                                    <span class="inline-flex items-center text-[10px] font-bold text-gray-600 bg-gray-100 border border-gray-200/60 px-2 py-0.5 rounded-full">VIDE</span>
                                @else
                                    <span class="inline-flex items-center text-[10px] font-bold text-brand-700 bg-brand-100 border border-brand-200/60 px-2 py-0.5 rounded-full">
                                        {{ max(0, $classe->capacite - $classe->effectif) }} places
                                    </span>
                                @endif
                            </div>

                            <div class="mb-3">
                                <div class="flex items-baseline gap-1 mb-2">
                                    <p class="font-display text-3xl font-extrabold text-gray-900">{{ $classe->effectif }}</p>
                                    <p class="text-base text-gray-400 font-medium">/ {{ $classe->capacite }}</p>
                                    <p class="text-xs text-gray-500 ml-auto">élèves</p>
                                </div>

                                <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                    <div class="h-2 rounded-full {{ $plein ? 'bg-gradient-to-r from-red-400 to-red-600' : ($taux >= 80 ? 'bg-gradient-to-r from-gold-300 to-gold-500' : 'bg-gradient-to-r from-brand-400 to-brand-600') }} transition-all"
                                         style="width: {{ min($taux, 100) }}%"></div>
                                </div>
                            </div>

                            <div class="pt-3 mt-3 border-t border-brand-100/50 space-y-1.5">
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="text-gray-500 flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-brand-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Scolarité
                                    </span>
                                    <span class="font-extrabold text-brand-700">{{ number_format($classe->scolarite_annuelle ?? 0, 0, ',', ' ') }} F</span>
                                </div>

                                @if($classe->professeurPrincipal)
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-gray-500 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Prof principal
                                        </span>
                                        <span class="font-bold text-gray-700 truncate ml-2">
                                            {{ $classe->professeurPrincipal->prenom }} {{ mb_substr($classe->professeurPrincipal->nom, 0, 1) }}.
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @empty
        {{-- EMPTY STATE --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand p-12 text-center">
            <div class="absolute -top-20 -right-20 w-60 h-60 bg-gradient-to-br from-brand-200/30 to-gold-200/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-gold-200/20 rounded-full blur-2xl"></div>

            <div class="relative max-w-md mx-auto">
                <div class="w-20 h-20 bg-gradient-to-br from-brand-400 to-brand-600 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-brand-glow ring-4 ring-brand-100">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>

                <h3 class="font-display text-xl font-extrabold text-gray-900 mb-2">Aucune classe créée</h3>
                <p class="text-sm text-gray-500 mb-6">
                    Commencez par créer vos classes (6<sup>e</sup> A, 5<sup>e</sup> B, Tle D, etc.).
                </p>

                @can('create', App\Models\Classe::class)
                    <a href="{{ route('classes.create') }}"
                       class="inline-flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-sm font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Créer une première classe
                    </a>
                @endcan
            </div>
        </div>
    @endforelse

    {{-- MODAL AJUSTEMENT TARIFS --}}
    <template x-teleport="body">
    <div x-show="openTarifsModal"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[9999] bg-black/45 backdrop-blur-[2px]"
         @click.self="openTarifsModal = false"
         @keydown.escape.window="openTarifsModal = false">

        <div class="fixed inset-0 flex items-center justify-center p-4 sm:p-6">
            <div x-show="openTarifsModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                 class="w-full max-w-4xl bg-white rounded-2xl shadow-2xl border border-brand-100 max-h-[90vh] overflow-hidden">

                <div class="flex items-center justify-between px-6 py-5 border-b border-brand-100 bg-gradient-to-r from-brand-50/60 via-white to-gold-50/30">
                    <div>
                        <h3 class="font-display text-2xl font-extrabold text-gray-900">
                            Ajuster les frais des classes
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Scolarité, inscription et réinscription en masse
                        </p>
                    </div>

                    <button type="button"
                            @click="openTarifsModal = false"
                            class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="overflow-y-auto max-h-[calc(90vh-88px)]">
                    <form method="POST" action="{{ route('classes.tarifs.ajuster') }}" class="p-6 space-y-6">
                        @csrf

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-2">
                                Mode de ciblage
                            </label>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <label class="border border-brand-100 rounded-xl p-4 cursor-pointer hover:bg-brand-50 transition-colors">
                                    <input type="radio" name="mode_ciblage" value="niveau" x-model="modeCiblage" class="mr-2">
                                    <span class="font-bold text-sm text-gray-800">Par niveau</span>
                                    <p class="text-[11px] text-gray-500 mt-1">Toutes les classes d’un niveau</p>
                                </label>

                                <label class="border border-brand-100 rounded-xl p-4 cursor-pointer hover:bg-brand-50 transition-colors">
                                    <input type="radio" name="mode_ciblage" value="intervalle" x-model="modeCiblage" class="mr-2">
                                    <span class="font-bold text-sm text-gray-800">De classe à classe</span>
                                    <p class="text-[11px] text-gray-500 mt-1">Plage ordonnée de classes</p>
                                </label>

                                <label class="border border-brand-100 rounded-xl p-4 cursor-pointer hover:bg-brand-50 transition-colors">
                                    <input type="radio" name="mode_ciblage" value="selection" x-model="modeCiblage" class="mr-2">
                                    <span class="font-bold text-sm text-gray-800">Sélection manuelle</span>
                                    <p class="text-[11px] text-gray-500 mt-1">Choix libre des classes</p>
                                </label>
                            </div>
                        </div>

                        <div x-show="modeCiblage === 'niveau'" x-cloak>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Niveau</label>
                            <select name="niveau_id"
                                    class="w-full px-4 py-3 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                                <option value="">Sélectionner un niveau...</option>
                                @foreach($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}">{{ $niveau->libelle ?? $niveau->code ?? $niveau->nom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="modeCiblage === 'intervalle'" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Classe de début</label>
                                <select name="classe_debut_id"
                                        class="w-full px-4 py-3 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                                    <option value="">Sélectionner...</option>
                                    @foreach($classesPourModal as $classe)
                                        <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Classe de fin</label>
                                <select name="classe_fin_id"
                                        class="w-full px-4 py-3 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                                    <option value="">Sélectionner...</option>
                                    @foreach($classesPourModal as $classe)
                                        <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div x-show="modeCiblage === 'selection'" x-cloak>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Classes à modifier</label>
                            <div class="max-h-56 overflow-y-auto border border-brand-100 rounded-xl p-3 bg-gray-50/50 grid grid-cols-1 md:grid-cols-2 gap-2">
                                @foreach($classesPourModal as $classe)
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" name="classe_ids[]" value="{{ $classe->id }}" class="rounded border-brand-200 text-brand-600 focus:ring-brand-200">
                                        <span>{{ $classe->nom }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Scolarité annuelle</label>
                                <input type="number" name="scolarite_annuelle" min="0" step="1000" required
                                       class="w-full px-4 py-3 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none shadow-sm">
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Frais inscription</label>
                                <input type="number" name="frais_inscription" min="0" step="1000"
                                       class="w-full px-4 py-3 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none shadow-sm">
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Frais réinscription</label>
                                <input type="number" name="frais_reinscription" min="0" step="1000"
                                       class="w-full px-4 py-3 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none shadow-sm">
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-5 border-t border-brand-100">
                            <p class="text-[11px] text-gray-500">
                                Astuce : utilisez <strong>par niveau</strong> pour harmoniser rapidement tous les montants.
                            </p>

                            <div class="flex items-center gap-3">
                                <button type="button"
                                        @click="openTarifsModal = false"
                                        class="px-5 py-3 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">
                                    Annuler
                                </button>

                                <button type="submit"
                                        class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-gold-400 to-gold-500 text-brand-900 text-[13px] font-extrabold rounded-xl shadow-gold-glow ring-1 ring-gold-200/60 hover:shadow-card-hover transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Appliquer les frais
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</template>
</div>
@endsection