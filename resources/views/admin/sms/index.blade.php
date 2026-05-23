@extends('layouts.app')
@section('title', 'Recharges SMS Avia')
@section('page-title', 'Recharges SMS — Avia Technologie')
@section('page-subtitle', 'Validation des paiements Wave et créditation des SMS')

@section('content')
@php $money = fn($v) => number_format((float) $v, 0, ',', ' '); @endphp

<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif

    {{-- KPIs --}}
    <section class="grid grid-cols-2 lg:grid-cols-6 gap-4">
        <div class="bg-white rounded-2xl border border-amber-100 p-4 shadow-card-gold">
            <p class="text-[10px] font-bold uppercase text-amber-600 tracking-wider">À payer</p>
            <p class="text-2xl font-extrabold text-amber-700 mt-1">{{ $stats['en_attente'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-blue-100 p-4 shadow-card-blue">
            <p class="text-[10px] font-bold uppercase text-blue-600 tracking-wider">Payé · à créditer</p>
            <p class="text-2xl font-extrabold text-blue-700 mt-1">{{ $stats['paye'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-100 p-4 shadow-card">
            <p class="text-[10px] font-bold uppercase text-emerald-600 tracking-wider">Crédité</p>
            <p class="text-2xl font-extrabold text-emerald-700 mt-1">{{ $stats['credite'] }}</p>
        </div>
        <div class="bg-gradient-to-br from-brand-500 to-brand-700 rounded-2xl p-4 shadow-brand-glow text-white">
            <p class="text-[10px] font-bold uppercase text-brand-100 tracking-wider">Revenus mois</p>
            <p class="text-xl font-extrabold mt-1">{{ $money($stats['revenus_mois']) }} F</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-card">
            <p class="text-[10px] font-bold uppercase text-gray-400 tracking-wider">Revenus total</p>
            <p class="text-xl font-extrabold text-gray-900 mt-1">{{ $money($stats['revenus_total']) }} F</p>
        </div>
        <div class="bg-white rounded-2xl border border-violet-100 p-4 shadow-card-violet">
            <p class="text-[10px] font-bold uppercase text-violet-600 tracking-wider">SMS crédités</p>
            <p class="text-2xl font-extrabold text-violet-700 mt-1">{{ number_format($stats['sms_credites'], 0, ',', ' ') }}</p>
        </div>
    </section>

    {{-- Filtres --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-4">
        <form method="GET" class="flex flex-wrap gap-2">
            @foreach(['' => 'Toutes', 'en_attente_paiement' => '⏳ À payer', 'paye' => '💵 Payé', 'credite' => '✓ Crédité', 'annule' => 'Annulé'] as $val => $lbl)
                <a href="{{ route('admin.sms.index', $val ? ['statut' => $val] : []) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold border transition
                          {{ ($statut ?? '') === $val ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-gray-600 border-gray-200 hover:border-brand-300' }}">
                    {{ $lbl }}
                </a>
            @endforeach
        </form>
    </div>

    {{-- Liste --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-extrabold text-gray-900">🔐 Demandes de recharge SMS</h3>
            <span class="text-xs font-semibold text-gray-500">{{ $recharges->total() }} résultat(s)</span>
        </div>

        @if($recharges->isEmpty())
            <div class="px-5 py-16 text-center text-sm text-gray-500">Aucune recharge.</div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-bold">Référence</th>
                        <th class="px-5 py-3 text-left font-bold">École</th>
                        <th class="px-5 py-3 text-left font-bold">Demandeur</th>
                        <th class="px-5 py-3 text-center font-bold">Nb SMS</th>
                        <th class="px-5 py-3 text-right font-bold">Montant</th>
                        <th class="px-5 py-3 text-center font-bold">Statut</th>
                        <th class="px-5 py-3 text-left font-bold">Date</th>
                        <th class="px-5 py-3 text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($recharges as $r)
                        @php
                            $sb = [
                                'en_attente_paiement' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => '⏳ À payer'],
                                'paye'                => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => '💵 Payé'],
                                'credite'             => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'label' => '✓ Crédité'],
                                'annule'              => ['bg' => 'bg-gray-100', 'text' => 'text-gray-500', 'label' => 'Annulé'],
                                'expire'              => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Expiré'],
                            ][$r->statut];
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-mono text-xs">{{ $r->reference }}</td>
                            <td class="px-5 py-3 font-bold text-gray-900">{{ $r->etablissement?->nom }}</td>
                            <td class="px-5 py-3 text-xs text-gray-700">{{ $r->demandeur?->name }}</td>
                            <td class="px-5 py-3 text-center font-bold">{{ number_format($r->nb_sms, 0, ',', ' ') }}</td>
                            <td class="px-5 py-3 text-right font-extrabold text-amber-700">{{ $money($r->montant_fcfa) }} F</td>
                            <td class="px-5 py-3 text-center"><span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold {{ $sb['bg'] }} {{ $sb['text'] }}">{{ $sb['label'] }}</span></td>
                            <td class="px-5 py-3 text-xs text-gray-500">{{ $r->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                @if($r->statut === 'en_attente_paiement')
                                    <form method="POST" action="{{ route('admin.sms.payer', $r->id) }}" class="inline" onsubmit="return confirm('Marquer comme PAYÉ ? (vérifiez Wave d\'abord)')">
                                        @csrf
                                        <button class="text-xs font-bold text-blue-700 hover:underline">✓ Marquer payé</button>
                                    </form>
                                @elseif($r->statut === 'paye')
                                    <form method="POST" action="{{ route('admin.sms.crediter', $r->id) }}" class="inline" onsubmit="return confirm('Créditer {{ $r->nb_sms }} SMS à « {{ $r->etablissement?->nom }} » ?')">
                                        @csrf
                                        <button class="text-xs font-bold text-emerald-700 hover:underline">💎 Créditer</button>
                                    </form>
                                @elseif($r->statut === 'credite')
                                    <span class="text-xs text-emerald-700">Crédité {{ $r->credite_at?->diffForHumans() }}</span>
                                @endif
                                @if(in_array($r->statut, ['en_attente_paiement', 'paye']))
                                    <span class="text-gray-300 mx-1">·</span>
                                    <form method="POST" action="{{ route('admin.sms.annuler', $r->id) }}" class="inline" onsubmit="return confirm('Annuler définitivement ?')">
                                        @csrf
                                        <button class="text-xs font-bold text-red-600">Annuler</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-gray-100">{{ $recharges->links() }}</div>
        @endif
    </div>
</div>
@endsection
