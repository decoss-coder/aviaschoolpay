@extends('layouts.app')

@section('title', 'Nouveau budget')
@section('page-title', 'Nouveau budget')
@section('page-subtitle', 'Définir un budget prévisionnel pour la période')

@section('content')
<div x-data="budgetForm()" class="max-w-5xl mx-auto">

    <form method="POST" action="{{ route('budgets.store') }}" class="space-y-6">
        @csrf

        {{-- Retour --}}
        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('budgets.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour à la liste
            </a>
        </div>

        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-bold mb-1">Veuillez corriger les erreurs :</p>
                <ul class="list-disc list-inside text-xs space-y-0.5">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">

                {{-- ═══════════════ SECTION 1 : IDENTITÉ DU BUDGET (brand) ═══════════════ --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand p-6">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-200/20 rounded-full blur-3xl"></div>

                    <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-brand-100/60">
                        <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center shadow-brand-glow">
                            <span class="font-display text-white font-extrabold text-sm">1</span>
                        </div>
                        <div>
                            <h3 class="font-display text-base font-extrabold text-gray-900">Identité du budget</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Nom, périodicité et statut</p>
                        </div>
                    </div>

                    <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Libellé <span class="text-red-500">*</span></label>
                            <input type="text" name="libelle" x-model="libelle" value="{{ old('libelle') }}" required maxlength="200"
                                   placeholder="Ex : Budget annuel 2026-2027"
                                   class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                            @error('libelle')<p class="text-[11px] text-rose-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Périodicité <span class="text-red-500">*</span></label>
                            <select name="periodicite" required
                                    class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm cursor-pointer">
                                <option value="annuel" @selected(old('periodicite', 'annuel') === 'annuel')>📅 Annuel</option>
                                <option value="trimestriel" @selected(old('periodicite') === 'trimestriel')>🗓️ Trimestriel</option>
                                <option value="mensuel" @selected(old('periodicite') === 'mensuel')>📆 Mensuel</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Statut initial</label>
                            <select name="statut"
                                    class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm cursor-pointer">
                                <option value="brouillon" @selected(old('statut', 'brouillon') === 'brouillon')>📝 Brouillon</option>
                                <option value="en_cours" @selected(old('statut') === 'en_cours')>✅ En cours</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- ═══════════════ SECTION 2 : PRÉVISIONNEL (or) ═══════════════ --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/40 rounded-2xl border border-gold-200/60 shadow-card-gold p-6">
                    <div class="absolute -bottom-10 -right-10 w-48 h-48 bg-gold-200/25 rounded-full blur-3xl"></div>

                    <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-gold-200/60">
                        <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-xl flex items-center justify-center shadow-gold-glow">
                            <span class="font-display text-white font-extrabold text-sm">2</span>
                        </div>
                        <div>
                            <h3 class="font-display text-base font-extrabold text-gray-900">Prévisionnel financier</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Estimations des revenus et dépenses</p>
                        </div>
                    </div>

                    <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Revenus prévisionnels <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="number" name="total_prevu_revenus" x-model.number="revenus" min="0" step="1000" value="{{ old('total_prevu_revenus', 0) }}" required
                                       class="w-full px-3 py-2.5 pr-14 bg-white border border-gold-200 rounded-xl text-sm font-bold text-emerald-700 placeholder:text-gray-400 focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                                <span class="absolute right-3 top-2.5 text-xs font-bold text-gold-600">FCFA</span>
                            </div>
                            @error('total_prevu_revenus')<p class="text-[11px] text-rose-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Dépenses prévisionnelles <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="number" name="total_prevu_depenses" x-model.number="depenses" min="0" step="1000" value="{{ old('total_prevu_depenses', 0) }}" required
                                       class="w-full px-3 py-2.5 pr-14 bg-white border border-gold-200 rounded-xl text-sm font-bold text-rose-700 placeholder:text-gray-400 focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                                <span class="absolute right-3 top-2.5 text-xs font-bold text-gold-600">FCFA</span>
                            </div>
                            @error('total_prevu_depenses')<p class="text-[11px] text-rose-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ APERÇU (sidebar) ═══════════════ --}}
            <div class="lg:col-span-1">
                <div class="sticky top-20 space-y-4">
                    <div class="relative overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-brand-800 rounded-2xl shadow-brand-glow p-5 text-white">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-gold-300 to-gold-500"></div>
                        <div class="absolute -top-6 -right-6 w-24 h-24 bg-gold-400/20 rounded-full blur-xl"></div>

                        <p class="relative text-[10px] text-brand-100 font-bold uppercase tracking-[0.15em] mb-3">Aperçu budget</p>
                        <h3 class="relative font-display text-xl font-extrabold leading-tight" x-text="libelle || 'Nouveau budget'"></h3>

                        <div class="relative mt-4 pt-4 border-t border-white/10 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-brand-100 font-medium">Revenus prévus</span>
                                <span class="font-display text-base font-extrabold text-emerald-200" x-text="formatAmount(revenus) + ' F'"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-brand-100 font-medium">Dépenses prévues</span>
                                <span class="font-display text-base font-extrabold text-rose-200" x-text="formatAmount(depenses) + ' F'"></span>
                            </div>
                        </div>

                        <div class="relative mt-4 pt-4 border-t border-white/10">
                            <p class="text-[10px] text-brand-100 font-bold uppercase tracking-wider mb-1">Solde prévisionnel</p>
                            <p class="font-display text-2xl font-extrabold" :class="solde >= 0 ? 'text-gold-300' : 'text-rose-300'"
                               x-text="formatAmount(solde) + ' FCFA'"></p>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-gold-50 to-white border border-gold-200/60 rounded-xl p-4">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-gold-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div>
                                <p class="text-[11px] font-bold text-gold-700">Conseil</p>
                                <p class="text-[11px] text-gray-600 mt-1 leading-relaxed">Vous pourrez ajouter les lignes budgétaires détaillées après création (ressources/charges).</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 pt-2">
            <a href="{{ route('budgets.index') }}"
               class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">
                Annuler
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Créer le budget
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function budgetForm() {
    return {
        libelle: @json(old('libelle', '')),
        revenus: {{ (int) old('total_prevu_revenus', 0) }},
        depenses: {{ (int) old('total_prevu_depenses', 0) }},
        get solde() { return this.revenus - this.depenses; },
        formatAmount(n) { return new Intl.NumberFormat('fr-FR').format(parseInt(n || 0)); }
    };
}
</script>
@endpush
@endsection
