<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Convocation conseil de classe — {{ $conseil->classe?->nom }}</title>
    <style>
        @page { margin: 25px 35px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 11px; line-height: 1.6; }

        .official-header { text-align: center; border-bottom: 3px double #0a7b3f; padding-bottom: 10px; margin-bottom: 18px; }
        .etab-nom-big { font-size: 16px; font-weight: bold; color: #0a7b3f; }
        .etab-sub { font-size: 9px; color: #475569; margin-top: 2px; }

        .meta { text-align: right; font-size: 9px; color: #64748b; margin-bottom: 15px; }

        h1.title {
            text-align: center; font-size: 22px; font-weight: bold; color: #0a7b3f;
            text-transform: uppercase; letter-spacing: 2px;
            margin: 0 0 6px 0; padding: 8px;
            border-top: 2px solid #0a7b3f; border-bottom: 2px solid #0a7b3f;
        }
        .subtitle { text-align: center; font-size: 11px; color: #94a3b8; font-style: italic; margin-bottom: 18px; }

        .info-bloc {
            background: linear-gradient(to bottom right, #ecfdf5, #d1fae5);
            border: 2px solid #10b981;
            border-radius: 6px;
            padding: 14px 18px;
            margin: 14px 0;
        }
        .info-bloc table { width: 100%; }
        .info-bloc td { padding: 5px 8px; font-size: 11px; vertical-align: top; }
        .info-bloc .lbl { color: #047857; font-weight: bold; text-transform: uppercase; font-size: 8px; }
        .info-bloc .val { color: #065f46; font-weight: bold; font-size: 12px; }

        h2 {
            font-size: 12px; color: #fff; background: #0a7b3f;
            padding: 5px 10px; border-radius: 4px;
            text-transform: uppercase; letter-spacing: 1px;
            margin: 14px 0 6px;
        }

        .ordre-jour {
            background: #f8fafc;
            border-left: 4px solid #0a7b3f;
            padding: 12px 16px;
            border-radius: 0 6px 6px 0;
            font-size: 10px;
            line-height: 1.7;
            white-space: pre-line;
        }

        table.eleves { width: 100%; border-collapse: collapse; font-size: 9px; }
        table.eleves th { background: #0a7b3f; color: white; padding: 4px 6px; text-align: left; font-size: 8px; text-transform: uppercase; }
        table.eleves td { padding: 3px 6px; border-bottom: 1px solid #e5e7eb; }
        table.eleves tr:nth-child(even) td { background: #fafafa; }
        .center { text-align: center; }

        .signature-bloc {
            margin-top: 30px;
            text-align: right;
        }
        .signature-bloc .lieu-date { font-size: 11px; color: #475569; margin-bottom: 30px; }
        .signature-bloc .fonction { font-size: 11px; font-weight: bold; color: #0f172a; }
        .signature-bloc .signature-line {
            width: 220px; margin-left: auto;
            border-top: 1px solid #94a3b8; margin-top: 50px; padding-top: 3px;
            text-align: center; font-size: 8px; color: #64748b;
        }

        .footer-doc {
            position: fixed; bottom: 8px; left: 0; right: 0;
            text-align: center; font-size: 7px; color: #94a3b8;
            border-top: 1px solid #e5e7eb; padding-top: 4px;
        }
    </style>
</head>
<body>

<div class="official-header">
    <div style="font-size:7px;color:#64748b;font-style:italic;">RÉPUBLIQUE — MINISTÈRE DE L'ÉDUCATION NATIONALE</div>
    <div class="etab-nom-big">{{ $etab?->nom ?? 'Établissement' }}</div>
    <div class="etab-sub">{{ $etab?->adresse }}{{ $etab?->ville ? ', '.$etab->ville : '' }} · Tél : {{ $etab?->telephone }}</div>
</div>

<div class="meta">
    Réf : CC-{{ $conseil->id }}-{{ now()->format('Y') }}<br>
    Édité le {{ now()->format('d/m/Y') }}
</div>

<h1 class="title">Convocation au Conseil de Classe</h1>
<div class="subtitle">— {{ $conseil->classe?->nom }} · {{ $conseil->trimestre?->libelle }} —</div>

<div class="info-bloc">
    <table>
        <tr>
            <td style="width:33%;">
                <div class="lbl">📅 Date</div>
                <div class="val">{{ $conseil->date_conseil?->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</div>
            </td>
            <td style="width:33%;">
                <div class="lbl">🕐 Heure</div>
                <div class="val">{{ substr($conseil->heure_debut, 0, 5) }}@if($conseil->heure_fin) → {{ substr($conseil->heure_fin, 0, 5) }}@endif</div>
            </td>
            <td style="width:34%;">
                <div class="lbl">📍 Lieu</div>
                <div class="val">{{ $conseil->lieu }}</div>
            </td>
        </tr>
    </table>
</div>

<p style="margin:14px 0;font-size:11px;line-height:1.7;text-align:justify;">
    Je soussigné(e), Chef d'établissement de <b>{{ $etab?->nom }}</b>, ai l'honneur de convoquer
    l'ensemble du corps enseignant de la classe de
    <b style="color:#0a7b3f;text-decoration:underline;">{{ $conseil->classe?->nom }}</b>,
    ainsi que les délégués élèves et parents, au conseil de classe du
    <b>{{ $conseil->trimestre?->libelle }}</b> qui se tiendra à la date et au lieu indiqués ci-dessus.
</p>

<h2>📋 Ordre du jour</h2>
<div class="ordre-jour">{{ $conseil->ordre_du_jour }}</div>

@if($conseil->participants)
<h2>👥 Participants attendus</h2>
<div style="background:#fef3c7;border:1px solid #f59e0b;padding:10px 14px;border-radius:4px;font-size:10px;color:#92400e;white-space:pre-line;">
    {{ $conseil->participants }}
</div>
@endif

<h2>📊 Élèves concernés ({{ $eleves->count() }})</h2>
<table class="eleves">
    <thead>
        <tr>
            <th style="width:5%;" class="center">#</th>
            <th style="width:15%;">Matricule</th>
            <th>Nom & Prénoms</th>
        </tr>
    </thead>
    <tbody>
        @foreach($eleves as $i => $insc)
            <tr>
                <td class="center">{{ $i+1 }}</td>
                <td style="font-family:DejaVu Sans Mono;">{{ $insc->eleve?->matricule_interne ?? '—' }}</td>
                <td><b>{{ $insc->eleve?->prenom }} {{ strtoupper($insc->eleve?->nom ?? '') }}</b></td>
            </tr>
        @endforeach
    </tbody>
</table>

<p style="margin-top:15px;font-size:10px;color:#475569;text-align:justify;">
    Je vous prie de bien vouloir assurer votre présence à cette réunion qui revêt une importance capitale pour
    le suivi pédagogique des élèves et la préparation du bulletin trimestriel.
</p>

<div class="signature-bloc">
    <div class="lieu-date">Fait à {{ $etab?->ville ?? '—' }}, le {{ now()->format('d/m/Y') }}</div>
    <div class="fonction">Le Directeur</div>
    <div class="signature-line">Signature & cachet</div>
</div>

<div class="footer-doc">
    Convocation officielle · {{ $etab?->nom ?? '' }} · Édité par AviaSchoolPay
</div>

</body>
</html>
