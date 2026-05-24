@extends('layouts.app')

@section('title', 'Point scolarité & inscription')
@section('page-title', 'Point scolarité & inscription')
@section('page-subtitle', 'AFF : inscription seule — NAFF : inscription + scolarité')

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
                <h1 class="font-display text-xl font-extrabold text-gray-900">Point inscription & scolarité</h1>
                <p class="text-sm text-gray-500 mt-1">Les montants viennent de la grille tarifaire. AFF paie l’inscription. NAFF paie inscription + scolarité.</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-center">
                <div class="rounded-xl bg-gray-50 border border-gray-100 p-3"><p class="text-[10px] uppercase text-gray-500 font-bold">Élèves</p><p class="text-xl font-extrabold">{{ $totaux['effectif'] }}</p></div>
                <div class="rounded-xl bg-blue-50 border border-blue-100 p-3"><p class="text-[10px] uppercase text-blue-600 font-bold">Reste inscription</p><p class="text-lg font-extrabold text-blue-800">{{ number_format($totaux['inscription_reste'],0,',',' ') }} F</p></div>
                <div class="rounded-xl bg-violet-50 border border-violet-100 p-3"><p class="text-[10px] uppercase text-violet-600 font-bold">Reste scolarité</p><p class="text-lg font-extrabold text-violet-800">{{ number_format($totaux['scolarite_reste'],0,',',' ') }} F</p></div>
                <div class="rounded-xl bg-red-50 border border-red-100 p-3"><p class="text-[10px] uppercase text-red-600 font-bold">Total restant</p><p class="text-lg font-extrabold text-red-800">{{ number_format($totaux['total_reste'],0,',',' ') }} F</p></div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('finances.point-postes.index') }}" class="no-print bg-white rounded-2xl border shadow-card p-5 grid grid-cols-1 md:grid-cols-5 gap-3">
        <div><label class="block text-[11px] font-bold uppercase text-gray-500 mb-1">Statut</label><select name="statut_eleve" class="w-full rounded-xl border-gray-200 text-sm"><option value="">Tous</option><option value="AFF" @selected($filters['statut_eleve']==='AFF')>AFF — Affecté</option><option value="NAFF" @selected($filters['statut_eleve']==='NAFF')>NAFF — Non affecté</option></select></div>
        <div><label class="block text-[11px] font-bold uppercase text-gray-500 mb-1">Niveau</label><select name="niveau_id" class="w-full rounded-xl border-gray-200 text-sm"><option value="">Tous</option>@foreach($niveaux as $niveau)<option value="{{ $niveau->id }}" @selected((string)$filters['niveau_id']===(string)$niveau->id)>{{ $niveau->libelle }}</option>@endforeach</select></div>
        <div><label class="block text-[11px] font-bold uppercase text-gray-500 mb-1">Classe</label><select name="classe_id" class="w-full rounded-xl border-gray-200 text-sm"><option value="">Toutes</option>@foreach($classes as $classe)<option value="{{ $classe->id }}" @selected((string)$filters['classe_id']===(string)$classe->id)>{{ $classe->nom }}</option>@endforeach</select></div>
        <div><label class="block text-[11px] font-bold uppercase text-gray-500 mb-1">Recherche</label><input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Nom, matricule" class="w-full rounded-xl border-gray-200 text-sm"></div>
        <div class="flex items-end gap-2"><button class="px-4 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-bold">Filtrer</button><a href="{{ route('finances.point-postes.index') }}" class="px-4 py-2.5 rounded-xl border text-sm font-bold">Reset</a><button type="button" onclick="window.print()" class="px-4 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-bold">Imprimer</button></div>
    </form>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border shadow-card p-5 print-card">
            <h2 class="font-bold mb-3">Synthèse par niveau</h2>
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50 text-[10px] uppercase text-gray-500"><tr><th class="px-3 py-2 text-left">Niveau</th><th>Eff.</th><th>Reste insc.</th><th>Reste scol.</th><th>Total</th></tr></thead><tbody class="divide-y divide-gray-50">@forelse($parNiveau as $niveau => $row)<tr><td class="px-3 py-2 font-bold">{{ $niveau }}</td><td class="text-center">{{ $row['effectif'] }}</td><td class="text-right">{{ number_format($row['inscription_reste'],0,',',' ') }}</td><td class="text-right">{{ number_format($row['scolarite_reste'],0,',',' ') }}</td><td class="text-right font-bold">{{ number_format($row['total_reste'],0,',',' ') }}</td></tr>@empty<tr><td colspan="5" class="py-4 text-center text-gray-400">Aucune donnée</td></tr>@endforelse</tbody></table></div>
        </div>
        <div class="bg-white rounded-2xl border shadow-card p-5 print-card">
            <h2 class="font-bold mb-3">Synthèse par classe</h2>
            <div class="overflow-x-auto max-h-80 overflow-y-auto"><table class="w-full text-sm"><thead class="bg-gray-50 text-[10px] uppercase text-gray-500 sticky top-0"><tr><th class="px-3 py-2 text-left">Classe</th><th>Niv.</th><th>Eff.</th><th>Total reste</th></tr></thead><tbody class="divide-y divide-gray-50">@forelse($parClasse as $classe => $row)<tr><td class="px-3 py-2 font-bold">{{ $classe }}</td><td>{{ $row['niveau'] }}</td><td class="text-center">{{ $row['effectif'] }}</td><td class="text-right font-bold">{{ number_format($row['total_reste'],0,',',' ') }} F</td></tr>@empty<tr><td colspan="4" class="py-4 text-center text-gray-400">Aucune donnée</td></tr>@endforelse</tbody></table></div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border shadow-card overflow-hidden print-card">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between gap-3"><h2 class="font-bold">Détail par élève</h2><span class="text-xs text-gray-500">{{ $lignes->count() }} ligne(s)</span></div>
        <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50 text-[10px] uppercase text-gray-500"><tr><th class="px-4 py-2 text-left">Élève</th><th>Statut</th><th>Niveau</th><th>Classe</th><th class="text-right">Insc. dû</th><th class="text-right">Insc. payé</th><th class="text-right">Insc. reste</th><th class="text-right">Scol. dû</th><th class="text-right">Scol. payé</th><th class="text-right">Scol. reste</th><th class="text-right">Total reste</th></tr></thead><tbody class="divide-y divide-gray-50">@forelse($lignes as $row)@php $g=$row['grille']; $e=$row['eleve']; @endphp<tr><td class="px-4 py-3"><p class="font-bold text-gray-900">{{ $e->prenom }} {{ $e->nom }}</p><p class="text-[11px] text-gray-400">{{ $e->matricule_interne }} @if($e->matricule_desps) · DESPS {{ $e->matricule_desps }} @endif</p></td><td class="text-center"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $e->statut_eleve==='AFF' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $e->statut_eleve }}</span></td><td class="text-center">{{ $row['niveau']?->libelle ?? '—' }}</td><td class="text-center font-semibold">{{ $row['classe']?->nom ?? '—' }}</td><td class="text-right">{{ number_format($g['inscription']['montant'],0,',',' ') }}</td><td class="text-right text-emerald-700">{{ number_format($g['inscription']['paye'],0,',',' ') }}</td><td class="text-right font-bold text-blue-700">{{ number_format($g['inscription']['reste'],0,',',' ') }}</td><td class="text-right">{{ number_format($g['scolarite']['montant'],0,',',' ') }}</td><td class="text-right text-emerald-700">{{ number_format($g['scolarite']['paye'],0,',',' ') }}</td><td class="text-right font-bold text-violet-700">{{ number_format($g['scolarite']['reste'],0,',',' ') }}</td><td class="text-right font-extrabold text-red-700">{{ number_format($g['total']['reste'],0,',',' ') }} F</td></tr>@empty<tr><td colspan="11" class="py-8 text-center text-gray-400">Aucune donnée pour ces filtres.</td></tr>@endforelse</tbody></table></div>
    </div>
</div>
@endsection
