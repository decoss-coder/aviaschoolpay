@extends('documents.pdf._layout')

@php $fmt = fn($v) => $v !== null ? number_format((float) $v, 2, ',', ' ') : '—'; @endphp

@section('titre', 'ÉLÈVES EN DIFFICULTÉ')
@section('periode', ($annee->libelle ?? '') . ' · ' . $trimestre->libelle . ' · seuil < ' . $seuil)

@section('content')

<div style="margin-bottom:10px;">
    <span class="kpi kpi-danger"><div class="lbl">Élèves concernés</div><div class="val">{{ $inscriptions->count() }}</div></span>
    <span class="kpi kpi-warn"><div class="lbl">Seuil critique</div><div class="val">&lt; {{ $seuil }}/20</div></span>
    <span class="kpi"><div class="lbl">Filtre</div><div class="val" style="font-size:10px;">{{ $filtre }}</div></span>
    <span class="kpi kpi-info"><div class="lbl">Trimestre</div><div class="val" style="font-size:10px;">{{ $trimestre->libelle }}</div></span>
</div>

@if($inscriptions->isEmpty())
    <div style="padding:25px;text-align:center;background:#ecfdf5;border:1px solid #10b981;border-radius:5px;">
        <div style="font-size:30px;">🎉</div>
        <p class="text-positive">Aucun élève en difficulté pour ce trimestre — Excellent travail pédagogique !</p>
    </div>
@else

<h2>Liste des élèves en difficulté ({{ $inscriptions->count() }})</h2>
<table class="data">
    <thead>
        <tr>
            <th style="width:4%;" class="center">#</th>
            <th style="width:11%;">Matricule</th>
            <th style="width:22%;">Élève</th>
            <th style="width:8%;">Classe</th>
            <th style="width:9%;" class="center">Moyenne</th>
            <th style="width:7%;" class="center">Rang</th>
            <th style="width:9%;">Mention</th>
            <th style="width:14%;">Contact parent</th>
            <th style="width:16%;">Téléphone</th>
        </tr>
    </thead>
    <tbody>
        @foreach($inscriptions as $i => $insc)
            @php
                $couleur = $insc->moyenne < 6 ? '#b91c1c' : ($insc->moyenne < 8 ? '#dc2626' : '#b45309');
                $gravite = $insc->moyenne < 6 ? 'CRITIQUE' : ($insc->moyenne < 8 ? 'SÉRIEUSE' : 'MODÉRÉE');
                $bgGravite = $insc->moyenne < 6 ? 'badge-danger' : ($insc->moyenne < 8 ? 'badge-danger' : 'badge-warn');
            @endphp
            <tr>
                <td class="center">{{ $i+1 }}</td>
                <td class="mono">{{ $insc->eleve?->matricule_interne ?? '—' }}</td>
                <td>
                    <b>{{ $insc->eleve?->prenom }} {{ strtoupper($insc->eleve?->nom ?? '') }}</b>
                    <br><span class="badge {{ $bgGravite }}" style="font-size:7px;">{{ $gravite }}</span>
                </td>
                <td>{{ $insc->classe?->nom ?? '—' }}</td>
                <td class="center" style="color:{{ $couleur }};font-weight:bold;font-size:13px;">{{ $fmt($insc->moyenne) }}</td>
                <td class="center">{{ $insc->rang ?? '—' }}</td>
                <td style="font-size:8px;">{{ $insc->mention ?? '—' }}</td>
                <td style="font-size:8px;">{{ $insc->eleve?->contact_urgence_nom ?? '—' }}</td>
                <td class="mono" style="font-size:8px;"><b>{{ $insc->eleve?->contact_urgence_tel ?? '—' }}</b></td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- Recommandations IA --}}
<h2>Recommandations pédagogiques</h2>
<div style="background:#fef3c7;border:1px solid #f59e0b;border-radius:5px;padding:12px;font-size:9px;color:#92400e;">
    <p style="font-weight:bold;margin-bottom:5px;">📌 Plan d'action suggéré :</p>
    <ol style="margin-left:20px;line-height:1.6;">
        <li><b>Convoquer les parents</b> des élèves dont la moyenne est &lt; 8/20 (situation critique).</li>
        <li><b>Cours de soutien</b> recommandés en français, mathématiques et matières scientifiques de base.</li>
        <li><b>Tutorat entre pairs</b> : associer chaque élève à un tuteur (élève du top 5 de la même classe).</li>
        <li><b>Suivi individuel</b> mensuel par le professeur principal avec fiche de progression.</li>
        <li><b>Détection</b> d'éventuels problèmes personnels (santé, famille) via entretien individuel.</li>
        <li><b>Communication aux parents</b> via SMS automatique des notes à chaque évaluation.</li>
    </ol>
</div>

<div style="margin-top:10px;padding:8px;background:#dbeafe;border:1px solid #3b82f6;border-radius:5px;font-size:9px;color:#1e3a8a;">
    💡 <b>Astuce :</b> Pour relancer les parents en masse, allez sur le centre de Communication et créez une annonce ciblée à ces parents (sélection manuelle ou par classe).
</div>
@endif

@endsection
