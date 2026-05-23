<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche de paie {{ $fiche->reference }}</title>
    <style>
        @page { margin: 18px 20px 30px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #111827; font-size: 10px; line-height: 1.35; }
        h1 { font-size: 18px; margin: 0; color: #0f172a; }
        h2 { font-size: 11px; margin: 10px 0 5px; color: #0f172a; padding: 5px 8px; background: #0d9488; color: #fff; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 4px; }

        .header { border-bottom: 3px solid #0d9488; padding-bottom: 10px; margin-bottom: 14px; }
        .header-table { width: 100%; }
        .header-table td { vertical-align: top; }
        .etab-nom { font-size: 14px; font-weight: bold; color: #0d9488; }
        .sub { font-size: 9px; color: #64748b; }
        .titre-doc { font-size: 18px; font-weight: bold; color: #0f172a; text-align: right; text-transform: uppercase; letter-spacing: 1px; }
        .ref-doc { font-size: 11px; color: #0d9488; text-align: right; font-family: DejaVu Sans Mono, monospace; font-weight: bold; margin-top: 4px; }
        .periode-doc { font-size: 10px; color: #475569; text-align: right; margin-top: 2px; }

        /* Identité enseignant */
        .identity-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; margin-bottom: 10px; }
        .identity-table { width: 100%; }
        .identity-table td { padding: 3px 8px; font-size: 10px; vertical-align: top; }
        .identity-table .lbl { color: #64748b; font-size: 8px; text-transform: uppercase; font-weight: bold; }
        .identity-table .val { color: #0f172a; font-weight: bold; }

        /* Tableaux */
        table.calcul { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        table.calcul td { padding: 6px 10px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
        table.calcul td.lbl { color: #475569; }
        table.calcul td.val { text-align: right; font-weight: bold; font-family: DejaVu Sans Mono, monospace; }
        table.calcul .positive { color: #047857; }
        table.calcul .negative { color: #b91c1c; }
        table.calcul .total td { background: #ecfdf5; border-top: 2px solid #10b981; border-bottom: 2px solid #10b981; font-weight: bold; color: #065f46; font-size: 11px; padding: 8px 10px; }
        table.calcul .brut td { background: #eff6ff; border-top: 2px solid #3b82f6; border-bottom: 2px solid #3b82f6; font-weight: bold; color: #1e40af; }
        table.calcul .section td { background: #f1f5f9; font-weight: bold; color: #475569; padding: 4px 10px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Pointage stats */
        .stats-row { width: 100%; margin: 8px 0 12px; border-collapse: collapse; }
        .stats-row td { padding: 8px 6px; text-align: center; background: #f8fafc; border: 1px solid #e2e8f0; width: 33%; vertical-align: top; }
        .stats-row .lbl { font-size: 8px; color: #64748b; text-transform: uppercase; font-weight: bold; }
        .stats-row .val { font-size: 16px; color: #0f172a; font-weight: bold; margin-top: 3px; }

        /* Net à payer encadré */
        .net-box { background: linear-gradient(to right, #d1fae5, #a7f3d0); border: 2px solid #10b981; border-radius: 8px; padding: 14px 18px; margin: 10px 0; }
        .net-box-table { width: 100%; }
        .net-box-table td { vertical-align: middle; }
        .net-box .lbl { font-size: 10px; color: #065f46; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .net-box .val { font-size: 22px; color: #064e3b; font-weight: bold; text-align: right; font-family: DejaVu Sans Mono, monospace; }
        .net-box .lettres { font-size: 9px; color: #047857; font-style: italic; margin-top: 4px; }

        /* Journalier */
        table.pointage { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.pointage th { background: #0d9488; color: white; padding: 5px 6px; font-size: 8px; text-transform: uppercase; text-align: center; }
        table.pointage td { padding: 4px 6px; border-bottom: 1px solid #f1f5f9; font-size: 9px; text-align: center; }
        table.pointage tr:nth-child(even) td { background: #f8fafc; }
        table.pointage .retard { color: #b45309; font-weight: bold; }
        table.pointage .incomplet { background: #fef3c7 !important; }

        /* Signatures */
        .signatures { margin-top: 20px; width: 100%; border-collapse: collapse; }
        .signatures td { width: 33.33%; padding: 0 8px; vertical-align: top; }
        .signatures .lbl { font-size: 9px; color: #64748b; text-transform: uppercase; font-weight: bold; text-align: center; }
        .signatures .line { border-top: 1px dashed #94a3b8; margin-top: 40px; padding-top: 5px; text-align: center; font-size: 8px; color: #94a3b8; }

        .footer { position: fixed; bottom: -20px; left: 0; right: 0; font-size: 8px; color: #94a3b8; text-align: center; padding-top: 5px; border-top: 1px solid #e5e7eb; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 8px; font-weight: bold; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-warn { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>

@php
    $money = fn($v) => number_format((float) $v, 0, ',', ' ');
    $statutBadgeClass = ['brouillon' => 'badge-warn', 'validee' => 'badge-info', 'payee' => 'badge-success', 'annulee' => 'badge-warn'][$fiche->statut] ?? 'badge-info';
    $typeRemLabels = ['fixe' => 'Salaire fixe', 'horaire' => 'Rémunération horaire', 'mixte' => 'Base + heures'];
@endphp

<div class="header">
    <table class="header-table">
        <tr>
            <td style="width:60%;">
                <div class="etab-nom">{{ $etablissement?->nom ?? 'Établissement' }}</div>
                @if($etablissement?->adresse)<div class="sub">{{ $etablissement->adresse }}</div>@endif
                @if($etablissement?->telephone)<div class="sub">Tél : {{ $etablissement->telephone }}{{ $etablissement->email ? ' · '.$etablissement->email : '' }}</div>@endif
            </td>
            <td style="width:40%;">
                <div class="titre-doc">Bulletin de paie</div>
                <div class="ref-doc">{{ $fiche->reference }}</div>
                <div class="periode-doc">{{ \Carbon\Carbon::parse($fiche->mois.'-01')->locale('fr')->isoFormat('MMMM YYYY') }}</div>
                <div style="text-align:right;margin-top:4px;">
                    <span class="badge {{ $statutBadgeClass }}">{{ strtoupper($fiche->statut) }}</span>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- Identité --}}
<div class="identity-box">
    <table class="identity-table">
        <tr>
            <td style="width:25%;">
                <div class="lbl">Nom & prénoms</div>
                <div class="val">{{ $fiche->enseignant->prenom }} {{ strtoupper($fiche->enseignant->nom) }}</div>
            </td>
            <td style="width:25%;">
                <div class="lbl">Matricule</div>
                <div class="val">{{ $fiche->enseignant->matricule_mena ?: '—' }}</div>
            </td>
            <td style="width:25%;">
                <div class="lbl">Statut</div>
                <div class="val">{{ ucfirst($fiche->enseignant->statut) }}</div>
            </td>
            <td style="width:25%;">
                <div class="lbl">Type rémunération</div>
                <div class="val">{{ $typeRemLabels[$fiche->type_remuneration] ?? $fiche->type_remuneration }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="lbl">Date de prise de fonction</div>
                <div class="val">{{ $fiche->enseignant->date_prise_fonction?->format('d/m/Y') ?? '—' }}</div>
            </td>
            <td>
                <div class="lbl">Téléphone</div>
                <div class="val">{{ $fiche->enseignant->telephone }}</div>
            </td>
            <td>
                <div class="lbl">Banque</div>
                <div class="val">{{ $fiche->enseignant->banque ?: '—' }}</div>
            </td>
            <td>
                <div class="lbl">Période</div>
                <div class="val">{{ \Carbon\Carbon::parse($fiche->periode_debut)->format('d/m') }} → {{ \Carbon\Carbon::parse($fiche->periode_fin)->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>
</div>

{{-- Statistiques de pointage --}}
<table class="stats-row">
    <tr>
        <td>
            <div class="lbl">Heures travaillées</div>
            <div class="val">{{ number_format($fiche->heures_travaillees, 1, ',', ' ') }} h</div>
            @if($fiche->heures_contractuelles)
                <div class="sub" style="margin-top:2px;">sur {{ number_format($fiche->heures_contractuelles, 1, ',', ' ') }} h contractuelles</div>
            @endif
        </td>
        <td>
            <div class="lbl">Jours travaillés / absences</div>
            <div class="val">{{ $fiche->nb_jours_travailles }} / {{ $fiche->nb_jours_absents }}</div>
        </td>
        <td>
            <div class="lbl">Retards</div>
            <div class="val">{{ $fiche->nb_retards }}</div>
        </td>
    </tr>
</table>

{{-- Calcul de la rémunération --}}
<h2>Calcul de la rémunération</h2>
<table class="calcul">
    <tr class="section"><td colspan="2">Éléments bruts</td></tr>

    @if($fiche->salaire_base > 0)
    <tr>
        <td class="lbl">Salaire de base</td>
        <td class="val">{{ $money($fiche->salaire_base) }} F</td>
    </tr>
    @endif

    @if($fiche->heures_travaillees > 0 && $fiche->taux_horaire > 0)
    <tr>
        <td class="lbl">{{ number_format($fiche->heures_travaillees, 2, ',', ' ') }} h × {{ $money($fiche->taux_horaire) }} F/h</td>
        <td class="val">{{ $money($fiche->montant_horaire) }} F</td>
    </tr>
    @endif

    @if($fiche->primes > 0)
    <tr>
        <td class="lbl">Primes</td>
        <td class="val positive">+ {{ $money($fiche->primes) }} F</td>
    </tr>
    @endif

    @if($fiche->indemnites > 0)
    <tr>
        <td class="lbl">Indemnités</td>
        <td class="val positive">+ {{ $money($fiche->indemnites) }} F</td>
    </tr>
    @endif

    <tr class="brut">
        <td>SALAIRE BRUT</td>
        <td class="val">{{ $money($fiche->salaire_brut) }} F</td>
    </tr>

    <tr class="section"><td colspan="2">Retenues</td></tr>

    <tr>
        <td class="lbl">CNPS — part salariale ({{ \App\Services\Salaire\SalaireService::TAUX_COTISATIONS_SOCIALES }} %)</td>
        <td class="val negative">− {{ $money($fiche->cotisations_sociales) }} F</td>
    </tr>
    <tr>
        <td class="lbl">IUTS — Impôt unique sur traitements et salaires ({{ \App\Services\Salaire\SalaireService::TAUX_IUTS }} %)</td>
        <td class="val negative">− {{ $money($fiche->impots) }} F</td>
    </tr>

    @if($fiche->avances > 0)
    <tr>
        <td class="lbl">Avances sur salaire</td>
        <td class="val negative">− {{ $money($fiche->avances) }} F</td>
    </tr>
    @endif

    @if($fiche->retenues > 0)
    <tr>
        <td class="lbl">Autres retenues</td>
        <td class="val negative">− {{ $money($fiche->retenues) }} F</td>
    </tr>
    @endif

    <tr class="total">
        <td>NET À PAYER</td>
        <td class="val">{{ $money($fiche->salaire_net) }} F</td>
    </tr>
</table>

{{-- Net à payer en gros --}}
<div class="net-box">
    <table class="net-box-table">
        <tr>
            <td style="width:50%;">
                <div class="lbl">Net à payer en chiffres</div>
                <div class="lettres">arrêté à la somme de</div>
            </td>
            <td style="width:50%;">
                <div class="val">{{ $money($fiche->salaire_net) }} F</div>
            </td>
        </tr>
    </table>
</div>

{{-- Détail journalier (si pertinent) --}}
@if($journalier->isNotEmpty() && in_array($fiche->type_remuneration, ['horaire', 'mixte']))
<h2>Détail des pointages</h2>
<table class="pointage">
    <thead>
        <tr>
            <th style="width:15%;">Date</th>
            <th style="width:20%;">Jour</th>
            <th style="width:18%;">Arrivée</th>
            <th style="width:18%;">Départ</th>
            <th style="width:14%;">Heures</th>
            <th style="width:15%;">Statut</th>
        </tr>
    </thead>
    <tbody>
        @foreach($journalier as $j)
            @php $d = \Carbon\Carbon::parse($j['date']); @endphp
            <tr class="{{ ! $j['complet'] ? 'incomplet' : '' }}">
                <td>{{ $d->format('d/m/Y') }}</td>
                <td>{{ $d->locale('fr')->isoFormat('dddd') }}</td>
                <td class="{{ $j['retard'] ? 'retard' : '' }}">{{ $j['arrivee'] ? substr($j['arrivee'], 0, 5) : '—' }}</td>
                <td>{{ $j['depart'] ? substr($j['depart'], 0, 5) : '—' }}</td>
                <td><b>{{ number_format($j['heures'], 2, ',', '') }}h</b></td>
                <td>
                    @if(! $j['complet'])
                        <span class="badge badge-warn">Incomplet</span>
                    @elseif($j['retard'])
                        <span class="badge badge-warn">Retard</span>
                    @else
                        <span class="badge badge-success">OK</span>
                    @endif
                </td>
            </tr>
        @endforeach
        <tr style="background:#f1f5f9;">
            <td colspan="4" style="text-align:right;font-weight:bold;">TOTAL HEURES</td>
            <td><b>{{ number_format($fiche->heures_travaillees, 2, ',', '') }}h</b></td>
            <td>—</td>
        </tr>
    </tbody>
</table>
@endif

{{-- Paiement effectué --}}
@if($fiche->statut === 'payee')
<div style="background:#ecfdf5;border:1px solid #10b981;border-radius:6px;padding:10px 14px;margin-top:12px;">
    <table style="width:100%;">
        <tr>
            <td style="font-size:9px;color:#065f46;text-transform:uppercase;font-weight:bold;">Paiement effectué</td>
            <td style="text-align:right;font-size:10px;color:#065f46;font-weight:bold;">
                Le {{ \Carbon\Carbon::parse($fiche->date_paiement_effectif)->format('d/m/Y') }}
                · {{ ucfirst(str_replace('_', ' ', $fiche->mode_paiement)) }}
            </td>
        </tr>
    </table>
</div>
@endif

{{-- Observations --}}
@if($fiche->observations)
<div style="background:#fef3c7;border:1px solid #f59e0b;border-radius:6px;padding:8px 12px;margin-top:8px;font-size:9px;color:#92400e;">
    <b>Observations :</b> {{ $fiche->observations }}
</div>
@endif

{{-- Signatures --}}
<table class="signatures">
    <tr>
        <td>
            <div class="lbl">L'Employé</div>
            <div class="line">{{ $fiche->enseignant->prenom }} {{ strtoupper($fiche->enseignant->nom) }}</div>
        </td>
        <td>
            <div class="lbl">Le Comptable</div>
            <div class="line">Signature & cachet</div>
        </td>
        <td>
            <div class="lbl">Le Directeur</div>
            <div class="line">Signature & cachet</div>
        </td>
    </tr>
</table>

<div class="footer">
    Bulletin de paie {{ $fiche->reference }} · {{ $etablissement?->nom ?? 'AviaSchoolPay' }} · Édité le {{ now()->format('d/m/Y à H:i') }}
</div>

</body>
</html>
