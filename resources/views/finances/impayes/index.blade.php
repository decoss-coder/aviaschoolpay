@extends('layouts.app')

@section('title', 'Élèves impayés')
@section('page-title', 'Élèves impayés')
@section('page-subtitle', 'Inscription et scolarité non soldées')

@section('content')
<div class="max-w-7xl mx-auto space-y-5">
    <form method="GET" action="{{ route('finances.impayes.index') }}" class="no-print bg-white rounded-2xl border shadow-card p-5 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <div>
                <label class="block text-[11px] font-bold uppercase text-gray-500 mb-1">Type d'impayé</label>
                <select name="poste" class="w-full rounded-xl border-gray-200 text-sm">
                    <option value="tous" @selected($poste === 'tous')>Tous</option>
                    <option value="inscription" @selected($poste === 'inscription')>Inscription</option>
                    <option value="scolarite" @selected($poste === 'scolarite')>Scolarité</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase text-gray-500 mb-1">Niveau</label>
                <select name="niveau_id" class="w-full rounded-xl border-gray-200 text-sm">
                    <option value="">Tous les niveaux</option>
                    @foreach($niveaux as $niveau)
                        <option value="{{ $niveau->id }}" @selected((int) $niveauId === (int) $niveau->id)>{{ $niveau->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase text-gray-500 mb-1">Classe</label>
                <select name="classe_id" class="w-full rounded-xl border-gray-200 text-sm">
                    <option value="">Toutes les classes</option>
                    @foreach($classes as $classe)
                        <option value="{{ $classe->id }}" @selected((int) $classeId === (int) $classe->id)>
                            {{ $classe->niveau?->libelle ? $classe->niveau->libelle.' - ' : '' }}{{ $classe->nom }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase text-gray-500 mb-1">Recherche</label>
                <input type="text" name="q" value="{{ $q }}" placeholder="Nom ou matricule" class="w-full rounded-xl border-gray-200 text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button class="flex-1 px-4 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-bold">Filtrer</button>
                <a href="{{ route('finances.impayes.index') }}" class="px-4 py-2.5 rounded-xl border text-sm font-bold text-gray-600">Reset</a>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-2xl border shadow-card p-5">
        <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
            <div>
                <h2 class="font-display text-lg font-extrabold text-gray-900">Liste des élèves non soldés</h2>
                <p class="text-xs text-gray-500">Année scolaire : {{ $annee->libelle }}</p>
            </div>
            <button onclick="window.print()" class="no-print px-4 py-2 rounded-xl bg-gray-900 text-white text-sm font-bold">Imprimer le filtre</button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-5">
            <div class="rounded-xl bg-red-50 border border-red-100 p-3">
                <p class="text-[10px] uppercase font-bold text-red-500">Élèves concernés</p>
                <p class="text-2xl font-extrabold text-red-700">{{ $totaux['effectif'] }}</p>
            </div>
            <div class="rounded-xl bg-amber-50 border border-amber-100 p-3">
                <p class="text-[10px] uppercase font-bold text-amber-600">Reste inscription</p>
                <p class="text-xl font-extrabold text-amber-700">{{ number_format($totaux['reste_inscription'], 0, ',', ' ') }} F</p>
            </div>
            <div class="rounded-xl bg-violet-50 border border-violet-100 p-3">
                <p class="text-[10px] uppercase font-bold text-violet-600">Reste scolarité</p>
                <p class="text-xl font-extrabold text-violet-700">{{ number_format($totaux['reste_scolarite'], 0, ',', ' ') }} F</p>
            </div>
            <div class="rounded-xl bg-gray-50 border border-gray-100 p-3">
                <p class="text-[10px] uppercase font-bold text-gray-500">Total restant</p>
                <p class="text-xl font-extrabold text-gray-900">{{ number_format($totaux['reste_total'], 0, ',', ' ') }} F</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-50 text-[10px] uppercase text-gray-500">
                    <tr>
                        <th class="px-3 py-2 text-left">Élève</th>
                        <th class="px-3 py-2 text-left">Matricule</th>
                        <th class="px-3 py-2 text-left">Niveau</th>
                        <th class="px-3 py-2 text-left">Classe</th>
                        <th class="px-3 py-2 text-right">Inscription</th>
                        <th class="px-3 py-2 text-right">Scolarité</th>
                        <th class="px-3 py-2 text-right">Payé</th>
                        <th class="px-3 py-2 text-right">Reste total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        <tr>
                            <td class="px-3 py-2 font-semibold text-gray-900">{{ $row['eleve_nom'] }}</td>
                            <td class="px-3 py-2 text-gray-500">{{ $row['matricule'] }}</td>
                            <td class="px-3 py-2">{{ $row['niveau_libelle'] }}</td>
                            <td class="px-3 py-2">{{ $row['classe_nom'] }}</td>
                            <td class="px-3 py-2 text-right text-amber-700 font-bold">{{ number_format($row['reste_inscription'], 0, ',', ' ') }}</td>
                            <td class="px-3 py-2 text-right text-violet-700 font-bold">{{ number_format($row['reste_scolarite'], 0, ',', ' ') }}</td>
                            <td class="px-3 py-2 text-right text-gray-500">{{ number_format($row['total_paye'], 0, ',', ' ') }}</td>
                            <td class="px-3 py-2 text-right text-red-700 font-extrabold">{{ number_format($row['reste_total'], 0, ',', ' ') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-8 text-center text-gray-400">Aucun impayé pour ce filtre.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 font-extrabold">
                    <tr>
                        <td colspan="4" class="px-3 py-2 text-right">TOTAL</td>
                        <td class="px-3 py-2 text-right">{{ number_format($totaux['reste_inscription'], 0, ',', ' ') }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($totaux['reste_scolarite'], 0, ',', ' ') }}</td>
                        <td></td>
                        <td class="px-3 py-2 text-right">{{ number_format($totaux['reste_total'], 0, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white rounded-2xl border shadow-card p-5">
            <h3 class="font-bold mb-3">Total restant par niveau</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-[10px] uppercase text-gray-500 bg-gray-50"><tr><th class="px-3 py-2 text-left">Niveau</th><th class="px-3 py-2 text-right">Élèves</th><th class="px-3 py-2 text-right">Inscription</th><th class="px-3 py-2 text-right">Scolarité</th><th class="px-3 py-2 text-right">Total</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($totauxParNiveau as $row)
                            <tr><td class="px-3 py-2 font-semibold">{{ $row['label'] }}</td><td class="px-3 py-2 text-right">{{ $row['effectif'] }}</td><td class="px-3 py-2 text-right">{{ number_format($row['reste_inscription'], 0, ',', ' ') }}</td><td class="px-3 py-2 text-right">{{ number_format($row['reste_scolarite'], 0, ',', ' ') }}</td><td class="px-3 py-2 text-right font-bold">{{ number_format($row['reste_total'], 0, ',', ' ') }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-2xl border shadow-card p-5">
            <h3 class="font-bold mb-3">Total restant par classe</h3>
            <div class="overflow-x-auto max-h-[420px] overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="text-[10px] uppercase text-gray-500 bg-gray-50 sticky top-0"><tr><th class="px-3 py-2 text-left">Classe</th><th class="px-3 py-2 text-right">Élèves</th><th class="px-3 py-2 text-right">Inscription</th><th class="px-3 py-2 text-right">Scolarité</th><th class="px-3 py-2 text-right">Total</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($totauxParClasse as $row)
                            <tr><td class="px-3 py-2 font-semibold">{{ $row['label'] }}</td><td class="px-3 py-2 text-right">{{ $row['effectif'] }}</td><td class="px-3 py-2 text-right">{{ number_format($row['reste_inscription'], 0, ',', ' ') }}</td><td class="px-3 py-2 text-right">{{ number_format($row['reste_scolarite'], 0, ',', ' ') }}</td><td class="px-3 py-2 text-right font-bold">{{ number_format($row['reste_total'], 0, ',', ' ') }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print, aside, header { display: none !important; }
    main { margin-left: 0 !important; }
    body { background: white !important; }
    .shadow-card, .shadow-card-brand, .shadow-card-violet { box-shadow: none !important; }
    table { page-break-inside: auto; }
    tr { page-break-inside: avoid; page-break-after: auto; }
}
</style>
@endsection
