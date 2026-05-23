@extends('documents.pdf._layout')

@section('titre', 'LISTE DES ÉLÈVES')
@section('periode', ($annee->libelle ?? '—') . ' · ' . $filtre)

@section('content')
@php
    $totalF = $inscriptions->filter(fn($i) => $i->eleve?->sexe === 'F')->count();
    $totalM = $inscriptions->filter(fn($i) => $i->eleve?->sexe === 'M')->count();
    $totalRedoublants = $inscriptions->filter(fn($i) => $i->eleve?->redoublant)->count();
@endphp

<div style="margin-bottom:10px;">
    <span class="kpi kpi-info"><div class="lbl">Effectif</div><div class="val">{{ $inscriptions->count() }}</div></span>
    <span class="kpi kpi-success"><div class="lbl">Filles</div><div class="val">{{ $totalF }}</div></span>
    <span class="kpi"><div class="lbl">Garçons</div><div class="val">{{ $totalM }}</div></span>
    <span class="kpi kpi-warn"><div class="lbl">Redoublants</div><div class="val">{{ $totalRedoublants }}</div></span>
</div>

@if($inscriptions->isEmpty())
    <p class="text-muted">Aucun élève à afficher pour ce filtre.</p>
@else
@php $parClasse = $inscriptions->groupBy(fn($i) => $i->classe?->nom ?? '—'); @endphp

@foreach($parClasse as $nomClasse => $items)
<h2>{{ $nomClasse }} <span style="font-size:9px;color:#64748b;font-weight:normal;">({{ $items->count() }} élève(s))</span></h2>
<table class="data">
    <thead>
        <tr>
            <th style="width:5%;">#</th>
            <th style="width:14%;">Matricule</th>
            <th style="width:30%;">Nom & Prénoms</th>
            <th style="width:6%;" class="center">Sexe</th>
            <th style="width:13%;">Né(e) le</th>
            <th style="width:22%;">Contact urgence</th>
            <th style="width:10%;" class="center">Statut</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $i => $insc)
            <tr>
                <td class="center">{{ $i+1 }}</td>
                <td class="mono">{{ $insc->eleve?->matricule_interne ?? '—' }}</td>
                <td><b>{{ $insc->eleve?->prenom }} {{ strtoupper($insc->eleve?->nom ?? '') }}</b></td>
                <td class="center">{{ $insc->eleve?->sexe ?? '—' }}</td>
                <td>{{ $insc->eleve?->date_naissance?->format('d/m/Y') ?? '—' }}</td>
                <td class="mono">{{ $insc->eleve?->contact_urgence_tel ?? '—' }}</td>
                <td class="center">
                    @if($insc->eleve?->redoublant)<span class="badge badge-warn">Redoubl.</span>@endif
                    @if($insc->type === 'nouvelle')<span class="badge badge-success">Nouveau</span>@endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
@endforeach
@endif

@endsection
