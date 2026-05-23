@php $isPortrait = ($orientation ?? 'landscape') === 'portrait'; @endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>{{ $titre }} — {{ $classe->nom }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: {{ $isPortrait ? '7.5pt' : '9pt' }};
        color: #111;
        padding: {{ $isPortrait ? '6mm 8mm' : '12px 18px' }};
    }

    .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
    .header-left { width: 55%; }
    .ecole { font-weight: bold; font-size: {{ $isPortrait ? '11pt' : '14pt' }}; text-transform: uppercase; color: #1e293b; letter-spacing: 0.3px; }
    .annee { font-size: {{ $isPortrait ? '8pt' : '10pt' }}; color: #64748b; }
    .titre-box { border: 1.5px solid #000; padding: 3px 10px; font-weight: bold; font-style: italic; font-size: {{ $isPortrait ? '10pt' : '12pt' }}; text-transform: uppercase; }

    .infos { margin: {{ $isPortrait ? '6px 0 4px 0' : '10px 0 6px 0' }}; font-size: {{ $isPortrait ? '8pt' : '10pt' }}; }
    .infos b { font-weight: bold; }
    .infos .underline { border-bottom: 1px solid #555; display: inline-block; min-width: {{ $isPortrait ? '90px' : '160px' }}; padding: 0 4px; }

    table { width: 100%; border-collapse: collapse; margin-top: 3px; table-layout: fixed; }
    th, td {
        border: 1px solid #333;
        padding: {{ $isPortrait ? '1px 3px' : '3px 4px' }};
        vertical-align: middle;
    }
    thead th { background: #f3f4f6; font-weight: bold; font-size: {{ $isPortrait ? '7pt' : '9pt' }}; text-align: center; }

    .col-n     { width: {{ $isPortrait ? '5%'  : '4%'  }}; text-align: center; }
    .col-mat   { width: {{ $isPortrait ? '13%' : '11%' }}; text-align: center; font-weight: bold; font-size: {{ $isPortrait ? '7pt' : '9pt' }}; }
    .col-nom   { width: {{ $isPortrait ? '32%' : '32%' }}; font-size: {{ $isPortrait ? '7.5pt' : '9pt' }}; }
    .col-sexe  { width: {{ $isPortrait ? '4%'  : '5%'  }}; text-align: center; }
    .col-moy   { width: {{ $isPortrait ? '6%'  : '6%'  }}; text-align: center; background: #fef3c7; font-weight: bold; }
    .col-notes { text-align: center; }

    .row-eleve td { height: {{ $isPortrait ? '18px' : '24px' }}; }
    .footer { margin-top: {{ $isPortrait ? '6px' : '12px' }}; font-size: 8pt; color: #777; display: flex; justify-content: space-between; }
    .signature { margin-top: {{ $isPortrait ? '4px' : '8px' }}; font-size: {{ $isPortrait ? '8pt' : '9pt' }}; }
    .signature .sig-zone { display: inline-block; width: {{ $isPortrait ? '120px' : '200px' }}; border-bottom: 1px solid #555; height: 22px; vertical-align: middle; }
</style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <div class="ecole">{{ $etab->nom ?? '—' }}</div>
        @if($etab && $etab->adresse)
            <div style="font-size: 7pt; color: #64748b;">{{ $etab->adresse }}</div>
        @endif
    </div>
    <div class="titre-box">{{ $titre }}</div>
    <div class="annee" style="text-align: right; width: 18%;">{{ $annee->libelle ?? '' }}</div>
</div>

<div class="infos">
    <b>CLASSE :</b> <span class="underline">{{ $classe->nom }}</span>
    <b>PROF :</b> <span class="underline">{{ trim(($enseignant->nom ?? '') . ' ' . ($enseignant->prenom ?? '')) }}</span>
    <b>MATIÈRE :</b> <span class="underline">{{ $matiere->nom ?? '—' }}</span>
</div>

<table>
    <thead>
        <tr>
            <th rowspan="2" class="col-n">N°</th>
            <th rowspan="2" class="col-mat">MATRICULE</th>
            <th rowspan="2" class="col-nom">NOM ET PRÉNOM</th>
            <th rowspan="2" class="col-sexe">GENRE</th>
            <th colspan="{{ $nbCols }}" style="background: #e0f2fe;">Interrogations et devoirs</th>
            <th rowspan="2" class="col-moy">MOY</th>
        </tr>
        <tr>
            @php $colWidth = ($isPortrait ? 40 : 42) / $nbCols; @endphp
            @for($i = 1; $i <= $nbCols; $i++)
                <th class="col-notes" style="width: {{ $colWidth }}%; font-size: {{ $isPortrait ? '7pt' : '9pt' }};">{{ $i }}</th>
            @endfor
        </tr>
    </thead>
    <tbody>
        @foreach($eleves as $i => $eleve)
        <tr class="row-eleve">
            <td class="col-n">{{ $i + 1 }}</td>
            <td class="col-mat">{{ $eleve->matricule_desps ?: ($eleve->matricule_interne ?: '—') }}</td>
            <td class="col-nom">{{ strtoupper(trim(($eleve->nom ?? '') . ' ' . ($eleve->prenom ?? ''))) }}</td>
            <td class="col-sexe">{{ $eleve->sexe ?? '' }}</td>
            @for($c = 1; $c <= $nbCols; $c++)
                <td class="col-notes"></td>
            @endfor
            <td class="col-moy"></td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="signature">
    <b>Signature de l'enseignant :</b> <span class="sig-zone"></span>
</div>

<div class="footer">
    <span>Édité le {{ now()->format('d/m/Y H:i') }}</span>
    <span>AviaSchoolPay · {{ $eleves->count() }} élève(s)</span>
</div>

</body>
</html>
