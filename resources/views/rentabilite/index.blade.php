@extends('layouts.app')
@section('title', 'Rentabilité')
@section('page-title', 'Rentabilité')

@section('content')
@php $money = fn($v) => number_format((float) $v, 0, ',', ' '); @endphp

<div class="space-y-6">
    @include('rentabilite._nav')

    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-blue-100 p-5 shadow-card-blue">
            <p class="text-xs font-bold uppercase text-blue-600 tracking-wider mb-2">Revenus</p>
            <p class="text-2xl font-extrabold text-blue-700">{{ $money($synthese['revenus']) }} <span class="text-sm text-gray-400 font-bold">F</span></p>
            <p class="text-xs text-gray-500 mt-1">{{ $synthese['annee'] ?: '—' }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-rose-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-rose-600 tracking-wider mb-2">Dépenses</p>
            <p class="text-2xl font-extrabold text-rose-700">{{ $money($synthese['depenses']) }} <span class="text-sm text-gray-400 font-bold">F</span></p>
            <p class="text-xs text-gray-500 mt-1">Exercice : {{ $synthese['exercice'] ?: '—' }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-2">Marge nette</p>
            <p class="text-2xl font-extrabold {{ $synthese['rentable'] ? 'text-brand-700' : 'text-red-700' }}">{{ $money($synthese['marge']) }} <span class="text-sm text-gray-400 font-bold">F</span></p>
            <p class="text-xs {{ $synthese['rentable'] ? 'text-brand-600' : 'text-red-600' }} mt-1 font-bold">{{ $synthese['taux_marge'] }}% · {{ $synthese['rentable'] ? '✓ Rentable' : '⚠ Déficit' }}</p>
        </div>
        <div class="bg-gradient-to-br from-amber-500 to-orange-700 rounded-2xl p-5 shadow-card-gold text-white">
            <p class="text-xs font-bold uppercase text-amber-100 tracking-wider mb-2">Masse salariale</p>
            <p class="text-2xl font-extrabold">{{ $money($synthese['masse_salariale_mensuelle']) }} <span class="text-sm font-bold opacity-80">F/mois</span></p>
            <p class="text-xs text-amber-100 mt-1">Ratio MS/CA : <b>{{ $synthese['ratio_ms_revenus'] }}%</b> {{ $synthese['ms_saine'] ? '✓' : '⚠' }}</p>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
            <h3 class="font-extrabold text-gray-900 mb-4">Performance par élève</h3>
            <div class="space-y-4">
                <div class="flex items-baseline justify-between">
                    <p class="text-xs font-bold uppercase text-gray-400">Revenu / élève</p>
                    <p class="text-lg font-extrabold text-blue-700">{{ $money($synthese['revenu_par_eleve']) }} F</p>
                </div>
                <div class="flex items-baseline justify-between">
                    <p class="text-xs font-bold uppercase text-gray-400">Coût / élève</p>
                    <p class="text-lg font-extrabold text-rose-700">{{ $money($synthese['cout_par_eleve']) }} F</p>
                </div>
                <div class="pt-3 border-t border-gray-100 flex items-baseline justify-between">
                    <p class="text-xs font-bold uppercase text-brand-600">Marge / élève</p>
                    <p class="text-xl font-extrabold {{ $synthese['marge_par_eleve'] >= 0 ? 'text-brand-700' : 'text-red-700' }}">{{ $money($synthese['marge_par_eleve']) }} F</p>
                </div>
                <div class="mt-4 p-3 bg-gray-50 rounded-xl text-center">
                    <p class="text-2xl font-extrabold text-gray-900">{{ $synthese['nb_eleves'] }}</p>
                    <p class="text-xs text-gray-500 font-semibold">élève(s) inscrit(s)</p>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-extrabold text-gray-900">Top 5 classes les plus rentables</h3>
                <a href="{{ route('rentabilite.classes') }}" class="text-xs font-bold text-amber-600 hover:text-amber-800">Voir tout →</a>
            </div>
            @if($topClasses->isEmpty())
                <div class="px-5 py-10 text-center text-sm text-gray-500">Aucune donnée — vérifiez les inscriptions de l'année en cours.</div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($topClasses as $i => $c)
                        <div class="px-5 py-4 flex items-center justify-between gap-4 hover:bg-gray-50">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-100 to-orange-100 text-amber-700 flex items-center justify-center text-xs font-extrabold">#{{ $i + 1 }}</span>
                                <div class="min-w-0">
                                    <p class="font-bold text-gray-900 truncate">{{ $c['nom'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $c['niveau'] ?: '—' }} · {{ $c['nb_eleves'] }} élève(s)</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-extrabold {{ $c['rentable'] ? 'text-brand-700' : 'text-red-700' }}">{{ $money($c['marge']) }} F</p>
                                <p class="text-xs font-bold text-gray-500">{{ $c['taux_marge'] }}% marge</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
