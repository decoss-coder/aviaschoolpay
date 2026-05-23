@extends('documents.pdf._layout')

@section('titre', 'CARNET DE PRÉSENCE')
@section('periode', $classe->nom . ' · Semaine du ' . $debut->format('d/m/Y'))

@section('content')

<div style="margin-bottom:8px;padding:8px;background:#dbeafe;border-radius:4px;font-size:9px;color:#1e3a8a;">
    💡 <b>Mode d'emploi :</b>
    Cochez <b>P</b> pour Présent, <b>A</b> pour Absent, <b>R</b> pour Retard, <b>D</b> pour Dispensé.
    Carnet à signer en fin de semaine par l'enseignant et le responsable de l'établissement.
</div>

@if($inscriptions->isEmpty())
    <p class="text-muted">Aucun élève inscrit dans cette classe.</p>
@else
<table class="data" style="font-size:9px;">
    <thead>
        <tr>
            <th style="width:4%;" rowspan="2" class="center">#</th>
            <th style="width:10%;" rowspan="2">Matricule</th>
            <th style="width:22%;" rowspan="2">Élève</th>
            <th style="width:4%;" rowspan="2" class="center">Sexe</th>
            @foreach($jours as $j)
                <th colspan="2" class="center" style="background:#0a7b3f;color:#fff;text-transform:capitalize;font-size:8px;">{{ $j['libelle'] }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach($jours as $j)
                <th class="center" style="background:#065f46;color:#fff;font-size:7px;width:5%;">Matin</th>
                <th class="center" style="background:#065f46;color:#fff;font-size:7px;width:5%;">Soir</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($inscriptions as $i => $insc)
            <tr style="height:24px;">
                <td class="center">{{ $i+1 }}</td>
                <td class="mono">{{ $insc->eleve?->matricule_interne ?? '—' }}</td>
                <td><b>{{ $insc->eleve?->prenom }} {{ strtoupper($insc->eleve?->nom ?? '') }}</b></td>
                <td class="center">{{ $insc->eleve?->sexe ?? '—' }}</td>
                @foreach($jours as $j)
                    <td class="center" style="border-left:1px solid #cbd5e1;background:#fdfdfd;height:24px;">&nbsp;</td>
                    <td class="center" style="background:#fdfdfd;height:24px;">&nbsp;</td>
                @endforeach
            </tr>
        @endforeach
        {{-- Quelques lignes vides pour ajouter manuellement --}}
        @for($k = 0; $k < 3; $k++)
            <tr style="height:24px;">
                <td class="center" style="color:#cbd5e1;">+</td>
                <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                @foreach($jours as $j)
                    <td style="border-left:1px solid #cbd5e1;">&nbsp;</td><td>&nbsp;</td>
                @endforeach
            </tr>
        @endfor
    </tbody>
</table>

{{-- Légende --}}
<div style="margin-top:8px;font-size:9px;color:#475569;">
    <b>Légende :</b>
    <span class="badge badge-success" style="margin:0 4px;">P = Présent</span>
    <span class="badge badge-danger" style="margin:0 4px;">A = Absent</span>
    <span class="badge badge-warn" style="margin:0 4px;">R = Retard</span>
    <span class="badge badge-info" style="margin:0 4px;">D = Dispensé</span>
</div>

{{-- Signatures --}}
<table style="width:100%;margin-top:25px;border-collapse:collapse;">
    <tr>
        <td style="width:33%;padding:0 10px;text-align:center;font-size:9px;color:#64748b;">
            <b style="text-transform:uppercase;">Enseignant</b>
            <div style="border-top:1px dashed #94a3b8;margin-top:35px;padding-top:3px;font-size:8px;">Signature</div>
        </td>
        <td style="width:33%;padding:0 10px;text-align:center;font-size:9px;color:#64748b;">
            <b style="text-transform:uppercase;">Surveillant général</b>
            <div style="border-top:1px dashed #94a3b8;margin-top:35px;padding-top:3px;font-size:8px;">Signature</div>
        </td>
        <td style="width:33%;padding:0 10px;text-align:center;font-size:9px;color:#64748b;">
            <b style="text-transform:uppercase;">Censeur</b>
            <div style="border-top:1px dashed #94a3b8;margin-top:35px;padding-top:3px;font-size:8px;">Signature & cachet</div>
        </td>
    </tr>
</table>
@endif

@endsection
