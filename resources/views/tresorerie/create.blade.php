@extends('layouts.app')

@section('title', 'Nouveau compte de trésorerie')
@section('page-title', 'Nouveau compte de trésorerie')
@section('page-subtitle', 'Caisse, compte bancaire ou portefeuille mobile money')

@section('content')
<div x-data="compteForm()" class="max-w-5xl mx-auto">

    <form method="POST" action="{{ route('tresorerie.store') }}" class="space-y-6">
        @csrf

        {{-- Retour --}}
        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('tresorerie.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour à la trésorerie
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

                {{-- ═══════════════ SECTION 1 : IDENTITÉ DU COMPTE (brand) ═══════════════ --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand p-6">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-200/20 rounded-full blur-3xl"></div>

                    <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-brand-100/60">
                        <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center shadow-brand-glow">
                            <span class="font-display text-white font-extrabold text-sm">1</span>
                        </div>
                        <div>
                            <h3 class="font-display text-base font-extrabold text-gray-900">Identité du compte</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Nom et type de compte de trésorerie</p>
                        </div>
                    </div>

                    <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nom du compte <span class="text-red-500">*</span></label>
                            <input type="text" name="nom" x-model="nom" value="{{ old('nom') }}" required maxlength="100"
                                   placeholder="Ex : Caisse principale, BICICI Compte courant..."
                                   class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Type de compte <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                @foreach([
                                    'caisse'       => ['Caisse', 'Espèces physiques', '💵'],
                                    'banque'       => ['Banque', 'Compte bancaire', '🏦'],
                                    'mobile_money' => ['Mobile money', 'Wave, OM, MoMo...', '📱'],
                                ] as $val => [$lbl, $desc, $emoji])
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="type" value="{{ $val }}" x-model="type"
                                               @checked(old('type', 'caisse') === $val) required class="sr-only peer">
                                        <div class="p-3 bg-white border border-brand-100 rounded-xl text-center hover:border-brand-300 transition-all peer-checked:bg-gradient-to-br peer-checked:from-brand-50 peer-checked:to-brand-100/50 peer-checked:border-brand-400 peer-checked:shadow-sm">
                                            <p class="text-2xl">{{ $emoji }}</p>
                                            <p class="text-[12px] font-bold text-gray-800 mt-1">{{ $lbl }}</p>
                                            <p class="text-[10px] text-gray-500 mt-0.5">{{ $desc }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ═══════════════ SECTION 2 : DÉTAILS BANCAIRES (bleu) ═══════════════ --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-blue-50/30 rounded-2xl border border-blue-100/60 shadow-card-blue p-6">
                    <div class="absolute -top-10 -left-10 w-40 h-40 bg-blue-200/20 rounded-full blur-3xl"></div>

                    <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-blue-100/60">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-sm shadow-blue-500/30">
                            <span class="font-display text-white font-extrabold text-sm">2</span>
                        </div>
                        <div>
                            <h3 class="font-display text-base font-extrabold text-gray-900">Détails bancaires</h3>
                            <p class="text-xs text-gray-500 mt-0.5">N° de compte, banque/opérateur, plan comptable</p>
                        </div>
                    </div>

                    <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">N° de compte</label>
                            <input type="text" name="numero_compte" value="{{ old('numero_compte') }}" maxlength="50"
                                   placeholder="N° RIB ou téléphone Mobile Money"
                                   class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm font-mono placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Banque / Opérateur</label>
                            <input type="text" name="banque" value="{{ old('banque') }}" maxlength="100"
                                   placeholder="BICICI, Wave, Orange Money..."
                                   class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">N° compte comptable lié</label>
                            <input type="text" name="compte_comptable_numero" value="{{ old('compte_comptable_numero') }}" maxlength="20"
                                   placeholder="Ex : 521000 (banque), 571000 (caisse), 533000 (mobile money)"
                                   class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm font-mono placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                            <p class="text-[11px] text-gray-400 mt-1">Pour le rapprochement automatique avec la comptabilité</p>
                        </div>
                    </div>
                </div>

                {{-- ═══════════════ SECTION 3 : PARAMÈTRES (or) ═══════════════ --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/40 rounded-2xl border border-gold-200/60 shadow-card-gold p-6">
                    <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-gold-200/25 rounded-full blur-3xl"></div>

                    <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-gold-200/60">
                        <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-xl flex items-center justify-center shadow-gold-glow">
                            <span class="font-display text-white font-extrabold text-sm">3</span>
                        </div>
                        <div>
                            <h3 class="font-display text-base font-extrabold text-gray-900">Paramètres financiers</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Solde de départ et statut</p>
                        </div>
                    </div>

                    <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Solde initial (FCFA) <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="number" name="solde_initial" x-model.number="solde" min="0" step="1" value="{{ old('solde_initial', 0) }}" required
                                       class="w-full px-3 py-2.5 pr-14 bg-white border border-gold-200 rounded-xl text-sm font-bold text-gold-700 focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                                <span class="absolute right-3 top-2.5 text-xs font-bold text-gold-600">FCFA</span>
                            </div>
                        </div>

                        <div class="flex items-end">
                            <label class="flex items-center gap-3 p-3 bg-white border border-gold-200 rounded-xl cursor-pointer hover:border-gold-300 transition-all w-full">
                                <input type="hidden" name="principal" value="0">
                                <input type="checkbox" name="principal" value="1" x-model="principal" @checked(old('principal')) class="w-4 h-4 rounded border-gold-300 text-gold-500 focus:ring-gold-200">
                                <span class="text-sm font-semibold text-gray-700">
                                    Définir comme compte <b class="text-gold-700">principal</b>
                                    <span class="block text-[11px] text-gray-500 font-normal mt-0.5">Recevra les paiements par défaut</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ APERÇU ═══════════════ --}}
            <div class="lg:col-span-1">
                <div class="sticky top-20 space-y-4">
                    <div class="relative overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-brand-800 rounded-2xl shadow-brand-glow p-5 text-white">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-gold-300 to-gold-500"></div>
                        <div class="absolute -top-6 -right-6 w-24 h-24 bg-gold-400/20 rounded-full blur-xl"></div>

                        <p class="relative text-[10px] text-brand-100 font-bold uppercase tracking-[0.15em] mb-3">Aperçu compte</p>
                        <h3 class="relative font-display text-xl font-extrabold leading-tight" x-text="nom || 'Nouveau compte'"></h3>
                        <p class="relative text-[11px] text-brand-100 mt-2 capitalize" x-text="typeLabel"></p>

                        <div class="relative mt-4 pt-4 border-t border-white/10">
                            <p class="text-[10px] text-brand-100 font-bold uppercase tracking-wider mb-1">Solde initial</p>
                            <p class="font-display text-2xl font-extrabold text-gold-300" x-text="formatAmount(solde) + ' FCFA'"></p>
                        </div>

                        <div class="relative mt-3" x-show="principal">
                            <span class="inline-block text-[10px] font-bold px-2 py-1 rounded-full bg-gold-300 text-brand-900">⭐ Compte principal</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 pt-2">
            <a href="{{ route('tresorerie.index') }}"
               class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">
                Annuler
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Créer le compte
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function compteForm() {
    return {
        nom: @json(old('nom', '')),
        type: @json(old('type', 'caisse')),
        solde: {{ (int) old('solde_initial', 0) }},
        principal: {{ old('principal') ? 'true' : 'false' }},
        get typeLabel() {
            const labels = { caisse: '💵 Caisse', banque: '🏦 Banque', mobile_money: '📱 Mobile money' };
            return labels[this.type] || '—';
        },
        formatAmount(n) { return new Intl.NumberFormat('fr-FR').format(parseInt(n || 0)); }
    };
}
</script>
@endpush
@endsection
