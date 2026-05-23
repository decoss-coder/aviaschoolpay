@extends('layouts.app')

@section('title', 'Comptabilité')
@section('page-title', 'Comptabilité')

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 0, ',', ' ');
@endphp

<div class="space-y-6">
    @include('comptabilite._nav')

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ session('error') }}</div>
    @endif

    @if(($setup['created_accounts'] ?? 0) > 0)
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            ✓ Plan SYSCOHADA initialisé pour {{ $etab->nom }} — {{ $setup['created_accounts'] }} compte(s) créé(s).
        </div>
    @endif

    {{-- KPI Cards --}}
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card hover:shadow-card-hover transition">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Exercice</p>
                <span class="w-8 h-8 bg-indigo-50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </span>
            </div>
            <p class="text-lg font-extrabold text-gray-900">{{ $stats['exercice'] }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $stats['nb_comptes'] }} comptes · {{ $stats['nb_ecritures'] }} écritures</p>
        </div>

        <div class="bg-white rounded-2xl border border-emerald-100 p-5 shadow-card hover:shadow-card-hover transition">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-bold uppercase text-emerald-600 tracking-wider">Trésorerie</p>
                <span class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 9v1"/></svg>
                </span>
            </div>
            <p class="text-2xl font-extrabold text-emerald-700">{{ $money($stats['tresorerie']) }} <span class="text-sm text-gray-400 font-bold">F</span></p>
            <p class="text-xs text-gray-500 mt-1">Classe 5 SYSCOHADA</p>
        </div>

        <div class="bg-white rounded-2xl border border-blue-100 p-5 shadow-card-blue hover:shadow-card-hover transition">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-bold uppercase text-blue-600 tracking-wider">Produits</p>
                <span class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </span>
            </div>
            <p class="text-2xl font-extrabold text-blue-700">{{ $money($stats['produits']) }} <span class="text-sm text-gray-400 font-bold">F</span></p>
            <p class="text-xs text-gray-500 mt-1">Charges : {{ $money($stats['charges']) }} F</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card hover:shadow-card-hover transition">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Résultat</p>
                <span class="w-8 h-8 {{ $stats['resultat'] >= 0 ? 'bg-brand-50' : 'bg-red-50' }} rounded-lg flex items-center justify-center">
                    @if($stats['resultat'] >= 0)
                        <svg class="w-4 h-4 text-brand-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    @else
                        <svg class="w-4 h-4 text-red-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    @endif
                </span>
            </div>
            <p class="text-2xl font-extrabold {{ $stats['resultat'] >= 0 ? 'text-brand-700' : 'text-red-600' }}">{{ $money($stats['resultat']) }} <span class="text-sm text-gray-400 font-bold">F</span></p>
            <p class="text-xs text-gray-500 mt-1">{{ $stats['resultat'] >= 0 ? 'Bénéfice' : 'Déficit' }} de l'exercice</p>
        </div>
    </section>

    {{-- Plan + actions --}}
    <section class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h3 class="font-extrabold text-gray-900">Plan comptable SYSCOHADA</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Déclinaison scolaire par classes 1 à 8</p>
                </div>
                <form method="POST" action="{{ route('comptabilite.initialiser') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl bg-white border border-gray-200 text-gray-700 hover:border-brand-300 hover:text-brand-700 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Synchroniser
                    </button>
                </form>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($classes as $classe)
                    <div class="px-5 py-4 flex items-center justify-between gap-4 hover:bg-gray-50 transition">
                        <div class="min-w-0 flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-50 to-brand-100 text-brand-700 flex items-center justify-center text-sm font-extrabold">{{ $classe['numero'] }}</span>
                            <div>
                                <p class="font-bold text-gray-900 truncate">{{ $classe['label'] }}</p>
                                <p class="text-xs text-gray-500">{{ $classe['count'] }} compte(s)</p>
                            </div>
                        </div>
                        <p class="text-sm font-extrabold text-gray-900 whitespace-nowrap">{{ $money($classe['solde']) }} F</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-amber-100 p-5 shadow-card-gold">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-extrabold text-gray-900">Paiements à comptabiliser</h3>
                        <p class="mt-1 text-3xl font-extrabold text-amber-600">{{ $paiementsAComptabiliser }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">en attente d'écriture</p>
                    </div>
                    <span class="px-2 py-1 rounded-lg bg-amber-100 text-amber-700 text-xs font-bold">SYSCOHADA</span>
                </div>
                <form method="POST" action="{{ route('comptabilite.synchroniser-paiements') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2.5 text-sm font-bold rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 text-white shadow-card hover:shadow-lg transition disabled:opacity-50" @disabled($paiementsAComptabiliser === 0)>
                        Générer les écritures
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card">
                <h3 class="font-extrabold text-gray-900 mb-3">Navigation rapide</h3>
                <div class="space-y-2">
                    <a href="{{ route('comptabilite.journal') }}" class="block rounded-xl border border-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:border-brand-300 hover:text-brand-700 hover:bg-brand-50/30 transition">📒 Journal</a>
                    <a href="{{ route('comptabilite.grand-livre') }}" class="block rounded-xl border border-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:border-brand-300 hover:text-brand-700 hover:bg-brand-50/30 transition">📚 Grand livre</a>
                    <a href="{{ route('comptabilite.bilan') }}" class="block rounded-xl border border-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:border-brand-300 hover:text-brand-700 hover:bg-brand-50/30 transition">⚖ Bilan</a>
                    <a href="{{ route('comptabilite.resultat') }}" class="block rounded-xl border border-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:border-brand-300 hover:text-brand-700 hover:bg-brand-50/30 transition">📈 Compte de résultat</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Dernières écritures --}}
    <section class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-extrabold text-gray-900">Dernières écritures</h3>
            <a href="{{ route('comptabilite.journal') }}" class="text-xs font-bold text-brand-600 hover:text-brand-800">Voir le journal →</a>
        </div>
        @if($recentes->isEmpty())
            <div class="px-5 py-12 text-center">
                <p class="font-bold text-gray-800">Aucune écriture comptable.</p>
                <p class="text-sm text-gray-500 mt-1">Les paiements confirmés peuvent être transformés en écritures depuis cette page.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-5 py-3 text-left font-bold">Date</th>
                            <th class="px-5 py-3 text-left font-bold">Pièce</th>
                            <th class="px-5 py-3 text-left font-bold">Libellé</th>
                            <th class="px-5 py-3 text-left font-bold">Débit</th>
                            <th class="px-5 py-3 text-left font-bold">Crédit</th>
                            <th class="px-5 py-3 text-right font-bold">Montant</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($recentes as $ecriture)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 whitespace-nowrap text-xs text-gray-600">{{ optional($ecriture->date_ecriture)->format('d/m/Y') }}</td>
                                <td class="px-5 py-3 font-mono text-xs font-semibold text-gray-700">{{ $ecriture->numero_piece }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ $ecriture->libelle }}</td>
                                <td class="px-5 py-3 font-mono text-xs text-gray-600">{{ $ecriture->compteDebit?->numero }}</td>
                                <td class="px-5 py-3 font-mono text-xs text-gray-600">{{ $ecriture->compteCredit?->numero }}</td>
                                <td class="px-5 py-3 text-right font-extrabold text-gray-900">{{ $money($ecriture->montant) }} F</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
