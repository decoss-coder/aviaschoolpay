@extends('layouts.app')
@section('title', 'Grille des moyennes')

@section('content')
<div class="max-w-[1400px] mx-auto px-4 py-6 space-y-4"
     x-data="bulletinModal({{ Js::from([
         'classeId'   => $classe?->id,
         'trimestres' => $trimestres->map(fn($t) => ['id' => $t->id, 'libelle' => $t->libelle, 'numero' => $t->numero])->values()->all(),
         'eleves'     => $eleves->map(fn($e) => [
             'id'        => $e->id,
             'nom'       => $e->nom,
             'prenom'    => $e->prenom,
             'matricule' => $e->matricule_desps ?: $e->matricule_interne,
         ])->values()->all(),
     ]) }})">

    {{-- ══════════════════════════════════════════════
         OVERLAY + DRAWER (fixed, no x-teleport)
    ══════════════════════════════════════════════ --}}

    {{-- Backdrop --}}
    <div x-show="ouvert"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="ouvert = false"
         class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm"
         style="display:none;"></div>

    {{-- Drawer --}}
    <div x-show="ouvert"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         @click.stop
         class="fixed right-0 top-0 bottom-0 z-50 w-full max-w-lg bg-white shadow-2xl flex flex-col"
         style="display:none;">

        {{-- ── Header ── --}}
        <div class="bg-gradient-to-r from-brand-700 via-brand-600 to-blue-600 px-6 py-5 flex-shrink-0">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-blue-200 text-[11px] uppercase tracking-widest font-bold">Impression</p>
                    <h2 class="text-white font-display text-xl font-extrabold mt-0.5">Générer les bulletins</h2>
                    <p class="text-blue-200 text-xs mt-1" x-text="classeLabel"></p>
                </div>
                <button @click="ouvert = false"
                        class="text-blue-200 hover:text-white mt-1 p-1 rounded-lg hover:bg-white/10 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Pill : nb élèves sélectionnés --}}
            <div class="mt-4 flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 bg-white/15 text-white text-xs font-bold px-3 py-1.5 rounded-full">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span x-text="selectionnes.length + ' élève(s) sélectionné(s)'"></span>
                </span>
                <span class="inline-flex items-center gap-1.5 bg-white/15 text-white text-xs font-bold px-3 py-1.5 rounded-full"
                      x-show="trimestreId">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span x-text="trimestreLabel"></span>
                </span>
            </div>
        </div>

        {{-- ── Corps (scrollable) ── --}}
        <div class="flex-1 overflow-y-auto">

            {{-- Erreur --}}
            <div x-show="erreur" class="mx-6 mt-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm font-medium" x-text="erreur"></div>

            <div class="px-6 py-5 space-y-6">

                {{-- ── Section 1 : Période ── --}}
                <div>
                    <label class="block text-[11px] uppercase tracking-widest font-extrabold text-gray-500 mb-3">
                        Période
                    </label>
                    <div class="grid gap-2" :class="trimestres.length <= 3 ? 'grid-cols-3' : 'grid-cols-2'">
                        <template x-for="t in trimestres" :key="t.id">
                            <button type="button"
                                    @click="trimestreId = t.id"
                                    :class="trimestreId === t.id
                                        ? 'border-brand-500 bg-brand-50 text-brand-700 ring-2 ring-brand-200'
                                        : 'border-gray-200 bg-white text-gray-700 hover:border-brand-300 hover:bg-brand-50/50'"
                                    class="flex flex-col items-center justify-center px-3 py-3 rounded-xl border-2 transition-all cursor-pointer">
                                <span class="text-[10px] font-bold uppercase tracking-widest opacity-60" x-text="'Période ' + t.numero"></span>
                                <span class="text-sm font-extrabold mt-0.5" x-text="t.libelle"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- ── Section 2 : Disposition ── --}}
                <div>
                    <label class="block text-[11px] uppercase tracking-widest font-extrabold text-gray-500 mb-3">
                        Disposition sur A4
                    </label>
                    <div class="grid grid-cols-3 gap-3">

                        {{-- 1/page --}}
                        <button type="button" @click="disposition = 1"
                                :class="disposition === 1 ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-200' : 'border-gray-200 bg-white hover:border-brand-300'"
                                class="flex flex-col items-center gap-2 px-3 py-4 rounded-xl border-2 transition-all cursor-pointer">
                            <div class="w-12 h-16 border-2 rounded-sm flex flex-col p-1 gap-0.5"
                                 :class="disposition === 1 ? 'border-brand-400 bg-brand-100/40' : 'border-gray-300 bg-gray-50'">
                                <div class="flex-1 rounded-sm"
                                     :class="disposition === 1 ? 'bg-brand-300' : 'bg-gray-300'"></div>
                            </div>
                            <span class="text-xs font-extrabold"
                                  :class="disposition === 1 ? 'text-brand-700' : 'text-gray-600'">1 / page</span>
                            <span class="text-[10px] text-gray-400 -mt-1">Détail complet</span>
                        </button>

                        {{-- 2/page --}}
                        <button type="button" @click="disposition = 2"
                                :class="disposition === 2 ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-200' : 'border-gray-200 bg-white hover:border-brand-300'"
                                class="flex flex-col items-center gap-2 px-3 py-4 rounded-xl border-2 transition-all cursor-pointer">
                            <div class="w-12 h-16 border-2 rounded-sm flex flex-col p-1 gap-1"
                                 :class="disposition === 2 ? 'border-brand-400 bg-brand-100/40' : 'border-gray-300 bg-gray-50'">
                                <div class="flex-1 rounded-sm"
                                     :class="disposition === 2 ? 'bg-brand-300' : 'bg-gray-300'"></div>
                                <div class="border-t border-dashed"
                                     :class="disposition === 2 ? 'border-brand-300' : 'border-gray-300'"></div>
                                <div class="flex-1 rounded-sm"
                                     :class="disposition === 2 ? 'bg-brand-300' : 'bg-gray-300'"></div>
                            </div>
                            <span class="text-xs font-extrabold"
                                  :class="disposition === 2 ? 'text-brand-700' : 'text-gray-600'">2 / page</span>
                            <span class="text-[10px] text-gray-400 -mt-1">Moyen format</span>
                        </button>

                        {{-- 4/page --}}
                        <button type="button" @click="disposition = 4"
                                :class="disposition === 4 ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-200' : 'border-gray-200 bg-white hover:border-brand-300'"
                                class="flex flex-col items-center gap-2 px-3 py-4 rounded-xl border-2 transition-all cursor-pointer">
                            <div class="w-12 h-16 border-2 rounded-sm grid grid-cols-2 gap-0.5 p-1"
                                 :class="disposition === 4 ? 'border-brand-400 bg-brand-100/40' : 'border-gray-300 bg-gray-50'">
                                <div class="rounded-sm" :class="disposition === 4 ? 'bg-brand-300' : 'bg-gray-300'"></div>
                                <div class="rounded-sm" :class="disposition === 4 ? 'bg-brand-300' : 'bg-gray-300'"></div>
                                <div class="rounded-sm" :class="disposition === 4 ? 'bg-brand-300' : 'bg-gray-300'"></div>
                                <div class="rounded-sm" :class="disposition === 4 ? 'bg-brand-300' : 'bg-gray-300'"></div>
                            </div>
                            <span class="text-xs font-extrabold"
                                  :class="disposition === 4 ? 'text-brand-700' : 'text-gray-600'">4 / page</span>
                            <span class="text-[10px] text-gray-400 -mt-1">Condensé</span>
                        </button>
                    </div>

                    {{-- Info disposition --}}
                    <p class="mt-2 text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2">
                        <span x-show="disposition === 1">Bulletin complet sur A4 : informations, tableau des matières, mention, signatures.</span>
                        <span x-show="disposition === 2">2 bulletins empilés sur A4 : format moyen, code matière, résumé et mention.</span>
                        <span x-show="disposition === 4">4 bulletins en grille 2×2 : format condensé, l'essentiel pour une vue rapide.</span>
                    </p>
                </div>

                {{-- ── Section 3 : Sélection des élèves ── --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="text-[11px] uppercase tracking-widest font-extrabold text-gray-500">
                            Élèves
                        </label>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-400" x-text="selectionnes.length + ' / ' + eleves.length"></span>
                            <button type="button" @click="toggleTous()"
                                    class="text-xs font-bold text-brand-600 hover:text-brand-800 underline underline-offset-2">
                                <span x-text="tousSelectionnes ? 'Tout désélectionner' : 'Tout sélectionner'"></span>
                            </button>
                        </div>
                    </div>

                    {{-- Recherche --}}
                    <div class="relative mb-2">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" x-model="recherche"
                               placeholder="Filtrer par nom ou matricule…"
                               class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-300 focus:border-brand-400 outline-none">
                    </div>

                    {{-- Liste --}}
                    <div class="border border-gray-200 rounded-xl overflow-hidden max-h-64 overflow-y-auto">
                        <template x-for="e in elevesFiltres" :key="e.id">
                            <label :class="selectionnes.includes(e.id) ? 'bg-brand-50' : 'bg-white hover:bg-gray-50'"
                                   class="flex items-center gap-3 px-4 py-2.5 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors">
                                <input type="checkbox"
                                       :value="e.id"
                                       x-model="selectionnes"
                                       class="w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-300">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate">
                                        <span class="uppercase" x-text="e.nom"></span>
                                        <span x-text="' ' + e.prenom"></span>
                                    </p>
                                    <p class="text-[11px] font-mono text-gray-400" x-text="e.matricule"></p>
                                </div>
                                <svg x-show="selectionnes.includes(e.id)"
                                     class="w-4 h-4 text-brand-500 flex-shrink-0"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </label>
                        </template>
                        <div x-show="elevesFiltres.length === 0"
                             class="px-4 py-6 text-center text-sm text-gray-400">
                            Aucun élève ne correspond à la recherche.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Pied (actions) ── --}}
        <div class="flex-shrink-0 border-t border-gray-100 bg-gray-50/80 px-6 py-4">

            {{-- Récap --}}
            <div class="flex items-center gap-4 mb-4 text-xs text-gray-600 bg-white rounded-xl px-4 py-3 border border-gray-100">
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>
                        <b x-text="nbPages"></b> page(s) A4
                    </span>
                </div>
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span><b x-text="selectionnes.length"></b> bulletin(s)</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z"/>
                    </svg>
                    <span><b x-text="disposition + '/page'"></b></span>
                </div>
            </div>

            {{-- Formulaire caché --}}
            <form action="{{ route('admin.rh.bulletins.pdf-masse') }}"
                  method="POST"
                  x-ref="formPdf"
                  target="_blank">
                @csrf
                <input type="hidden" name="classe_id"    :value="classeId">
                <input type="hidden" name="trimestre_id" :value="trimestreId">
                <input type="hidden" name="disposition"  :value="disposition">
                <template x-for="id in selectionnes" :key="id">
                    <input type="hidden" name="eleve_ids[]" :value="id">
                </template>
            </form>

            <div class="flex items-center gap-3">
                <button type="button" @click="ouvert = false"
                        class="flex-1 px-4 py-3 text-sm font-bold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition">
                    Annuler
                </button>
                <button type="button" @click="soumettre()"
                        :disabled="selectionnes.length === 0 || !trimestreId"
                        :class="selectionnes.length === 0 || !trimestreId
                            ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
                            : 'bg-gradient-to-r from-brand-600 to-blue-600 hover:from-brand-700 hover:to-blue-700 text-white shadow-brand-glow'"
                        class="flex-[2] flex items-center justify-center gap-2 px-4 py-3 text-sm font-bold rounded-xl transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span x-text="selectionnes.length > 0 ? 'Générer ' + selectionnes.length + ' bulletin(s)' : 'Sélectionner des élèves'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════
         CONTENU PRINCIPAL (grille)
    ══════════════════════════════════════════════ --}}

    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('admin.rh.dashboard') }}" class="hover:text-brand-600">RH</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">Grille des moyennes</span>
    </div>

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="font-display text-2xl font-extrabold text-gray-900">Grille des moyennes</h1>
            <p class="text-sm text-gray-500 mt-1">
                Vue d'ensemble — moyennes publiées par les enseignants, par matière et par période.
            </p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <label class="text-xs font-bold text-gray-500 uppercase">Classe :</label>
            <select name="classe_id" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold">
                <option value="">— Sélectionner —</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ $classeId == $c->id ? 'selected' : '' }}>{{ $c->nom }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if(!$classe)
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6 text-center text-sm text-blue-700">
            Sélectionnez une classe pour afficher la grille des moyennes.
        </div>
    @elseif($matieres->isEmpty() || $eleves->isEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center text-sm text-amber-700">
            Pas encore de matière affectée ou d'élève dans cette classe.
        </div>
    @else

    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="font-bold text-gray-800">
                    {{ $classe->nom }} — {{ $annee?->libelle }}
                    @if($estPremierCycle)
                        <span class="ml-2 text-[10px] font-bold bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full uppercase tracking-wide">Premier cycle</span>
                    @endif
                </h2>
                <p class="text-xs text-gray-400 mt-0.5">{{ $eleves->count() }} élèves · {{ $matieres->count() }} matières · {{ $trimestres->count() }} périodes</p>
            </div>
            {{-- Bouton qui ouvre le drawer --}}
            <button type="button" @click="ouvert = true"
                    class="inline-flex items-center gap-2 bg-gradient-to-r from-brand-600 to-blue-600
                           hover:from-brand-700 hover:to-blue-700 text-white text-sm font-bold
                           px-5 py-2.5 rounded-xl shadow-brand-glow transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Générer les bulletins
            </button>
        </div>

        {{-- Table avec colonnes fixes --}}
        <div class="overflow-auto" style="max-height: calc(100vh - 280px);">
            <table class="text-xs border-collapse" style="min-width: max-content;">
                <thead>
                    {{-- ── Ligne 1 : N° / Matricule / Élève / Matières / MOY ANNUELLE ── --}}
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th rowspan="2"
                            class="sticky top-0 left-0 z-40 bg-gray-50 px-2 py-2
                                   text-[10px] font-bold text-gray-500 uppercase
                                   border-r border-b-2 border-gray-200 whitespace-nowrap"
                            style="min-width:36px;">N°</th>
                        <th rowspan="2"
                            class="sticky top-0 z-40 bg-gray-50 px-2 py-2
                                   text-[10px] font-bold text-gray-500 uppercase
                                   border-r border-b-2 border-gray-200 whitespace-nowrap"
                            style="left:36px; min-width:100px;">Matricule</th>
                        <th rowspan="2"
                            class="sticky top-0 z-40 bg-gray-50 px-3 py-2
                                   text-[10px] font-bold text-gray-500 uppercase
                                   border-r-2 border-b-2 border-gray-300 whitespace-nowrap"
                            style="left:136px; min-width:180px;">Élève</th>

                        @foreach($matieres as $m)
                            @if($estPremierCycle && $m->sousDisciplines->isNotEmpty())
                                {{-- Pour chaque sous-discipline : une cellule col-header de taille T+1 --}}
                                @foreach($m->sousDisciplines as $sd)
                                <th colspan="{{ $trimestres->count() + 1 }}"
                                    class="sticky top-0 z-20 px-2 py-2 text-center text-xs font-extrabold
                                           {{ $loop->first ? 'border-l-2 border-purple-300' : 'border-l border-purple-100' }}
                                           border-b border-purple-100 bg-purple-50 text-purple-900 whitespace-nowrap">
                                    <span class="block text-[8px] font-bold text-purple-400 uppercase tracking-wider leading-none mb-0.5">{{ $m->code }}</span>
                                    {{ $sd->code }}
                                </th>
                                @endforeach
                            @else
                            <th colspan="{{ $trimestres->count() + 1 }}"
                                class="sticky top-0 z-20 px-2 py-2 text-center text-xs font-extrabold
                                       text-blue-900 bg-blue-50 border-l border-b border-blue-100 whitespace-nowrap">
                                {{ $m->code }}
                            </th>
                            @endif
                        @endforeach

                        <th rowspan="2"
                            class="sticky top-0 z-20 px-2 py-2 text-center text-[10px] font-extrabold
                                   text-brand-800 bg-brand-100 border-l-2 border-b-2 border-brand-300 whitespace-nowrap">
                            MOY<br>ANNUELLE
                        </th>
                    </tr>

                    {{-- ── Ligne 2 : T1 / T2 / T3 / Moy. par colonne feuille ── --}}
                    <tr class="bg-gray-50 border-b-2 border-blue-200">
                        @foreach($matieres as $m)
                            @if($estPremierCycle && $m->sousDisciplines->isNotEmpty())
                                @foreach($m->sousDisciplines as $sd)
                                    @foreach($trimestres as $t)
                                    <th class="sticky z-20 px-2 py-1 text-center text-[10px] font-bold text-purple-700
                                               {{ $loop->parent->first && $loop->first ? 'border-l-2 border-purple-300' : 'border-l border-purple-100' }}
                                               bg-purple-50 whitespace-nowrap"
                                        style="top:38px;">T{{ $t->numero }}</th>
                                    @endforeach
                                    <th class="sticky z-20 px-1 py-1 text-center text-[10px] font-bold text-purple-900
                                               bg-purple-100 border-l border-purple-200 whitespace-nowrap"
                                        style="top:38px;">Moy.</th>
                                @endforeach
                            @else
                                @foreach($trimestres as $t)
                                <th class="sticky z-20 px-2 py-1 text-center text-[10px] font-bold text-blue-700
                                           bg-blue-50 border-l border-blue-100 whitespace-nowrap"
                                    style="top:38px;">T{{ $t->numero }}</th>
                                @endforeach
                                <th class="sticky z-20 px-1 py-1 text-center text-[10px] font-bold text-blue-900
                                           bg-blue-100 border-l border-blue-200 whitespace-nowrap"
                                    style="top:38px;">Moy.</th>
                            @endif
                        @endforeach
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @foreach($eleves as $i => $eleve)
                    @php $sumMatieres = 0; $cntMatieres = 0; @endphp
                    <tr class="group hover:bg-blue-50/20">
                        <td class="sticky left-0 z-10 px-2 py-1.5 text-[10px] font-mono text-gray-400 text-center
                                   bg-white group-hover:bg-blue-50/30 border-r border-gray-200 whitespace-nowrap"
                            style="min-width:36px;">{{ $i + 1 }}</td>
                        <td class="sticky z-10 px-2 py-1.5 text-[10px] font-mono font-bold text-gray-700
                                   bg-white group-hover:bg-blue-50/30 border-r border-gray-200 whitespace-nowrap"
                            style="left:36px; min-width:100px;">
                            {{ $eleve->matricule_desps ?: $eleve->matricule_interne }}
                        </td>
                        <td class="sticky z-10 px-3 py-1.5
                                   bg-white group-hover:bg-blue-50/30
                                   border-r-2 border-gray-300 whitespace-nowrap"
                            style="left:136px; min-width:180px;">
                            <span class="font-semibold text-xs text-gray-800 uppercase">{{ $eleve->nom }}</span>
                            <span class="text-xs text-gray-500"> {{ $eleve->prenom }}</span>
                        </td>

                        @foreach($matieres as $m)
                            @if($estPremierCycle && $m->sousDisciplines->isNotEmpty())
                                {{-- Sous-disciplines : affichage T1/T2/T3/Moy par sous-discipline --}}
                                @php $sumSd = 0; $totalPoidsParent = 0; @endphp
                                @foreach($m->sousDisciplines as $sd)
                                    @php $sumTrimSd = 0; $cntTrimSd = 0; @endphp
                                    @foreach($trimestres as $t)
                                        @php
                                            $key = $sd->id . '_' . $t->id;
                                            $val = $moyennes->get($key)?->get($eleve->id)?->moyenne;
                                            $colorSd = $val !== null ? ($val >= 14 ? 'green' : ($val >= 10 ? 'amber' : 'red')) : 'gray';
                                            if ($val !== null) { $sumTrimSd += $val; $cntTrimSd++; }
                                        @endphp
                                        <td class="px-2 py-1.5 text-center whitespace-nowrap
                                                   {{ $loop->parent->first && $loop->first ? 'border-l-2 border-purple-200' : 'border-l border-purple-50' }}
                                                   {{ $val !== null ? "bg-{$colorSd}-50/40" : '' }}">
                                            @if($val !== null)
                                                <span class="font-bold text-{{ $colorSd }}-700">{{ number_format($val, 2) }}</span>
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    @php
                                        $moySd = $cntTrimSd > 0 ? round($sumTrimSd / $cntTrimSd, 2) : null;
                                        if ($moySd !== null) {
                                            $colorMsd = $moySd >= 14 ? 'green' : ($moySd >= 10 ? 'amber' : 'red');
                                            $poids = (float) ($sd->poids_dans_parent ?? 1);
                                            $sumSd += $moySd * $poids;
                                            $totalPoidsParent += $poids;
                                        }
                                    @endphp
                                    <td class="px-2 py-1.5 text-center border-l border-purple-200 whitespace-nowrap
                                               {{ $moySd !== null ? 'bg-purple-100/60' : 'bg-gray-50' }}">
                                        @if($moySd !== null)
                                            <span class="font-extrabold text-{{ $colorMsd }}-700">{{ number_format($moySd, 2) }}</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                @php
                                    $moyMatiere = $totalPoidsParent > 0 ? round($sumSd / $totalPoidsParent, 2) : null;
                                    if ($moyMatiere !== null) {
                                        $sumMatieres += $moyMatiere * ($m->coefficient_defaut ?? 1);
                                        $cntMatieres += ($m->coefficient_defaut ?? 1);
                                    }
                                @endphp
                            @else
                                {{-- Matière normale --}}
                                @php $sumTrim = 0; $cntTrim = 0; @endphp
                                @foreach($trimestres as $t)
                                    @php
                                        $key = $m->id . '_' . $t->id;
                                        $val = $moyennes->get($key)?->get($eleve->id)?->moyenne;
                                        $color = $val !== null ? ($val >= 14 ? 'green' : ($val >= 10 ? 'amber' : 'red')) : 'gray';
                                        if ($val !== null) { $sumTrim += $val; $cntTrim++; }
                                    @endphp
                                    <td class="px-2 py-1.5 text-center border-l border-blue-50 whitespace-nowrap
                                               {{ $val !== null ? "bg-{$color}-50/40" : '' }}">
                                        @if($val !== null)
                                            <span class="font-bold text-{{ $color }}-700">{{ number_format($val, 2) }}</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                @php
                                    $moyMatiere = $cntTrim > 0 ? round($sumTrim / $cntTrim, 2) : null;
                                    if ($moyMatiere !== null) {
                                        $colorM = $moyMatiere >= 14 ? 'green' : ($moyMatiere >= 10 ? 'amber' : 'red');
                                        $sumMatieres += $moyMatiere * ($m->coefficient_defaut ?? 1);
                                        $cntMatieres += ($m->coefficient_defaut ?? 1);
                                    }
                                @endphp
                                <td class="px-2 py-1.5 text-center border-l border-blue-200 whitespace-nowrap
                                           {{ $moyMatiere !== null ? 'bg-blue-100/60' : 'bg-gray-50' }}">
                                    @if($moyMatiere !== null)
                                        <span class="font-extrabold text-{{ $colorM }}-700">{{ number_format($moyMatiere, 2) }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            @endif
                        @endforeach

                        @php
                            $moyAnnuelle = $cntMatieres > 0 ? round($sumMatieres / $cntMatieres, 2) : null;
                            $colorA = $moyAnnuelle !== null ? ($moyAnnuelle >= 14 ? 'green' : ($moyAnnuelle >= 10 ? 'amber' : 'red')) : 'gray';
                        @endphp
                        <td class="px-2 py-1.5 text-center bg-brand-50 border-l-2 border-brand-200 whitespace-nowrap">
                            @if($moyAnnuelle !== null)
                                <span class="font-extrabold text-{{ $colorA }}-700">{{ number_format($moyAnnuelle, 2) }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50 text-xs text-gray-500
                    flex items-center justify-between flex-wrap gap-2">
            <span>Les moyennes sont publiées par les enseignants depuis leur grille de notes.</span>
            <span class="font-mono">
                Matières : {{ $matieres->count() }} · Périodes : {{ $trimestres->count() }} · Élèves : {{ $eleves->count() }}
            </span>
        </div>
    </div>
    @endif
</div>

<script>
function bulletinModal(cfg = {}) {
    return {
        ouvert:       false,
        classeId:     cfg.classeId ?? null,
        trimestres:   cfg.trimestres ?? [],
        eleves:       cfg.eleves ?? [],
        trimestreId:  null,
        disposition:  1,
        recherche:    '',
        selectionnes: [],
        erreur:       '',

        init() {
            if (this.trimestres.length > 0) {
                this.trimestreId = this.trimestres[0].id;
            }
            this.selectionnes = this.eleves.map(e => e.id);
        },

        get elevesFiltres() {
            const q = this.recherche.toLowerCase().trim();
            if (!q) return this.eleves;
            return this.eleves.filter(e =>
                (e.nom + ' ' + e.prenom).toLowerCase().includes(q) ||
                (e.matricule || '').toLowerCase().includes(q)
            );
        },

        get tousSelectionnes() {
            return this.elevesFiltres.length > 0 &&
                   this.elevesFiltres.every(e => this.selectionnes.includes(e.id));
        },

        get nbPages() {
            return Math.ceil(this.selectionnes.length / this.disposition);
        },

        get classeLabel() {
            const t = this.trimestres.find(t => t.id === this.trimestreId);
            return this.selectionnes.length + ' élève(s) · ' + (t ? t.libelle : '—');
        },

        get trimestreLabel() {
            const t = this.trimestres.find(t => t.id === this.trimestreId);
            return t ? t.libelle : '';
        },

        toggleTous() {
            const ids = this.elevesFiltres.map(e => e.id);
            if (this.tousSelectionnes) {
                this.selectionnes = this.selectionnes.filter(id => !ids.includes(id));
            } else {
                const toAdd = ids.filter(id => !this.selectionnes.includes(id));
                this.selectionnes = [...this.selectionnes, ...toAdd];
            }
        },

        soumettre() {
            this.erreur = '';
            if (!this.trimestreId) {
                this.erreur = 'Veuillez sélectionner une période.';
                return;
            }
            if (this.selectionnes.length === 0) {
                this.erreur = 'Veuillez sélectionner au moins un élève.';
                return;
            }
            this.$refs.formPdf.submit();
        },
    };
}
</script>
@endsection
