@extends('layouts.app')

@section('title', 'Aperçu de l\'import')
@section('page-title', 'Aperçu avant validation')
@section('page-subtitle', 'Vérifiez, corrigez et affectez les élèves à leurs classes')

@php
    $classesForJs = [];
    foreach ($classes as $c) {
        $effectif = (int) ($c->effectif ?? 0);
        $capacite = (int) ($c->capacite ?? 0);

        $classesForJs[] = [
            'id' => (int) $c->id,
            'nom' => $c->nom,
            'niveau_id' => (int) $c->niveau_id,
            'niveau_libelle' => $c->niveau->libelle ?? $c->niveau->code ?? 'Sans niveau',
            'capacite' => $capacite,
            'effectif' => $effectif,
            'label' => $c->nom . ' — ' . $effectif . '/' . $capacite,
        ];
    }

    $lignesForJs = $job->donnees_normalisees ?? [];
    $niveauIdInit = $job->niveau_id ? (string) $job->niveau_id : '';
    $classeCibleInit = $job->classe_cible_id ? (string) $job->classe_cible_id : '';
    $statsInit = [
        'valides' => (int) $job->lignes_valides,
        'erreurs' => (int) $job->lignes_erreur,
    ];
@endphp

@section('content')
<div x-data="previewApp()" x-init="init()">

    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('eleves.import.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour aux imports
        </a>
        <form method="POST" action="{{ route('eleves.import.annuler', $job) }}" onsubmit="return confirm('Annuler cet import ? Aucune donnée ne sera enregistrée.');">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-red-200 text-red-600 text-[12px] font-bold rounded-lg hover:bg-red-50 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                Annuler l'import
            </button>
        </form>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="relative overflow-hidden bg-gradient-to-br from-white to-brand-50/50 border border-brand-100/60 rounded-xl p-4 shadow-card-brand">
            <div class="absolute -top-4 -right-4 w-16 h-16 bg-brand-200/30 rounded-full blur-xl"></div>
            <div class="relative">
                <p class="font-display text-2xl font-extrabold text-brand-600 leading-none" x-text="stats.valides"></p>
                <p class="text-[11px] text-gray-500 font-medium mt-1">Lignes valides</p>
            </div>
        </div>
        <div class="relative overflow-hidden bg-gradient-to-br from-white to-red-50/50 border border-red-100/60 rounded-xl p-4 shadow-[0_8px_24px_-8px_rgba(239,68,68,0.18)]">
            <div class="absolute -top-4 -right-4 w-16 h-16 bg-red-200/30 rounded-full blur-xl"></div>
            <div class="relative">
                <p class="font-display text-2xl font-extrabold text-red-600 leading-none" x-text="stats.erreurs"></p>
                <p class="text-[11px] text-gray-500 font-medium mt-1">Lignes en erreur</p>
            </div>
        </div>
        <div class="relative overflow-hidden bg-gradient-to-br from-white to-gold-50/50 border border-gold-200/60 rounded-xl p-4 shadow-card-gold">
            <div class="absolute -top-4 -right-4 w-16 h-16 bg-gold-200/30 rounded-full blur-xl"></div>
            <div class="relative">
                <p class="font-display text-2xl font-extrabold text-gold-700 leading-none" x-text="classesUtilisees"></p>
                <p class="text-[11px] text-gray-500 font-medium mt-1">Classe(s) utilisée(s)</p>
            </div>
        </div>
        <div class="relative overflow-hidden bg-gradient-to-br from-white to-violet-50/50 border border-violet-100/60 rounded-xl p-4 shadow-card-violet">
            <div class="absolute -top-4 -right-4 w-16 h-16 bg-violet-200/30 rounded-full blur-xl"></div>
            <div class="relative">
                <p class="font-display text-2xl font-extrabold text-violet-600 leading-none" x-text="lignesSansClasse"></p>
                <p class="text-[11px] text-gray-500 font-medium mt-1">Sans classe (pré-inscrits)</p>
            </div>
        </div>
    </div>

    <div class="relative overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-brand-800 rounded-2xl shadow-brand-glow mb-6 p-5">
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-gold-400/20 rounded-full blur-3xl"></div>
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-gold-300 to-gold-500"></div>

        <div class="relative">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-[10px] text-brand-100 font-bold uppercase tracking-[0.15em]">Destination</p>
                    <h3 class="font-display text-lg font-extrabold text-white mt-1">Niveau & classes des élèves</h3>
                </div>
                <button type="button" @click="ouvrirModalCreerClasse(false)"
                        class="inline-flex items-center gap-2 px-3 py-2 bg-gold-400 hover:bg-gold-500 text-brand-900 text-[12px] font-bold rounded-lg shadow-sm transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Créer une classe
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-brand-100 uppercase tracking-wider mb-1.5">Niveau des élèves</label>
                    <select x-model="niveauId" @change="onNiveauChange()"
                            class="w-full px-3 py-2.5 bg-white/90 backdrop-blur border border-white/30 rounded-xl text-sm font-bold text-gray-900 focus:bg-white focus:border-white focus:ring-2 focus:ring-white/30 outline-none shadow-sm cursor-pointer">
                        <option value="">Tous niveaux</option>
                        @foreach($niveaux as $niveau)
                            <option value="{{ $niveau->id }}">{{ $niveau->libelle ?? $niveau->code }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-brand-100 uppercase tracking-wider mb-1.5">
                        Classe par défaut (tous les élèves)
                    </label>
                    <select x-model="classeDefaut" @change="onClasseDefautChange()"
                            class="w-full px-3 py-2.5 bg-white/90 backdrop-blur border border-white/30 rounded-xl text-sm font-bold text-gray-900 focus:bg-white focus:border-white focus:ring-2 focus:ring-white/30 outline-none shadow-sm cursor-pointer">
                        <option value="">— Choisir classe par classe —</option>
                        <template x-for="(classesNiveau, niveauNom) in classesFiltrees" :key="niveauNom">
                            <optgroup :label="niveauNom">
                                <template x-for="c in classesNiveau" :key="c.id">
                                    <option :value="c.id" x-text="c.label"></option>
                                </template>
                            </optgroup>
                        </template>
                    </select>
                </div>
            </div>
            <p class="text-[11px] text-brand-100 mt-2">
                💡 Astuce : choisissez un niveau pour filtrer les classes. Vous pouvez aussi créer une classe à la volée.
            </p>
        </div>
    </div>

    @if(!empty($job->erreurs))
    <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-red-50/40 rounded-2xl border border-red-200 shadow-[0_8px_24px_-8px_rgba(239,68,68,0.15)] mb-6" x-data="{ open: true }">
        <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-5 py-4 border-b border-red-100 bg-gradient-to-r from-red-50 to-transparent">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-gradient-to-br from-red-400 to-red-600 rounded-lg flex items-center justify-center shadow-sm">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div class="text-left">
                    <p class="font-display text-sm font-extrabold text-red-800">{{ count($job->erreurs) }} ligne(s) en erreur</p>
                    <p class="text-[11px] text-red-600 mt-0.5">Ces lignes ne seront pas importées.</p>
                </div>
            </div>
            <svg class="w-4 h-4 text-red-600 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-cloak class="max-h-[200px] overflow-y-auto divide-y divide-red-50">
            @foreach($job->erreurs as $erreur)
            <div class="px-5 py-2.5 flex items-start gap-3">
                <span class="text-[10px] font-extrabold text-white bg-red-500 px-2 py-0.5 rounded-full flex-shrink-0 mt-0.5">L.{{ $erreur['ligne'] ?? '?' }}</span>
                <p class="text-[12px] font-medium text-red-800">{{ $erreur['message'] ?? 'Erreur inconnue' }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div x-show="nbSelectionnes > 0" x-cloak x-transition
         class="relative overflow-hidden bg-gradient-to-r from-gold-50 via-white to-gold-50/40 border border-gold-200 rounded-xl p-3 mb-3 shadow-card-gold">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gold-500 text-white text-[11px] font-extrabold rounded-full shadow-sm">
                    <span x-text="nbSelectionnes"></span> sélectionné(s)
                </span>
                <button type="button" @click="toutDeselectionner()" class="text-[11px] text-gray-500 hover:text-gray-700 underline">Tout désélectionner</button>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <label class="text-[11px] font-bold text-gold-800">Affecter à :</label>
                <select x-model="classeGroupee"
                        class="px-3 py-1.5 bg-white border border-gold-300 rounded-lg text-[12px] font-bold focus:border-gold-500 focus:ring-1 focus:ring-gold-200 outline-none">
                    <option value="">— Aucune classe —</option>
                    <template x-for="(classesNiveau, niveauNom) in classesFiltrees" :key="niveauNom">
                        <optgroup :label="niveauNom">
                            <template x-for="c in classesNiveau" :key="c.id">
                                <option :value="c.id" x-text="c.label"></option>
                            </template>
                        </optgroup>
                    </template>
                </select>
                <button type="button" @click="appliquerClasseASelection()"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-gradient-to-r from-gold-400 to-gold-600 text-white text-[11px] font-bold rounded-lg shadow-sm hover:shadow-md transition-all">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Appliquer
                </button>
                <button type="button" @click="ouvrirModalCreerClasse(true)"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-violet-300 text-violet-700 text-[11px] font-bold rounded-lg hover:bg-violet-50 transition-all">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Créer classe
                </button>
            </div>
        </div>
    </div>

    <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/20 rounded-2xl border border-brand-100/60 shadow-card-brand mb-6">
        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-100/60 bg-gradient-to-r from-brand-50/60 via-white to-gold-50/30">
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" :checked="toutSelectionne" @change="toggleTout()"
                           class="w-4 h-4 rounded border-brand-300 text-brand-600 focus:ring-brand-200">
                    <span class="text-[11px] font-bold text-gray-700">Tout</span>
                </label>
                <span class="inline-flex items-center text-[11px] font-bold text-brand-700 bg-brand-100 border border-brand-200/60 px-2.5 py-1 rounded-full" x-text="lignes.length + ' ligne(s)'"></span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="sauvegarderModifications()" x-show="modifie" x-cloak
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-gradient-to-r from-gold-400 to-gold-600 text-white text-[11px] font-bold rounded-lg shadow-gold-glow">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Sauvegarder
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gradient-to-r from-brand-50/40 to-transparent border-b border-brand-100/60">
                        <th class="px-3 py-3 w-10"></th>
                        <th class="px-3 py-3 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em] w-12">#</th>
                        <th class="px-3 py-3 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Matricule DESPS</th>
                        <th class="px-3 py-3 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Nom</th>
                        <th class="px-3 py-3 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Prénom(s)</th>
                        <th class="px-3 py-3 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em] w-20">Sexe</th>
                        <th class="px-3 py-3 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Date naiss.</th>
                        <th class="px-3 py-3 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em] w-40">Statut élève</th>
                        <th class="px-3 py-3 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em] w-56">Classe affectée</th>
                        <th class="px-3 py-3 text-center text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em] w-16"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-50/60">
                    <template x-for="(ligne, idx) in lignes" :key="idx">
                        <tr class="hover:bg-brand-50/30 transition-colors" :class="ligne._selectionne ? 'bg-gold-50/30' : ''">
                            <td class="px-3 py-2">
                                <input type="checkbox" x-model="ligne._selectionne"
                                       class="w-4 h-4 rounded border-brand-300 text-brand-600 focus:ring-brand-200">
                            </td>
                            <td class="px-3 py-2 text-[11px] font-bold text-gray-500" x-text="idx + 1"></td>
                            <td class="px-3 py-2">
                                <input type="text" x-model="ligne.matricule_desps" @input="modifie = true" maxlength="10"
                                       class="w-full px-2 py-1 bg-transparent border border-transparent rounded-md text-[12px] font-mono focus:bg-white focus:border-brand-300 focus:ring-1 focus:ring-brand-200 outline-none">
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" x-model="ligne.nom" @input="modifie = true"
                                       class="w-full px-2 py-1 bg-transparent border border-transparent rounded-md text-[12px] font-bold focus:bg-white focus:border-brand-300 focus:ring-1 focus:ring-brand-200 outline-none uppercase">
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" x-model="ligne.prenom" @input="modifie = true"
                                       class="w-full px-2 py-1 bg-transparent border border-transparent rounded-md text-[12px] focus:bg-white focus:border-brand-300 focus:ring-1 focus:ring-brand-200 outline-none">
                            </td>
                            <td class="px-3 py-2">
                                <select x-model="ligne.sexe" @change="modifie = true"
                                        class="w-full px-2 py-1 bg-transparent border border-transparent rounded-md text-[12px] font-bold focus:bg-white focus:border-brand-300 focus:ring-1 focus:ring-brand-200 outline-none cursor-pointer"
                                        :class="ligne.sexe === 'F' ? 'text-pink-600' : 'text-blue-600'">
                                    <option value="M">M</option>
                                    <option value="F">F</option>
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                <input type="date" x-model="ligne.date_naissance" @change="modifie = true"
                                       class="w-full px-2 py-1 bg-transparent border border-transparent rounded-md text-[12px] focus:bg-white focus:border-brand-300 focus:ring-1 focus:ring-brand-200 outline-none">
                            </td>
                            <td class="px-3 py-2">
                                <div class="space-y-1">
                                    <select x-model="ligne.statut_eleve" @change="modifie = true"
                                            class="w-full px-2 py-1 bg-white border border-violet-200 rounded-md text-[11px] font-bold focus:border-violet-400 focus:ring-1 focus:ring-violet-200 outline-none cursor-pointer"
                                            :class="ligne.statut_eleve === 'AFF' ? 'text-emerald-700' : (ligne.statut_eleve === 'NAFF' ? 'text-amber-700' : 'text-gray-400 italic')">
                                        <option value="">— Vide —</option>
                                        <option value="AFF">AFF</option>
                                        <option value="NAFF">NAFF</option>
                                    </select>

                                    <template x-if="ligne.raw_statut && ligne.raw_statut !== ''">
                                        <p class="text-[10px] font-medium text-gray-500">
                                            OCR : <span class="font-bold text-violet-700" x-text="ligne.raw_statut"></span>
                                        </p>
                                    </template>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <select x-model="ligne.classe_id" @change="modifie = true"
                                        class="w-full px-2 py-1 bg-white border border-gold-200 rounded-md text-[11px] font-bold focus:border-gold-400 focus:ring-1 focus:ring-gold-200 outline-none cursor-pointer"
                                        :class="!ligne.classe_id ? 'text-gray-400 italic' : 'text-gold-700'">
                                    <option value="">— Aucune (pré-inscrit) —</option>
                                    <template x-for="(classesNiveau, niveauNom) in classesFiltrees" :key="niveauNom">
                                        <optgroup :label="niveauNom">
                                            <template x-for="c in classesNiveau" :key="c.id">
                                                <option :value="c.id" x-text="c.label"></option>
                                            </template>
                                        </optgroup>
                                    </template>
                                </select>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <button type="button" @click="supprimer(idx)" class="p-1 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="lignes.length === 0">
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-gray-400 text-sm">
                                Aucune ligne valide. Revenez en arrière.
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex items-center justify-between gap-3">
        <div class="text-[12px] text-gray-500">
            <p><span class="font-bold text-gray-700" x-text="lignes.length"></span> élève(s) seront créés.</p>
            <p class="mt-0.5">
                <span x-show="lignesAvecClasse > 0"><span class="font-bold text-brand-700" x-text="lignesAvecClasse"></span> dans <span x-text="classesUtilisees"></span> classe(s) · </span>
                <span x-show="lignesSansClasse > 0"><span class="font-bold text-gold-700" x-text="lignesSansClasse"></span> en pré-inscription sans classe</span>
            </p>
        </div>

        <form method="POST" action="{{ route('eleves.import.confirmer', $job) }}" @submit="onSubmitConfirmer($event)">
            @csrf
            <button type="submit" :disabled="lignes.length === 0 || modifie"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-sm font-extrabold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span x-show="!modifie">Valider et importer <span x-text="lignes.length"></span> élève(s)</span>
                <span x-show="modifie" x-cloak>Sauvegardez d'abord vos modifications</span>
            </button>
        </form>
    </div>

    <div x-show="modalCreerClasseOuverte" x-cloak x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         @click.self="fermerModal()" @keydown.escape.window="fermerModal()">
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 border border-brand-200"
             @click.stop x-transition.scale>

            <div class="flex items-center gap-3 mb-5 pb-4 border-b border-brand-100">
                <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-xl flex items-center justify-center shadow-gold-glow">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                </div>
                <div class="flex-1">
                    <h3 class="font-display text-base font-extrabold text-gray-900">Créer une classe à la volée</h3>
                    <p class="text-xs text-gray-500 mt-0.5" x-show="modalCreerClassePourSelection">Sera appliquée aux <span class="font-bold text-gold-700" x-text="nbSelectionnes"></span> élève(s) sélectionné(s)</p>
                    <p class="text-xs text-gray-500 mt-0.5" x-show="!modalCreerClassePourSelection">La classe sera ajoutée à la liste et disponible pour affectation</p>
                </div>
                <button @click="fermerModal()" class="p-1 text-gray-400 hover:text-red-500 rounded">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div x-show="erreurCreation" x-cloak class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                <p class="text-[12px] font-medium text-red-700" x-text="erreurCreation"></p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Niveau <span class="text-red-500">*</span></label>
                    <select x-model="nouvelleClasse.niveau_id"
                            class="w-full px-3 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                        <option value="">Sélectionner un niveau...</option>
                        @foreach($niveaux as $n)
                            <option value="{{ $n->id }}">{{ $n->libelle ?? $n->code }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nom de la classe <span class="text-red-500">*</span></label>
                    <input type="text" x-model="nouvelleClasse.nom" placeholder="ex : 6ème 1, CM2 A, Tle A2..."
                           class="w-full px-3 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Capacité maximale</label>
                    <input type="number" x-model.number="nouvelleClasse.capacite" min="1" max="200" placeholder="30"
                           class="w-full px-3 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm">
                    <p class="text-[10px] text-gray-400 mt-1">Nombre maximum d'élèves (défaut : 30)</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 mt-6 pt-4 border-t border-gray-100">
                <button type="button" @click="fermerModal()"
                        class="px-4 py-2 bg-white border border-gray-200 text-gray-700 text-[12px] font-bold rounded-lg hover:bg-gray-50 transition-all">
                    Annuler
                </button>
                <button type="button" @click="creerClasse()" :disabled="creationEnCours"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-brand-500 to-brand-700 text-white text-[12px] font-extrabold rounded-lg shadow-brand-glow disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                    <svg x-show="!creationEnCours" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    <svg x-show="creationEnCours" x-cloak class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span x-text="creationEnCours ? 'Création...' : 'Créer la classe'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function previewApp() {
    return {
        lignes: @js($lignesForJs),
        classes: @js($classesForJs),
        niveauId: @js($niveauIdInit),
        classeDefaut: @js($classeCibleInit),
        stats: @js($statsInit),

        modifie: false,
        classeGroupee: '',

        modalCreerClasseOuverte: false,
        modalCreerClassePourSelection: false,
        nouvelleClasse: { nom: '', niveau_id: '', capacite: 30 },
        creationEnCours: false,
        erreurCreation: null,

        init() {
            window.previewAppState = this;

            this.classes = (this.classes || []).map(c => this.normaliserClasse(c));
            this.niveauId = this.niveauId ? String(this.niveauId) : '';
            this.classeDefaut = this.classeDefaut ? String(this.classeDefaut) : '';

            this.lignes.forEach(l => {
                if (l.date_naissance && typeof l.date_naissance === 'string' && l.date_naissance.includes('T')) {
                    l.date_naissance = l.date_naissance.split('T')[0];
                }

                if (l._selectionne === undefined) {
                    l._selectionne = false;
                }

                if (l.classe_id === undefined || l.classe_id === null || l.classe_id === '') {
                    l.classe_id = this.classeDefaut || '';
                } else {
                    l.classe_id = String(l.classe_id);
                }

                if (l.raw_statut === undefined || l.raw_statut === null) {
                    l.raw_statut = '';
                }

                if (l.statut_eleve === undefined || l.statut_eleve === null) {
                    l.statut_eleve = '';
                } else {
                    l.statut_eleve = this.normaliserStatut(l.statut_eleve);
                }
            });
        },

        normaliserClasse(c) {
            const effectif = parseInt(c.effectif || 0, 10);
            const capacite = parseInt(c.capacite || 0, 10);

            return {
                id: String(c.id),
                nom: c.nom,
                niveau_id: c.niveau_id !== null && c.niveau_id !== undefined ? String(c.niveau_id) : '',
                niveau_libelle: c.niveau_libelle || 'Sans niveau',
                capacite: capacite,
                effectif: effectif,
                label: c.label || (c.nom + ' — ' + effectif + '/' + capacite),
            };
        },

        normaliserStatut(value) {
            const v = String(value || '').trim().toUpperCase();
            return ['AFF', 'NAFF'].includes(v) ? v : '';
        },

        get classesFiltrees() {
            let liste = this.classes;

            if (this.niveauId) {
                liste = liste.filter(c => String(c.niveau_id) === String(this.niveauId));
            }

            const groupes = {};
            liste.forEach(c => {
                const niv = c.niveau_libelle || 'Sans niveau';
                if (!groupes[niv]) groupes[niv] = [];
                groupes[niv].push(c);
            });

            return groupes;
        },

        get nbSelectionnes() {
            return this.lignes.filter(l => l._selectionne).length;
        },

        get toutSelectionne() {
            return this.lignes.length > 0 && this.lignes.every(l => l._selectionne);
        },

        get lignesAvecClasse() {
            return this.lignes.filter(l => l.classe_id && l.classe_id !== '').length;
        },

        get lignesSansClasse() {
            return this.lignes.filter(l => !l.classe_id || l.classe_id === '').length;
        },

        get classesUtilisees() {
            const ids = new Set();
            this.lignes.forEach(l => {
                if (l.classe_id && l.classe_id !== '') {
                    ids.add(String(l.classe_id));
                }
            });
            return ids.size;
        },

        onNiveauChange() {
            this.modifie = true;
            this.nouvelleClasse.niveau_id = this.niveauId || '';

            if (this.classeDefaut && !this.classeAppartientAuNiveau(this.classeDefaut, this.niveauId)) {
                this.classeDefaut = '';
            }

            if (this.classeGroupee && !this.classeAppartientAuNiveau(this.classeGroupee, this.niveauId)) {
                this.classeGroupee = '';
            }
        },

        onClasseDefautChange() {
            this.appliquerClasseATousSansClasse();
        },

        classeAppartientAuNiveau(classeId, niveauId) {
            if (!classeId) return false;
            if (!niveauId) return true;

            const classe = this.classes.find(c => String(c.id) === String(classeId));
            if (!classe) return false;

            return String(classe.niveau_id) === String(niveauId);
        },

        toggleTout() {
            const nouvelEtat = !this.toutSelectionne;
            this.lignes.forEach(l => l._selectionne = nouvelEtat);
        },

        toutDeselectionner() {
            this.lignes.forEach(l => l._selectionne = false);
        },

        appliquerClasseASelection() {
            const selected = this.lignes.filter(l => l._selectionne);

            if (selected.length === 0) {
                alert('Aucune ligne sélectionnée.');
                return;
            }

            selected.forEach(l => {
                l.classe_id = this.classeGroupee || '';
            });

            this.modifie = true;
        },

        appliquerClasseATousSansClasse() {
            if (!this.classeDefaut) {
                this.modifie = true;
                return;
            }

            this.lignes.forEach(l => {
                if (!l.classe_id || l.classe_id === '') {
                    l.classe_id = this.classeDefaut;
                }
            });

            this.modifie = true;
        },

        supprimer(idx) {
            if (confirm('Retirer cette ligne de l\'import ?')) {
                this.lignes.splice(idx, 1);
                this.modifie = true;
                this.stats.valides = this.lignes.length;
            }
        },

        ouvrirModalCreerClasse(pourSelection) {
            this.modalCreerClassePourSelection = !!pourSelection;
            this.nouvelleClasse = {
                nom: '',
                niveau_id: this.niveauId || '',
                capacite: 30,
            };
            this.erreurCreation = null;
            this.modalCreerClasseOuverte = true;
        },

        fermerModal() {
            this.modalCreerClasseOuverte = false;
            this.modalCreerClassePourSelection = false;
            this.erreurCreation = null;
            this.creationEnCours = false;
        },

        async creerClasse() {
            this.erreurCreation = null;

            if (!this.nouvelleClasse.niveau_id) {
                this.erreurCreation = 'Le niveau est obligatoire.';
                return;
            }

            if (!this.nouvelleClasse.nom || this.nouvelleClasse.nom.trim() === '') {
                this.erreurCreation = 'Le nom de la classe est obligatoire.';
                return;
            }

            this.creationEnCours = true;

            try {
                const response = await fetch('{{ route('classes.quickCreate') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                    },
                    body: JSON.stringify(this.nouvelleClasse),
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    this.erreurCreation = data.message || 'Erreur lors de la création.';
                    this.creationEnCours = false;
                    return;
                }

                const nouvelleClasseData = this.normaliserClasse(data.classe);
                this.classes.push(nouvelleClasseData);

                if (this.modalCreerClassePourSelection) {
                    const selected = this.lignes.filter(l => l._selectionne);
                    selected.forEach(l => {
                        l.classe_id = nouvelleClasseData.id;
                    });
                    this.modifie = true;
                } else {
                    this.classeDefaut = nouvelleClasseData.id;
                    this.appliquerClasseATousSansClasse();
                }

                this.creationEnCours = false;
                this.fermerModal();
            } catch (err) {
                this.erreurCreation = 'Erreur réseau : ' + err.message;
                this.creationEnCours = false;
            }
        },

        async sauvegarderModifications() {
            try {
                const payload = {
                    donnees: this.lignes.map(l => ({
                        ...l,
                        statut_eleve: this.normaliserStatut(l.statut_eleve),
                        _selectionne: undefined,
                    })),
                    niveau_id: this.niveauId || null,
                    classe_cible_id: this.classeDefaut || null,
                };

                const response = await fetch('{{ route('eleves.import.preview.update', $job) }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (data && data.success) {
                    this.modifie = false;
                    if (data.stats) {
                        this.stats = data.stats;
                    }
                } else {
                    alert(data.message || 'Erreur lors de la sauvegarde.');
                }
            } catch (e) {
                alert('Erreur réseau lors de la sauvegarde. Réessayez.');
            }
        },

        async sauvegarderDestination() {
            return;
        },

        onSubmitConfirmer(event) {
            if (this.modifie) {
                event.preventDefault();
                alert('Vous avez des modifications non sauvegardées. Cliquez d\'abord sur « Sauvegarder ».');
                return false;
            }

            if (!confirm('Confirmer la création de ces élèves ? Cette action est définitive.')) {
                event.preventDefault();
                return false;
            }

            return true;
        },

        getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.getAttribute('content')) {
                return meta.getAttribute('content');
            }

            const input = document.querySelector('input[name="_token"]');
            return input ? input.value : null;
        },
    };
}
</script>
@endpush