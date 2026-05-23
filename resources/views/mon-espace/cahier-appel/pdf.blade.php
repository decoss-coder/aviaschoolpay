<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Cahier d'appel — {{ $classe->nom }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #000; padding: 8mm 10mm; }

    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 3mm; }
    .ecole { font-weight: bold; font-size: 11pt; text-transform: uppercase; color: #6d28d9; }
    .titre { border: 2px solid #6d28d9; padding: 3px 12px; font-weight: bold; font-size: 11pt; text-transform: uppercase; color: #6d28d9; }
    .infos { font-size: 9pt; margin-bottom: 2mm; }
    .infos b { font-weight: bold; }
    .infos .underline { border-bottom: 1px solid #555; display: inline-block; min-width: 100px; padding: 0 4px; }
    .legende { font-size: 7.5pt; color: #6b7280; font-style: italic; margin-bottom: 2mm; }

    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    th, td { border: 1px solid #333; padding: 2px 3px; vertical-align: middle; }
    thead th { background: #6d28d9; color: white; font-weight: bold; text-align: center; font-size: 8pt; text-transform: uppercase; }
    .sub-header { background: #ede9fe; color: #4c1d95; font-size: 7.5pt; }

    .col-n     { width: 3%;  text-align: center; }
    .col-mat   { width: 8%;  text-align: center; font-weight: bold; font-size: 7.5pt; }
    .col-nom   { width: 22%; font-size: 8pt; }
    .col-sexe  { width: 3%;  text-align: center; }

    .col-statut { text-align: center; font-weight: bold; font-size: 9pt; }
    .st-present  { background: #d1fae5; color: #047857; }
    .st-absent   { background: #fee2e2; color: #b91c1c; }
    .st-retard   { background: #fef3c7; color: #92400e; }
    .st-excuse   { background: #dbeafe; color: #1e3a8a; }
    .st-dispense { background: #ede9fe; color: #5b21b6; }

    .row-eleve td { height: 18px; }
    .col-total { width: 5%; text-align: center; background: #faf5ff; font-weight: bold; font-size: 8pt; }
    .footer { margin-top: 3mm; font-size: 7.5pt; color: #777; display: flex; justify-content: space-between; }

    .empty { color: #cbd5e1; font-style: italic; }

    @php
        // Grouper les séances par jour
        $parJour = [];
        foreach ($seances as $s) {
            $parJour[$s['date']][] = $s;
        }
    @endphp
</style>
</head>
<body>

<div class="header">
    <div>
        <div class="ecole">{{ $etab->nom ?? '—' }}</div>
        <div style="font-size: 7.5pt; color: #64748b;">DESPS {{ $etab->code_desps ?? '—' }}</div>
    </div>
    <div class="titre">CAHIER D'APPEL · Semaine du {{ $semaine->format('d/m/Y') }}</div>
</div>

<div class="infos">
    <b>CLASSE :</b> <span class="underline">{{ $classe->nom }}</span>
    <b>ENSEIGNANT :</b> <span class="underline">{{ trim(($ens->nom ?? '') . ' ' . ($ens->prenom ?? '')) }}</span>
    <b>EFFECTIF :</b> <span class="underline">{{ $eleves->count() }}</span>
    <b>SÉANCES :</b> <span class="underline">{{ count($seances) }}</span>
</div>
<div class="legende">Légende : <b>P</b> = Présent · <b>A</b> = Absent · <b>R</b> = Retard · <b>E</b> = Excusé · <b>D</b> = Dispensé · case vide = non saisi</div>

@if(empty($seances))
    <div style="margin-top: 6mm; padding: 8mm; border: 2px dashed #6d28d9; text-align: center; color: #6d28d9; font-weight: bold;">
        Aucune séance configurée dans l'emploi du temps pour cette classe sur la semaine du {{ $semaine->format('d/m/Y') }}.
    </div>
@else
<table>
    <thead>
        <tr>
            <th class="col-n" rowspan="2">N°</th>
            <th class="col-mat" rowspan="2">MATRICULE</th>
            <th class="col-nom" rowspan="2">NOM ET PRÉNOM</th>
            <th class="col-sexe" rowspan="2">G</th>
            @foreach($parJour as $date => $seancesJour)
                @php $jourLib = \Carbon\Carbon::parse($date)->locale('fr')->isoFormat('ddd D/MM'); @endphp
                <th colspan="{{ count($seancesJour) }}" style="border-bottom: 2px solid white;">{{ $jourLib }}</th>
            @endforeach
            <th class="col-total" rowspan="2">ABS</th>
        </tr>
        <tr class="sub-header">
            @foreach($seances as $s)
                <th class="col-statut" style="background: #ede9fe; color: #4c1d95;">
                    {{ $s['libelle_creneau'] }}
                    @if($s['matiere'])<br><span style="font-size: 6.5pt;">{{ $s['matiere'] }}</span>@endif
                </th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($eleves as $i => $eleve)
        @php $totalAbs = 0; @endphp
        <tr class="row-eleve">
            <td class="col-n">{{ $i + 1 }}</td>
            <td class="col-mat">{{ $eleve->matricule_desps ?: ($eleve->matricule_interne ?: '—') }}</td>
            <td class="col-nom">{{ strtoupper($eleve->nom ?? '') }} {{ ucfirst(strtolower($eleve->prenom ?? '')) }}</td>
            <td class="col-sexe">{{ $eleve->sexe ?? '' }}</td>
            @foreach($seances as $s)
                @php
                    $key = $eleve->id . '_' . $s['date'] . '_' . $s['creneau_id'];
                    $p = $presences->get($key);
                    $symb = match ($p?->statut) {
                        'present'  => 'P', 'absent' => 'A', 'retard' => 'R',
                        'excuse'   => 'E', 'dispense' => 'D', default => '',
                    };
                    $cls = $p ? 'st-' . $p->statut : '';
                    if ($p?->statut === 'absent') $totalAbs++;
                @endphp
                <td class="col-statut {{ $cls }}">{{ $symb }}</td>
            @endforeach
            <td class="col-total">{{ $totalAbs > 0 ? $totalAbs : '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    <span>Édité le {{ now()->format('d/m/Y H:i') }}</span>
    <span>Signature enseignant : ____________________</span>
</div>

</body>
</html>
