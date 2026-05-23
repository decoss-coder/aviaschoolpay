@extends('layouts.app')

@section('title', 'Import PDF')
@section('page-title', 'Import depuis un PDF')
@section('page-subtitle', 'Uploadez une liste au format PDF (DRENA, DESPS, feuille de notes...)')

@section('content')
<div x-data="pdfImport()" class="max-w-4xl mx-auto">

    {{-- Retour --}}
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('eleves.import.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour aux méthodes d'import
        </a>
    </div>

    {{-- Erreurs serveur Laravel --}}
    @if(session('error'))
        <div class="relative overflow-hidden bg-gradient-to-r from-red-50 to-red-100/50 border border-red-200 rounded-xl p-4 mb-6">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-gradient-to-br from-red-400 to-red-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-red-800">Erreur lors de l'import</p>
                    <p class="text-[12px] text-red-600 mt-1">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-gradient-to-r from-red-50 to-red-100/50 border border-red-200 rounded-xl p-4 text-red-800 text-sm mb-6">
            <p class="font-bold mb-1">Merci de corriger :</p>
            <ul class="list-disc list-inside text-[12px] space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Erreur AJAX --}}
    <div x-show="messageError" x-cloak
         class="relative overflow-hidden bg-gradient-to-r from-red-50 to-red-100/50 border border-red-200 rounded-xl p-4 mb-6">
        <div class="flex items-start gap-3">
            <div class="w-8 h-8 bg-gradient-to-br from-red-400 to-red-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-bold text-red-800">Erreur lors de l'import</p>
                <p class="text-[12px] text-red-600 mt-1" x-text="messageError"></p>
            </div>
        </div>
    </div>

    <form x-ref="form"
          method="POST"
          action="{{ route('eleves.import.pdf.upload') }}"
          enctype="multipart/form-data"
          class="space-y-6"
          @submit.prevent="submitForm">
        @csrf

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- SECTION 1 : UPLOAD --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-blue-50/30 rounded-2xl border border-blue-100/60 shadow-card-blue p-6">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-blue-200/20 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-5 pb-4 border-b border-blue-100/60">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-sm shadow-blue-500/30">
                    <span class="font-display text-white font-extrabold text-sm">1</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Uploadez votre PDF</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Liste DRENA, DESPS, feuille de notes, ou tout autre document tabulaire</p>
                </div>
            </div>

            <div class="relative">
                <div @drop.prevent="onDrop($event)"
                     @dragover.prevent="dragOver = true"
                     @dragleave.prevent="dragOver = false"
                     :class="dragOver ? 'border-blue-400 bg-blue-50' : 'border-blue-200 bg-gradient-to-br from-blue-50/30 to-white'"
                     class="border-2 border-dashed rounded-2xl p-8 text-center transition-all cursor-pointer"
                     @click="$refs.fichierInput.click()">

                    <template x-if="!fichierNom">
                        <div>
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-400 via-blue-500 to-blue-700 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-500/30 ring-4 ring-blue-100">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <p class="font-display text-base font-extrabold text-gray-900 mb-1">Glissez votre PDF ici</p>
                            <p class="text-sm text-gray-500 mb-3">ou <span class="text-blue-600 font-bold underline">cliquez pour parcourir</span></p>
                            <p class="text-[11px] text-gray-400">Format : .pdf · Taille max : 15 Mo · Jusqu'à ~1000 élèves</p>
                        </div>
                    </template>

                    <template x-if="fichierNom">
                        <div>
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-500/40">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <p class="font-display text-base font-extrabold text-gray-900" x-text="fichierNom"></p>
                            <p class="text-[12px] text-blue-600 font-bold mt-1" x-text="fichierTaille"></p>
                            <button type="button" @click.stop="reset()" class="mt-3 text-[11px] text-gray-500 hover:text-red-600 underline">Changer de fichier</button>
                        </div>
                    </template>
                </div>

                <input type="file"
                       name="fichier"
                       x-ref="fichierInput"
                       @change="onFileSelected($event)"
                       accept=".pdf"
                       class="hidden"
                       required>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- SECTION 2 : CLASSE CIBLE --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/40 rounded-2xl border border-gold-200/60 shadow-card-gold p-6">
            <div class="absolute -top-10 -left-10 w-40 h-40 bg-gold-200/20 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-5 pb-4 border-b border-gold-200/60">
                <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-xl flex items-center justify-center shadow-gold-glow">
                    <span class="font-display text-white font-extrabold text-sm">2</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Classe cible (optionnel)</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Tous les élèves extraits seront rattachés à cette classe</p>
                </div>
            </div>

            <div class="relative">
                <select name="classe_cible_id"
                        class="w-full px-4 py-3 bg-white border border-gold-200 rounded-xl text-sm font-bold text-gray-900 focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none shadow-sm cursor-pointer">
                    <option value="">Aucune classe (à définir plus tard)</option>
                    @foreach($classes as $classe)
                        <option value="{{ $classe->id }}" {{ $classePreselect == $classe->id ? 'selected' : '' }}>
                            {{ $classe->nom }} — {{ $classe->niveau->nom ?? $classe->niveau->libelle ?? $classe->niveau->code ?? '' }}
                            ({{ $classe->effectif }}/{{ $classe->capacite }})
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-gray-500 mt-2">
                    <svg class="w-3 h-3 inline text-gold-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Astuce : si votre PDF contient une indication de classe (ex: « Tle A2 »), nous essaierons de la détecter automatiquement.
                </p>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- STRUCTURE ATTENDUE --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-50/50 to-white rounded-2xl border border-blue-100/60 shadow-card-blue p-5">
            <div class="absolute -top-6 -right-6 w-24 h-24 bg-blue-200/30 rounded-full blur-2xl"></div>
            <div class="relative flex items-start gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm shadow-blue-500/30">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="flex-1">
                    <p class="font-display text-sm font-extrabold text-blue-800 mb-2">Structure attendue dans le PDF</p>
                    <p class="text-[12px] text-blue-700 mb-3">Nous détectons automatiquement les tableaux au format suivant :</p>

                    <div class="bg-white border border-blue-200 rounded-lg overflow-hidden font-mono text-[11px]">
                        <div class="grid grid-cols-4 bg-blue-50 border-b border-blue-200 text-blue-800 font-bold">
                            <div class="px-3 py-2 border-r border-blue-200">N°</div>
                            <div class="px-3 py-2 border-r border-blue-200">MATRICULE</div>
                            <div class="px-3 py-2 border-r border-blue-200">NOM ET PRÉNOMS</div>
                            <div class="px-3 py-2 text-center">GENRE</div>
                        </div>
                        <div class="grid grid-cols-4 border-b border-blue-100 text-gray-700">
                            <div class="px-3 py-1.5 border-r border-blue-100">1</div>
                            <div class="px-3 py-1.5 border-r border-blue-100">15195226N</div>
                            <div class="px-3 py-1.5 border-r border-blue-100">ATTIOUA AMOIN CHANTAL</div>
                            <div class="px-3 py-1.5 text-center font-bold text-pink-600">F</div>
                        </div>
                        <div class="grid grid-cols-4 text-gray-700">
                            <div class="px-3 py-1.5 border-r border-blue-100">2</div>
                            <div class="px-3 py-1.5 border-r border-blue-100">17510061U</div>
                            <div class="px-3 py-1.5 border-r border-blue-100">BAGATE DAOUDA</div>
                            <div class="px-3 py-1.5 text-center font-bold text-blue-600">M</div>
                        </div>
                    </div>

                    <ul class="space-y-1 mt-3 text-[11px] text-blue-700">
                        <li class="flex items-start gap-2">
                            <span class="text-blue-500 font-extrabold mt-0.5">•</span>
                            <span><strong>PDF texte</strong> (créé depuis Word, Excel, logiciel DRENA/DESPS) → extraction instantanée</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-blue-500 font-extrabold mt-0.5">•</span>
                            <span><strong>PDF scanné</strong> (photo d'un document papier) → utilisez plutôt <a href="{{ route('eleves.import.photo.form') }}" class="underline font-bold text-violet-600">l'import photo</a></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-blue-500 font-extrabold mt-0.5">•</span>
                            <span>Le matricule DESPS est <strong>auto-validé</strong> (8 chiffres + 1 lettre)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-blue-500 font-extrabold mt-0.5">•</span>
                            <span>Vous pourrez <strong>corriger toutes les lignes</strong> dans l'étape de preview avant validation</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('eleves.import.index') }}"
               class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">
                Annuler
            </a>

            <button type="submit"
                    :disabled="!fichierNom || loading"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-500 via-blue-600 to-blue-700 text-white text-sm font-extrabold rounded-xl shadow-lg shadow-blue-500/30 ring-1 ring-blue-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <svg x-show="loading" x-cloak class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span x-text="loading ? progressText : 'Analyser le PDF'"></span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function pdfImport() {
    return {
        fichierNom: null,
        fichierTaille: null,
        dragOver: false,
        loading: false,
        messageError: '',
        progressText: 'Extraction en cours...',
        maxSizeBytes: 15 * 1024 * 1024,

        onFileSelected(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.validerEtDefinirFichier(file);
        },

        onDrop(event) {
            this.dragOver = false;

            const file = event.dataTransfer.files[0];
            if (!file) return;

            this.validerEtDefinirFichier(file, true);
        },

        validerEtDefinirFichier(file, assignToInput = false) {
            this.messageError = '';

            const ext = (file.name.split('.').pop() || '').toLowerCase();

            if (ext !== 'pdf') {
                this.messageError = 'Seuls les fichiers PDF sont acceptés.';
                this.reset();
                return;
            }

            if (file.size > this.maxSizeBytes) {
                this.messageError = 'Le fichier dépasse la taille maximale autorisée (15 Mo).';
                this.reset();
                return;
            }

            if (assignToInput) {
                const dt = new DataTransfer();
                dt.items.add(file);
                this.$refs.fichierInput.files = dt.files;
            }

            this.setFichier(file);
        },

        setFichier(file) {
            this.fichierNom = file.name;
            this.fichierTaille = this.formatBytes(file.size);
        },

        reset() {
            this.fichierNom = null;
            this.fichierTaille = null;
            this.loading = false;
            this.progressText = 'Extraction en cours...';
            this.messageError = '';
            this.$refs.fichierInput.value = '';
        },

        async submitForm() {
            if (this.loading) return;

            this.messageError = '';

            const file = this.$refs.fichierInput.files[0] || null;

            if (!file) {
                this.messageError = 'Merci de sélectionner un fichier PDF.';
                return;
            }

            const ext = (file.name.split('.').pop() || '').toLowerCase();
            if (ext !== 'pdf') {
                this.messageError = 'Seuls les fichiers PDF sont acceptés.';
                return;
            }

            if (file.size > this.maxSizeBytes) {
                this.messageError = 'Le fichier dépasse la taille maximale autorisée (15 Mo).';
                return;
            }

            this.loading = true;
            this.progressText = 'Extraction en cours...';

            try {
                const formData = new FormData(this.$refs.form);

                const response = await fetch(this.$refs.form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                    },
                    body: formData,
                });

                const contentType = response.headers.get('content-type') || '';
                let data = null;

                if (contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    const text = await response.text();

                    if (response.redirected && response.url) {
                        window.location.href = response.url;
                        return;
                    }

                    console.error('[Import PDF] Réponse non JSON', text);

                    throw new Error(
                        'Réponse inattendue du serveur. Vérifiez que le contrôleur PDF renvoie du JSON pour les requêtes AJAX.'
                    );
                }

                if (!response.ok) {
                    let message = data?.message || 'Une erreur est survenue pendant l’analyse du PDF.';

                    if (data?.errors && typeof data.errors === 'object') {
                        const allErrors = Object.values(data.errors).flat().filter(Boolean);
                        if (allErrors.length) {
                            message = allErrors.join(' ');
                        }
                    }

                    throw new Error(message);
                }

                if (!data || data.success !== true) {
                    throw new Error(data?.message || 'Le serveur n’a pas confirmé l’import.');
                }

                if (!data.redirect) {
                    throw new Error('Le serveur a répondu sans URL de redirection.');
                }

                this.progressText = 'Redirection...';
                window.location.href = data.redirect;

            } catch (error) {
                console.error('[Import PDF] Erreur JS', error);
                this.messageError = error.message || 'Erreur réseau pendant l’import PDF.';
                this.loading = false;
                this.progressText = 'Extraction en cours...';
            }
        },

        formatBytes(bytes) {
            if (bytes < 1024) return bytes + ' o';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko';
            return (bytes / 1024 / 1024).toFixed(2) + ' Mo';
        },

        getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.getAttribute('content')) {
                return meta.getAttribute('content');
            }

            const tokenInput = this.$refs.form.querySelector('input[name="_token"]');
            return tokenInput ? tokenInput.value : '';
        },
    };
}
</script>
@endpush