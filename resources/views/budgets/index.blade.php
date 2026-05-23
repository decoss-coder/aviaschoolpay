@extends('layouts.app')
@section('title', 'Budgets')
@section('page-title', 'Budgets')
@section('page-subtitle', 'Pilotage prévisionnel et suivi des écarts')

@section('content')
@php
    $money = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $statutBadge = [
        'brouillon' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => 'Brouillon'],
        'valide'    => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Validé'],
        'en_cours'  => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'label' => 'En cours'],
        'cloture'   => ['bg' => 'bg-gray-100', 'text' => 'text-gray-500', 'label' => 'Clôturé'],
    ];
    $resultatPrevu = (int) $totalPrevuRevenus - (int) $totalPrevuDepenses;
    $resultatReel  = (int) $totalReelRevenus  - (int) $totalReelDepenses;
@endphp

<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-700 rounded-xl flex items-center justify-center shadow-card-violet">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Module 14</p>
                <h2 class="font-display text-2xl font-extrabold text-gray-900">Budgets & pilotage</h2>
            </div>
        </div>
        <a href="{{ route('budgets.create') }}" class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-brand-500 to-brand-700 text-white shadow-brand-glow hover:shadow-lg transition flex items-center gap-2 self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Nouveau budget
        </a>
    </div>

    {{-- KPIs --}}
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-3">Budgets actifs</p>
            <p class="text-2xl font-extrabold text-gray-900">{{ $totalActifs }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $budgets->count() }} au total</p>
        </div>
        <div class="bg-white rounded-2xl border border-blue-100 p-5 shadow-card-blue">
            <p class="text-xs font-bold uppercase text-blue-600 tracking-wider mb-3">Revenus prévus</p>
            <p class="text-2xl font-extrabold text-blue-700">{{ $money($totalPrevuRevenus) }} <span class="text-sm text-gray-400 font-bold">F</span></p>
            <p class="text-xs text-gray-500 mt-1">Réel : {{ $money($totalReelRevenus) }} F</p>
        </div>
        <div class="bg-white rounded-2xl border border-rose-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-rose-600 tracking-wider mb-3">Dépenses prévues</p>
            <p class="text-2xl font-extrabold text-rose-700">{{ $money($totalPrevuDepenses) }} <span class="text-sm text-gray-400 font-bold">F</span></p>
            <p class="text-xs text-gray-500 mt-1">Réel : {{ $money($totalReelDepenses) }} F</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-3">Résultat prévu</p>
            <p class="text-2xl font-extrabold {{ $resultatPrevu >= 0 ? 'text-brand-700' : 'text-red-700' }}">{{ $money($resultatPrevu) }} <span class="text-sm text-gray-400 font-bold">F</span></p>
            <p class="text-xs text-gray-500 mt-1">Réel : <span class="font-bold {{ $resultatReel >= 0 ? 'text-brand-700' : 'text-red-600' }}">{{ $money($resultatReel) }} F</span></p>
        </div>
    </section>

    {{-- Liste --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-extrabold text-gray-900">Liste des budgets</h3>
            <span class="text-xs font-semibold text-gray-500">{{ $budgets->count() }} budget(s)</span>
        </div>

        @if($budgets->isEmpty())
            <div class="px-5 py-16 text-center">
                <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2z"/></svg>
                </div>
                <p class="font-bold text-gray-800">Aucun budget créé</p>
                <p class="text-sm text-gray-500 mt-1 mb-4">Établissez votre premier prévisionnel.</p>
                <a href="{{ route('budgets.create') }}" class="inline-flex items-center px-4 py-2 bg-brand-600 text-white text-sm font-bold rounded-xl">Nouveau budget</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-5 py-3 text-left font-bold">Libellé</th>
                            <th class="px-5 py-3 text-left font-bold">Exercice</th>
                            <th class="px-5 py-3 text-center font-bold">Lignes</th>
                            <th class="px-5 py-3 text-right font-bold">Revenus (P / R)</th>
                            <th class="px-5 py-3 text-right font-bold">Dépenses (P / R)</th>
                            <th class="px-5 py-3 text-right font-bold">Résultat</th>
                            <th class="px-5 py-3 text-center font-bold">Statut</th>
                            <th class="px-5 py-3 text-right font-bold"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($budgets as $b)
                            @php
                                $sb = $statutBadge[$b->statut] ?? $statutBadge['brouillon'];
                                $tauxDep = $b->total_prevu_depenses > 0 ? round(($b->total_reel_depenses / $b->total_prevu_depenses) * 100, 1) : 0;
                                $alerte = $tauxDep >= 90;
                                $resPrevu = $b->resultatPrevu();
                                $resReel  = $b->resultatReel();
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <div class="font-semibold text-gray-900">{{ $b->libelle }}</div>
                                    <div class="text-xs text-gray-500">{{ ucfirst($b->periodicite) }} · créé par {{ $b->creePar?->name ?? '—' }}</div>
                                </td>
                                <td class="px-5 py-3 text-gray-700">{{ $b->exercice?->libelle ?? '—' }}</td>
                                <td class="px-5 py-3 text-center font-bold text-gray-700">{{ $b->lignes_count }}</td>
                                <td class="px-5 py-3 text-right">
                                    <div class="font-bold text-gray-800">{{ $money($b->total_prevu_revenus) }} F</div>
                                    <div class="text-xs text-blue-700">{{ $money($b->total_reel_revenus) }} F</div>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <div class="font-bold text-gray-800">{{ $money($b->total_prevu_depenses) }} F</div>
                                    <div class="text-xs {{ $alerte ? 'text-red-700 font-bold' : 'text-rose-600' }}">
                                        {{ $money($b->total_reel_depenses) }} F
                                        @if($alerte) ⚠ @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <div class="font-bold {{ $resPrevu >= 0 ? 'text-brand-700' : 'text-red-700' }}">{{ $money($resPrevu) }} F</div>
                                    <div class="text-xs {{ $resReel >= 0 ? 'text-brand-700' : 'text-red-600' }}">{{ $money($resReel) }} F</div>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold {{ $sb['bg'] }} {{ $sb['text'] }}">{{ $sb['label'] }}</span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('budgets.show', $b->id) }}" class="text-brand-600 hover:text-brand-800 text-sm font-bold">Ouvrir →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
