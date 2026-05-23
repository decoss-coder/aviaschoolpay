@extends('layouts.app')
@section('title', 'Mes classes')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8 space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="font-display text-2xl font-extrabold text-gray-900">Mes classes</h1>
        <span class="text-sm text-gray-400">{{ $annee->libelle ?? '' }}</span>
    </div>

    @if($affectations->isEmpty())
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-12 text-center">
            <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2"/></svg>
            <p class="text-gray-500 font-medium">Aucune affectation pour cette année scolaire.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach($affectations as $classeId => $aff)
            @php
                $classe  = $aff->first()->classe;
                $matieres = $aff->pluck('matiere');
            @endphp
            <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-5 hover:shadow-card-brand hover:border-brand-200 transition">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">{{ $classe->nom }}</h3>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $classe->niveau->libelle ?? '' }}</p>
                    </div>
                    <span class="text-xs bg-brand-100 text-brand-700 font-bold px-2 py-0.5 rounded-full">
                        {{ $classe->effectif ?? 0 }} élèves
                    </span>
                </div>

                <div class="flex flex-wrap gap-1.5 mb-4">
                    @foreach($matieres as $m)
                    <span class="text-xs bg-gray-100 text-gray-700 font-semibold px-2 py-0.5 rounded-full">
                        {{ $m->code ?? $m->nom }}
                    </span>
                    @endforeach
                </div>

                <div class="grid grid-cols-4 gap-2 mb-2">
                    <a href="{{ route('mon-espace.eleves', $classe) }}"
                       class="flex flex-col items-center gap-1 rounded-xl bg-gray-50 hover:bg-brand-50 hover:text-brand-700 py-2 transition text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                        <span class="text-[10px] font-bold">Élèves</span>
                    </a>
                    <a href="{{ route('mon-espace.grille-notes.index', $classe) }}"
                       class="flex flex-col items-center gap-1 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 py-2 transition border border-blue-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M3 18h18M3 6h18"/></svg>
                        <span class="text-[10px] font-bold">Grille notes</span>
                    </a>
                    <a href="{{ route('mon-espace.moyennes', $classe) }}"
                       class="flex flex-col items-center gap-1 rounded-xl bg-gray-50 hover:bg-indigo-50 hover:text-indigo-700 py-2 transition text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        <span class="text-[10px] font-bold">Moyennes</span>
                    </a>
                    <a href="{{ route('mon-espace.devoirs', $classe) }}"
                       class="flex flex-col items-center gap-1 rounded-xl bg-gray-50 hover:bg-gold-50 hover:text-gold-600 py-2 transition text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        <span class="text-[10px] font-bold">Devoirs</span>
                    </a>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <a href="{{ route('mon-espace.feuille-de-note.index', $classe) }}"
                       class="flex flex-col items-center gap-1 rounded-xl bg-gray-50 hover:bg-purple-50 hover:text-purple-700 py-2 transition text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        <span class="text-[10px] font-bold">Notes</span>
                    </a>
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" type="button"
                                class="w-full flex flex-col items-center gap-1 rounded-xl bg-gray-50 hover:bg-emerald-50 hover:text-emerald-700 py-2 transition text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 014-4h4M9 17v-6a4 4 0 014-4h4m-4 12V5a2 2 0 012-2h2a2 2 0 012 2v14M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span class="text-[10px] font-bold">Fiche</span>
                        </button>
                        <div x-show="open" @click.outside="open = false" x-transition x-cloak
                             class="absolute z-10 top-full mt-1 left-0 right-0 bg-white shadow-lg border border-gray-100 rounded-lg overflow-hidden">
                            <a href="{{ route('mon-espace.fiche-classe.pdf', ['classe' => $classe, 'orientation' => 'landscape']) }}" target="_blank"
                               class="block px-3 py-1.5 text-[10px] font-semibold text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 border-b border-gray-50">📄 PDF Paysage</a>
                            <a href="{{ route('mon-espace.fiche-classe.pdf', ['classe' => $classe, 'orientation' => 'portrait']) }}" target="_blank"
                               class="block px-3 py-1.5 text-[10px] font-semibold text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 border-b border-gray-50">📄 PDF Portrait</a>
                            <a href="{{ route('mon-espace.fiche-classe.excel', $classe) }}"
                               class="block px-3 py-1.5 text-[10px] font-semibold text-gray-700 hover:bg-emerald-50 hover:text-emerald-700">📊 Excel</a>
                        </div>
                    </div>
                    <a href="{{ route('mon-espace.cahier-appel.appel-jour', $classe) }}"
                       class="flex flex-col items-center gap-1 rounded-xl bg-violet-50 hover:bg-violet-100 text-violet-700 py-2 transition border border-violet-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-[10px] font-bold">Appel</span>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
