@extends('layouts.app')

@section('title', 'Nouvelle classe')
@section('page-title', 'Créer une nouvelle classe')
@section('page-subtitle', 'Année scolaire ' . ($annee->libelle ?? '2025-2026'))

@section('content')
<div x-data="classeForm()" class="max-w-5xl mx-auto">

    <form method="POST" action="{{ route('classes.store') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="annee_scolaire_id" value="{{ $annee->id }}">
        <input type="hidden" name="scolarite_annuelle" value="0">
        <input type="hidden" name="frais_inscription" value="0">
        <input type="hidden" name="frais_reinscription" value="0">

        <div class="mb-4">
            <a href="{{ route('classes.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour à la liste
            </a>
        </div>

        @if($errors->any())
        <div class="bg-gradient-to-r from-red-50 to-red-100/50 border border-red-200 rounded-xl p-4 text-red-800 text-sm">
            <p class="font-bold mb-1">Merci de corriger les erreurs suivantes :</p>
            <ul class="list-disc list-inside text-[12px] space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">

                <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand p-6">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-200/20 rounded-full blur-3xl"></div>
                    <div class="relative flex items-center gap-3 mb-5 pb-4 border-b border-brand-100/60">
                        <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center shadow-brand-glow">
                            <span class="font-display text-white font-extrabold text-sm">1</span>
                        </div>
                        <div>
                            <h3 class="font-display text-base font-extrabold text-gray-900">Identité de la classe</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Niveau, série et nom de la classe</p>
                        </div>
                    </div>

                    <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Niveau <span class="text-red-500">*</span></label>
                            <select name="niveau_id" required x-model="niveauId" @change="onNiveauChange()"
                                    class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                                <option value="">Sélectionner un niveau...</option>
                                @foreach($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}" data-nom="{{ $niveau->code }}" data-cycle="{{ $niveau->cycle ?? '' }}" data-scolarite="{{ (int) ($niveau->frais_scolarite_defaut ?? 0) }}" data-inscription="{{ (int) ($niveau->frais_inscription_defaut ?? 0) }}" data-reinscription="{{ (int) ($niveau->frais_reinscription_defaut ?? 0) }}" {{ $niveauPreselect == $niveau->id ? 'selected' : '' }}>
                                        {{ $niveau->libelle }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-gray-400 mt-1">Les tarifs sont repris automatiquement depuis le niveau.</p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Série <span class="text-gray-400 font-medium">(optionnel)</span></label>
                            <select name="serie_id"
                                    class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                                <option value="">Aucune série</option>
                                @foreach($series as $serie)
                                    <option value="{{ $serie->id }}">{{ $serie->code }}</option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-gray-400 mt-1">Requis uniquement pour le lycée (A, C, D...).</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nom de la classe <span class="text-red-500">*</span></label>
                            <input type="text" name="nom" x-model="nom" required maxlength="50"
                                   placeholder="Ex : 6e A, 3e B, Tle D1..."
                                   class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm">
                            <p class="text-[10px] text-gray-400 mt-1">Choisissez un nom court et clair, visible sur les bulletins.</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Description <span class="text-gray-400 font-medium">(optionnel)</span></label>
                            <textarea name="description" rows="2" maxlength="500"
                                      placeholder="Remarques, particularités de la classe..."
                                      class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm resize-none"></textarea>
                        </div>
                    </div>
                </div>

                <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-blue-50/30 rounded-2xl border border-blue-100/60 shadow-card-blue p-6">
                    <div class="absolute -top-10 -left-10 w-40 h-40 bg-blue-200/20 rounded-full blur-3xl"></div>
                    <div class="relative flex items-center gap-3 mb-5 pb-4 border-b border-blue-100/60">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-sm shadow-blue-500/30">
                            <span class="font-display text-white font-extrabold text-sm">2</span>
                        </div>
                        <div>
                            <h3 class="font-display text-base font-extrabold text-gray-900">Capacité & encadrement</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Nombre d'élèves max et professeur principal</p>
                        </div>
                    </div>

                    <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Capacité maximale <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="number" name="capacite" x-model="capacite" required min="1" max="200" value="30"
                                       class="w-full px-3 py-2.5 pr-16 bg-white border border-blue-100 rounded-xl text-sm font-bold focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none shadow-sm">
                                <span class="absolute right-3 top-2.5 text-xs font-bold text-blue-600">élèves</span>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1">Au-delà, l'inscription sera refusée.</p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Professeur principal</label>
                            <select name="professeur_principal_id"
                                    class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none shadow-sm cursor-pointer">
                                <option value="">Pas encore désigné</option>
                                @foreach($enseignants as $ens)
                                    <option value="{{ $ens->id }}">{{ $ens->prenom }} {{ $ens->nom }}</option>
                                @endforeach
                            </select>
                            @if($enseignants->isEmpty())
                                <p class="text-[10px] text-gold-600 mt-1">Aucun enseignant enregistré. Créez-en d'abord ou laissez vide.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gold-200 bg-gold-50 px-5 py-4 text-sm text-gold-900">
                    <b>Tarification retirée de cette page :</b> la scolarité, l’inscription et la réinscription se règlent maintenant dans <b>Gestion des niveaux</b> ou <b>Grilles tarifaires</b>, afin d’éviter les erreurs classe par classe.
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="sticky top-20 space-y-4">
                    <div class="relative overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-brand-800 rounded-2xl shadow-brand-glow p-5 text-white">
                        <div class="absolute -top-10 -right-10 w-32 h-32 bg-gold-400/20 rounded-full blur-3xl"></div>
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-gold-300 to-gold-500"></div>

                        <div class="relative">
                            <p class="text-[10px] text-brand-100 font-bold uppercase tracking-[0.15em] mb-3">Aperçu en direct</p>
                            <h3 class="font-display text-2xl font-extrabold leading-tight" x-text="nom || 'Nom de la classe'"></h3>
                            <p class="text-[11px] text-brand-100 mt-1" x-text="niveauNom || 'Aucun niveau'"></p>

                            <div class="mt-4 pt-4 border-t border-white/10 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] text-brand-100 font-medium">Capacité</span>
                                    <span class="font-display text-base font-extrabold" x-text="(capacite || 0) + ' élèves'"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] text-brand-100 font-medium">Scolarité niveau</span>
                                    <span class="font-display text-base font-extrabold text-gold-200" x-text="formatAmount(scolariteAnnuelle) + ' F'"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] text-brand-100 font-medium">Inscription niveau</span>
                                    <span class="text-sm font-bold" x-text="formatAmount(fraisInscription) + ' F'"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] text-brand-100 font-medium">Réinscription niveau</span>
                                    <span class="text-sm font-bold" x-text="formatAmount(fraisReinscription) + ' F'"></span>
                                </div>
                            </div>

                            <div class="mt-4 pt-4 border-t border-white/10">
                                <p class="text-[10px] text-brand-100 font-bold uppercase tracking-wider mb-1">Total annuel nouvel élève</p>
                                <p class="font-display text-2xl font-extrabold text-gold-300" x-text="formatAmount(totalNouveau) + ' FCFA'"></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-gold-50 to-white border border-gold-200/60 rounded-xl p-4">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-gold-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div>
                                <p class="text-[11px] font-bold text-gold-700">Bon à savoir</p>
                                <p class="text-[11px] text-gray-600 mt-1 leading-relaxed">Pour modifier les montants, utilisez le menu Gestion des niveaux ou Grilles tarifaires.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 pt-2">
            <a href="{{ route('classes.index') }}"
               class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">
                Annuler
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Créer la classe
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function classeForm() {
    return {
        nom: '',
        niveauId: '{{ $niveauPreselect ?? "" }}',
        niveauNom: '',
        capacite: 30,
        scolariteAnnuelle: 0,
        fraisInscription: 0,
        fraisReinscription: 0,

        init() {
            if (this.niveauId) this.onNiveauChange();
        },
        get totalNouveau() {
            return (this.scolariteAnnuelle || 0) + (this.fraisInscription || 0);
        },
        onNiveauChange() {
            const select = document.querySelector('select[name="niveau_id"]');
            const opt = select.options[select.selectedIndex];
            this.niveauNom = opt.dataset.nom || '';
            this.scolariteAnnuelle = Number(opt.dataset.scolarite || 0);
            this.fraisInscription = Number(opt.dataset.inscription || 0);
            this.fraisReinscription = Number(opt.dataset.reinscription || 0);
        },
        formatAmount(n) {
            return new Intl.NumberFormat('fr-FR').format(n || 0);
        }
    }
}
</script>
@endpush
