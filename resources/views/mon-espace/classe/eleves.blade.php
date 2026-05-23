@extends('layouts.app')
@section('title', 'Élèves · ' . $classe->nom)

@section('content')
@php
    $nbG = $eleves->where('sexe','M')->count();
    $nbF = $eleves->where('sexe','F')->count();
    $nbR = $eleves->where('redoublant', true)->count();
    $nbAFF  = $eleves->where('statut_eleve','AFF')->count();
    $nbNAFF = $eleves->where('statut_eleve','NAFF')->count();
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-4" x-data="{ search: '', sortBy: 'nom', view: 'list' }">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('mon-espace.classes') }}" class="hover:text-brand-600 font-medium">Mes classes</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">{{ $classe->nom }}</span>
        <span>/</span>
        <span>Élèves</span>
    </div>

    {{-- Header --}}
    <div class="bg-gradient-to-r from-brand-600 to-brand-700 rounded-2xl shadow-brand-glow text-white px-6 py-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-extrabold">{{ $classe->nom }}</h1>
            <div class="flex flex-wrap gap-1.5 mt-2">
                @foreach($matieres as $m)
                <span class="text-[10px] bg-white/15 backdrop-blur text-white font-bold px-2 py-0.5 rounded-full">{{ $m->code }}</span>
                @endforeach
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('mon-espace.evaluations', $classe) }}"
               class="bg-white/15 backdrop-blur hover:bg-white/25 text-white text-xs font-bold px-3 py-2 rounded-lg transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg>
                Évaluations
            </a>
            <a href="{{ route('mon-espace.moyennes', $classe) }}"
               class="bg-white/15 backdrop-blur hover:bg-white/25 text-white text-xs font-bold px-3 py-2 rounded-lg transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                Moyennes
            </a>
            <a href="{{ route('mon-espace.devoirs', $classe) }}"
               class="bg-white/15 backdrop-blur hover:bg-white/25 text-white text-xs font-bold px-3 py-2 rounded-lg transition flex items-center gap-1.5">
                📚 Devoirs
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="bg-white rounded-2xl shadow-card border border-brand-100 p-4">
            <p class="text-[10px] font-bold text-brand-600 uppercase">Effectif</p>
            <p class="text-2xl font-extrabold text-brand-700 mt-1">{{ $eleves->count() }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-blue-100 p-4">
            <p class="text-[10px] font-bold text-blue-600 uppercase">Garçons</p>
            <p class="text-2xl font-extrabold text-blue-700 mt-1">{{ $nbG }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-pink-100 p-4">
            <p class="text-[10px] font-bold text-pink-600 uppercase">Filles</p>
            <p class="text-2xl font-extrabold text-pink-700 mt-1">{{ $nbF }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-amber-100 p-4">
            <p class="text-[10px] font-bold text-amber-600 uppercase">Redoublants</p>
            <p class="text-2xl font-extrabold text-amber-700 mt-1">{{ $nbR }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-purple-100 p-4">
            <p class="text-[10px] font-bold text-purple-600 uppercase">Affectés / Non</p>
            <p class="text-xl font-extrabold text-purple-700 mt-1">{{ $nbAFF }} <span class="text-sm text-gray-400">/ {{ $nbNAFF }}</span></p>
        </div>
    </div>

    {{-- Toolbar : recherche + sort + view + export --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-3 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2 flex-1 max-w-md">
            <div class="relative flex-1">
                <input type="text" x-model="search" placeholder="🔍 Rechercher (nom, prénom, matricule...)"
                       class="w-full pl-3 pr-4 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-brand-300 outline-none">
            </div>
            <select x-model="sortBy" class="rounded-lg border border-gray-200 text-sm px-3 py-2 font-semibold">
                <option value="nom">Tri : Nom</option>
                <option value="matricule">Tri : Matricule</option>
                <option value="age">Tri : Âge</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <button @click="view = 'list'"
                    :class="view === 'list' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-600'"
                    class="text-xs font-bold px-3 py-2 rounded-lg flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                Liste
            </button>
            <button @click="view = 'grid'"
                    :class="view === 'grid' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-600'"
                    class="text-xs font-bold px-3 py-2 rounded-lg flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Grille
            </button>
            <a href="{{ route('mon-espace.fiche-classe.pdf', ['classe' => $classe, 'orientation' => 'landscape']) }}"
               target="_blank"
               class="text-xs font-bold bg-red-100 text-red-700 hover:bg-red-200 px-3 py-2 rounded-lg flex items-center gap-1">
                📄 PDF
            </a>
            <a href="{{ route('mon-espace.fiche-classe.excel', $classe) }}"
               class="text-xs font-bold bg-green-100 text-green-700 hover:bg-green-200 px-3 py-2 rounded-lg flex items-center gap-1">
                📊 Excel
            </a>
        </div>
    </div>

    @if($eleves->isEmpty())
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-12 text-center">
            <svg class="w-16 h-16 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857"/></svg>
            <p class="text-gray-400">Aucun élève dans cette classe.</p>
        </div>
    @else

    {{-- Vue Liste --}}
    <div x-show="view === 'list'" class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-3 py-3 text-left text-xs font-bold text-gray-500 uppercase">N°</th>
                        <th class="px-3 py-3 text-left text-xs font-bold text-gray-500 uppercase">Matricule</th>
                        <th class="px-3 py-3 text-left text-xs font-bold text-gray-500 uppercase">Nom et prénom</th>
                        <th class="px-3 py-3 text-center text-xs font-bold text-gray-500 uppercase">Sexe</th>
                        <th class="px-3 py-3 text-center text-xs font-bold text-gray-500 uppercase">Âge</th>
                        <th class="px-3 py-3 text-center text-xs font-bold text-gray-500 uppercase">Statut</th>
                        <th class="px-3 py-3 text-center text-xs font-bold text-gray-500 uppercase">LV2</th>
                        <th class="px-3 py-3 text-center text-xs font-bold text-gray-500 uppercase">R</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($eleves as $i => $eleve)
                    @php
                        $mat = $eleve->matricule_desps ?: $eleve->matricule_interne;
                        $hay = strtolower(($eleve->nom ?? '') . ' ' . ($eleve->prenom ?? '') . ' ' . ($mat ?? ''));
                    @endphp
                    <tr class="hover:bg-brand-50/30 transition"
                        x-show="!search || '{{ $hay }}'.includes(search.toLowerCase())">
                        <td class="px-3 py-2.5 text-xs text-gray-400 font-mono">{{ $i + 1 }}</td>
                        <td class="px-3 py-2.5 text-xs font-mono font-bold text-gray-700">{{ $mat }}</td>
                        <td class="px-3 py-2.5">
                            <p class="font-semibold text-sm text-gray-800">
                                <span class="uppercase">{{ $eleve->nom }}</span> {{ $eleve->prenom }}
                            </p>
                        </td>
                        <td class="px-3 py-2.5 text-center">
                            <span class="text-xs font-bold {{ $eleve->sexe === 'M' ? 'text-blue-600' : 'text-pink-600' }}">
                                {{ $eleve->sexe ?? '—' }}
                            </span>
                        </td>
                        <td class="px-3 py-2.5 text-center text-xs">{{ $eleve->date_naissance?->age ?? '—' }}</td>
                        <td class="px-3 py-2.5 text-center">
                            @if($eleve->statut_eleve === 'AFF')
                                <span class="text-[10px] font-bold bg-green-100 text-green-700 px-2 py-0.5 rounded-full">AFF</span>
                            @elseif($eleve->statut_eleve === 'NAFF')
                                <span class="text-[10px] font-bold bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">NAFF</span>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-center text-xs">{{ $eleve->lv2 ?? '—' }}</td>
                        <td class="px-3 py-2.5 text-center">
                            @if($eleve->redoublant)
                                <span class="text-[10px] font-bold bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">R</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Vue Grille --}}
    <div x-show="view === 'grid'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($eleves as $eleve)
        @php
            $mat = $eleve->matricule_desps ?: $eleve->matricule_interne;
            $hay = strtolower(($eleve->nom ?? '') . ' ' . ($eleve->prenom ?? '') . ' ' . ($mat ?? ''));
        @endphp
        <div x-show="!search || '{{ $hay }}'.includes(search.toLowerCase())"
             class="bg-white rounded-xl shadow-card border border-gray-100 p-3 flex items-center gap-3 hover:shadow-lg hover:border-brand-200 transition">
            <div class="w-11 h-11 rounded-full flex items-center justify-center font-extrabold text-sm
                        {{ $eleve->sexe === 'M' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700' }}">
                {{ strtoupper(substr($eleve->prenom ?? '', 0, 1)) }}{{ strtoupper(substr($eleve->nom ?? '', 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-sm text-gray-800 truncate"><span class="uppercase">{{ $eleve->nom }}</span> {{ $eleve->prenom }}</p>
                <p class="text-[10px] font-mono text-gray-500">{{ $mat }}</p>
                <div class="flex gap-1 mt-1">
                    @if($eleve->redoublant)<span class="text-[9px] bg-amber-100 text-amber-700 font-bold px-1.5 py-0.5 rounded">R</span>@endif
                    @if($eleve->statut_eleve)<span class="text-[9px] bg-gray-100 text-gray-600 font-bold px-1.5 py-0.5 rounded">{{ $eleve->statut_eleve }}</span>@endif
                    @if($eleve->lv2)<span class="text-[9px] bg-purple-100 text-purple-700 font-bold px-1.5 py-0.5 rounded">{{ $eleve->lv2 }}</span>@endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
