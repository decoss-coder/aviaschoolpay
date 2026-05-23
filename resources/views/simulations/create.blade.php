@extends('layouts.app')

@section('title', 'Nouveau scénario')
@section('page-title', 'Nouveau scénario de simulation')
@section('page-subtitle', "Modélisez l'impact d'une décision financière")

@section('content')
<div class="max-w-5xl mx-auto" x-data="{ type: '{{ old('type', 'augmentation_effectif') }}' }">

    <form method="POST" action="{{ route('simulations.store') }}" class="space-y-6">
        @csrf

        {{-- Retour --}}
        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('simulations.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour aux simulations
            </a>
        </div>

        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
            </div>
        @endif

        {{-- ═══════════════ SECTION 1 : DÉFINITION (bleu cyan ≈ brand) ═══════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-blue-50/30 rounded-2xl border border-blue-100/60 shadow-card-blue p-6">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-blue-200/20 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-blue-100/60">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-sm shadow-blue-500/30">
                    <span class="font-display text-white font-extrabold text-sm">1</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Définition du scénario</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Nom, type d'impact et horizon temporel</p>
                </div>
            </div>

            <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nom du scénario <span class="text-red-500">*</span></label>
                    <input type="text" name="nom" value="{{ old('nom') }}" required
                           placeholder="Ex : Hausse scolarité +10% en 2026"
                           class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Type <span class="text-red-500">*</span></label>
                    <select name="type" x-model="type" required
                            class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm cursor-pointer">
                        <option value="augmentation_effectif">👥 Augmentation effectif</option>
                        <option value="reduction_effectif">👥 Réduction effectif</option>
                        <option value="augmentation_tarif">⬆ Augmentation tarif</option>
                        <option value="reduction_tarif">⬇ Réduction tarif</option>
                        <option value="recrutement">🧑‍🏫 Recrutement</option>
                        <option value="reduction_personnel">🚪 Réduction personnel</option>
                        <option value="reduction_couts">✂ Réduction coûts</option>
                        <option value="ajout_service">➕ Ajout service</option>
                        <option value="investissement">🏗 Investissement</option>
                        <option value="scenario_libre">✏ Scénario libre</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Horizon <span class="text-red-500">*</span></label>
                    <select name="horizon" required
                            class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm cursor-pointer">
                        <option value="3_mois">3 mois</option>
                        <option value="6_mois">6 mois</option>
                        <option value="1_an" selected>1 an</option>
                        <option value="2_ans">2 ans</option>
                        <option value="3_ans">3 ans</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Description (facultatif)</label>
                    <textarea name="description" rows="2" placeholder="Hypothèses, justification du scénario..."
                              class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm resize-none">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ═══════════════ SECTION 2 : PARAMÈTRES (or) ═══════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/40 rounded-2xl border border-gold-200/60 shadow-card-gold p-6">
            <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-gold-200/25 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-gold-200/60">
                <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-xl flex items-center justify-center shadow-gold-glow">
                    <span class="font-display text-white font-extrabold text-sm">2</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Paramètres du scénario</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Valeurs utilisées pour le calcul d'impact</p>
                </div>
            </div>

            <div class="relative">
                {{-- Augmentation/Réduction effectif --}}
                <div x-show="['augmentation_effectif', 'reduction_effectif'].includes(type)" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nombre d'élèves concernés</label>
                        <input type="number" name="parametres[nb_eleves]" min="1" value="50"
                               class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Coût marginal / élève (FCFA)</label>
                        <input type="number" name="parametres[cout_par_eleve]" min="0" value="30000"
                               class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                    </div>
                    <p class="md:col-span-2 text-[11px] text-gray-500">Le revenu marginal est calculé sur la base du revenu moyen par élève actuel.</p>
                </div>

                {{-- Augmentation/Réduction tarif --}}
                <div x-show="['augmentation_tarif', 'reduction_tarif'].includes(type)" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Variation (%)</label>
                        <input type="number" name="parametres[pourcentage]" min="0" step="0.1" value="10"
                               class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                    </div>
                </div>

                {{-- Recrutement / Réduction personnel --}}
                <div x-show="['recrutement', 'reduction_personnel'].includes(type)" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nombre de personnes</label>
                        <input type="number" name="parametres[nb_personnes]" min="1" value="2"
                               class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Salaire mensuel moyen (FCFA)</label>
                        <input type="number" name="parametres[salaire_moyen]" min="0" value="200000"
                               class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                    </div>
                </div>

                {{-- Réduction coûts --}}
                <div x-show="type === 'reduction_couts'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Pourcentage de réduction (%)</label>
                        <input type="number" name="parametres[pourcentage]" min="0" max="100" step="0.1" value="10"
                               class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                    </div>
                </div>

                {{-- Ajout service --}}
                <div x-show="type === 'ajout_service'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Revenu annuel attendu (FCFA)</label>
                        <input type="number" name="parametres[revenu_annuel]" min="0"
                               class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Coût annuel (FCFA)</label>
                        <input type="number" name="parametres[cout_annuel]" min="0"
                               class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                    </div>
                </div>

                {{-- Investissement --}}
                <div x-show="type === 'investissement'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Coût investissement (FCFA)</label>
                        <input type="number" name="parametres[cout_investissement]" min="0"
                               class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Revenu annuel généré (FCFA)</label>
                        <input type="number" name="parametres[revenu_annuel_genere]" min="0"
                               class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                    </div>
                </div>

                {{-- Scénario libre --}}
                <div x-show="type === 'scenario_libre'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Impact revenus (FCFA)</label>
                        <input type="number" name="parametres[impact_revenus]" placeholder="Positif ou négatif"
                               class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Impact dépenses (FCFA)</label>
                        <input type="number" name="parametres[impact_depenses]" placeholder="Positif ou négatif"
                               class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 pt-2">
            <a href="{{ route('simulations.index') }}"
               class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">
                Annuler
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
                🧮 Calculer le scénario
            </button>
        </div>
    </form>
</div>
@endsection
