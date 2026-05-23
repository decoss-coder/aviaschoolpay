@extends('layouts.app')
@section('title', 'Dépense ' . $depense->reference)
@section('page-title', 'Dépense ' . $depense->reference)

@section('content')
@php
    $money = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $statutBadge = [
        'brouillon' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => 'Brouillon', 'ring' => 'ring-gray-200'],
        'soumise'   => ['bg' => 'bg-amber-100', 'text' => 'text-amber-800', 'label' => 'En attente', 'ring' => 'ring-amber-300'],
        'approuvee' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'label' => 'Approuvée', 'ring' => 'ring-emerald-300'],
        'rejetee'   => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Rejetée', 'ring' => 'ring-red-300'],
        'payee'     => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'Payée', 'ring' => 'ring-blue-300'],
        'annulee'   => ['bg' => 'bg-gray-100', 'text' => 'text-gray-500', 'label' => 'Annulée', 'ring' => 'ring-gray-200'],
    ];
    $b = $statutBadge[$depense->statut] ?? $statutBadge['brouillon'];
    $modeIcons = [
        'especes' => '💵', 'cheque' => '🧾', 'virement' => '🏦', 'mobile_money' => '📱', 'carte' => '💳',
    ];
@endphp

<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ session('error') }}</div>
    @endif

    <div class="flex items-center gap-3">
        <a href="{{ route('depenses.index') }}" class="text-sm font-semibold text-gray-500 hover:text-brand-600">← Retour aux dépenses</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Main card --}}
        <div class="lg:col-span-2 space-y-5">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
                <div class="px-6 py-5 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                    <div>
                        <p class="font-mono text-xs font-bold text-gray-500">{{ $depense->reference }}</p>
                        <h2 class="font-display text-2xl font-extrabold text-gray-900 mt-1">{{ $depense->libelle }}</h2>
                        @if($depense->beneficiaire)
                            <p class="text-sm text-gray-500 mt-1">Bénéficiaire : <span class="font-semibold text-gray-700">{{ $depense->beneficiaire }}</span></p>
                        @endif
                    </div>
                    <span class="self-start inline-flex px-3 py-1.5 rounded-xl text-xs font-extrabold ring-2 {{ $b['bg'] }} {{ $b['text'] }} {{ $b['ring'] }}">{{ $b['label'] }}</span>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-5">
                        <div>
                            <p class="text-xs font-bold uppercase text-gray-400 mb-1">Montant</p>
                            <p class="text-2xl font-extrabold text-gray-900">{{ $money($depense->montant) }} <span class="text-sm text-gray-400 font-bold">F</span></p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase text-gray-400 mb-1">Date</p>
                            <p class="font-semibold text-gray-800">{{ $depense->date_depense?->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase text-gray-400 mb-1">Mode</p>
                            <p class="font-semibold text-gray-800">{{ $modeIcons[$depense->mode_paiement] ?? '' }} {{ ucfirst(str_replace('_', ' ', $depense->mode_paiement)) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase text-gray-400 mb-1">Catégorie</p>
                            <p class="font-semibold text-gray-800 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full" style="background:{{ $depense->categorie?->couleur ?: '#94a3b8' }}"></span>
                                {{ $depense->categorie?->nom ?: '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase text-gray-400 mb-1">Fréquence</p>
                            <p class="font-semibold text-gray-800">{{ ucfirst($depense->frequence) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase text-gray-400 mb-1">N° facture</p>
                            <p class="font-semibold text-gray-800">{{ $depense->numero_facture ?: '—' }}</p>
                        </div>
                    </div>

                    @if($depense->description)
                        <div class="mt-6 pt-5 border-t border-gray-100">
                            <p class="text-xs font-bold uppercase text-gray-400 mb-2">Description</p>
                            <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $depense->description }}</p>
                        </div>
                    @endif

                    @if($depense->statut === 'rejetee' && $depense->motif_rejet)
                        <div class="mt-6 pt-5 border-t border-red-100">
                            <p class="text-xs font-bold uppercase text-red-600 mb-2">Motif du rejet</p>
                            <p class="text-sm text-red-800 leading-relaxed bg-red-50 rounded-xl p-3">{{ $depense->motif_rejet }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Workflow timeline --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-6">
                <h3 class="font-extrabold text-gray-900 mb-4">Historique</h3>
                <div class="space-y-4">
                    <div class="flex gap-3">
                        <div class="flex-shrink-0 w-9 h-9 bg-brand-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-brand-700" fill="currentColor" viewBox="0 0 20 20"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900">Dépense soumise</p>
                            <p class="text-xs text-gray-500">par <b>{{ $depense->soumisePar?->name ?? 'Utilisateur supprimé' }}</b> · {{ $depense->created_at?->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>

                    @if($depense->date_approbation)
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-9 h-9 {{ $depense->statut === 'approuvee' ? 'bg-emerald-100' : 'bg-red-100' }} rounded-full flex items-center justify-center">
                                @if($depense->statut === 'approuvee')
                                    <svg class="w-4 h-4 text-emerald-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                @else
                                    <svg class="w-4 h-4 text-red-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900">{{ $depense->statut === 'approuvee' ? 'Dépense approuvée' : 'Dépense rejetée' }}</p>
                                <p class="text-xs text-gray-500">par <b>{{ $depense->approuveePar?->name ?? '—' }}</b> · {{ $depense->date_approbation->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    @endif

                    @if($depense->comptabilisee && $depense->ecriture)
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-9 h-9 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 014-4h6m0 0l-3-3m3 3l-3 3"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900">Comptabilisée — Pièce {{ $depense->ecriture->numero_piece }}</p>
                                <p class="text-xs text-gray-500">Écriture SYSCOHADA générée automatiquement</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Side actions --}}
        <div class="space-y-5">
            @if($depense->statut === 'soumise')
                <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
                    <h3 class="font-extrabold text-gray-900 mb-3">Actions de validation</h3>
                    <form method="POST" action="{{ route('depenses.approuver', $depense->id) }}" onsubmit="return confirm('Approuver cette dépense ? Elle sera comptabilisée et déduite de la trésorerie principale.')">
                        @csrf
                        <button type="submit" class="w-full px-4 py-3 bg-gradient-to-r from-emerald-500 to-emerald-700 text-white text-sm font-bold rounded-xl shadow-card hover:shadow-lg transition flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Approuver
                        </button>
                    </form>

                    <div x-data="{ open: false }" class="mt-3">
                        <button @click="open = true" class="w-full px-4 py-3 bg-white border-2 border-red-200 text-red-700 text-sm font-bold rounded-xl hover:bg-red-50 transition flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Rejeter
                        </button>

                        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                            <form method="POST" action="{{ route('depenses.rejeter', $depense->id) }}" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 mx-4">
                                @csrf
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Motif du rejet</h3>
                                <p class="text-xs text-gray-500 mb-4">Le motif sera communiqué au demandeur.</p>
                                <textarea name="motif_rejet" rows="4" required maxlength="500"
                                          class="w-full rounded-xl border-gray-200 text-sm focus:border-red-400 focus:ring-red-100"
                                          placeholder="Justification du rejet…"></textarea>
                                <div class="mt-4 flex gap-2">
                                    <button type="submit" class="flex-1 px-4 py-2.5 bg-red-600 text-white text-sm font-bold rounded-xl">Confirmer le rejet</button>
                                    <button type="button" @click="open = false" class="px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-xl">Annuler</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
                <h3 class="font-extrabold text-gray-900 mb-3">Informations</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Exercice</dt>
                        <dd class="font-semibold text-gray-800">{{ $depense->exercice?->libelle ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Demandeur</dt>
                        <dd class="font-semibold text-gray-800">{{ $depense->soumisePar?->name ?? '—' }}</dd>
                    </div>
                    @if($depense->ecriture)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Pièce comptable</dt>
                        <dd class="font-mono text-xs font-bold text-blue-700">{{ $depense->ecriture->numero_piece }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Créé le</dt>
                        <dd class="font-semibold text-gray-800">{{ $depense->created_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
