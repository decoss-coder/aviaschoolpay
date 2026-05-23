@extends('documents.pdf._layout')

@section('titre', 'ANNUAIRE PARENTS')
@section('periode', ($annee->libelle ?? '—') . ' · ' . $filtre)

@section('content')

<div style="margin-bottom:10px;">
    <span class="kpi kpi-info"><div class="lbl">Contacts</div><div class="val">{{ $inscriptions->count() }}</div></span>
</div>

@if($inscriptions->isEmpty())
    <p class="text-muted">Aucun contact parent disponible pour ce filtre.</p>
@else
@php $parClasse = $inscriptions->groupBy(fn($i) => $i->classe?->nom ?? '—'); @endphp

@foreach($parClasse as $nomClasse => $items)
<h2>{{ $nomClasse }} <span style="font-size:9px;color:#64748b;font-weight:normal;">({{ $items->count() }})</span></h2>
<table class="data">
    <thead>
        <tr>
            <th style="width:5%;">#</th>
            <th style="width:14%;">Matricule</th>
            <th style="width:30%;">Élève</th>
            <th style="width:28%;">Parent / Tuteur</th>
            <th style="width:23%;">Téléphone</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $i => $insc)
            <tr>
                <td class="center">{{ $i+1 }}</td>
                <td class="mono">{{ $insc->eleve?->matricule_interne ?? '—' }}</td>
                <td><b>{{ $insc->eleve?->prenom }} {{ strtoupper($insc->eleve?->nom ?? '') }}</b></td>
                <td>{{ $insc->eleve?->contact_urgence_nom ?? '—' }}</td>
                <td class="mono"><b>{{ $insc->eleve?->contact_urgence_tel ?? '—' }}</b></td>
            </tr>
        @endforeach
    </tbody>
</table>
@endforeach
@endif

@endsection
