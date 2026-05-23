@extends('layouts.app')

@section('title', 'Journal comptable')
@section('page-title', 'Journal comptable')
@section('page-subtitle', 'Écritures validées par période')

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 0, ',', ' ');
@endphp

<div class="space-y-6">
    @include('comptabilite._nav')

    <section class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
        <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4">
            <form method="GET" action="{{ route('comptabilite.journal') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                <label class="block">
                    <span class="block text-xs font-bold uppercase text-gray-400 mb-1">Mois</span>
                    <input type="month" name="mois" value="{{ $mois }}" class="rounded-xl border-gray-200 text-sm focus:border-brand-300 focus:ring-brand-100">
                </label>
                <button type="submit" class="btn-primary text-sm">Filtrer</button>
                <a href="{{ route('comptabilite.journal') }}" class="btn-secondary text-sm">Réinitialiser</a>
            </form>

            <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                <div class="rounded-xl bg-gray-50 px-4 py-3">
                    <p class="text-xs font-bold uppercase text-gray-400">Total débit / crédit</p>
                    <p class="text-lg font-extrabold text-gray-900">{{ $money($total) }} F</p>
                </div>
                <form method="POST" action="{{ route('comptabilite.synchroniser-paiements') }}">
                    @csrf
                    <button type="submit" class="btn-secondary text-sm" @disabled($paiementsAComptabiliser === 0)>
                        Comptabiliser {{ $paiementsAComptabiliser }} paiement(s)
                    </button>
                </form>
            </div>
        </div>
    </section>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="font-extrabold text-gray-900">Journal de {{ \Carbon\Carbon::parse($mois.'-01')->locale('fr')->isoFormat('MMMM YYYY') }}</h3>
                <p class="text-xs text-gray-500 mt-0.5">{{ $ecritures->total() }} écriture(s)</p>
            </div>
        </div>

        @if($ecritures->isEmpty())
            <div class="px-5 py-12 text-center">
                <p class="font-bold text-gray-800">Aucune écriture pour cette période.</p>
                <p class="text-sm text-gray-500 mt-1">Le journal se remplira après comptabilisation des paiements ou saisie d’écritures.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-400">
                        <tr>
                            <th class="px-5 py-3 text-left">Date</th>
                            <th class="px-5 py-3 text-left">Pièce</th>
                            <th class="px-5 py-3 text-left">Libellé</th>
                            <th class="px-5 py-3 text-left">Débit</th>
                            <th class="px-5 py-3 text-left">Crédit</th>
                            <th class="px-5 py-3 text-right">Montant</th>
                            <th class="px-5 py-3 text-center">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($ecritures as $ecriture)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 whitespace-nowrap">{{ optional($ecriture->date_ecriture)->format('d/m/Y') }}</td>
                                <td class="px-5 py-3 font-semibold text-gray-700">{{ $ecriture->numero_piece }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ $ecriture->libelle }}</td>
                                <td class="px-5 py-3">
                                    <span class="font-bold text-gray-900">{{ $ecriture->compteDebit?->numero }}</span>
                                    <span class="block text-xs text-gray-500">{{ $ecriture->compteDebit?->libelle }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="font-bold text-gray-900">{{ $ecriture->compteCredit?->numero }}</span>
                                    <span class="block text-xs text-gray-500">{{ $ecriture->compteCredit?->libelle }}</span>
                                </td>
                                <td class="px-5 py-3 text-right font-extrabold text-gray-900">{{ $money($ecriture->montant) }} F</td>
                                <td class="px-5 py-3 text-center">
                                    <span class="px-2 py-1 rounded-lg text-xs font-bold {{ $ecriture->valide ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                        {{ $ecriture->valide ? 'Validée' : 'Brouillon' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $ecritures->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
