@extends('layouts.app')

@section('title', 'Import Excel')
@section('page-title', 'Import depuis Excel ou CSV')
@section('page-subtitle', 'Uploadez votre fichier de liste d\'élèves')

@section('content')
<div x-data="excelImport()" class="max-w-4xl mx-auto">

    {{-- Retour --}}
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('eleves.import.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour aux méthodes d'import
        </a>
        <a href="{{ route('eleves.import.template') }}" class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-gold-200 text-gold-700 text-[12px] font-bold rounded-lg hover:bg-gold-50 transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Télécharger le modèle
        </a>
    </div>

    {{-- Erreurs --}}
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
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('eleves.import.excel.upload') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- SECTION 1 : UPLOAD FICHIER --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand p-6">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-200/20 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-5 pb-4 border-b border-brand-100/60">
                <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center shadow-brand-glow">
                    <span class="font-display text-white font-extrabold text-sm">1</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Uploadez votre fichier</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Excel (.xlsx, .xls), CSV ou OpenDocument</p>
                </div>
            </div>

            {{-- Drag & drop zone --}}
            <div class="relative">
                <div @drop.prevent="onDrop($event)" @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false"
                     :class="dragOver ? 'border-brand-400 bg-brand-50' : 'border-brand-200 bg-gradient-to-br from-brand-50/30 to-white'"
                     class="border-2 border-dashed rounded-2xl p-8 text-center transition-all cursor-pointer"
                     @click="$refs.fichierInput.click()">

                    <template x-if="!fichierNom">
                        <div>
                            <div class="w-16 h-16 bg-gradient-to-br from-brand-400 to-brand-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-brand-glow ring-4 ring-brand-100">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            </div>
                            <p class="font-display text-base font-extrabold text-gray-900 mb-1">Glissez votre fichier ici</p>
                            <p class="text-sm text-gray-500 mb-3">ou <span class="text-brand-600 font-bold underline">cliquez pour parcourir</span></p>
                            <p class="text-[11px] text-gray-400">Formats : .xlsx, .xls, .csv, .ods · Taille max : 10 Mo</p>
                        </div>
                    </template>

                    <template x-if="fichierNom">
                        <div>
                            <div class="w-16 h-16 bg-gradient-to-br from-brand-500 to-brand-700 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-brand-glow">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <p class="font-display text-base font-extrabold text-gray-900" x-text="fichierNom"></p>
                            <p class="text-[12px] text-brand-600 font-bold mt-1" x-text="fichierTaille"></p>
                            <button type="button" @click.stop="reset()" class="mt-3 text-[11px] text-gray-500 hover:text-red-600 underline">Changer de fichier</button>
                        </div>
                    </template>
                </div>
                <input type="file" name="fichier" x-ref="fichierInput" @change="onFileSelected($event)"
                       accept=".xlsx,.xls,.csv,.ods" class="hidden" required>
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
                    <p class="text-xs text-gray-500 mt-0.5">Tous les élèves importés seront rattachés à cette classe</p>
                </div>
            </div>

            <div class="relative">
                <select name="classe_cible_id"
                        class="w-full px-4 py-3 bg-white border border-gold-200 rounded-xl text-sm font-bold text-gray-900 focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none shadow-sm cursor-pointer">
                    <option value="">Aucune classe (à définir plus tard pour chaque élève)</option>
                    @foreach($classes as $classe)
                        <option value="{{ $classe->id }}" {{ $classePreselect == $classe->id ? 'selected' : '' }}>
                            {{ $classe->nom }} — {{ $classe->niveau->nom ?? '' }}
                            ({{ $classe->effectif }}/{{ $classe->capacite }} places)
                        </option>
                    @endforeach
                </select>
                @if($classes->isEmpty())
                    <p class="text-[11px] text-gold-600 mt-2">
                        <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Aucune classe créée. <a href="{{ route('classes.create') }}" class="font-bold underline">Créer une classe →</a>
                    </p>
                @endif
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- ASTUCES / AIDE --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-50/50 to-white rounded-2xl border border-blue-100/60 shadow-card-blue p-5">
            <div class="absolute -top-6 -right-6 w-24 h-24 bg-blue-200/30 rounded-full blur-2xl"></div>
            <div class="relative flex items-start gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm shadow-blue-500/30">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="flex-1">
                    <p class="font-display text-sm font-extrabold text-blue-800 mb-2">Comment ça fonctionne</p>
                    <ul class="space-y-1.5 text-[12px] text-blue-700">
                        <li class="flex items-start gap-2">
                            <span class="text-blue-500 font-extrabold mt-0.5">•</span>
                            <span>Votre fichier doit contenir une ligne d'<strong>en-tête</strong> (titres des colonnes) dans les 5 premières lignes.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-blue-500 font-extrabold mt-0.5">•</span>
                            <span>Les colonnes <strong>« Nom et Prénoms »</strong> et <strong>« Sexe »</strong> sont obligatoires. Le <strong>matricule DESPS</strong> est recommandé.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-blue-500 font-extrabold mt-0.5">•</span>
                            <span>L'ordre des colonnes n'a pas d'importance — nous détectons automatiquement les libellés.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-blue-500 font-extrabold mt-0.5">•</span>
                            <span>Dans « Nom et Prénoms », le <strong>premier mot</strong> est considéré comme le nom de famille (ex : BAGATE DAOUDA → Nom : BAGATE, Prénom : Daouda).</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-blue-500 font-extrabold mt-0.5">•</span>
                            <span>Vous pourrez <strong>corriger toutes les lignes</strong> dans l'étape suivante avant l'import final.</span>
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
            <button type="submit" :disabled="!fichierNom || loading" @click="loading = true"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-sm font-extrabold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                <svg x-show="loading" x-cloak class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span x-text="loading ? 'Analyse en cours...' : 'Analyser le fichier'"></span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function excelImport() {
    return {
        fichierNom: null,
        fichierTaille: null,
        dragOver: false,
        loading: false,

        onFileSelected(event) {
            const file = event.target.files[0];
            if (file) this.setFichier(file);
        },

        onDrop(event) {
            this.dragOver = false;
            const file = event.dataTransfer.files[0];
            if (file) {
                const allowed = ['xlsx', 'xls', 'csv', 'ods'];
                const ext = file.name.split('.').pop().toLowerCase();
                if (!allowed.includes(ext)) {
                    alert('Format non supporté. Utilisez .xlsx, .xls, .csv ou .ods');
                    return;
                }
                // Mettre le fichier dans l'input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                this.$refs.fichierInput.files = dataTransfer.files;
                this.setFichier(file);
            }
        },

        setFichier(file) {
            this.fichierNom = file.name;
            this.fichierTaille = this.formatBytes(file.size);
        },

        reset() {
            this.fichierNom = null;
            this.fichierTaille = null;
            this.$refs.fichierInput.value = '';
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