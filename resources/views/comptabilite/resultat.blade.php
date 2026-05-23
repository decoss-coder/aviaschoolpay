@extends('layouts.app')

@section('title', 'Compte de résultat')
@section('page-title', 'Compte de résultat')
@section('page-subtitle', 'Produits, charges et résultat net')

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 0, ',', ' ');
@endphp

<div class="space-y-6">
    @include('comptabilite._nav')

    <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase text-gray-400">Produits</p>
            <p class="mt-2 text-2xl font-extrabold text-blue-700">{{ $money($totalProduits) }} F</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase text-gray-400">Charges</p>
            <p class="mt-2 text-2xl font-extrabold text-red-600">{{ $money($totalCharges) }} F</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase text-gray-400">Résultat net</p>
            <p class="mt-2 text-2xl font-extrabold {{ $resultatNet >= 0 ? 'text-brand-700' : 'text-red-600' }}">{{ $money($resultatNet) }} F</p>
        </div>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-extrabold text-gray-900">Produits - classe 7</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($produits as $compte)
                    <div class="px-5 py-3 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="font-bold text-gray-900 truncate">{{ $compte->numero }} - {{ $compte->libelle }}</p>
                            <p class="text-xs text-gray-500">{{ $compte->categorie ?: 'sans catégorie' }}</p>
                        </div>
                        <p class="font-extrabold text-gray-900 whitespace-nowrap">{{ $money($compte->solde_actuel) }} F</p>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-gray-500">Aucun compte de produit.</div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-extrabold text-gray-900">Charges - classe 6</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($charges as $compte)
                    <div class="px-5 py-3 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="font-bold text-gray-900 truncate">{{ $compte->numero }} - {{ $compte->libelle }}</p>
                            <p class="text-xs text-gray-500">{{ $compte->categorie ?: 'sans catégorie' }}</p>
                        </div>
                        <p class="font-extrabold text-gray-900 whitespace-nowrap">{{ $money($compte->solde_actuel) }} F</p>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-gray-500">Aucun compte de charge.</div>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection
