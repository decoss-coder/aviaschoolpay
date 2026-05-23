@extends('layouts.app')
@section('title', 'Rentabilité par classe')
@section('page-title', 'Rentabilité par classe')

@section('content')
@php $money = fn($v) => number_format((float) $v, 0, ',', ' '); @endphp

<div class="space-y-6">
    @include('rentabilite._nav')

    {{-- Résumé --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-emerald-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-emerald-600 tracking-wider">Classes rentables</p>
            <p class="text-2xl font-extrabold text-emerald-700 mt-2">{{ $classes->where('rentable', true)->count() }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-red-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-red-600 tracking-wider">Classes déficitaires</p>
            <p class="text-2xl font-extrabold text-red-700 mt-2">{{ $classes->where('rentable', false)->count() }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Marge globale</p>
            <p class="text-2xl font-extrabold {{ $synthese['rentable'] ? 'text-brand-700' : 'text-red-700' }} mt-2">{{ $money($synthese['marge']) }} F</p>
        </div>
    </div>

    {{-- Tableau --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="font-extrabold text-gray-900">Détail par classe</h3>
                <p class="text-xs text-gray-500 mt-0.5">Coût alloué au prorata du nombre d'élèves</p>
            </div>
            <span class="text-xs font-semibold text-gray-500">{{ $classes->count() }} classe(s)</span>
        </div>

        @if($classes->isEmpty())
            <div class="px-5 py-12 text-center text-sm text-gray-500">Aucune classe pour l'année en cours.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-5 py-3 text-left font-bold">Classe</th>
                            <th class="px-5 py-3 text-center font-bold">Élèves</th>
                            <th class="px-5 py-3 text-right font-bold">Revenus</th>
                            <th class="px-5 py-3 text-right font-bold">Coût alloué</th>
                            <th class="px-5 py-3 text-right font-bold">Marge</th>
                            <th class="px-5 py-3 text-right font-bold">Rev/élève</th>
                            <th class="px-5 py-3 text-center font-bold">Taux</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($classes as $c)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <p class="font-bold text-gray-900">{{ $c['nom'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $c['niveau'] ?: '—' }}</p>
                                </td>
                                <td class="px-5 py-3 text-center font-bold text-gray-700">{{ $c['nb_eleves'] }}</td>
                                <td class="px-5 py-3 text-right font-extrabold text-blue-700">{{ $money($c['revenus']) }} F</td>
                                <td class="px-5 py-3 text-right font-extrabold text-rose-700">{{ $money($c['cout_alloue']) }} F</td>
                                <td class="px-5 py-3 text-right font-extrabold {{ $c['rentable'] ? 'text-brand-700' : 'text-red-700' }}">{{ $money($c['marge']) }} F</td>
                                <td class="px-5 py-3 text-right text-xs font-bold text-gray-600">{{ $money($c['revenu_par_eleve']) }}</td>
                                <td class="px-5 py-3 text-center">
                                    @php
                                        $tx = $c['taux_marge'];
                                        $cls = $tx >= 20 ? 'bg-emerald-100 text-emerald-700' : ($tx >= 0 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700');
                                    @endphp
                                    <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold {{ $cls }}">{{ $tx }}%</span>
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
