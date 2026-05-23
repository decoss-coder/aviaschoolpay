<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Attestation de paiement — {{ $eleve->prenom }} {{ $eleve->nom }}</title>
    <style>
        @page { margin: 25px 35px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 11px; line-height: 1.5; }

        .official-header { text-align: center; border-bottom: 3px double #0a7b3f; padding-bottom: 10px; margin-bottom: 20px; }
        .etab-nom-big { font-size: 16px; font-weight: bold; color: #0a7b3f; letter-spacing: 1px; }
        .etab-sub { font-size: 9px; color: #475569; margin-top: 2px; }

        .meta { text-align: right; font-size: 9px; color: #64748b; margin-bottom: 20px; }
        .meta .numero { font-family: DejaVu Sans Mono, monospace; font-weight: bold; color: #0a7b3f; font-size: 11px; }

        h1.title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            color: #0a7b3f;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 0 0 8px 0;
            padding: 8px;
            border-top: 2px solid #0a7b3f;
            border-bottom: 2px solid #0a7b3f;
        }

        .corps { padding: 12px 20px; font-size: 11px; line-height: 1.7; text-align: justify; }
        .nom-eleve { font-size: 14px; font-weight: bold; color: #0a7b3f; text-transform: uppercase; }
        .montant-grand { font-size: 18px; font-weight: bold; color: #047857; font-family: DejaVu Sans Mono, monospace; }

        .infos { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; padding: 10px 14px; margin: 14px 0; }
        .infos table { width: 100%; }
        .infos td { padding: 3px 6px; font-size: 10px; }
        .infos .lbl { color: #64748b; font-weight: bold; text-transform: uppercase; font-size: 8px; }

        .recap-paiements { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 9px; }
        .recap-paiements th { background: #0a7b3f; color: #fff; padding: 5px 6px; font-size: 8px; text-transform: uppercase; text-align: left; }
        .recap-paiements td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; }
        .recap-paiements tr:nth-child(even) td { background: #fafafa; }
        .recap-paiements .right { text-align: right; font-family: DejaVu Sans Mono, monospace; }
        .recap-paiements tfoot td { background: #d1fae5; font-weight: bold; color: #065f46; border-top: 2px solid #10b981; font-size: 10px; }

        .badge-solde { display: inline-block; padding: 4px 12px; border-radius: 15px; font-size: 11px; font-weight: bold; }
        .badge-ok { background: #d1fae5; color: #065f46; border: 2px solid #10b981; }
        .badge-pasok { background: #fee2e2; color: #991b1b; border: 2px solid #ef4444; }

        .signature-bloc { margin-top: 40px; text-align: right; }
        .signature-bloc .lieu-date { font-size: 10px; color: #475569; margin-bottom: 35px; }
        .signature-bloc .signature-line { width: 200px; margin-left: auto; border-top: 1px solid #94a3b8; margin-top: 45px; padding-top: 3px; text-align: center; font-size: 8px; color: #64748b; }

        .footer-doc { position: fixed; bottom: 10px; left: 0; right: 0; text-align: center; font-size: 7px; color: #94a3b8; border-top: 1px solid #e5e7eb; padding-top: 4px; }
    </style>
</head>
<body>

@php $money = fn($v) => number_format((float) $v, 0, ',', ' '); @endphp

<div class="official-header">
    <div style="font-size:7px;color:#64748b;font-style:italic;">RÉPUBLIQUE — MINISTÈRE DE L'ÉDUCATION NATIONALE</div>
    <div class="etab-nom-big">{{ $etab?->nom ?? 'Établissement' }}</div>
    <div class="etab-sub">{{ $etab?->adresse }}{{ $etab?->ville ? ', '.$etab->ville : '' }} · Tél : {{ $etab?->telephone }}</div>
</div>

<div class="meta">
    N° attestation : <span class="numero">{{ $numero }}</span><br>
    Édité le {{ now()->format('d/m/Y') }}
</div>

<h1 class="title">Attestation de Paiement</h1>
<div style="text-align:center;font-size:10px;color:#94a3b8;font-style:italic;margin-bottom:20px;">— Année scolaire {{ $inscription->anneeScolaire?->libelle ?? '—' }} —</div>

<div class="corps">
Je soussigné(e), Comptable / Chef d'établissement de <b>{{ $etab?->nom }}</b>,

<br><br>

Atteste que <span class="nom-eleve">{{ $eleve->prenom }} {{ $eleve->nom }}</span>

<div class="infos">
    <table>
        <tr>
            <td><div class="lbl">Matricule</div></td>
            <td><b>{{ $eleve->matricule_interne ?? '—' }}</b></td>
            <td><div class="lbl">Classe</div></td>
            <td><b>{{ $eleve->classe?->nom ?? '—' }}</b></td>
        </tr>
        <tr>
            <td><div class="lbl">Né(e) le</div></td>
            <td><b>{{ $eleve->date_naissance?->format('d/m/Y') ?? '—' }}</b></td>
            <td><div class="lbl">Année</div></td>
            <td><b>{{ $inscription->anneeScolaire?->libelle ?? '—' }}</b></td>
        </tr>
    </table>
</div>

a effectué les versements suivants au titre de sa scolarité :

</div>

<table class="recap-paiements">
    <thead>
        <tr>
            <th style="width:18%;">Date</th>
            <th style="width:22%;">N° reçu</th>
            <th style="width:20%;">Mode</th>
            <th style="width:20%;" class="right">Inscription</th>
            <th style="width:20%;" class="right">Scolarité</th>
        </tr>
    </thead>
    <tbody>
        @forelse($paiements as $p)
            <tr>
                <td>{{ $p->date_paiement?->format('d/m/Y') ?? '—' }}</td>
                <td style="font-family:DejaVu Sans Mono;font-size:8px;">{{ $p->numero_recu ?? $p->reference }}</td>
                <td style="font-size:8px;">{{ ucfirst(str_replace('_', ' ', $p->mode)) }}</td>
                <td class="right">{{ $money($p->montant_inscription) }} F</td>
                <td class="right">{{ $money($p->montant_scolarite) }} F</td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:15px;">Aucun paiement enregistré</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" class="right">TOTAL VERSÉ</td>
            <td colspan="2" class="right"><span class="montant-grand">{{ $money($totalPaye) }} F CFA</span></td>
        </tr>
    </tfoot>
</table>

<div style="margin:14px 0;padding:10px 14px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:4px;font-size:11px;">
    <table style="width:100%;">
        <tr>
            <td style="width:35%;color:#64748b;font-weight:bold;text-transform:uppercase;font-size:9px;">Frais total dû :</td>
            <td style="width:25%;text-align:right;"><b style="font-family:DejaVu Sans Mono;">{{ $money($inscription->montant_net) }} F</b></td>
            <td style="width:15%;color:#64748b;font-weight:bold;text-transform:uppercase;font-size:9px;text-align:right;">Reste :</td>
            <td style="width:25%;text-align:right;">
                @if($solde)
                    <span class="badge-solde badge-ok">✓ SOLDÉ</span>
                @else
                    <b style="color:#b91c1c;font-family:DejaVu Sans Mono;">{{ $money($reste) }} F</b>
                @endif
            </td>
        </tr>
    </table>
</div>

<div class="corps" style="margin-top:10px;">
    En foi de quoi la présente attestation est délivrée à l'intéressé(e) pour servir et valoir ce que de droit.
</div>

<div class="signature-bloc">
    <div class="lieu-date">Fait à {{ $etab?->ville ?? '—' }}, le {{ now()->format('d/m/Y') }}</div>
    <div style="font-size:10px;font-weight:bold;">Le Comptable</div>
    <div class="signature-line">Signature & cachet</div>
</div>

<div class="footer-doc">
    Attestation officielle — {{ $numero }} · {{ $etab?->nom }} · Document généré par AviaSchoolPay
</div>

</body>
</html>
