<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Pré-reçu {{ $paiement->reference }}</title>
    <style>
        @page { margin: 20px 25px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 11px; line-height: 1.4; }

        .watermark {
            position: absolute; top: 280px; left: 80px;
            transform: rotate(-25deg);
            font-size: 70px; font-weight: bold;
            color: #fbbf24; opacity: 0.15;
            letter-spacing: 4px;
        }

        h1 { font-size: 18px; margin: 0; color: #92400e; }
        .header { text-align: center; border-bottom: 3px dashed #f59e0b; padding-bottom: 14px; margin-bottom: 18px; }
        .header .etab { font-size: 14px; font-weight: bold; color: #0ea5e9; }
        .header .sub { font-size: 10px; color: #64748b; }
        .header .label {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            padding: 4px 16px;
            border-radius: 16px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 6px;
        }

        .alert {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 10px 14px;
            margin-bottom: 14px;
            font-size: 11px;
            color: #78350f;
        }
        .alert strong { color: #92400e; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table th, table td { padding: 8px 6px; border-bottom: 1px solid #e5e7eb; text-align: left; font-size: 11px; }
        table th { background: #fef3c7; font-weight: bold; color: #78350f; font-size: 10px; text-transform: uppercase; }

        .reference-box {
            background: #fffbeb;
            border: 2px dashed #f59e0b;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            margin: 14px 0;
        }
        .reference-box .label {
            font-size: 9px;
            color: #78350f;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1.5px;
        }
        .reference-box .value {
            font-size: 24px;
            font-weight: bold;
            color: #92400e;
            font-family: DejaVu Sans Mono, monospace;
            letter-spacing: 2px;
            margin-top: 6px;
        }

        .montant-box {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 14px;
            text-align: center;
            margin: 14px 0;
        }
        .montant-box .label { font-size: 10px; color: #075985; text-transform: uppercase; font-weight: bold; }
        .montant-box .value { font-size: 22px; font-weight: bold; color: #0c4a6e; margin-top: 4px; }

        .instructions {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 12px 14px;
            margin: 14px 0;
            font-size: 10px;
        }
        .instructions h3 {
            font-size: 11px; margin: 0 0 8px 0; color: #1e293b;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .instructions ol { margin: 0 0 0 18px; padding: 0; color: #475569; }
        .instructions li { margin-bottom: 4px; }

        .footer { margin-top: 24px; font-size: 9px; color: #64748b; text-align: center; }
        .footer .meta { margin-top: 4px; }
    </style>
</head>
<body>

<div class="watermark">PRÉ-REÇU</div>

<div class="header">
    <div class="etab">{{ $paiement->etablissement?->nom ?? 'Établissement' }}</div>
    @if($paiement->etablissement?->adresse)
        <div class="sub">{{ $paiement->etablissement->adresse }}</div>
    @endif
    @if($paiement->etablissement?->telephone)
        <div class="sub">Tél : {{ $paiement->etablissement->telephone }}</div>
    @endif
    <h1 style="margin-top:8px;">PRÉ-REÇU DE PAIEMENT</h1>
    <span class="label">⏳ En attente de confirmation</span>
</div>

<div class="alert">
    <strong>Document à présenter à la direction de l'école.</strong><br>
    Ce pré-reçu atteste qu'un paiement a été initié. Présentez-le au secrétariat
    avec votre justificatif Wave pour finaliser l'encaissement et obtenir le reçu officiel.
</div>

<div class="reference-box">
    <div class="label">Référence du paiement</div>
    <div class="value">{{ $paiement->reference }}</div>
</div>

<table>
    <tr>
        <th style="width:35%;">Date d'initiation</th>
        <td>{{ $paiement->created_at?->format('d/m/Y à H:i') }}</td>
    </tr>
    <tr>
        <th>Élève</th>
        <td>
            <strong>{{ $paiement->eleve?->prenom }} {{ $paiement->eleve?->nom }}</strong><br>
            <span style="font-size:9px;color:#64748b;">Matricule : {{ $paiement->eleve?->matricule_desps ?? $paiement->eleve?->matricule_interne ?? '—' }}</span>
        </td>
    </tr>
    <tr>
        <th>Classe</th>
        <td>{{ $paiement->inscription?->classe?->nom ?? '—' }}</td>
    </tr>
    <tr>
        <th>Mode de paiement</th>
        <td>
            @if($paiement->mode === 'wave')
                Wave (paiement en ligne)
            @else
                {{ str_replace('_', ' ', ucfirst($paiement->mode)) }}
            @endif
        </td>
    </tr>
    @if($paiement->wave_checkout_url)
    <tr>
        <th>Lien Wave</th>
        <td style="font-family: DejaVu Sans Mono, monospace; font-size: 9px; word-break: break-all;">
            {{ $paiement->wave_checkout_url }}
        </td>
    </tr>
    @endif
</table>

<div class="montant-box">
    <div class="label">Montant à régler</div>
    <div class="value">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</div>
</div>

<div class="instructions">
    <h3>Marche à suivre pour le parent</h3>
    <ol>
        <li>Effectuez le paiement sur Wave en ouvrant le lien (déjà ouvert dans l'app).</li>
        <li>Conservez le SMS / la capture de confirmation Wave.</li>
        <li>Présentez ce pré-reçu (avec la référence ci-dessus) au secrétariat de l'école.</li>
        <li>La direction confirmera votre paiement et vous remettra le <strong>reçu officiel</strong>.</li>
    </ol>
</div>

<div class="footer">
    Document généré le {{ now()->format('d/m/Y à H:i') }} — AviaSchoolPay<br>
    <div class="meta">
        Ce pré-reçu n'a pas de valeur libératoire tant que le paiement n'est pas confirmé par l'école.<br>
        Référence interne : <strong>{{ $paiement->reference }}</strong> · Paiement N° {{ $paiement->id }}
    </div>
</div>

</body>
</html>
