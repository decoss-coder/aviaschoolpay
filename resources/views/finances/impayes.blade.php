@extends('layouts.app')

@section('title', 'Élèves non soldés')
@section('page-title', 'Élèves non soldés')
@section('page-subtitle', 'Inscription et scolarité — '.$annee->libelle)

@push('styles')
<style>
@media print {
    aside, header, .no-print { display: none !important; }
    main { margin-left: 0 !important; }
    body { background: white !important; }
    .print-card { box-shadow: none !important; border: 1px solid #ddd !important; }
    table { font-size: 11px; }
}
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="bg-white rounded-2xl border shadow-card p-5 print-card">
        <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
            <div>
                <h1 class="font-display text-xl font-extrabold text-gray-900">Liste des élèves non soldés</h1>
                <p class="text-sm text-gray-500 mt-1">Tri par inscription ou scolarité, niveau et classe. Impression selon les filtres appliqués.</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-center">
                <div class="rounded-xl bg-red-50 border border-red-100 p-3">
                    <p class="text-[10px] uppercase text-red-500 font-bold">Élèves</p>
                    <p class="text-xl font-extrabold text-red-700">{{ $totaux['effectif'] }}</p>
                </div>
                <div class="rounded-xl bg-amber-50 border border-amber-100 p-3">
                    <p class="text-[10px] uppercase text-amber-600 font-bold">Reste inscription</p>
                    <p class="text-lg font-extrabold text-amber-800">{{ number_format($totaux['inscription'], 0, ',', ' ') }} F</p>
                </div>
                <div class="rounded-xl bg-violet-50 border border-violet-100 p-3">
                    <p class="text-[10px] uppercase text-violet-600 font-bold">Reste scolarité</p>
                    <p class="text-lg font-extrabold text-violet-800">{{ number_format($totaux['scolarite'], 0, ',', ' ') }} F</p>
                </div>
                <div class="rounded-xl bg-brand-50 border border-brand-100 p-3">
                    <p class="text-[10px] uppercase text-brand-600 font-bold">Total restant</p>
                    <p class="text-lg font-extrabold text-brand-800">{{ number_format($totaux['total'], 0, ',', ' ') }} F</p>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('finances.impayes.index') }}" class="no-print bg-white rounded-2xl border shadow-card p-5 grid grid-cols-1 md:grid-cols-5 gap-3">
        <div>
            <label class="block text-[11px] font-bold uppercase text-gray-500 mb-1">Type d'impayé</label>
            <select name="poste" class="w-full rounded-xl border-gray-200 text-sm">
                <option value="tous" @selected($filters['poste']==='tous')>Tous</option>
                <option value="inscription" @selected($filters['poste']==='inscription')>Inscription</option>
                <option value="scolarite" @selected($filters['poste']==='scolarite')>Scolarité</option>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold uppercase text-gray-500 mb-1">Niveau</label>
            <select name="niveau_id" class="w-full rounded-xl border-gray-200 text-sm">
                <option value="">Tous les niveaux</option>
                @foreach($niveaux as $niveau)
                    <option value="{{ $niveau->id }}" @selected((string)$filters['niveau_id']===(string)$niveau->id)>{{ $niveau->libelle }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold uppercase text-gray-500 mb-1">Classe</label>
            <select name="classe_id" class="w-full rounded-xl border-gray-200 text-sm">
                <option value="">Toutes les classes</option>
                @foreach($classes as $classe)
                    <option value="{{ $classe->id }}" @selected((string)$filters['classe_id']===(string)$classe->id)>{{ $classe->nom }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold uppercase text-gray-500 mb-1">Recherche</label>
            <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Nom, prénom, matricule" class="w-full rounded-xl border-gray-200 text-sm">
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-bold">Filtrer</button>
            <a href="{{ route('finances.impayes.index') }}" class="px-4 py-2.5 rounded-xl border text-sm font-bold">Reset</a>
            <button type="button" onclick="window.print()" class="px-4 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-bold">Imprimer</button>
        </div>
    </form>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border shadow-card p-5 print-card">
            <h2 class="font-bold mb-3">Total restant par niveau</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-[10px] uppercase text-gray-500"><tr><th class="px-3 py-2 text-left">Niveau</th><th>Élèves</th><th>Inscription</th><th>Scolarité</th><th>Total</th></tr></thead>
                    <tbody class="divide-y divide-gray-50">
                    @forelse($totauxParNiveau as $niveau => $row)
                        <tr><td class="px-3 py-2 font-bold">{{ $niveau }}</td><td class="text-center">{{ $row['effectif'] }}</td><td class="text-right">{{ number_format($row['inscription'],0,',',' ') }}</td><td class="text-right">{{ number_format($row['scolarite'],0,',',' ') }}</td><td class="text-right font-bold">{{ number_format($row['total'],0,',',' ') }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-center text-gray-400">Aucun impayé</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="bg-white rounded-2xl border shadow-card p-5 print-card">
            <h2 class="font-bold mb-3">Total restant par classe</h2>
            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-[10px] uppercase text-gray-500 sticky top-0"><tr><th class="px-3 py-2 text-left">Classe</th><th>Niv.</th><th>Élèves</th><th>Total</th></tr></thead>
                    <tbody class="divide-y divide-gray-50">
                    @forelse($totauxParClasse as $classe => $row)
                        <tr><td class="px-3 py-2 font-bold">{{ $classe }}</td><td>{{ $row['niveau'] }}</td><td class="text-center">{{ $row['effectif'] }}</td><td class="text-right font-bold">{{ number_format($row['total'],0,',',' ') }} F</td></tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-center text-gray-400">Aucun impayé</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border shadow-card overflow-hidden print-card">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between gap-3">
            <h2 class="font-bold">Détail des élèves non soldés</h2>
            <span class="text-xs text-gray-500">{{ $impayes->count() }} ligne(s)</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-[10px] uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-2 text-left">Élève</th>
                        <th class="px-4 py-2">Niveau</th>
                        <th class="px-4 py-2">Classe</th>
                        <th class="px-4 py-2 text-right">Reste inscription</th>
                        <th class="px-4 py-2 text-right">Reste scolarité</th>
                        <th class="px-4 py-2 text-right">Total restant</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                @forelse($impayes as $inscription)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-bold text-gray-900">{{ $inscription->eleve?->prenom }} {{ $inscription->eleve?->nom }}</p>
                            <p class="text-[11px] text-gray-400">{{ $inscription->eleve?->matricule_interne ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-3 text-center">{{ $inscription->classe?->niveau?->libelle ?? '—' }}</td>
                        <td class="px-4 py-3 text-center font-semibold">{{ $inscription->classe?->nom ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-amber-700 font-bold">{{ number_format($inscription->reste_inscription_calc,0,',',' ') }} F</td>
                        <td class="px-4 py-3 text-right text-violet-700 font-bold">{{ number_format($inscription->reste_scolarite_calc,0,',',' ') }} F</td>
                        <td class="px-4 py-3 text-right text-red-700 font-extrabold">{{ number_format($inscription->reste_total_calc,0,',',' ') }} F</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-gray-400">Aucun élève non soldé pour ces filtres.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
