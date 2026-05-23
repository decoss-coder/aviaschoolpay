<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu {{ $paiement->numero_recu }}</title>
    <style>
        @page { margin: 20px 25px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 11px; line-height: 1.4; }
        h1 { font-size: 18px; margin: 0; color: #0f172a; }
        .header { text-align: center; border-bottom: 2px solid #0ea5e9; padding-bottom: 12px; margin-bottom: 18px; }
        .header .etab { font-size: 14px; font-weight: bold; color: #0ea5e9; }
        .header .sub { font-size: 10px; color: #64748b; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 9px; font-weight: bold; background: #d1fae5; color: #065f46; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table th, table td { padding: 8px 6px; border-bottom: 1px solid #e5e7eb; text-align: left; font-size: 11px; }
        table th { background: #f1f5f9; font-weight: bold; color: #475569; font-size: 10px; text-transform: uppercase; }
        .montant-box { background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px; padding: 14px; text-align: center; margin: 14px 0; }
        .montant-box .label { font-size: 10px; color: #075985; text-transform: uppercase; font-weight: bold; }
        .montant-box .value { font-size: 22px; font-weight: bold; color: #0c4a6e; margin-top: 4px; }
        .footer { margin-top: 30px; font-size: 9px; color: #64748b; text-align: center; }
        .signature { margin-top: 30px; display: table; width: 100%; }
        .signature .col { display: table-cell; width: 50%; }
        .signature .line { border-top: 1px dashed #94a3b8; margin-top: 50px; padding-top: 4px; font-size: 9px; color: #64748b; text-align: center; }
    </style>
</head>
<body>

<div class="header">
    <div class="etab">{{ $paiement->etablissement?->nom ?? 'Établissement' }}</div>
    @if($paiement->etablissement?->adresse)
        <div class="sub">{{ $paiement->etablissement->adresse }}</div>
    @endif
    @if($paiement->etablissement?->telephone)
        <div class="sub">Tél : {{ $paiement->etablissement->telephone }}</div>
    @endif
    <h1 style="margin-top:8px;">REÇU DE PAIEMENT</h1>
    <div class="sub">N° {{ $paiement->numero_recu }}</div>
    <div style="margin-top:6px;"><span class="badge">{{ ucfirst($paiement->statut) }}</span></div>
</div>

<table>
    <tr>
        <th style="width:35%;">Référence</th>
        <td style="font-family: DejaVu Sans Mono, monospace;">{{ $paiement->reference }}</td>
    </tr>
    <tr>
        <th>Date paiement</th>
        <td>{{ $paiement->date_paiement?->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <th>Élève</th>
        <td>
            <strong>{{ $paiement->eleve?->prenom }} {{ $paiement->eleve?->nom }}</strong><br>
            <span style="font-size:9px;color:#64748b;">Matricule : {{ $paiement->eleve?->matricule_interne ?? '—' }}</span>
        </td>
    </tr>
    <tr>
        <th>Classe</th>
        <td>{{ $paiement->inscription?->classe?->nom ?? '—' }}</td>
    </tr>
    <tr>
        <th>Mode de paiement</th>
        <td>{{ str_replace('_', ' ', ucfirst($paiement->mode)) }}</td>
    </tr>
    @if($paiement->encaissePar)
    <tr>
        <th>Encaissé par</th>
        <td>{{ $paiement->encaissePar->name }}</td>
    </tr>
    @endif
    @if($paiement->observations)
    <tr>
        <th>Observations</th>
        <td>{{ $paiement->observations }}</td>
    </tr>
    @endif
</table>

<div class="montant-box">
    <div class="label">Montant encaissé</div>
    <div class="value">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</div>
</div>

<div class="signature">
    <div class="col">
        <div class="line">L'élève / le parent</div>
    </div>
    <div class="col">
        <div class="line">Cachet & signature direction</div>
    </div>
</div>

<div class="footer">
    Document généré le {{ now()->format('d/m/Y à H:i') }} — AviaSchoolPay<br>
    Ce reçu fait office de preuve de paiement.
</div>

</body>
</html>
