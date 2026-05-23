@extends('layouts.app')
@section('title', 'Rentabilité par service')
@section('page-title', 'Rentabilité par service')

@section('content')
@php $money = fn($v) => number_format((float) $v, 0, ',', ' '); @endphp

<div class="space-y-6">
    @include('rentabilite._nav')

    @if($services->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <p class="font-bold text-gray-800">Pas encore de données de services</p>
            <p class="text-sm text-gray-500 mt-1">Les services apparaîtront dès qu'il y a des paiements avec un <code>poste_cible</code> (scolarité, cantine, transport, activités…)</p>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            @foreach($services as $s)
                @php
                    $color = ['Scolarite' => 'blue', 'Cantine' => 'amber', 'Transport' => 'violet', 'Activites' => 'emerald'][$s['service']] ?? 'gray';
                @endphp
                <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
                    <div class="h-1.5 bg-gradient-to-r from-{{ $color }}-400 to-{{ $color }}-700"></div>
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="font-display text-xl font-extrabold text-gray-900">{{ $s['service'] }}</h3>
                                <p class="text-xs text-gray-500">Centre de profit</p>
                            </div>
                            <span class="inline-flex px-3 py-1 rounded-xl text-xs font-extrabold {{ $s['rentable'] ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                {{ $s['rentable'] ? '✓ Rentable' : '⚠ Déficit' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-3 gap-3 mb-4">
                            <div>
                                <p class="text-[10px] font-bold uppercase text-gray-400">Revenus</p>
                                <p class="text-sm font-extrabold text-blue-700">{{ $money($s['revenus']) }} F</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase text-gray-400">Coût</p>
                                <p class="text-sm font-extrabold text-rose-700">{{ $money($s['cout']) }} F</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase text-gray-400">Marge</p>
                                <p class="text-sm font-extrabold {{ $s['rentable'] ? 'text-brand-700' : 'text-red-700' }}">{{ $money($s['marge']) }} F</p>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-gray-100">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-bold text-gray-600">Taux de marge</span>
                                <span class="font-extrabold {{ $s['rentable'] ? 'text-brand-700' : 'text-red-700' }}">{{ $s['taux_marge'] }}%</span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full {{ $s['rentable'] ? 'bg-gradient-to-r from-brand-400 to-brand-600' : 'bg-gradient-to-r from-red-400 to-red-600' }} rounded-full"
                                     style="width: {{ min(100, abs($s['taux_marge'])) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-900">
            <p class="font-bold mb-1">ℹ Méthodologie</p>
            <p>Les revenus sont agrégés depuis les paiements confirmés selon leur <code>poste_cible</code>. Le coût est actuellement estimé à 60% des revenus (ratio par défaut). Pour un calcul précis, créez des lignes budgétaires de dépense par service et liez-les aux catégories.</p>
        </div>
    @endif
</div>
@endsection
