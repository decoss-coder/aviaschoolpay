@php $isPortrait = ($orientation ?? 'landscape') === 'portrait'; @endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Liste de classe — {{ $classe->nom }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: {{ $isPortrait ? '7.5pt' : '9pt' }};
        color: #000;
        padding: {{ $isPortrait ? '6mm 7mm' : '10mm 12mm' }};
    }

    .header-line { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1mm; font-size: {{ $isPortrait ? '8pt' : '9pt' }}; }
    .ecole-nom { font-weight: bold; text-transform: uppercase; font-size: {{ $isPortrait ? '9pt' : '10pt' }}; letter-spacing: 0.3px; }
    .annee { font-style: italic; }

    .titre-bloc { margin-top: 2mm; margin-bottom: {{ $isPortrait ? '2mm' : '4mm' }}; display: table; width: 100%; }
    .titre-bloc .titre-row { display: table-row; }
    .titre-bloc .cell { display: table-cell; vertical-align: middle; }
    .titre-bloc .cell-titre { font-weight: bold; font-style: italic; font-size: {{ $isPortrait ? '11pt' : '14pt' }}; text-transform: uppercase; }
    .titre-bloc .cell-meta { text-align: right; font-size: {{ $isPortrait ? '7.5pt' : '9pt' }}; }
    .titre-bloc .cell-meta b { font-weight: bold; }

    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    th, td {
        border: 1px solid #000;
        padding: {{ $isPortrait ? '1px 2px' : '2px 4px' }};
        height: {{ $isPortrait ? '15px' : '20px' }};
        vertical-align: middle;
    }
    th {
        font-weight: bold;
        text-align: center;
        font-size: {{ $isPortrait ? '7pt' : '9pt' }};
        text-transform: uppercase;
        background: #f5f5f5;
        white-space: nowrap;
    }

    .col-n     { width: 4%;  text-align: center; }
    .col-mat   { width: {{ $isPortrait ? '13%' : '11%' }}; font-weight: bold; font-size: {{ $isPortrait ? '7pt' : '8.5pt' }}; }
    .col-nom   { width: {{ $isPortrait ? '38%' : '32%' }}; }
    .col-r     { width: 3%;  text-align: center; }
    .col-an    { width: 4%;  text-align: center; }
    .col-s     { width: 3%;  text-align: center; }
    .col-nat   { width: 5%;  text-align: center; }
    .col-statut{ width: 6%;  text-align: center; }
    .col-lv2   { width: 8%;  text-align: center; }
    .col-arts  { width: 8%;  text-align: center; }
    .col-vide  { width: {{ $isPortrait ? '4%' : '13%' }}; }

    td.col-mat { white-space: nowrap; }
    td.col-nom { font-size: {{ $isPortrait ? '7.5pt' : '9pt' }}; }
    .row td { font-size: {{ $isPortrait ? '7pt' : '9pt' }}; }

    .footer { margin-top: {{ $isPortrait ? '3mm' : '6mm' }}; font-size: {{ $isPortrait ? '7pt' : '8pt' }}; color: #555; display: flex; justify-content: space-between; }
</style>
</head>
<body>

<div class="header-line">
    <span class="ecole-nom">{{ $etab->nom ?? '—' }}</span>
    <span class="annee">Année Scolaire {{ $annee->libelle ?? '—' }}</span>
</div>

<div class="titre-bloc">
    <div class="titre-row">
        <div class="cell cell-titre">LISTE DE CLASSE - {{ $classe->nom }}</div>
        <div class="cell cell-meta">
            <b>Éducateur :</b> {{ $classe->educateur ?? '—' }}<br>
            <b>Prof. Princ. :</b>
            @php $pp = $classe->professeurPrincipal ?? null; @endphp
            {{ $pp ? strtoupper(trim(($pp->nom ?? '').' '.($pp->prenom ?? ''))) : (trim(($ens->nom ?? '').' '.($ens->prenom ?? ''))) }}
        </div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th class="col-n">N°</th>
            <th class="col-mat">MATRICULE</th>
            <th class="col-nom">NOM ET PRENOMS</th>
            <th class="col-r">R</th>
            <th class="col-an">AN</th>
            <th class="col-s">S</th>
            <th class="col-nat">NAT</th>
            <th class="col-statut">STATUT</th>
            <th class="col-lv2">LV2</th>
            <th class="col-arts">ARTS</th>
            <th class="col-vide"></th>
            <th class="col-vide"></th>
        </tr>
    </thead>
    <tbody>
        @foreach($eleves as $i => $eleve)
        <tr class="row">
            <td class="col-n">{{ $i + 1 }}</td>
            <td class="col-mat">{{ $eleve->matricule_desps ?: ($eleve->matricule_interne ?: '—') }}</td>
            <td class="col-nom">{{ strtoupper($eleve->nom ?? '') }} {{ ucfirst(strtolower($eleve->prenom ?? '')) }}</td>
            <td class="col-r">{{ $eleve->redoublant ? 'R' : '' }}</td>
            <td class="col-an">{{ $eleve->date_naissance?->age ?? '' }}</td>
            <td class="col-s">{{ $eleve->sexe ?? '' }}</td>
            <td class="col-nat">
                @php
                    $nat = trim((string)($eleve->nationalite ?? ''));
                    echo $nat !== '' ? strtoupper(substr($nat, 0, 2)) : '';
                @endphp
            </td>
            <td class="col-statut">{{ $eleve->statut_eleve ?? '' }}</td>
            <td class="col-lv2">{{ $eleve->lv2 ?? '' }}</td>
            <td class="col-arts">{{ $eleve->option_arts ?? '' }}</td>
            <td class="col-vide"></td>
            <td class="col-vide"></td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    <span>Édité le {{ now()->format('d/m/Y H:i') }} · AviaSchoolPay</span>
    <span>Effectif : <b>{{ $eleves->count() }}</b>
        — Garçons : {{ $eleves->where('sexe','M')->count() }}
        · Filles : {{ $eleves->where('sexe','F')->count() }}
        @if($eleves->where('redoublant',true)->count() > 0)
            · Redoublants : {{ $eleves->where('redoublant',true)->count() }}
        @endif
    </span>
</div>

</body>
</html>
