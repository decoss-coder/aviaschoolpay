@extends('layouts.app')
@section('title', 'Trésorerie')
@section('page-title', 'Trésorerie')
@section('page-subtitle', 'Soldes, mouvements et virements en temps réel')

@section('content')
@php
    $money = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $typeColors = [
        'caisse'        => ['bg' => 'from-emerald-500 to-emerald-700', 'icon' => '💵', 'label' => 'Caisse'],
        'banque'        => ['bg' => 'from-blue-500 to-blue-700', 'icon' => '🏦', 'label' => 'Banque'],
        'mobile_money'  => ['bg' => 'from-violet-500 to-violet-700', 'icon' => '📱', 'label' => 'Mobile Money'],
    ];
    $solde30j = (int) $entrees - (int) $sorties;
@endphp

<div class="space-y-6" x-data="{ modalCompte: false, modalVirement: false }">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-700 rounded-xl flex items-center justify-center shadow-card-brand">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Module 13</p>
                <h2 class="font-display text-2xl font-extrabold text-gray-900">Trésorerie</h2>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('tresorerie.mouvements') }}" class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-white border border-gray-200 text-gray-700 hover:border-brand-300 hover:text-brand-700 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Mouvements
            </a>
            <button @click="modalVirement = true" {{ $comptes->count() < 2 ? 'disabled' : '' }}
                    class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-white border border-violet-200 text-violet-700 hover:bg-violet-50 transition flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                Virement interne
            </button>
            <button @click="modalCompte = true"
                    class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-brand-500 to-brand-700 text-white shadow-brand-glow hover:shadow-lg transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Nouveau compte
            </button>
        </div>
    </div>

    {{-- KPI globaux --}}
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-brand-600 to-brand-800 rounded-2xl p-5 shadow-card-brand text-white">
            <p class="text-xs font-bold uppercase text-brand-100 tracking-wider mb-3">Trésorerie totale</p>
            <p class="text-3xl font-extrabold">{{ $money($totalTreso) }} <span class="text-sm font-bold opacity-80">F</span></p>
            <p class="text-xs text-brand-100 mt-1">{{ $comptes->count() }} compte(s) actif(s)</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-3">Caisse</p>
            <p class="text-2xl font-extrabold text-emerald-700">{{ $money($totalCaisse) }} <span class="text-sm text-gray-400 font-bold">F</span></p>
            <p class="text-xs text-gray-500 mt-1">{{ $comptes->where('type', 'caisse')->count() }} compte(s)</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-3">Banque</p>
            <p class="text-2xl font-extrabold text-blue-700">{{ $money($totalBanque) }} <span class="text-sm text-gray-400 font-bold">F</span></p>
            <p class="text-xs text-gray-500 mt-1">{{ $comptes->where('type', 'banque')->count() }} compte(s)</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-3">Mobile Money</p>
            <p class="text-2xl font-extrabold text-violet-700">{{ $money($totalMM) }} <span class="text-sm text-gray-400 font-bold">F</span></p>
            <p class="text-xs text-gray-500 mt-1">{{ $comptes->where('type', 'mobile_money')->count() }} compte(s)</p>
        </div>
    </section>

    {{-- Comptes & graphique --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        {{-- Comptes --}}
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-card p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-extrabold text-gray-900">Comptes de trésorerie</h3>
                @if($comptes->isNotEmpty())
                    <span class="text-xs font-semibold text-gray-500">{{ $comptes->count() }} actif(s)</span>
                @endif
            </div>

            @if($comptes->isEmpty())
                <div class="text-center py-10">
                    <p class="font-bold text-gray-800">Aucun compte de trésorerie</p>
                    <p class="text-sm text-gray-500 mt-1 mb-4">Créez votre première caisse ou compte bancaire.</p>
                    <button @click="modalCompte = true" class="px-4 py-2 bg-brand-600 text-white text-sm font-bold rounded-xl">Créer un compte</button>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($comptes as $compte)
                        @php $tc = $typeColors[$compte->type] ?? $typeColors['caisse']; @endphp
                        <div class="relative rounded-2xl border border-gray-100 overflow-hidden hover:shadow-card-hover transition group">
                            <div class="h-1 bg-gradient-to-r {{ $tc['bg'] }}"></div>
                            <div class="p-4">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-2xl">{{ $tc['icon'] }}</span>
                                        <div>
                                            <p class="font-bold text-gray-900 leading-tight">{{ $compte->nom }}</p>
                                            <p class="text-xs text-gray-500">{{ $tc['label'] }}{{ $compte->banque ? ' · '.$compte->banque : '' }}{{ $compte->operateur ? ' · '.$compte->operateur : '' }}</p>
                                        </div>
                                    </div>
                                    @if($compte->principal)
                                        <span class="text-[10px] bg-gold-100 text-gold-600 font-extrabold px-2 py-0.5 rounded-full uppercase tracking-wider">Principal</span>
                                    @endif
                                </div>
                                <p class="text-2xl font-extrabold text-gray-900">{{ $money($compte->solde_actuel) }} <span class="text-xs text-gray-400 font-bold">F</span></p>
                                @if($compte->numero_compte)
                                    <p class="text-xs font-mono text-gray-400 mt-1">{{ $compte->numero_compte }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Flux 30j --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
            <h3 class="font-extrabold text-gray-900 mb-1">Flux 30 jours</h3>
            <p class="text-xs text-gray-500 mb-4">Évolution des entrées/sorties</p>

            <div class="grid grid-cols-3 gap-2 mb-4">
                <div class="text-center">
                    <p class="text-[10px] font-bold uppercase text-emerald-600">Entrées</p>
                    <p class="text-sm font-extrabold text-emerald-700">{{ $money($entrees) }} F</p>
                </div>
                <div class="text-center">
                    <p class="text-[10px] font-bold uppercase text-red-600">Sorties</p>
                    <p class="text-sm font-extrabold text-red-700">{{ $money($sorties) }} F</p>
                </div>
                <div class="text-center">
                    <p class="text-[10px] font-bold uppercase {{ $solde30j >= 0 ? 'text-brand-600' : 'text-red-600' }}">Solde</p>
                    <p class="text-sm font-extrabold {{ $solde30j >= 0 ? 'text-brand-700' : 'text-red-700' }}">{{ $money($solde30j) }} F</p>
                </div>
            </div>

            <div class="h-40">
                <canvas id="fluxChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Derniers mouvements --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-extrabold text-gray-900">Derniers mouvements</h3>
            <a href="{{ route('tresorerie.mouvements') }}" class="text-xs font-bold text-brand-600 hover:text-brand-800">Voir tout →</a>
        </div>

        @if($derniersMouvements->isEmpty())
            <div class="px-5 py-10 text-center text-sm text-gray-500">Aucun mouvement enregistré.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-5 py-3 text-left font-bold">Date</th>
                            <th class="px-5 py-3 text-left font-bold">Compte</th>
                            <th class="px-5 py-3 text-left font-bold">Libellé</th>
                            <th class="px-5 py-3 text-center font-bold">Sens</th>
                            <th class="px-5 py-3 text-right font-bold">Montant</th>
                            <th class="px-5 py-3 text-right font-bold">Solde après</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($derniersMouvements as $m)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 text-xs text-gray-600">{{ $m->date_mouvement?->format('d/m/Y') }}</td>
                                <td class="px-5 py-3 font-semibold text-gray-800">{{ $m->compte?->nom ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ $m->libelle }}</td>
                                <td class="px-5 py-3 text-center">
                                    @if($m->sens === 'entree')
                                        <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-700">↑ Entrée</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold bg-red-100 text-red-700">↓ Sortie</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right font-extrabold {{ $m->sens === 'entree' ? 'text-emerald-700' : 'text-red-700' }}">
                                    {{ $m->sens === 'entree' ? '+' : '−' }}{{ $money($m->montant) }} F
                                </td>
                                <td class="px-5 py-3 text-right text-xs font-bold text-gray-600">{{ $money($m->solde_apres) }} F</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Modal : Nouveau compte --}}
    <div x-show="modalCompte" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="modalCompte = false">
        <form method="POST" action="{{ route('tresorerie.store') }}" class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            @csrf
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-gray-900">Nouveau compte de trésorerie</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Caisse, banque ou mobile money</p>
                </div>
                <button type="button" @click="modalCompte = false" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Nom du compte *</label>
                        <input name="nom" required maxlength="100" class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100" placeholder="Ex: Caisse principale" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Type *</label>
                        <select name="type" required class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100">
                            <option value="caisse">Caisse</option>
                            <option value="banque">Banque</option>
                            <option value="mobile_money">Mobile Money</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Solde initial (F) *</label>
                        <input name="solde_initial" type="number" min="0" step="1" value="0" required class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">N° de compte</label>
                        <input name="numero_compte" maxlength="50" class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Banque / Opérateur</label>
                        <input name="banque" maxlength="100" class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100" placeholder="Ex: BICICI, Wave…" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">N° compte comptable</label>
                        <input name="compte_comptable_numero" maxlength="20" class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100" placeholder="Ex: 521000" />
                    </div>
                    <div class="sm:col-span-2 flex items-center gap-2 p-3 bg-gold-50 rounded-xl">
                        <input type="hidden" name="principal" value="0" />
                        <input type="checkbox" name="principal" value="1" id="chk-principal" class="rounded text-gold-500" />
                        <label for="chk-principal" class="text-sm font-semibold text-gray-700">Définir comme compte <b>principal</b></label>
                        <span class="text-xs text-gray-500 ml-2">(Les dépenses approuvées passent par ce compte)</span>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-2 justify-end">
                <button type="button" @click="modalCompte = false" class="px-4 py-2 text-sm font-bold text-gray-600 hover:text-gray-900">Annuler</button>
                <button type="submit" class="px-6 py-2 bg-brand-600 text-white text-sm font-bold rounded-xl hover:bg-brand-700">Créer le compte</button>
            </div>
        </form>
    </div>

    {{-- Modal : Virement interne --}}
    <div x-show="modalVirement" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="modalVirement = false">
        <form method="POST" action="{{ route('tresorerie.virement') }}" class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
            @csrf
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-extrabold text-gray-900">Virement interne</h3>
                <button type="button" @click="modalVirement = false" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Compte source *</label>
                    <select name="compte_source_id" required class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100">
                        <option value="">— Sélectionner —</option>
                        @foreach($comptes as $c)
                            <option value="{{ $c->id }}">{{ $c->nom }} — {{ $money($c->solde_actuel) }} F</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Compte destination *</label>
                    <select name="compte_destination_id" required class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100">
                        <option value="">— Sélectionner —</option>
                        @foreach($comptes as $c)
                            <option value="{{ $c->id }}">{{ $c->nom }} — {{ $money($c->solde_actuel) }} F</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Montant *</label>
                        <input name="montant" type="number" min="1" required class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Date *</label>
                        <input name="date_virement" type="date" value="{{ now()->format('Y-m-d') }}" required class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Motif</label>
                    <input name="motif" maxlength="200" class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100" placeholder="Ex: Réapprovisionnement caisse" />
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-2 justify-end">
                <button type="button" @click="modalVirement = false" class="px-4 py-2 text-sm font-bold text-gray-600 hover:text-gray-900">Annuler</button>
                <button type="submit" class="px-6 py-2 bg-violet-600 text-white text-sm font-bold rounded-xl hover:bg-violet-700">Effectuer le virement</button>
            </div>
        </form>
    </div>

    @if($errors->any())
        <script>document.addEventListener('alpine:init', () => {});</script>
    @endif
</div>

@push('styles')<style>[x-cloak]{display:none!important}</style>@endpush

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('fluxChart');
    if (!ctx) return;
    const data = @json($fluxQuotidiens);
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.date),
            datasets: [
                { label: 'Entrées', data: data.map(d => d.entree), backgroundColor: 'rgba(16, 185, 129, 0.7)', borderRadius: 4 },
                { label: 'Sorties', data: data.map(d => d.sortie), backgroundColor: 'rgba(239, 68, 68, 0.7)', borderRadius: 4 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0 } },
                y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 9 }, callback: v => (v/1000) + 'k' } }
            }
        }
    });
});
</script>
@endsection
