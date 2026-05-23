@extends('layouts.app')

@section('title', 'Bilan')
@section('page-title', 'Bilan')
@section('page-subtitle', 'Actif, passif et résultat')

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 0, ',', ' ');
@endphp

<div class="space-y-6">
    @include('comptabilite._nav')

    <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase text-gray-400">Total actif</p>
            <p class="mt-2 text-2xl font-extrabold text-brand-700">{{ $money($totalActif) }} F</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase text-gray-400">Total passif</p>
            <p class="mt-2 text-2xl font-extrabold text-blue-700">{{ $money($totalPassif) }} F</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase text-gray-400">Écart</p>
            <p class="mt-2 text-2xl font-extrabold {{ abs($ecart) < 1 ? 'text-emerald-700' : 'text-amber-600' }}">{{ $money($ecart) }} F</p>
        </div>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-extrabold text-gray-900">Actif</h3>
                <p class="text-xs text-gray-500 mt-0.5">Immobilisations, stocks, tiers débiteurs et trésorerie</p>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($actifs as $compte)
                    <div class="px-5 py-3 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="font-bold text-gray-900 truncate">{{ $compte->numero }} - {{ $compte->libelle }}</p>
                            <p class="text-xs text-gray-500">{{ ucfirst($compte->type) }}</p>
                        </div>
                        <p class="font-extrabold text-gray-900 whitespace-nowrap">{{ $money($compte->solde_actuel) }} F</p>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-gray-500">Aucun compte d’actif.</div>
                @endforelse
                @if($resultat < 0)
                    <div class="px-5 py-3 flex items-center justify-between gap-4 bg-red-50">
                        <p class="font-bold text-red-800">Perte de l’exercice</p>
                        <p class="font-extrabold text-red-800">{{ $money(abs($resultat)) }} F</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-extrabold text-gray-900">Passif</h3>
                <p class="text-xs text-gray-500 mt-0.5">Ressources durables, dettes et résultat bénéficiaire</p>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($passifs as $compte)
                    <div class="px-5 py-3 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="font-bold text-gray-900 truncate">{{ $compte->numero }} - {{ $compte->libelle }}</p>
                            <p class="text-xs text-gray-500">{{ ucfirst($compte->type) }}</p>
                        </div>
                        <p class="font-extrabold text-gray-900 whitespace-nowrap">{{ $money($compte->solde_actuel) }} F</p>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-gray-500">Aucun compte de passif.</div>
                @endforelse
                @if($resultat > 0)
                    <div class="px-5 py-3 flex items-center justify-between gap-4 bg-emerald-50">
                        <p class="font-bold text-emerald-800">Résultat bénéficiaire</p>
                        <p class="font-extrabold text-emerald-800">{{ $money($resultat) }} F</p>
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
