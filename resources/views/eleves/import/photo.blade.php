@extends('layouts.app')

@section('title', 'Import par photo')
@section('page-title', 'Import par photo (IA)')
@section('page-subtitle', 'Photographiez une liste papier, l\'IA extrait les élèves automatiquement')

@section('content')
<div x-data="photoImport()" class="max-w-4xl mx-auto">

    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('eleves.import.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour
        </a>
        <a href="{{ route('eleves.import.photo.diagnostic') }}" target="_blank"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-violet-200 text-violet-700 text-[11px] font-bold rounded-lg hover:bg-violet-50 transition-all shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Diagnostic
        </a>
    </div>

    {{-- Fallback absolu : si Alpine ne charge pas, on affiche au moins un message --}}
    <noscript>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
            <p class="text-sm font-bold text-red-800">JavaScript désactivé</p>
            <p class="text-[12px] text-red-600 mt-1">Cette fonctionnalité nécessite JavaScript activé dans votre navigateur.</p>
        </div>
    </noscript>

    {{-- Message d'erreur dynamique (AJAX) --}}
    <div x-show="errorMessage" x-cloak x-transition
         class="relative overflow-hidden bg-gradient-to-r from-red-50 to-red-100/50 border border-red-200 rounded-xl p-4 mb-6">
        <div class="flex items-start gap-3">
            <div class="w-8 h-8 bg-gradient-to-br from-red-400 to-red-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-bold text-red-800">Erreur</p>
                <p class="text-[12px] text-red-600 mt-1" x-text="errorMessage"></p>
                <div class="mt-2 flex gap-3">
                    <button type="button" @click="errorMessage = null; loading = false"
                            class="text-[11px] font-bold text-red-700 underline">Fermer</button>
                    <a href="{{ route('eleves.import.photo.diagnostic') }}" target="_blank"
                       class="text-[11px] font-bold text-red-700 underline">Diagnostic</a>
                </div>
            </div>
        </div>
    </div>

    {{-- IMPORTANT : pas de @submit.prevent car iOS l'ignore parfois.
         On utilise un form classique + bouton avec @click.prevent qui appelle onSubmit() --}}
    <form id="photo-form" method="POST" action="{{ route('eleves.import.photo.upload') }}"
          enctype="multipart/form-data" class="space-y-6" x-ref="form">
        @csrf

        {{-- SECTION 1 : PHOTO --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-violet-50/30 rounded-2xl border border-violet-100/60 shadow-card-violet p-6">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-violet-200/20 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-5 pb-4 border-b border-violet-100/60">
                <div class="w-10 h-10 bg-gradient-to-br from-violet-400 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-violet-500/30">
                    <span class="font-display text-white font-extrabold text-sm">1</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Photographiez ou uploadez</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Utilisez votre smartphone pour prendre la photo directement</p>
                </div>
            </div>

            <div class="relative">
                {{-- État vide --}}
                <template x-if="!imagePreview && !loading">
                    <div>
                        <div @drop.prevent="onDrop($event)" @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false"
                             :class="dragOver ? 'border-violet-400 bg-violet-50' : 'border-violet-200 bg-gradient-to-br from-violet-50/30 to-white'"
                             class="border-2 border-dashed rounded-2xl p-8 text-center transition-all">
                            <div class="w-16 h-16 bg-gradient-to-br from-violet-400 via-purple-500 to-purple-700 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-violet-500/30 ring-4 ring-violet-100">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <p class="font-display text-base font-extrabold text-gray-900 mb-1">Choisissez une option</p>
                            <p class="text-sm text-gray-500 mb-5">Prenez une photo ou sélectionnez un fichier existant</p>

                            {{-- 2 boutons mais un seul input - iOS choisit via le bouton natif --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-md mx-auto">
                                <button type="button" @click="document.getElementById('fichier-input').click()"
                                        class="inline-flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-violet-500 to-purple-600 text-white text-sm font-bold rounded-xl shadow-lg shadow-violet-500/30 hover:-translate-y-0.5 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Prendre une photo
                                </button>
                                <button type="button" @click="document.getElementById('fichier-input').click()"
                                        class="inline-flex items-center justify-center gap-2 px-4 py-3 bg-white border border-violet-200 text-violet-700 text-sm font-bold rounded-xl shadow-sm hover:bg-violet-50 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    Choisir un fichier
                                </button>
                            </div>

                            <p class="text-[11px] text-gray-400 mt-4">Formats : JPG, PNG, WEBP, HEIC · Max 20 Mo</p>
                        </div>
                    </div>
                </template>

                {{-- INPUT UNIQUE — pas de x-ref pour éviter les problèmes avec Alpine --}}
                <input type="file" name="fichier" id="fichier-input" accept="image/*"
                       onchange="window.Alpine && Alpine.store ? null : null;"
                       @change="onFileSelected($event)"
                       style="display: none;">

                {{-- Preview --}}
                <template x-if="imagePreview && !loading">
                    <div class="space-y-4">
                        <div class="relative bg-gradient-to-br from-violet-50 to-white border border-violet-200 rounded-2xl p-3">
                            <img :src="imagePreview" class="w-full max-h-96 object-contain rounded-xl shadow-md">
                            <button type="button" @click="reset()"
                                    class="absolute top-5 right-5 w-8 h-8 bg-white/90 backdrop-blur hover:bg-red-50 text-gray-600 hover:text-red-600 rounded-full shadow-md flex items-center justify-center transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="flex items-center justify-between gap-2 px-1">
                            <p class="text-[12px] text-gray-600">
                                <span class="font-bold" x-text="fichierNom"></span>
                                <span class="text-gray-400 mx-1">·</span>
                                <span x-text="fichierTaille"></span>
                            </p>
                            <button type="button" @click="reset()" class="text-[11px] text-violet-600 font-bold hover:text-violet-800 underline">Autre photo</button>
                        </div>
                    </div>
                </template>

                {{-- Loading --}}
                <template x-if="loading">
                    <div class="border-2 border-violet-300 bg-gradient-to-br from-violet-50/50 to-white rounded-2xl p-10 text-center">
                        <div class="relative w-20 h-20 mx-auto mb-5">
                            <div class="absolute inset-0 bg-gradient-to-br from-violet-400 to-purple-600 rounded-2xl animate-pulse shadow-lg shadow-violet-500/40"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <svg class="w-10 h-10 text-white animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </div>
                        </div>
                        <p class="font-display text-base font-extrabold text-gray-900">L'IA analyse votre photo...</p>
                        <p class="text-sm text-gray-500 mt-1" x-text="loadingMessage"></p>
                        <div class="mt-4">
                            <p class="font-display text-3xl font-extrabold text-violet-600"><span x-text="elapsedTime"></span>s</p>
                            <p class="text-[10px] text-gray-400 mt-0.5">Temps écoulé</p>
                        </div>
                        <div class="flex items-center justify-center gap-1.5 mt-4">
                            <div class="w-2 h-2 bg-violet-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                            <div class="w-2 h-2 bg-purple-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                            <div class="w-2 h-2 bg-violet-600 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                        </div>
                        <button type="button" x-show="elapsedTime > 30" @click="annulerAnalyse()"
                                class="mt-4 text-[11px] text-red-600 font-bold underline hover:text-red-800">Annuler</button>
                    </div>
                </template>
            </div>
        </div>

        {{-- SECTION 2 : CLASSE CIBLE --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/40 rounded-2xl border border-gold-200/60 shadow-card-gold p-6">
            <div class="absolute -top-10 -left-10 w-40 h-40 bg-gold-200/20 rounded-full blur-3xl"></div>
            <div class="relative flex items-center gap-3 mb-5 pb-4 border-b border-gold-200/60">
                <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-xl flex items-center justify-center shadow-gold-glow">
                    <span class="font-display text-white font-extrabold text-sm">2</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Classe cible (optionnel)</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Tous les élèves détectés seront rattachés à cette classe</p>
                </div>
            </div>
            <div class="relative">
                <select name="classe_cible_id" id="classe-cible"
                        class="w-full px-4 py-3 bg-white border border-gold-200 rounded-xl text-sm font-bold text-gray-900 focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none shadow-sm cursor-pointer">
                    <option value="">Aucune classe (à définir plus tard)</option>
                    @foreach($classes as $classe)
                        <option value="{{ $classe->id }}" {{ $classePreselect == $classe->id ? 'selected' : '' }}>
                            {{ $classe->nom }} — {{ $classe->niveau->nom ?? '' }} ({{ $classe->effectif }}/{{ $classe->capacite }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('eleves.import.index') }}"
               class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">
                Annuler
            </a>
            {{-- IMPORTANT : type="button" + @click.prevent, pour éviter tout submit HTML parasite --}}
            <button type="button" id="btn-analyser"
                    @click.prevent="onSubmit()"
                    :disabled="!imagePreview || loading"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-violet-500 via-purple-500 to-purple-700 text-white text-sm font-extrabold rounded-xl shadow-lg shadow-violet-500/30 ring-1 ring-violet-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <svg x-show="loading" x-cloak class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span x-text="loading ? 'Analyse en cours...' : 'Analyser avec l\'IA'"></span>
            </button>
        </div>
    </form>

    {{-- Zone de debug visible en bas si erreur non-catchée --}}
    <div x-show="debugLog.length > 0" x-cloak class="mt-6 p-4 bg-gray-100 border border-gray-300 rounded-xl">
        <p class="text-[11px] font-bold text-gray-700 mb-2">Journal (debug) :</p>
        <div class="space-y-1 font-mono text-[10px] text-gray-600 max-h-40 overflow-y-auto">
            <template x-for="(log, idx) in debugLog" :key="idx">
                <div x-text="log"></div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Version DÉFENSIVE : fonctionne même si Alpine.js a des soucis
function photoImport() {
    return {
        imagePreview: null,
        fichierNom: null,
        fichierTaille: null,
        dragOver: false,
        loading: false,
        loadingMessage: 'Téléversement en cours...',
        errorMessage: null,
        elapsedTime: 0,
        timerInterval: null,
        abortController: null,
        debugLog: [],

        log(msg) {
            const ts = new Date().toLocaleTimeString();
            const line = `[${ts}] ${msg}`;
            this.debugLog.push(line);
            if (this.debugLog.length > 20) this.debugLog.shift();
            console.log('[PhotoImport]', line);
        },

        onFileSelected(event) {
            try {
                const file = event.target.files[0];
                if (file) {
                    this.log('Fichier sélectionné: ' + file.name + ' (' + file.size + ' bytes)');
                    this.setFichier(file);
                } else {
                    this.log('onFileSelected: aucun fichier');
                }
            } catch (e) {
                this.log('ERREUR onFileSelected: ' + e.message);
                this.errorMessage = 'Erreur lors de la sélection : ' + e.message;
            }
        },

        onDrop(event) {
            this.dragOver = false;
            try {
                const file = event.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    // Injecter dans l'input pour que FormData le trouve
                    const input = document.getElementById('fichier-input');
                    try {
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        input.files = dt.files;
                    } catch (err) {
                        this.log('DataTransfer non supporté, fichier stocké en mémoire');
                    }
                    this.setFichier(file);
                } else {
                    alert('Seules les images sont acceptées (JPG, PNG, WEBP, HEIC).');
                }
            } catch (e) {
                this.log('ERREUR onDrop: ' + e.message);
            }
        },

        setFichier(file) {
            this.fichierNom = file.name;
            this.fichierTaille = this.formatBytes(file.size);
            this.errorMessage = null;

            const reader = new FileReader();
            reader.onload = (e) => {
                this.imagePreview = e.target.result;
                this.log('Preview chargée');
            };
            reader.onerror = (e) => {
                this.log('ERREUR lecture image');
                this.errorMessage = 'Impossible de lire l\'image sélectionnée.';
            };
            reader.readAsDataURL(file);
        },

        reset() {
            this.imagePreview = null;
            this.fichierNom = null;
            this.fichierTaille = null;
            this.loading = false;
            this.errorMessage = null;
            this.stopTimer();
            const input = document.getElementById('fichier-input');
            if (input) input.value = '';
            this.log('Reset effectué');
        },

        startTimer() {
            this.elapsedTime = 0;
            this.timerInterval = setInterval(() => {
                this.elapsedTime++;
                if (this.elapsedTime <= 10) {
                    this.loadingMessage = 'Téléversement de l\'image...';
                } else if (this.elapsedTime <= 25) {
                    this.loadingMessage = 'L\'IA lit le tableau...';
                } else if (this.elapsedTime <= 60) {
                    this.loadingMessage = 'Analyse approfondie en cours...';
                } else if (this.elapsedTime <= 120) {
                    this.loadingMessage = 'Temps plus long que prévu, patientez...';
                } else {
                    this.loadingMessage = 'Anormalement long. Vous pouvez annuler et réessayer.';
                }
            }, 1000);
        },

        stopTimer() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
        },

        annulerAnalyse() {
            if (this.abortController) {
                try { this.abortController.abort(); } catch(e) {}
            }
            this.loading = false;
            this.stopTimer();
            this.errorMessage = 'Analyse annulée.';
            this.log('Analyse annulée par l\'utilisateur');
        },

        // Récupération CSRF robuste : meta tag OU input caché du form
        getCsrfToken() {
            // Priorité 1 : meta tag (standard Laravel)
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.getAttribute('content')) {
                return meta.getAttribute('content');
            }
            // Priorité 2 : input caché généré par @csrf
            const input = document.querySelector('input[name="_token"]');
            if (input && input.value) {
                return input.value;
            }
            this.log('ATTENTION : aucun token CSRF trouvé');
            return null;
        },

        async onSubmit() {
            this.log('onSubmit appelé');

            try {
                // 1. Vérifier le fichier
                const input = document.getElementById('fichier-input');
                if (!input) {
                    this.errorMessage = 'Erreur interne : champ fichier introuvable dans la page.';
                    this.log('ERREUR: input fichier-input introuvable');
                    return;
                }

                const file = input.files && input.files[0];
                if (!file) {
                    this.errorMessage = 'Aucune image sélectionnée. Prenez ou choisissez une photo.';
                    this.log('ERREUR: aucun fichier dans l\'input');
                    return;
                }

                this.log('Fichier valide: ' + file.name + ' (' + file.size + ' bytes)');

                // 2. Préparer les données
                const formData = new FormData();
                formData.append('fichier', file);

                const classeCible = document.getElementById('classe-cible');
                if (classeCible && classeCible.value) {
                    formData.append('classe_cible_id', classeCible.value);
                }

                // CSRF
                const csrfToken = this.getCsrfToken();
                if (csrfToken) {
                    formData.append('_token', csrfToken);
                } else {
                    this.errorMessage = 'Token de sécurité introuvable. Rechargez la page (Ctrl+F5).';
                    return;
                }

                this.log('FormData préparé avec CSRF');

                // 3. Activer le loading
                this.loading = true;
                this.errorMessage = null;
                this.startTimer();

                // 4. Envoyer
                this.abortController = new AbortController();
                const timeoutId = setTimeout(() => {
                    this.log('Timeout 3 minutes atteint');
                    this.abortController.abort();
                }, 180000);

                const url = '{{ route('eleves.import.photo.upload') }}';
                this.log('Envoi POST vers ' + url);

                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    signal: this.abortController.signal,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                clearTimeout(timeoutId);
                this.stopTimer();

                this.log('Réponse reçue : HTTP ' + response.status);

                // 5. Parser la réponse
                let data = null;
                const contentType = response.headers.get('content-type') || '';

                if (contentType.includes('application/json')) {
                    data = await response.json().catch(e => {
                        this.log('ERREUR parse JSON: ' + e.message);
                        return null;
                    });
                } else {
                    const text = await response.text().catch(() => '');
                    this.log('Réponse non-JSON (longueur: ' + text.length + ')');
                    if (text.length < 500) this.log('Contenu: ' + text.substring(0, 300));
                }

                // 6. Traiter le résultat
                if (!response.ok) {
                    this.loading = false;
                    const msg = data?.message
                        || data?.errors?.fichier?.[0]
                        || `Erreur serveur HTTP ${response.status}. Vérifiez les logs Laravel.`;
                    this.errorMessage = msg;
                    this.log('Échec: ' + msg);
                    return;
                }

                if (data?.success && data.redirect) {
                    this.log('Succès, redirection vers ' + data.redirect);
                    window.location.href = data.redirect;
                } else {
                    this.loading = false;
                    this.errorMessage = data?.message || 'Réponse inattendue du serveur.';
                    this.log('Réponse inattendue: ' + JSON.stringify(data).substring(0, 200));
                }

            } catch (err) {
                this.stopTimer();
                this.loading = false;

                if (err.name === 'AbortError') {
                    this.errorMessage = 'L\'analyse a été interrompue (timeout 3 min ou annulation).';
                } else if (err.message?.includes('Failed to fetch') || err.message?.includes('NetworkError')) {
                    this.errorMessage = 'Erreur réseau. Vérifiez votre connexion et réessayez.';
                } else {
                    this.errorMessage = 'Erreur : ' + (err.message || 'inconnue');
                }
                this.log('EXCEPTION onSubmit: ' + err.message);
                console.error('[PhotoImport] Exception:', err);
            }
        },

        formatBytes(bytes) {
            if (bytes < 1024) return bytes + ' o';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko';
            return (bytes / 1024 / 1024).toFixed(2) + ' Mo';
        },
    };
}
</script>
@endpush