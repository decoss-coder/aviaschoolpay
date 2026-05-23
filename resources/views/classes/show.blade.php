@extends('layouts.app')

@section('title', $classe->nom)
@section('page-title', $classe->nom)
@section('page-subtitle', ($classe->niveau->libelle ?? $classe->niveau->code ?? '') . ($classe->serie ? ' — Série ' . $classe->serie->nom : '') . ' · ' . ($classe->anneeScolaire->libelle ?? ''))

@section('content')
<div>

    {{-- HERO HEADER --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-brand-800 rounded-2xl shadow-brand-glow mb-6">
        <div class="absolute -top-20 -right-20 w-72 h-72 bg-gold-400/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-10 -left-10 w-60 h-60 bg-brand-400/30 rounded-full blur-3xl"></div>
        <div class="absolute top-0 right-0 bottom-0 w-1 bg-gradient-to-b from-gold-300 via-gold-400 to-gold-500"></div>

        <div class="relative p-6 lg:p-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center gap-6">
                <div class="w-20 h-20 lg:w-24 lg:h-24 bg-white/15 backdrop-blur rounded-2xl flex items-center justify-center ring-4 ring-white/20 shadow-2xl flex-shrink-0">
                    <span class="font-display text-3xl lg:text-4xl font-extrabold text-white">{{ mb_substr($classe->nom, 0, 3) }}</span>
                </div>

                <div class="flex-1 min-w-0">
                    <h1 class="font-display text-2xl lg:text-3xl font-extrabold text-white tracking-tight leading-tight">
                        {{ $classe->nom }}
                    </h1>
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white/15 backdrop-blur text-white text-[11px] font-bold rounded-full border border-white/20">
                            {{ $classe->niveau->libelle ?? $classe->niveau->code ?? 'Sans niveau' }}
                        </span>
                        @if($classe->serie)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gold-400/20 backdrop-blur text-gold-100 text-[11px] font-bold rounded-full border border-gold-300/40">
                            Série {{ $classe->serie->nom }}
                        </span>
                        @endif
                        @if($classe->professeurPrincipal)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white/15 backdrop-blur text-white text-[11px] font-bold rounded-full border border-white/20">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $classe->professeurPrincipal->prenom }} {{ $classe->professeurPrincipal->nom }}
                        </span>
                        @endif
                    </div>
                    <p class="text-[12px] text-brand-100 mt-3">
                        Scolarité annuelle : <span class="font-extrabold text-gold-300">{{ number_format($classe->scolarite_annuelle ?? 0, 0, ',', ' ') }} FCFA</span>
                        @if(($classe->frais_inscription ?? 0) > 0)
                            · Inscription : <span class="font-bold text-white">{{ number_format($classe->frais_inscription, 0, ',', ' ') }} FCFA</span>
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 lg:flex-col lg:items-stretch">
                    <a href="{{ route('eleves.create', ['classe_id' => $classe->id]) }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-gold-300 to-gold-500 text-brand-900 text-[13px] font-extrabold rounded-xl shadow-gold-glow hover:shadow-lg transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Ajouter des élèves
                    </a>
                    <a href="{{ route('classes.edit', $classe) }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white/15 backdrop-blur text-white text-[13px] font-bold rounded-xl border border-white/20 hover:bg-white/25 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Modifier
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- QUICK STATS --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
        <div class="relative overflow-hidden bg-gradient-to-br from-white to-brand-50/50 border border-brand-100/60 rounded-xl p-4 shadow-card-brand">
            <div class="absolute -top-4 -right-4 w-16 h-16 bg-brand-200/30 rounded-full blur-xl"></div>
            <div class="relative">
                <p class="font-display text-2xl font-extrabold text-gray-900 leading-none">{{ $stats['effectif'] }}<span class="text-sm text-gray-400">/{{ $stats['capacite'] }}</span></p>
                <p class="text-[11px] text-gray-500 font-medium mt-1">Effectif / Capacité</p>
                <div class="w-full bg-gray-100 rounded-full h-1 mt-2 overflow-hidden">
                    <div class="h-1 rounded-full {{ $stats['taux_remplissage'] >= 100 ? 'bg-gradient-to-r from-red-400 to-red-600' : 'bg-gradient-to-r from-brand-400 to-brand-600' }}" style="width: {{ min($stats['taux_remplissage'], 100) }}%"></div>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-blue-50/50 border border-blue-100/60 rounded-xl p-4 shadow-card-blue">
            <div class="absolute -top-4 -right-4 w-16 h-16 bg-blue-200/30 rounded-full blur-xl"></div>
            <div class="relative">
                <p class="font-display text-2xl font-extrabold text-blue-600 leading-none">{{ $stats['garcons'] }}</p>
                <p class="text-[11px] text-gray-500 font-medium mt-1">Garçons</p>
            </div>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-pink-50/50 border border-pink-100/60 rounded-xl p-4 shadow-[0_8px_24px_-8px_rgba(236,72,153,0.18)]">
            <div class="absolute -top-4 -right-4 w-16 h-16 bg-pink-200/30 rounded-full blur-xl"></div>
            <div class="relative">
                <p class="font-display text-2xl font-extrabold text-pink-600 leading-none">{{ $stats['filles'] }}</p>
                <p class="text-[11px] text-gray-500 font-medium mt-1">Filles</p>
            </div>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-violet-50/50 border border-violet-100/60 rounded-xl p-4 shadow-card-violet">
            <div class="absolute -top-4 -right-4 w-16 h-16 bg-violet-200/30 rounded-full blur-xl"></div>
            <div class="relative">
                <p class="font-display text-2xl font-extrabold {{ $stats['moyenne_classe'] && $stats['moyenne_classe'] >= 10 ? 'text-brand-600' : 'text-violet-600' }} leading-none">
                    {{ $stats['moyenne_classe'] ?? '—' }}<span class="text-sm text-gray-400">/20</span>
                </p>
                <p class="text-[11px] text-gray-500 font-medium mt-1">Moyenne classe</p>
            </div>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-gold-50/50 border border-gold-200/60 rounded-xl p-4 shadow-card-gold">
            <div class="absolute -top-4 -right-4 w-16 h-16 bg-gold-200/30 rounded-full blur-xl"></div>
            <div class="relative">
                <p class="font-display text-2xl font-extrabold text-gold-600 leading-none">{{ $stats['places_restantes'] }}</p>
                <p class="text-[11px] text-gray-500 font-medium mt-1">Places dispo</p>
            </div>
        </div>
    </div>

    {{-- LISTE DES ÉLÈVES --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/20 rounded-2xl border border-brand-100/60 shadow-card-brand">
        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-100/60 bg-gradient-to-r from-brand-50/60 via-white to-gold-50/30">
            <div class="flex items-center gap-3">
                <h3 class="font-display text-base font-extrabold text-gray-900">Élèves rattachés</h3>
                <span class="inline-flex items-center text-[11px] font-bold text-brand-700 bg-brand-100 border border-brand-200/60 px-2.5 py-1 rounded-full">{{ $eleves->count() }}</span>
            </div>
            @if($eleves->isNotEmpty())
            <a href="{{ route('eleves.index', ['classe_id' => $classe->id]) }}" class="text-[11px] font-bold text-brand-600 hover:text-brand-700">Vue complète →</a>
            @endif
        </div>

        @if($eleves->isEmpty())
            <div class="p-12 text-center relative overflow-hidden">
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-200/20 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-gold-200/20 rounded-full blur-2xl"></div>
                <div class="relative max-w-md mx-auto">
                    <div class="w-16 h-16 bg-gradient-to-br from-brand-400 to-brand-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-brand-glow">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857"/></svg>
                    </div>
                    <h3 class="font-display text-lg font-extrabold text-gray-900 mb-1">Classe vide</h3>
                    <p class="text-sm text-gray-500 mb-5">Cette classe n'a aucun élève rattaché. Vous pouvez en ajouter un par un ou importer une liste.</p>
                    <div class="flex items-center justify-center gap-2 flex-wrap">
                        <a href="{{ route('eleves.create', ['classe_id' => $classe->id]) }}"
                           class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-brand-500 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Ajouter un élève
                        </a>
                        <a href="#"
                           class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gold-200 text-gold-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gold-50 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Importer une liste
                        </a>
                    </div>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gradient-to-r from-brand-50/40 to-transparent border-b border-brand-100/60">
                            <th class="px-6 py-3 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Élève</th>
                            <th class="px-4 py-3 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Matricule</th>
                            <th class="px-4 py-3 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Date inscription</th>
                            <th class="px-4 py-3 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Statut</th>
                            <th class="px-4 py-3 text-center text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-50/60">
                        @foreach($eleves as $eleve)
                            <tr class="hover:bg-brand-50/30 transition-colors">
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-[11px] font-extrabold shadow-sm ring-2 ring-white flex-shrink-0
                                            {{ $eleve->sexe === 'F' ? 'bg-gradient-to-br from-pink-400 to-pink-600 text-white' : 'bg-gradient-to-br from-blue-400 to-blue-600 text-white' }}">
                                            {{ strtoupper(substr($eleve->prenom, 0, 1)) }}{{ strtoupper(substr($eleve->nom, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[13px] font-bold text-gray-900 truncate">{{ $eleve->nom }} {{ $eleve->prenom }}</p>
                                            <p class="text-[11px] text-gray-400">{{ $eleve->sexe }} · {{ $eleve->age }} ans</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-[12px] font-mono font-bold text-gray-800">{{ $eleve->matricule_interne }}</p>
                                    @if($eleve->matricule_desps)
                                        <p class="text-[10px] text-brand-600 font-medium">DESPS · {{ $eleve->matricule_desps }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-[12px] text-gray-700">
                                        {{ $eleve->date_premiere_inscription?->format('d/m/Y') ?? $eleve->created_at?->format('d/m/Y') ?? '—' }}
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    @if($eleve->statut === 'inscrit')
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-brand-700 bg-brand-100 border border-brand-200/60 px-2 py-0.5 rounded-full">✓ Inscrit</span>
                                    @elseif($eleve->statut === 'pre_inscrit')
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-gold-700 bg-gold-100 border border-gold-200/60 px-2 py-0.5 rounded-full">Pré-inscrit</span>
                                    @elseif($eleve->statut === 'radie')
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-red-700 bg-red-100 border border-red-200/60 px-2 py-0.5 rounded-full">Radié</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-gray-600 bg-gray-100 border border-gray-200/60 px-2 py-0.5 rounded-full">{{ $eleve->statut ?? '—' }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('eleves.show', $eleve) }}" class="p-2 text-gray-500 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors" title="Voir">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                        <a href="{{ route('paiements.create', ['eleve_id' => $eleve->id]) }}" class="p-2 text-gold-600 hover:text-gold-700 hover:bg-gold-50 rounded-lg transition-colors" title="Payer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ZONE DANGER --}}
    <div class="mt-6 p-4 bg-gradient-to-br from-red-50/40 to-white border border-red-100/60 rounded-xl flex items-center justify-between">
        <div>
            <p class="text-[13px] font-bold text-red-800">Zone sensible</p>
            <p class="text-[11px] text-red-600 mt-0.5">Actions irréversibles sur cette classe</p>
        </div>
        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('classes.duplicate', $classe) }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-blue-200 text-blue-700 text-[12px] font-bold rounded-lg hover:bg-blue-50 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    Dupliquer
                </button>
            </form>
            @if($stats['effectif'] === 0)
            <form method="POST" action="{{ route('classes.destroy', $classe) }}" onsubmit="return confirm('Supprimer définitivement cette classe ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-red-200 text-red-600 text-[12px] font-bold rounded-lg hover:bg-red-50 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                    Supprimer
                </button>
            </form>
            @else
            <span class="text-[11px] text-gray-400 italic">Suppression impossible : classe non vide</span>
            @endif
        </div>
    </div>
</div>
@endsection