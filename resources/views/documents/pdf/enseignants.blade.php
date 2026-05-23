@extends('documents.pdf._layout')

@php $money = fn($v) => number_format((float) $v, 0, ',', ' '); @endphp

@section('titre', 'LISTE DES ENSEIGNANTS')
@section('periode', $etab?->nom ?? '')

@section('content')

@php
    $totalSalaires = $enseignants->sum('salaire_base');
    $parStatut = $enseignants->groupBy('statut');
@endphp

<div style="margin-bottom:10px;">
    <span class="kpi kpi-info"><div class="lbl">Total enseignants</div><div class="val">{{ $enseignants->count() }}</div></span>
    <span class="kpi kpi-success"><div class="lbl">Titulaires</div><div class="val">{{ $parStatut['titulaire']?->count() ?? 0 }}</div></span>
    <span class="kpi kpi-warn"><div class="lbl">Vacataires</div><div class="val">{{ $parStatut['vacataire']?->count() ?? 0 }}</div></span>
    <span class="kpi"><div class="lbl">Masse salariale base</div><div class="val" style="font-size:11px;">{{ $money($totalSalaires) }} F</div></span>
</div>

@if($enseignants->isEmpty())
    <p class="text-muted">Aucun enseignant actif.</p>
@else
<table class="data">
    <thead>
        <tr>
            <th style="width:4%;">#</th>
            <th style="width:12%;">Matricule</th>
            <th style="width:22%;">Nom & Prénoms</th>
            <th style="width:9%;" class="center">Statut</th>
            <th style="width:13%;">Téléphone</th>
            <th style="width:18%;">Spécialité</th>
            <th style="width:12%;" class="right">Salaire base</th>
            <th style="width:10%;" class="center">Type rém.</th>
        </tr>
    </thead>
    <tbody>
        @foreach($enseignants as $i => $ens)
            @php
                $sc = ['titulaire' => 'badge-success', 'contractuel' => 'badge-info', 'vacataire' => 'badge-warn', 'stagiaire' => 'badge-info'][$ens->statut] ?? 'badge-info';
                $tc = ['fixe' => 'badge-info', 'horaire' => 'badge-warn', 'mixte' => 'badge-success'][$ens->type_remuneration ?? 'fixe'];
            @endphp
            <tr>
                <td class="center">{{ $i+1 }}</td>
                <td class="mono">{{ $ens->matricule_mena ?? '—' }}</td>
                <td><b>{{ $ens->prenom }} {{ strtoupper($ens->nom) }}</b>
                    @if($ens->affectations->isNotEmpty())
                        <br><span style="font-size:7px;color:#64748b;">
                        @foreach($ens->affectations->take(3) as $af)
                            {{ $af->matiere?->nom ?? '' }}/{{ $af->classe?->nom ?? '' }}@if(! $loop->last), @endif
                        @endforeach
                        @if($ens->affectations->count() > 3) +{{ $ens->affectations->count() - 3 }}@endif
                        </span>
                    @endif
                </td>
                <td class="center"><span class="badge {{ $sc }}">{{ ucfirst($ens->statut) }}</span></td>
                <td class="mono" style="font-size:8px;">{{ $ens->telephone }}</td>
                <td>{{ $ens->specialite ?? '—' }}</td>
                <td class="right">{{ $money($ens->salaire_base) }} F</td>
                <td class="center"><span class="badge {{ $tc }}">{{ ucfirst($ens->type_remuneration ?? 'fixe') }}</span></td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6" class="right">MASSE SALARIALE TOTALE</td>
            <td class="right">{{ $money($totalSalaires) }} F</td>
            <td>—</td>
        </tr>
    </tfoot>
</table>
@endif

@endsection
