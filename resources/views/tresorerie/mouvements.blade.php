@extends('layouts.app')
@section('title', 'Mouvements de trésorerie')
@section('page-title', 'Mouvements de trésorerie')
@section('page-subtitle', 'Historique complet des entrées et sorties')

@section('content')
@php
    $money = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $sensColor = ['entree' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'sign' => '+', 'label' => 'Entrée'],
                  'sortie' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'sign' => '−', 'label' => 'Sortie']];
@endphp

<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('tresorerie.index') }}" class="text-sm font-semibold text-gray-500 hover:text-brand-600">← Retour à la trésorerie</a>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-emerald-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-emerald-600 tracking-wider mb-2">Total entrées</p>
            <p class="text-2xl font-extrabold text-emerald-700">+{{ $money($totalEntrees) }} <span class="text-sm font-bold opacity-70">F</span></p>
        </div>
        <div class="bg-white rounded-2xl border border-red-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-red-600 tracking-wider mb-2">Total sorties</p>
            <p class="text-2xl font-extrabold text-red-700">−{{ $money($totalSorties) }} <span class="text-sm font-bold opacity-70">F</span></p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-2">Solde net</p>
            @php $net = (int) $totalEntrees - (int) $totalSorties; @endphp
            <p class="text-2xl font-extrabold {{ $net >= 0 ? 'text-brand-700' : 'text-red-700' }}">{{ $money($net) }} <span class="text-sm text-gray-400 font-bold">F</span></p>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Compte</label>
                <select name="compte" class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100">
                    <option value="">Tous</option>
                    @foreach($comptes as $c)
                        <option value="{{ $c->id }}" @selected(request('compte') == $c->id)>{{ $c->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Sens</label>
                <select name="sens" class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100">
                    <option value="">Tous</option>
                    <option value="entree" @selected(request('sens') === 'entree')>Entrées</option>
                    <option value="sortie" @selected(request('sens') === 'sortie')>Sorties</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Du</label>
                <input type="date" name="date_debut" value="{{ request('date_debut') }}" class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100" />
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Au</label>
                <input type="date" name="date_fin" value="{{ request('date_fin') }}" class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100" />
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-brand-600 text-white text-sm font-bold rounded-xl hover:bg-brand-700">Filtrer</button>
                <a href="{{ route('tresorerie.mouvements') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-bold rounded-xl">⟲</a>
            </div>
        </form>
    </div>

    {{-- Tableau --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-extrabold text-gray-900">Tous les mouvements</h3>
            <span class="text-xs font-semibold text-gray-500">{{ $mouvements->total() }} résultat(s)</span>
        </div>

        @if($mouvements->isEmpty())
            <div class="px-5 py-16 text-center text-sm text-gray-500">Aucun mouvement trouvé.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-5 py-3 text-left font-bold">Date</th>
                            <th class="px-5 py-3 text-left font-bold">Compte</th>
                            <th class="px-5 py-3 text-left font-bold">Libellé</th>
                            <th class="px-5 py-3 text-left font-bold">Réf. interne</th>
                            <th class="px-5 py-3 text-center font-bold">Sens</th>
                            <th class="px-5 py-3 text-right font-bold">Montant</th>
                            <th class="px-5 py-3 text-right font-bold">Solde après</th>
                            <th class="px-5 py-3 text-left font-bold">Auteur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($mouvements as $m)
                            @php $sc = $sensColor[$m->sens]; @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 text-xs text-gray-600 whitespace-nowrap">{{ $m->date_mouvement?->format('d/m/Y') }}</td>
                                <td class="px-5 py-3 font-semibold text-gray-800">{{ $m->compte?->nom ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ $m->libelle }}</td>
                                <td class="px-5 py-3 text-xs">
                                    @if($m->reference_type)
                                        <span class="font-mono text-gray-500">{{ $m->reference_type }}#{{ $m->reference_id ?? '—' }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold {{ $sc['bg'] }} {{ $sc['text'] }}">{{ $sc['label'] }}</span>
                                </td>
                                <td class="px-5 py-3 text-right font-extrabold {{ $sc['text'] }}">{{ $sc['sign'] }}{{ $money($m->montant) }} F</td>
                                <td class="px-5 py-3 text-right text-xs font-bold text-gray-700">{{ $money($m->solde_apres) }} F</td>
                                <td class="px-5 py-3 text-xs text-gray-600">{{ $m->saisiePar?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-gray-100">{{ $mouvements->links() }}</div>
        @endif
    </div>
</div>
@endsection
