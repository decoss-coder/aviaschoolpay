@extends('layouts.app')

@section('title', 'Grand livre')
@section('page-title', 'Grand livre')
@section('page-subtitle', 'Mouvements par compte SYSCOHADA')

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 0, ',', ' ');
@endphp

<div class="space-y-6">
    @include('comptabilite._nav')

    <section class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
        <form method="GET" action="{{ route('comptabilite.grand-livre') }}" class="flex flex-col lg:flex-row lg:items-end gap-3">
            <label class="block flex-1">
                <span class="block text-xs font-bold uppercase text-gray-400 mb-1">Compte</span>
                <select name="compte_id" class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-300 focus:ring-brand-100">
                    @foreach($comptes as $item)
                        <option value="{{ $item->id }}" @selected($compte && $compte->id === $item->id)>
                            {{ $item->numero }} - {{ $item->libelle }}
                        </option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="btn-primary text-sm">Afficher</button>
        </form>
    </section>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 class="font-extrabold text-gray-900">
                    {{ $compte ? $compte->numero.' - '.$compte->libelle : 'Aucun compte disponible' }}
                </h3>
                @if($compte)
                    <p class="text-xs text-gray-500 mt-0.5">Solde actuel : {{ $money($compte->solde_actuel) }} F</p>
                @endif
            </div>
            @if($compte)
                <span class="px-3 py-2 rounded-xl bg-brand-50 text-brand-700 text-xs font-bold uppercase">{{ $compte->type }}</span>
            @endif
        </div>

        @if(! $compte)
            <div class="px-5 py-12 text-center">
                <p class="font-bold text-gray-800">Aucun compte actif.</p>
            </div>
        @elseif($ecritures->isEmpty())
            <div class="px-5 py-12 text-center">
                <p class="font-bold text-gray-800">Aucun mouvement sur ce compte.</p>
                <p class="text-sm text-gray-500 mt-1">Les lignes apparaîtront dès qu’une écriture validée touchera ce compte.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-400">
                        <tr>
                            <th class="px-5 py-3 text-left">Date</th>
                            <th class="px-5 py-3 text-left">Pièce</th>
                            <th class="px-5 py-3 text-left">Libellé</th>
                            <th class="px-5 py-3 text-center">Sens</th>
                            <th class="px-5 py-3 text-right">Débit</th>
                            <th class="px-5 py-3 text-right">Crédit</th>
                            <th class="px-5 py-3 text-right">Solde</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($ecritures as $ecriture)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 whitespace-nowrap">{{ optional($ecriture->date_ecriture)->format('d/m/Y') }}</td>
                                <td class="px-5 py-3 font-semibold text-gray-700">{{ $ecriture->numero_piece }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ $ecriture->libelle }}</td>
                                <td class="px-5 py-3 text-center">
                                    <span class="px-2 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-bold">{{ $ecriture->sens_compte }}</span>
                                </td>
                                <td class="px-5 py-3 text-right font-semibold text-gray-900">
                                    {{ $ecriture->sens_compte === 'Débit' ? $money($ecriture->montant).' F' : '-' }}
                                </td>
                                <td class="px-5 py-3 text-right font-semibold text-gray-900">
                                    {{ $ecriture->sens_compte === 'Crédit' ? $money($ecriture->montant).' F' : '-' }}
                                </td>
                                <td class="px-5 py-3 text-right font-extrabold text-gray-900">{{ $money($ecriture->solde_progressif) }} F</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
