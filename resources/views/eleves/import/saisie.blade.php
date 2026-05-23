@extends('layouts.app')

@section('title', 'Saisie rapide')
@section('page-title', 'Saisie rapide des élèves')
@section('page-subtitle', 'Entrez vos élèves directement dans le tableau')

@section('content')
<div x-data="saisieRapide()" x-init="init()" class="max-w-7xl mx-auto">

    {{-- Retour --}}
    <div class="mb-4">
        <a href="{{ route('eleves.import.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour aux méthodes d'import
        </a>
    </div>

    @if(session('error'))
        <div class="relative overflow-hidden bg-gradient-to-r from-red-50 to-red-100/50 border border-red-200 rounded-xl p-4 mb-6">
            <p class="text-sm font-bold text-red-800">{{ session('error') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('eleves.import.saisie.submit') }}" @submit="onSubmit" x-ref="form">
        @csrf

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- SECTION 1 : CLASSE CIBLE --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/40 rounded-2xl border border-gold-200/60 shadow-card-gold p-5 mb-5">
            <div class="absolute -top-10 -left-10 w-40 h-40 bg-gold-200/20 rounded-full blur-3xl"></div>
            <div class="relative flex flex-col lg:flex-row items-start lg:items-center gap-4">
                <div class="flex items-center gap-3 flex-1">
                    <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-xl flex items-center justify-center shadow-gold-glow flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                    </div>
                    <div>
                        <h3 class="font-display text-base font-extrabold text-gray-900">Classe cible (optionnel)</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Tous les élèves saisis seront rattachés à cette classe</p>
                    </div>
                </div>
                <select name="classe_cible_id" x-model="classeCibleId"
                        class="w-full lg:w-80 px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-bold text-gray-900 focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none shadow-sm cursor-pointer">
                    <option value="">Aucune classe (à définir plus tard)</option>
                    @foreach($classes as $classe)
                        <option value="{{ $classe->id }}" {{ $classePreselect == $classe->id ? 'selected' : '' }}>
                            {{ $classe->nom }} — {{ $classe->niveau->nom ?? '' }}
                            ({{ $classe->effectif }}/{{ $classe->capacite }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- AIDE CLAVIER --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-50/50 to-white rounded-2xl border border-blue-100/60 shadow-card-blue p-4 mb-5">
            <div class="relative flex items-start gap-3">
                <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm shadow-blue-500/30">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="flex-1">
                    <p class="text-[12px] font-bold text-blue-800">Raccourcis clavier</p>
                    <div class="flex flex-wrap items-center gap-4 mt-1.5 text-[11px] text-blue-700">
                        <span><kbd class="px-1.5 py-0.5 bg-white border border-blue-200 rounded font-mono text-[10px]">Tab</kbd> Cellule suivante</span>
                        <span><kbd class="px-1.5 py-0.5 bg-white border border-blue-200 rounded font-mono text-[10px]">Entrée</kbd> Ligne suivante</span>
                        <span><kbd class="px-1.5 py-0.5 bg-white border border-blue-200 rounded font-mono text-[10px]">Ctrl+V</kbd> Coller depuis Excel</span>
                        <span><kbd class="px-1.5 py-0.5 bg-white border border-blue-200 rounded font-mono text-[10px]">Ctrl+D</kbd> Dupliquer ligne</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- BARRE D'ACTIONS TABLEAU --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
                <h3 class="font-display text-base font-extrabold text-gray-900 flex items-center gap-2">
                    <span class="w-1 h-5 bg-gradient-to-b from-brand-400 to-brand-600 rounded-full"></span>
                    Tableau de saisie
                </h3>
                <span class="inline-flex items-center text-[11px] font-bold text-brand-700 bg-brand-100 border border-brand-200/60 px-2.5 py-1 rounded-full" x-text="lignes.length + ' ligne(s)'"></span>
                <span class="inline-flex items-center text-[11px] font-bold text-brand-600" x-text="lignesValides + ' valide(s)'" x-show="lignesValides > 0"></span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="ajouterLigne()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-brand-200 text-brand-700 text-[12px] font-bold rounded-lg hover:bg-brand-50 transition-all shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Ajouter 1 ligne
                </button>
                <button type="button" @click="ajouterLignes(10)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-brand-200 text-brand-700 text-[12px] font-bold rounded-lg hover:bg-brand-50 transition-all shadow-sm">
                    + 10 lignes
                </button>
                <button type="button" @click="viderTableau()" x-show="lignes.length > 1"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-red-200 text-red-600 text-[12px] font-bold rounded-lg hover:bg-red-50 transition-all shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                    Tout effacer
                </button>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- LE TABLEAU ÉDITABLE --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden bg-white rounded-2xl border border-brand-100/60 shadow-card-brand mb-5">
            <div class="overflow-x-auto">
                <table class="w-full" @paste="onPaste($event)">
                    <thead>
                        <tr class="bg-gradient-to-r from-brand-50/60 via-white to-gold-50/30 border-b border-brand-100/60">
                            <th class="px-2 py-2.5 text-center text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em] w-10">#</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em] w-32">Matricule DESPS</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">
                                Nom et prénoms <span class="text-red-500">*</span>
                            </th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em] w-16">
                                Sexe <span class="text-red-500">*</span>
                            </th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em] w-36">Date naiss.</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em] w-20">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-50/60">
                        <template x-for="(ligne, idx) in lignes" :key="ligne._id">
                            <tr :class="idx % 2 === 0 ? 'bg-white' : 'bg-brand-50/20'" class="hover:bg-brand-50/40 transition-colors">
                                <td class="px-2 py-1 text-center text-[11px] font-bold text-gray-400" x-text="idx + 1"></td>

                                {{-- Matricule --}}
                                <td class="px-2 py-1">
                                    <input type="text" x-model="ligne.matricule"
                                           :name="'lignes[' + idx + '][matricule]'"
                                           :data-row="idx" :data-col="0"
                                           @keydown="onKeyDown($event, idx, 0)"
                                           maxlength="10"
                                           placeholder="15195226N"
                                           class="w-full px-2 py-1.5 bg-transparent border border-transparent rounded-md text-[12px] font-mono focus:bg-white focus:border-brand-300 focus:ring-1 focus:ring-brand-200 outline-none transition-all uppercase">
                                </td>

                                {{-- Nom complet --}}
                                <td class="px-2 py-1">
                                    <input type="text" x-model="ligne.nom_complet"
                                           :name="'lignes[' + idx + '][nom_complet]'"
                                           :data-row="idx" :data-col="1"
                                           @keydown="onKeyDown($event, idx, 1)"
                                           @input="validerLigne(idx)"
                                           placeholder="ATTIOUA AMOIN CHANTAL"
                                           class="w-full px-2 py-1.5 bg-transparent border border-transparent rounded-md text-[12px] font-medium focus:bg-white focus:border-brand-300 focus:ring-1 focus:ring-brand-200 outline-none transition-all uppercase">
                                </td>

                                {{-- Sexe --}}
                                <td class="px-2 py-1">
                                    <select x-model="ligne.sexe"
                                            :name="'lignes[' + idx + '][sexe]'"
                                            :data-row="idx" :data-col="2"
                                            @keydown="onKeyDown($event, idx, 2)"
                                            @change="validerLigne(idx)"
                                            class="w-full px-2 py-1.5 bg-transparent border border-transparent rounded-md text-[12px] font-bold text-center focus:bg-white focus:border-brand-300 focus:ring-1 focus:ring-brand-200 outline-none transition-all cursor-pointer"
                                            :class="ligne.sexe === 'F' ? 'text-pink-600' : (ligne.sexe === 'M' ? 'text-blue-600' : 'text-gray-400')">
                                        <option value="">?</option>
                                        <option value="M">M</option>
                                        <option value="F">F</option>
                                    </select>
                                </td>

                                {{-- Date naissance --}}
                                <td class="px-2 py-1">
                                    <input type="date" x-model="ligne.date_naissance"
                                           :name="'lignes[' + idx + '][date_naissance]'"
                                           :data-row="idx" :data-col="3"
                                           @keydown="onKeyDown($event, idx, 3)"
                                           class="w-full px-2 py-1.5 bg-transparent border border-transparent rounded-md text-[12px] focus:bg-white focus:border-brand-300 focus:ring-1 focus:ring-brand-200 outline-none transition-all">
                                </td>

                                {{-- Actions --}}
                                <td class="px-2 py-1">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" @click="dupliquerLigne(idx)" class="p-1 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors" title="Dupliquer (Ctrl+D)">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                        <button type="button" @click="supprimerLigne(idx)" class="p-1 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded transition-colors" title="Supprimer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Footer tableau --}}
            <div class="flex items-center justify-between px-4 py-3 border-t border-brand-100/60 bg-gradient-to-r from-brand-50/40 to-transparent">
                <p class="text-[11px] text-gray-500">
                    <span class="font-bold text-brand-700" x-text="lignesValides"></span> / <span x-text="lignes.length"></span> ligne(s) valide(s)
                    <span class="text-gray-400 mx-2">·</span>
                    Astuce : utilisez <kbd class="px-1 py-0.5 bg-white border border-gray-200 rounded text-[10px] font-mono">Tab</kbd> pour naviguer rapidement
                </p>
                <button type="button" @click="ajouterLigne()"
                        class="inline-flex items-center gap-1.5 text-[12px] font-bold text-brand-600 hover:text-brand-700">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Ajouter une ligne
                </button>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- ACTIONS FINALES --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-[12px] text-gray-500">
                    <span class="font-bold text-gray-700" x-text="lignesValides"></span> élève(s) prêt(s) à être importés.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('eleves.import.index') }}"
                   class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">
                    Annuler
                </a>
                <button type="submit" :disabled="lignesValides === 0"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>Valider <span x-text="lignesValides"></span> élève(s)</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function saisieRapide() {
    return {
        lignes: [],
        classeCibleId: '{{ $classePreselect ?? '' }}',
        nextId: 1,

        init() {
            // Commencer avec 5 lignes vides
            this.ajouterLignes(5);
            // Focus sur la première cellule
            this.$nextTick(() => {
                const firstInput = this.$el.querySelector('input[data-row="0"][data-col="1"]');
                if (firstInput) firstInput.focus();
            });
        },

        get lignesValides() {
            return this.lignes.filter(l => this.estValide(l)).length;
        },

        estValide(ligne) {
            return ligne.nom_complet
                && ligne.nom_complet.trim().includes(' ')  // Au moins 2 mots
                && (ligne.sexe === 'M' || ligne.sexe === 'F');
        },

        validerLigne(idx) {
            // Trigger pour mise à jour réactive du compteur
        },

        ajouterLigne() {
            this.lignes.push(this.ligneVide());
        },

        ajouterLignes(n) {
            for (let i = 0; i < n; i++) {
                this.lignes.push(this.ligneVide());
            }
        },

        ligneVide() {
            return {
                _id: this.nextId++,
                matricule: '',
                nom_complet: '',
                sexe: '',
                date_naissance: '',
            };
        },

        dupliquerLigne(idx) {
            const copie = { ...this.lignes[idx], _id: this.nextId++ };
            this.lignes.splice(idx + 1, 0, copie);
            this.$nextTick(() => {
                const nextInput = this.$el.querySelector(`input[data-row="${idx + 1}"][data-col="1"]`);
                if (nextInput) nextInput.focus();
            });
        },

        supprimerLigne(idx) {
            if (this.lignes.length === 1) {
                this.lignes[0] = this.ligneVide();
            } else {
                this.lignes.splice(idx, 1);
            }
        },

        viderTableau() {
            if (!confirm('Effacer toutes les lignes saisies ?')) return;
            this.lignes = [];
            this.ajouterLignes(5);
        },

        onKeyDown(event, row, col) {
            // Entrée → ligne suivante (même colonne)
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                // Si dernière ligne, en ajouter une
                if (row === this.lignes.length - 1) {
                    this.ajouterLigne();
                }
                this.$nextTick(() => {
                    const next = this.$el.querySelector(`[data-row="${row + 1}"][data-col="${col}"]`);
                    if (next) { next.focus(); if (next.select) next.select(); }
                });
                return;
            }

            // Ctrl+D → dupliquer ligne
            if (event.ctrlKey && event.key === 'd') {
                event.preventDefault();
                this.dupliquerLigne(row);
                return;
            }

            // Flèches haut/bas → ligne précédente/suivante
            if (event.key === 'ArrowDown' && !event.shiftKey) {
                if (event.target.tagName === 'SELECT' && event.target.options.length > 1) return;
                event.preventDefault();
                const next = this.$el.querySelector(`[data-row="${row + 1}"][data-col="${col}"]`);
                if (next) next.focus();
            }
            if (event.key === 'ArrowUp' && !event.shiftKey) {
                if (event.target.tagName === 'SELECT' && event.target.options.length > 1) return;
                event.preventDefault();
                const prev = this.$el.querySelector(`[data-row="${row - 1}"][data-col="${col}"]`);
                if (prev) prev.focus();
            }
        },

        /**
         * Coller depuis Excel : parse le presse-papiers (cellules séparées par \t, lignes par \n)
         * et remplit automatiquement le tableau à partir de la ligne actuelle.
         */
        onPaste(event) {
            const texte = (event.clipboardData || window.clipboardData).getData('text');
            if (!texte || !texte.includes('\t') && !texte.includes('\n')) return; // Simple texte, laisser le comportement natif

            event.preventDefault();

            const target = event.target;
            const rowDebut = parseInt(target.dataset.row || '0', 10);
            const colDebut = parseInt(target.dataset.col || '0', 10);

            const lignesColees = texte.trim().split(/\r?\n/);
            const colonnes = ['matricule', 'nom_complet', 'sexe', 'date_naissance'];

            lignesColees.forEach((ligneStr, i) => {
                const cellules = ligneStr.split('\t');
                const idxLigne = rowDebut + i;

                // S'assurer qu'on a assez de lignes
                while (idxLigne >= this.lignes.length) {
                    this.ajouterLigne();
                }

                // Remplir les cellules à partir de la colonne de départ
                cellules.forEach((valeur, j) => {
                    const idxCol = colDebut + j;
                    const champ = colonnes[idxCol];
                    if (!champ) return;

                    let v = valeur.trim();

                    // Normalisation du sexe
                    if (champ === 'sexe') {
                        const vu = v.toUpperCase();
                        if (['M', 'MASCULIN', 'GARCON', 'H'].includes(vu)) v = 'M';
                        else if (['F', 'FEMININ', 'FEMME', 'FILLE'].includes(vu)) v = 'F';
                        else v = '';
                    }

                    // Uppercase auto pour matricule et nom
                    if (champ === 'matricule' || champ === 'nom_complet') {
                        v = v.toUpperCase();
                    }

                    // Normalisation date jj/mm/aaaa → aaaa-mm-jj
                    if (champ === 'date_naissance' && v.match(/^\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}$/)) {
                        const parts = v.split(/[\/\-\.]/);
                        let year = parts[2];
                        if (year.length === 2) year = (parseInt(year) > 30 ? '19' : '20') + year;
                        v = `${year}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
                    }

                    this.lignes[idxLigne][champ] = v;
                });
            });
        },

        onSubmit(event) {
            // Filtrer les lignes vides avant soumission
            const lignesPleines = this.lignes.filter(l => this.estValide(l));
            if (lignesPleines.length === 0) {
                event.preventDefault();
                alert('Aucune ligne valide à importer. Chaque ligne doit avoir au moins un nom+prénom et un sexe (M/F).');
                return;
            }
            // Remplacer this.lignes par lignesPleines pour que le formulaire n'envoie que les lignes valides
            this.lignes = lignesPleines;
        },
    };
}
</script>
@endpush