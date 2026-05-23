@extends('layouts.app')
@section('title', 'Rapports financiers')
@section('page-title', 'Rapports financiers')
@section('page-subtitle', 'Génération de PDF officiels')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-gradient-to-br from-slate-700 to-slate-900 rounded-xl flex items-center justify-center shadow-card">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <div>
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Reporting</p>
            <h2 class="font-display text-2xl font-extrabold text-gray-900">Générateur de rapports PDF</h2>
        </div>
    </div>

    <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-900">
        <p class="font-bold mb-1">ℹ Mode de génération</p>
        <p>Chaque rapport s'ouvre dans un nouvel onglet en aperçu. Cochez <b>Télécharger</b> pour récupérer le fichier directement.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- 1. Paiements --}}
        <form method="GET" action="{{ route('rapports.paiements.pdf') }}" target="_blank"
              class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden hover:shadow-card-hover transition">
            <div class="h-1 bg-gradient-to-r from-blue-400 to-blue-600"></div>
            <div class="p-6 space-y-4">
                <div class="flex items-start gap-3">
                    <span class="text-3xl">💰</span>
                    <div>
                        <h3 class="font-display text-lg font-extrabold text-gray-900">État des paiements</h3>
                        <p class="text-xs text-gray-500">Détail de tous les paiements confirmés sur une période</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Du</label>
                        <input type="date" name="date_debut" value="{{ now()->startOfMonth()->format('Y-m-d') }}" required
                               class="w-full rounded-xl border-gray-200 text-sm focus:border-blue-400 focus:ring-blue-100" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Au</label>
                        <input type="date" name="date_fin" value="{{ now()->endOfMonth()->format('Y-m-d') }}" required
                               class="w-full rounded-xl border-gray-200 text-sm focus:border-blue-400 focus:ring-blue-100" />
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Classe (facultatif)</label>
                        <select name="classe_id" class="w-full rounded-xl border-gray-200 text-sm focus:border-blue-400 focus:ring-blue-100">
                            <option value="">Toutes les classes</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                    <label class="flex items-center gap-2 text-xs text-gray-600">
                        <input type="checkbox" name="download" value="1" class="rounded" />
                        Télécharger
                    </label>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-blue-500 to-blue-700 text-white text-sm font-bold rounded-xl shadow-card-blue hover:shadow-lg transition flex items-center justify-center gap-2">
                        📄 Générer le PDF
                    </button>
                </div>
            </div>
        </form>

        {{-- 2. Bilan scolarité --}}
        <form method="GET" action="{{ route('rapports.bilan-scolarite.pdf') }}" target="_blank"
              class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden hover:shadow-card-hover transition">
            <div class="h-1 bg-gradient-to-r from-emerald-400 to-emerald-600"></div>
            <div class="p-6 space-y-4">
                <div class="flex items-start gap-3">
                    <span class="text-3xl">🎓</span>
                    <div>
                        <h3 class="font-display text-lg font-extrabold text-gray-900">Bilan scolarité</h3>
                        <p class="text-xs text-gray-500">Récapitulatif : dû, payé, reste, par classe + top débiteurs</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Année scolaire</label>
                    <select name="annee_id" class="w-full rounded-xl border-gray-200 text-sm focus:border-emerald-400 focus:ring-emerald-100">
                        <option value="">{{ $anneeCourante?->libelle ?? '— Aucune année active —' }} (en cours)</option>
                        @foreach($annees as $a)
                            <option value="{{ $a->id }}">{{ $a->libelle }}{{ $a->en_cours ? ' ✓' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="bg-emerald-50 rounded-xl p-3 text-xs text-emerald-800">
                    💡 Comprend : montants dûs, encaissés, restes à payer, taux de recouvrement par classe, et liste des 20 plus gros débiteurs.
                </div>

                <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                    <label class="flex items-center gap-2 text-xs text-gray-600">
                        <input type="checkbox" name="download" value="1" class="rounded" />
                        Télécharger
                    </label>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-700 text-white text-sm font-bold rounded-xl shadow-card hover:shadow-lg transition flex items-center justify-center gap-2">
                        📄 Générer le PDF
                    </button>
                </div>
            </div>
        </form>

        {{-- 3. Rapport mensuel --}}
        <form method="GET" action="{{ route('rapports.mensuel.pdf') }}" target="_blank"
              class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden hover:shadow-card-hover transition">
            <div class="h-1 bg-gradient-to-r from-violet-400 to-violet-600"></div>
            <div class="p-6 space-y-4">
                <div class="flex items-start gap-3">
                    <span class="text-3xl">📅</span>
                    <div>
                        <h3 class="font-display text-lg font-extrabold text-gray-900">Rapport mensuel</h3>
                        <p class="text-xs text-gray-500">Revenus, dépenses et résultat d'un mois</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Mois</label>
                    <input type="month" name="mois" value="{{ now()->format('Y-m') }}" required
                           class="w-full rounded-xl border-gray-200 text-sm focus:border-violet-400 focus:ring-violet-100" />
                </div>

                <div class="bg-violet-50 rounded-xl p-3 text-xs text-violet-800">
                    💡 Inclut : revenus ventilés (inscription/scolarité), dépenses par catégorie, détail journalier, résultat net.
                </div>

                <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                    <label class="flex items-center gap-2 text-xs text-gray-600">
                        <input type="checkbox" name="download" value="1" class="rounded" />
                        Télécharger
                    </label>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-violet-500 to-violet-700 text-white text-sm font-bold rounded-xl shadow-card-violet hover:shadow-lg transition flex items-center justify-center gap-2">
                        📄 Générer le PDF
                    </button>
                </div>
            </div>
        </form>

        {{-- 4. Rapport trimestriel --}}
        <form method="GET" action="{{ route('rapports.trimestriel.pdf') }}" target="_blank"
              class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden hover:shadow-card-hover transition">
            <div class="h-1 bg-gradient-to-r from-amber-400 to-amber-600"></div>
            <div class="p-6 space-y-4">
                <div class="flex items-start gap-3">
                    <span class="text-3xl">📊</span>
                    <div>
                        <h3 class="font-display text-lg font-extrabold text-gray-900">Rapport trimestriel</h3>
                        <p class="text-xs text-gray-500">Bilan d'un trimestre civil (3 mois)</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Année</label>
                        <select name="annee" required class="w-full rounded-xl border-gray-200 text-sm focus:border-amber-400 focus:ring-amber-100">
                            @for($y = now()->year; $y >= now()->year - 3; $y--)
                                <option value="{{ $y }}" @selected($y === (int) now()->year)>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Trimestre</label>
                        <select name="trimestre" required class="w-full rounded-xl border-gray-200 text-sm focus:border-amber-400 focus:ring-amber-100">
                            @php $tActuel = (int) ceil(now()->month / 3); @endphp
                            <option value="1" @selected($tActuel === 1)>T1 (Jan - Mars)</option>
                            <option value="2" @selected($tActuel === 2)>T2 (Avr - Juin)</option>
                            <option value="3" @selected($tActuel === 3)>T3 (Juil - Sept)</option>
                            <option value="4" @selected($tActuel === 4)>T4 (Oct - Déc)</option>
                        </select>
                    </div>
                </div>

                <div class="bg-amber-50 rounded-xl p-3 text-xs text-amber-800">
                    💡 Synthèse trimestrielle complète pour les bilans périodiques et la direction.
                </div>

                <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                    <label class="flex items-center gap-2 text-xs text-gray-600">
                        <input type="checkbox" name="download" value="1" class="rounded" />
                        Télécharger
                    </label>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 text-white text-sm font-bold rounded-xl shadow-card-gold hover:shadow-lg transition flex items-center justify-center gap-2">
                        📄 Générer le PDF
                    </button>
                </div>
            </div>
        </form>

    </div>
</div>
@endsection
