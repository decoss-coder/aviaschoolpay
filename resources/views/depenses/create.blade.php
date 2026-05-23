@extends('layouts.app')

@section('title', 'Nouvelle dépense')
@section('page-title', 'Nouvelle dépense')
@section('page-subtitle', 'Enregistrer une dépense à soumettre pour validation')

@section('content')
<div x-data="depenseForm()" class="max-w-5xl mx-auto">

    <form method="POST" action="{{ route('depenses.store') }}" class="space-y-6">
        @csrf

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- BREADCRUMB + RETOUR --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('depenses.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour à la liste
            </a>
        </div>

        {{-- Erreurs globales --}}
        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-bold mb-1">Veuillez corriger les erreurs :</p>
                <ul class="list-disc list-inside text-xs space-y-0.5">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- SECTION 1 : INFORMATIONS PRINCIPALES (vert brand) --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand p-6">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-200/20 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-brand-100/60">
                <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center shadow-brand-glow">
                    <span class="font-display text-white font-extrabold text-sm">1</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Informations principales</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Libellé, catégorie, montant et date</p>
                </div>
            </div>

            <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Libellé <span class="text-red-500">*</span></label>
                    <input type="text" name="libelle" value="{{ old('libelle') }}" required maxlength="300"
                           placeholder="Ex : Achat fournitures de bureau"
                           class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Catégorie <span class="text-red-500">*</span></label>
                    <select name="categorie_id" required
                            class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm cursor-pointer">
                        <option value="">Sélectionner...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('categorie_id') == $cat->id)>
                                {{ $cat->nom }} ({{ ucfirst($cat->type) }})
                            </option>
                        @endforeach
                    </select>
                    @if($categories->isEmpty())
                        <p class="text-[11px] text-amber-600 mt-1">⚠ Aucune catégorie. <a href="{{ route('depenses.categories') }}" class="underline font-bold">Créer une catégorie</a>.</p>
                    @endif
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Date de la dépense <span class="text-red-500">*</span></label>
                    <input type="date" name="date_depense" value="{{ old('date_depense', now()->format('Y-m-d')) }}" required
                           class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Montant (FCFA) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="montant" x-model="montant" min="1" step="1" value="{{ old('montant') }}" required
                               placeholder="0"
                               class="w-full px-3 py-2.5 pr-14 bg-white border border-brand-100 rounded-xl text-sm font-bold text-brand-700 placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-brand-600">FCFA</span>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Fréquence</label>
                    <select name="frequence"
                            class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm cursor-pointer">
                        @foreach([
                            'ponctuelle'    => 'Ponctuelle',
                            'mensuelle'     => 'Mensuelle',
                            'trimestrielle' => 'Trimestrielle',
                            'annuelle'      => 'Annuelle',
                            'hebdomadaire'  => 'Hebdomadaire',
                            'quotidienne'   => 'Quotidienne',
                        ] as $k => $v)
                            <option value="{{ $k }}" @selected(old('frequence', 'ponctuelle') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- SECTION 2 : MODE DE PAIEMENT (bleu) --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-blue-50/30 rounded-2xl border border-blue-100/60 shadow-card-blue p-6">
            <div class="absolute -top-10 -left-10 w-40 h-40 bg-blue-200/20 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-blue-100/60">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-sm shadow-blue-500/30">
                    <span class="font-display text-white font-extrabold text-sm">2</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Mode de règlement</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Comment cette dépense est-elle payée ?</p>
                </div>
            </div>

            <div class="relative">
                <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Mode de paiement <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                    @foreach([
                        'especes'      => ['Espèces',        '💵'],
                        'cheque'       => ['Chèque',         '📝'],
                        'virement'     => ['Virement',       '🏦'],
                        'mobile_money' => ['Mobile money',   '📱'],
                        'carte'        => ['Carte bancaire', '💳'],
                    ] as $k => [$lbl, $emoji])
                        <label class="relative cursor-pointer">
                            <input type="radio" name="mode_paiement" value="{{ $k }}"
                                   @checked(old('mode_paiement', 'especes') === $k) required class="sr-only peer">
                            <div class="p-3 bg-white border border-blue-100 rounded-xl text-center hover:border-blue-300 transition-all peer-checked:bg-gradient-to-br peer-checked:from-blue-50 peer-checked:to-blue-100/50 peer-checked:border-blue-400 peer-checked:shadow-sm">
                                <p class="text-xl">{{ $emoji }}</p>
                                <p class="text-[12px] font-bold text-gray-800 mt-0.5">{{ $lbl }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- SECTION 3 : DÉTAILS COMPLÉMENTAIRES (violet) --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-violet-50/30 rounded-2xl border border-violet-100/60 shadow-card-violet p-6">
            <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-violet-200/20 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-violet-100/60">
                <div class="w-10 h-10 bg-gradient-to-br from-violet-400 to-purple-600 rounded-xl flex items-center justify-center shadow-sm shadow-violet-500/30">
                    <span class="font-display text-white font-extrabold text-sm">3</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Détails complémentaires</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Bénéficiaire, facture, observations — facultatifs</p>
                </div>
            </div>

            <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Bénéficiaire</label>
                    <input type="text" name="beneficiaire" value="{{ old('beneficiaire') }}" maxlength="200"
                           placeholder="Fournisseur, prestataire..."
                           class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">N° de facture / reçu</label>
                    <input type="text" name="numero_facture" value="{{ old('numero_facture') }}" maxlength="50"
                           placeholder="FAC-2026-XXX"
                           class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm font-mono placeholder:text-gray-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Description / observations</label>
                    <textarea name="description" rows="3" placeholder="Précisions complémentaires..."
                              class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm leading-relaxed">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- RÉCAP MONTANT (carte dégradé brand) --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-brand-500 via-brand-600 to-brand-700 rounded-2xl p-5 shadow-brand-glow">
            <div class="absolute -top-6 -right-6 w-24 h-24 bg-gold-400/20 rounded-full blur-xl"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <p class="text-[10px] text-brand-100 font-bold uppercase tracking-wider">Montant à enregistrer</p>
                    <p class="font-display text-3xl font-extrabold text-white mt-1">
                        <span x-text="formatAmount(montant)">0</span>
                        <span class="text-sm font-medium text-brand-100">FCFA</span>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-brand-100">Statut initial</p>
                    <p class="text-xs font-bold text-gold-300">⏳ En attente</p>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- ACTIONS --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="flex items-center justify-between gap-3 pt-2">
            <a href="{{ route('depenses.index') }}"
               class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">
                Annuler
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                Enregistrer et soumettre
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function depenseForm() {
    return {
        montant: {{ (int) old('montant', 0) }},
        formatAmount(n) {
            return new Intl.NumberFormat('fr-FR').format(parseInt(n || 0));
        }
    };
}
</script>
@endpush
