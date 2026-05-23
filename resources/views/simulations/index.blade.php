@extends('layouts.app')
@section('title', 'Simulations financières')
@section('page-title', 'Simulations financières')
@section('page-subtitle', 'Scénarios prévisionnels et études d\'impact')

@section('content')
@php
    $money = fn($v) => number_format((float) $v, 0, ',', ' ');
    $typesLabels = [
        'augmentation_effectif' => '👥 Augmentation effectif',
        'reduction_effectif'    => '👥 Réduction effectif',
        'augmentation_tarif'    => '⬆ Augmentation tarif',
        'reduction_tarif'       => '⬇ Réduction tarif',
        'recrutement'           => '🧑‍🏫 Recrutement',
        'reduction_personnel'   => '🚪 Réduction personnel',
        'reduction_couts'       => '✂ Réduction coûts',
        'ajout_service'         => '➕ Ajout service',
        'investissement'        => '🏗 Investissement',
        'scenario_libre'        => '✏ Scénario libre',
    ];
@endphp

<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-cyan-500 to-sky-700 rounded-xl flex items-center justify-center shadow-card-blue">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Module 16</p>
                <h2 class="font-display text-2xl font-extrabold text-gray-900">Simulations & projections</h2>
            </div>
        </div>
        <a href="{{ route('simulations.create') }}" class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-cyan-500 to-sky-700 text-white shadow-card-blue hover:shadow-lg transition flex items-center gap-2 self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Nouvelle simulation
        </a>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Total</p>
            <p class="text-2xl font-extrabold text-gray-900 mt-2">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-emerald-600 tracking-wider">Rentables</p>
            <p class="text-2xl font-extrabold text-emerald-700 mt-2">{{ $stats['rentables'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-amber-100 p-5 shadow-card-gold">
            <p class="text-xs font-bold uppercase text-amber-600 tracking-wider">⭐ Favoris</p>
            <p class="text-2xl font-extrabold text-amber-700 mt-2">{{ $stats['favoris'] }}</p>
        </div>
    </div>

    @if($sims->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 p-16 text-center">
            <p class="text-4xl mb-3">🧮</p>
            <p class="font-bold text-gray-800">Aucune simulation</p>
            <p class="text-sm text-gray-500 mt-1 mb-4">Créez votre premier scénario prévisionnel pour évaluer un projet.</p>
            <a href="{{ route('simulations.create') }}" class="inline-flex items-center px-4 py-2 bg-cyan-600 text-white text-sm font-bold rounded-xl">Nouvelle simulation</a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($sims as $s)
                @php $rentable = $s->impact_marge > 0; @endphp
                <div class="bg-white rounded-2xl border border-gray-100 shadow-card hover:shadow-card-hover transition overflow-hidden">
                    <div class="h-1 {{ $rentable ? 'bg-gradient-to-r from-emerald-400 to-emerald-600' : 'bg-gradient-to-r from-red-400 to-red-600' }}"></div>
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="min-w-0">
                                <p class="text-xs font-bold uppercase text-gray-400">{{ $typesLabels[$s->type] ?? $s->type }} · {{ str_replace('_', ' ', $s->horizon) }}</p>
                                <h3 class="font-display text-lg font-extrabold text-gray-900 truncate mt-1">{{ $s->nom }}</h3>
                            </div>
                            <form method="POST" action="{{ route('simulations.favori', $s->id) }}" class="flex-shrink-0">
                                @csrf
                                <button type="submit" class="text-2xl leading-none {{ $s->favori ? 'text-amber-500' : 'text-gray-300 hover:text-amber-400' }}">★</button>
                            </form>
                        </div>

                        <div class="grid grid-cols-2 gap-3 my-4">
                            <div>
                                <p class="text-[10px] font-bold uppercase text-blue-600">Impact revenus</p>
                                <p class="text-sm font-extrabold text-blue-700">{{ $money($s->impact_revenus) }} F</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase text-rose-600">Impact dépenses</p>
                                <p class="text-sm font-extrabold text-rose-700">{{ $money($s->impact_depenses) }} F</p>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-gray-100">
                            <div class="flex items-baseline justify-between">
                                <span class="text-xs font-bold text-gray-600">Impact marge</span>
                                <span class="text-xl font-extrabold {{ $rentable ? 'text-brand-700' : 'text-red-700' }}">{{ $money($s->impact_marge) }} F</span>
                            </div>
                            @if($s->roi_pourcent !== null)
                                <div class="flex items-baseline justify-between mt-1 text-xs">
                                    <span class="text-gray-500">ROI</span>
                                    <span class="font-bold {{ $s->roi_pourcent >= 0 ? 'text-brand-700' : 'text-red-700' }}">{{ $s->roi_pourcent }}%</span>
                                </div>
                            @endif
                            @if($s->delai_rentabilite_mois)
                                <div class="flex items-baseline justify-between text-xs">
                                    <span class="text-gray-500">Rentable en</span>
                                    <span class="font-bold text-gray-700">{{ $s->delai_rentabilite_mois }} mois</span>
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                            <span class="text-gray-400">par {{ $s->creePar?->name ?? '—' }}</span>
                            <a href="{{ route('simulations.show', $s->id) }}" class="font-bold text-cyan-600 hover:text-cyan-800">Détails →</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div>{{ $sims->links() }}</div>
    @endif
</div>
@endsection
