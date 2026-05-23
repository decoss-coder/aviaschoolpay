@extends('layouts.app')
@section('title', 'Budget — ' . $budget->libelle)
@section('page-title', $budget->libelle)
@section('page-subtitle', 'Pilotage budgétaire détaillé')

@section('content')
@php
    $money = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $statutBadge = [
        'brouillon' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => 'Brouillon'],
        'valide'    => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Validé'],
        'en_cours'  => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'label' => 'En cours'],
        'cloture'   => ['bg' => 'bg-gray-100', 'text' => 'text-gray-500', 'label' => 'Clôturé'],
    ];
    $sb = $statutBadge[$budget->statut];
    $editable = $budget->statut !== 'cloture';
    $resPrevu = $budget->resultatPrevu();
    $resReel  = $budget->resultatReel();
    $ecartGlobal = $budget->ecartGlobal();
    $tauxRevenu  = $budget->total_prevu_revenus  > 0 ? round(($budget->total_reel_revenus  / $budget->total_prevu_revenus)  * 100, 1) : 0;
    $tauxDepense = $budget->total_prevu_depenses > 0 ? round(($budget->total_reel_depenses / $budget->total_prevu_depenses) * 100, 1) : 0;
@endphp

<div class="space-y-6" x-data="{ modalLigne: false, ligneType: 'depense' }">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="flex items-center gap-3">
        <a href="{{ route('budgets.index') }}" class="text-sm font-semibold text-gray-500 hover:text-brand-600">← Retour aux budgets</a>
    </div>

    {{-- Header carte --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-6 py-5 bg-gradient-to-r from-indigo-50 via-white to-emerald-50 border-b border-gray-100 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase text-gray-500 tracking-wider">{{ $budget->exercice?->libelle ?? 'Exercice' }} · {{ ucfirst($budget->periodicite) }}</p>
                <h2 class="font-display text-2xl font-extrabold text-gray-900 mt-1">{{ $budget->libelle }}</h2>
                <p class="text-xs text-gray-500 mt-1">
                    Créé par <b class="text-gray-700">{{ $budget->creePar?->name ?? '—' }}</b>
                    @if($budget->valide_par)
                        · Validé par <b class="text-gray-700">{{ $budget->validePar?->name ?? '—' }}</b>
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 self-start">
                <span class="inline-flex px-3 py-1.5 rounded-xl text-xs font-extrabold {{ $sb['bg'] }} {{ $sb['text'] }}">{{ $sb['label'] }}</span>
                @if($budget->statut === 'brouillon')
                    <form method="POST" action="{{ route('budgets.valider', $budget->id) }}" onsubmit="return confirm('Valider ce budget ? Il passera en statut « En cours ».')">
                        @csrf
                        <button class="px-3 py-1.5 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-700">✓ Valider</button>
                    </form>
                @endif
                @if(in_array($budget->statut, ['en_cours', 'valide']))
                    <form method="POST" action="{{ route('budgets.recalculer', $budget->id) }}" onsubmit="return confirm('Rejouer tous les paiements et dépenses de l\'exercice pour reconstruire le réel ?')">
                        @csrf
                        <button class="px-3 py-1.5 bg-blue-600 text-white text-xs font-bold rounded-xl hover:bg-blue-700 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Recalculer
                        </button>
                    </form>
                    <form method="POST" action="{{ route('budgets.cloturer', $budget->id) }}" onsubmit="return confirm('Clôturer définitivement ce budget ?')">
                        @csrf
                        <button class="px-3 py-1.5 bg-gray-700 text-white text-xs font-bold rounded-xl hover:bg-gray-900">Clôturer</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- KPIs résumés --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-gray-100">
            <div class="p-5">
                <p class="text-xs font-bold uppercase text-blue-600 tracking-wider mb-1">Revenus</p>
                <p class="text-xl font-extrabold text-gray-900">{{ $money($budget->total_reel_revenus) }} F</p>
                <p class="text-xs text-gray-500">sur {{ $money($budget->total_prevu_revenus) }} prévu</p>
                <div class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500 rounded-full" style="width:{{ min(100, $tauxRevenu) }}%"></div>
                </div>
                <p class="text-xs font-bold text-blue-700 mt-1">{{ $tauxRevenu }}%</p>
            </div>
            <div class="p-5">
                <p class="text-xs font-bold uppercase text-rose-600 tracking-wider mb-1">Dépenses</p>
                <p class="text-xl font-extrabold text-gray-900">{{ $money($budget->total_reel_depenses) }} F</p>
                <p class="text-xs text-gray-500">sur {{ $money($budget->total_prevu_depenses) }} prévu</p>
                <div class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full {{ $tauxDepense >= 100 ? 'bg-red-600' : ($tauxDepense >= 90 ? 'bg-amber-500' : 'bg-rose-500') }} rounded-full" style="width:{{ min(100, $tauxDepense) }}%"></div>
                </div>
                <p class="text-xs font-bold {{ $tauxDepense >= 90 ? 'text-red-700' : 'text-rose-700' }} mt-1">{{ $tauxDepense }}% @if($tauxDepense >= 90) ⚠ @endif</p>
            </div>
            <div class="p-5">
                <p class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-1">Résultat prévu</p>
                <p class="text-xl font-extrabold {{ $resPrevu >= 0 ? 'text-brand-700' : 'text-red-700' }}">{{ $money($resPrevu) }} F</p>
                <p class="text-xs text-gray-500">Réel : {{ $money($resReel) }} F</p>
            </div>
            <div class="p-5">
                <p class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-1">Écart global</p>
                <p class="text-xl font-extrabold {{ $ecartGlobal >= 0 ? 'text-brand-700' : 'text-red-700' }}">{{ $money($ecartGlobal) }} F</p>
                <p class="text-xs text-gray-500">Réel vs prévu</p>
            </div>
        </div>
    </div>

    {{-- Encadré pédagogique alimentation auto --}}
    <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-900">
        <p class="font-bold mb-1">ℹ Alimentation automatique du « Réel »</p>
        <ul class="list-disc list-inside space-y-0.5 leading-relaxed">
            <li><b>Lignes revenus</b> : le <code class="bg-white px-1 rounded">montant_reel</code> s'incrémente automatiquement à chaque paiement confirmé selon le N° compte comptable (<code class="bg-white px-1 rounded">706100</code> = scolarité, <code class="bg-white px-1 rounded">706200</code> = inscription).</li>
            <li><b>Lignes dépenses</b> : alimentées à chaque dépense approuvée correspondant à la catégorie ou au N° compte de la ligne.</li>
            <li>Pour les budgets créés en cours d'année, cliquez sur <b>Recalculer</b> pour rejouer l'historique.</li>
        </ul>
    </div>

    {{-- Actions --}}
    @if($editable)
        <div class="flex justify-end gap-2">
            <button @click="ligneType = 'revenu'; modalLigne = true" class="px-4 py-2 bg-white border-2 border-blue-200 text-blue-700 text-sm font-bold rounded-xl hover:bg-blue-50">+ Ligne revenu</button>
            <button @click="ligneType = 'depense'; modalLigne = true" class="px-4 py-2 bg-white border-2 border-rose-200 text-rose-700 text-sm font-bold rounded-xl hover:bg-rose-50">+ Ligne dépense</button>
        </div>
    @endif

    {{-- Tables lignes --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        {{-- Revenus --}}
        <div class="bg-white rounded-2xl border border-blue-100 shadow-card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-blue-50/50 flex items-center justify-between">
                <h3 class="font-extrabold text-blue-800">Lignes de revenus</h3>
                <span class="text-xs font-bold text-blue-600">{{ $lignesRevenus->count() }} ligne(s)</span>
            </div>
            @if($lignesRevenus->isEmpty())
                <div class="px-5 py-10 text-center text-sm text-gray-500">Aucune ligne de revenu.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold">Libellé</th>
                                <th class="px-4 py-3 text-right font-bold">Prévu</th>
                                <th class="px-4 py-3 text-right font-bold">Réel</th>
                                <th class="px-4 py-3 text-center font-bold">%</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($lignesRevenus as $l)
                                <tr class="hover:bg-blue-50/30">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900">{{ $l->libelle }}</div>
                                        <div class="text-xs text-gray-500 flex items-center gap-2 mt-0.5">
                                            <span>{{ ucfirst($l->service) }}</span>
                                            @if($l->compte_comptable_numero)
                                                <span class="font-mono bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded text-[10px] font-bold" title="Compte alimenté par paiements">⟲ {{ $l->compte_comptable_numero }}</span>
                                            @else
                                                <span class="text-amber-600 text-[10px] font-bold">⚠ Pas de compte lié — non alimenté</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-800">{{ $money($l->montant_prevu) }} F</td>
                                    <td class="px-4 py-3 text-right font-bold text-blue-700">{{ $money($l->montant_reel) }} F</td>
                                    <td class="px-4 py-3 text-center text-xs font-bold {{ $l->taux_realisation >= 100 ? 'text-emerald-700' : 'text-gray-600' }}">{{ $l->taux_realisation }}%</td>
                                    <td class="px-4 py-3 text-right">
                                        @if($editable)
                                            <form method="POST" action="{{ route('budgets.lignes.destroy', [$budget->id, $l->id]) }}" onsubmit="return confirm('Supprimer cette ligne ?')" class="inline">
                                                @csrf @method('DELETE')
                                                <button class="text-red-500 hover:text-red-700 text-xs font-bold">×</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Dépenses --}}
        <div class="bg-white rounded-2xl border border-rose-100 shadow-card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-rose-50/50 flex items-center justify-between">
                <h3 class="font-extrabold text-rose-800">Lignes de dépenses</h3>
                <span class="text-xs font-bold text-rose-600">{{ $lignesDepenses->count() }} ligne(s)</span>
            </div>
            @if($lignesDepenses->isEmpty())
                <div class="px-5 py-10 text-center text-sm text-gray-500">Aucune ligne de dépense.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold">Libellé</th>
                                <th class="px-4 py-3 text-right font-bold">Prévu</th>
                                <th class="px-4 py-3 text-right font-bold">Réel</th>
                                <th class="px-4 py-3 text-center font-bold">%</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($lignesDepenses as $l)
                                <tr class="hover:bg-rose-50/30">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900">{{ $l->libelle }}</div>
                                        <div class="text-xs text-gray-500 flex flex-wrap items-center gap-2 mt-0.5">
                                            <span>{{ ucfirst($l->service) }}</span>
                                            @if($l->categorieDepense)
                                                <span class="bg-rose-100 text-rose-700 px-1.5 py-0.5 rounded text-[10px] font-bold flex items-center gap-1" title="Catégorie de dépense alimentant la ligne">
                                                    <span class="w-1.5 h-1.5 rounded-full" style="background:{{ $l->categorieDepense->couleur ?: '#94a3b8' }}"></span>
                                                    ⟲ {{ $l->categorieDepense->nom }}
                                                </span>
                                            @elseif($l->compte_comptable_numero)
                                                <span class="font-mono bg-rose-100 text-rose-700 px-1.5 py-0.5 rounded text-[10px] font-bold">⟲ {{ $l->compte_comptable_numero }}</span>
                                            @else
                                                <span class="text-amber-600 text-[10px] font-bold">⚠ Pas de catégorie/compte — non alimenté</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-800">{{ $money($l->montant_prevu) }} F</td>
                                    <td class="px-4 py-3 text-right font-bold {{ $l->alerte_depassement ? 'text-red-700' : 'text-rose-700' }}">
                                        {{ $money($l->montant_reel) }} F
                                        @if($l->alerte_depassement) <span class="text-xs">⚠</span>@endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-xs font-bold {{ $l->alerte_depassement ? 'text-red-700' : 'text-gray-600' }}">{{ $l->taux_realisation }}%</td>
                                    <td class="px-4 py-3 text-right">
                                        @if($editable)
                                            <form method="POST" action="{{ route('budgets.lignes.destroy', [$budget->id, $l->id]) }}" onsubmit="return confirm('Supprimer cette ligne ?')" class="inline">
                                                @csrf @method('DELETE')
                                                <button class="text-red-500 hover:text-red-700 text-xs font-bold">×</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal ajout ligne --}}
    <div x-show="modalLigne" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="modalLigne = false">
        <form method="POST" :action="ligneType === 'revenu' ? '{{ route('budgets.lignes.store', $budget->id) }}' : '{{ route('budgets.lignes.store', $budget->id) }}'" class="bg-white rounded-2xl shadow-xl w-full max-w-xl">
            @csrf
            <input type="hidden" name="type" :value="ligneType" />
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-extrabold text-gray-900">
                    Nouvelle ligne <span x-text="ligneType === 'revenu' ? 'revenu' : 'dépense'"></span>
                </h3>
                <button type="button" @click="modalLigne = false" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Libellé *</label>
                    <input name="libelle" required maxlength="200" class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Service *</label>
                        <select name="service" required class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100">
                            @foreach(['scolarite' => 'Scolarité', 'cantine' => 'Cantine', 'transport' => 'Transport', 'activites' => 'Activités', 'salaires' => 'Salaires', 'fonctionnement' => 'Fonctionnement', 'investissement' => 'Investissement', 'autre' => 'Autre'] as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Montant prévu (F) *</label>
                        <input name="montant_prevu" type="number" min="0" required class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100" />
                    </div>
                </div>
                <div x-show="ligneType === 'depense'" class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Catégorie dépense</label>
                        <select name="categorie_depense_id" class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100">
                            <option value="">— Aucune —</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Seuil alerte (%)</label>
                        <input name="seuil_alerte_pourcent" type="number" min="1" max="200" value="90" class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">N° compte comptable</label>
                    <input name="compte_comptable_numero" maxlength="20" class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Observations</label>
                    <textarea name="observations" rows="2" class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-2 justify-end">
                <button type="button" @click="modalLigne = false" class="px-4 py-2 text-sm font-bold text-gray-600">Annuler</button>
                <button type="submit" class="px-6 py-2 bg-brand-600 text-white text-sm font-bold rounded-xl hover:bg-brand-700">Ajouter la ligne</button>
            </div>
        </form>
    </div>
</div>

@push('styles')<style>[x-cloak]{display:none!important}</style>@endpush
@endsection
