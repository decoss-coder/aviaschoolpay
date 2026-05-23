@extends('documents.pdf._layout')

@php $money = fn($v) => number_format((float) $v, 0, ',', ' '); @endphp

@section('titre', 'RÉCAPITULATIF ANNUEL ÉCOLE')
@section('periode', 'Année scolaire : ' . ($annee->libelle ?? '—'))

@section('content')

<div style="text-align:center;padding:18px;background:linear-gradient(to bottom right, #d1fae5, #ecfdf5);border:2px solid #10b981;border-radius:8px;margin-bottom:16px;">
    <div style="font-size:9px;color:#065f46;text-transform:uppercase;font-weight:bold;letter-spacing:1px;">📈 Bilan de l'année</div>
    <div style="font-size:18px;color:#0a7b3f;font-weight:bold;margin-top:3px;">{{ $annee->libelle }}</div>
    <div style="font-size:9px;color:#065f46;margin-top:3px;">
        Du {{ $annee->date_debut?->format('d/m/Y') }} au {{ $annee->date_fin?->format('d/m/Y') }}
    </div>
</div>

<h2>👥 Effectifs</h2>
<div style="margin-bottom:8px;">
    <span class="kpi kpi-info"><div class="lbl">Élèves inscrits</div><div class="val">{{ $nb_eleves }}</div></span>
    <span class="kpi kpi-success"><div class="lbl">Filles</div><div class="val">{{ $nb_filles }}</div></span>
    <span class="kpi"><div class="lbl">Garçons</div><div class="val">{{ $nb_garcons }}</div></span>
    <span class="kpi kpi-warn"><div class="lbl">Redoublants</div><div class="val">{{ $nb_redoublants }}</div></span>
</div>

<h2>📚 Effectif par niveau</h2>
<table class="data">
    <thead>
        <tr>
            <th>Niveau</th>
            <th class="center">Classes</th>
            <th class="center">Total</th>
            <th class="center">Filles</th>
            <th class="center">Garçons</th>
            <th class="center">% école</th>
        </tr>
    </thead>
    <tbody>
        @foreach($par_niveau as $n)
            <tr>
                <td><b>{{ $n['libelle'] }}</b></td>
                <td class="center">{{ $n['classes'] }}</td>
                <td class="center"><b>{{ $n['effectif'] }}</b></td>
                <td class="center">{{ $n['filles'] }}</td>
                <td class="center">{{ $n['garcons'] }}</td>
                <td class="center">{{ $nb_eleves > 0 ? round(($n['effectif'] / $nb_eleves) * 100, 1) : 0 }}%</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td>TOTAL</td>
            <td class="center">{{ $par_niveau->sum('classes') }}</td>
            <td class="center">{{ $nb_eleves }}</td>
            <td class="center">{{ $nb_filles }}</td>
            <td class="center">{{ $nb_garcons }}</td>
            <td class="center">100%</td>
        </tr>
    </tfoot>
</table>

<h2>💰 Finances de l'année</h2>
<table style="width:100%;border-collapse:collapse;margin-bottom:10px;">
    <tr>
        <td style="width:25%;padding:12px;background:#dbeafe;border-radius:5px;text-align:center;">
            <div style="font-size:8px;color:#1e40af;font-weight:bold;text-transform:uppercase;">Dû total</div>
            <div style="font-size:13px;color:#1e3a8a;font-weight:bold;margin-top:3px;">{{ $money($total_du) }} F</div>
        </td>
        <td style="width:2%;"></td>
        <td style="width:25%;padding:12px;background:#d1fae5;border-radius:5px;text-align:center;">
            <div style="font-size:8px;color:#065f46;font-weight:bold;text-transform:uppercase;">Encaissé</div>
            <div style="font-size:13px;color:#047857;font-weight:bold;margin-top:3px;">{{ $money($total_paye) }} F</div>
            <div style="font-size:7px;color:#065f46;margin-top:1px;">{{ $taux_recouvrement }}% recouvré</div>
        </td>
        <td style="width:2%;"></td>
        <td style="width:25%;padding:12px;background:#fee2e2;border-radius:5px;text-align:center;">
            <div style="font-size:8px;color:#991b1b;font-weight:bold;text-transform:uppercase;">Reste à payer</div>
            <div style="font-size:13px;color:#b91c1c;font-weight:bold;margin-top:3px;">{{ $money($total_reste) }} F</div>
            <div style="font-size:7px;color:#991b1b;margin-top:1px;">{{ $nb_non_soldes }} élève(s)</div>
        </td>
        <td style="width:2%;"></td>
        <td style="width:19%;padding:12px;background:#fef3c7;border-radius:5px;text-align:center;">
            <div style="font-size:8px;color:#92400e;font-weight:bold;text-transform:uppercase;">Soldés</div>
            <div style="font-size:13px;color:#92400e;font-weight:bold;margin-top:3px;">{{ $nb_soldes }}</div>
            <div style="font-size:7px;color:#92400e;margin-top:1px;">/{{ $nb_eleves }}</div>
        </td>
    </tr>
</table>

<h2>📊 Résultat d'exploitation</h2>
<table class="data">
    <tbody>
        <tr>
            <td style="width:60%;background:#f0fdf4;">Revenus encaissés (scolarités)</td>
            <td class="right text-positive" style="background:#f0fdf4;font-size:12px;"><b>{{ $money($total_paye) }} F</b></td>
        </tr>
        <tr>
            <td style="background:#fef2f2;">Dépenses approuvées</td>
            <td class="right text-negative" style="background:#fef2f2;font-size:12px;"><b>−{{ $money($total_depenses) }} F</b></td>
        </tr>
        <tr>
            <td style="background:{{ $resultat >= 0 ? '#ecfdf5' : '#fef2f2' }};border-top:3px solid {{ $resultat >= 0 ? '#10b981' : '#ef4444' }};font-weight:bold;font-size:12px;">
                {{ $resultat >= 0 ? '✓ RÉSULTAT BÉNÉFICIAIRE' : '⚠ RÉSULTAT DÉFICITAIRE' }}
            </td>
            <td class="right" style="background:{{ $resultat >= 0 ? '#ecfdf5' : '#fef2f2' }};border-top:3px solid {{ $resultat >= 0 ? '#10b981' : '#ef4444' }};font-size:16px;font-weight:bold;color:{{ $resultat >= 0 ? '#047857' : '#b91c1c' }};">
                {{ $resultat >= 0 ? '+' : '' }}{{ $money($resultat) }} F
            </td>
        </tr>
    </tbody>
</table>

<h2>👨‍🏫 Personnel enseignant</h2>
<table class="data">
    <tr>
        <td style="width:50%;"><b>Enseignants actifs</b></td>
        <td class="right" style="font-size:11px;font-weight:bold;">{{ $nb_enseignants }}</td>
    </tr>
    <tr>
        <td><b>Ratio élèves / enseignant</b></td>
        <td class="right" style="font-size:11px;font-weight:bold;">
            {{ $nb_enseignants > 0 ? round($nb_eleves / $nb_enseignants, 1) : '—' }}
        </td>
    </tr>
    <tr>
        <td><b>Masse salariale annuelle (estimation)</b></td>
        <td class="right" style="font-size:11px;font-weight:bold;">{{ $money($masse_salariale) }} F</td>
    </tr>
    <tr>
        <td><b>Ratio MS / Revenus</b></td>
        <td class="right" style="font-size:11px;font-weight:bold;color:{{ $ratio_ms_revenus <= 65 ? '#047857' : '#b45309' }};">
            {{ $ratio_ms_revenus }}% {{ $ratio_ms_revenus <= 65 ? '✓ sain' : '⚠ élevé' }}
        </td>
    </tr>
</table>

@if($taux_reussite !== null)
<h2>🎓 Performance pédagogique</h2>
<div style="padding:12px;background:linear-gradient(to right, {{ $taux_reussite >= 70 ? '#d1fae5' : ($taux_reussite >= 50 ? '#fef3c7' : '#fee2e2') }}, white);border-radius:5px;text-align:center;">
    <div style="font-size:8px;color:#64748b;text-transform:uppercase;font-weight:bold;">Taux de réussite global (Trimestre 3, moyenne ≥ 10)</div>
    <div style="font-size:32px;font-weight:bold;color:{{ $taux_reussite >= 70 ? '#047857' : ($taux_reussite >= 50 ? '#92400e' : '#b91c1c') }};margin-top:5px;">
        {{ $taux_reussite }}%
    </div>
</div>
@endif

{{-- Conclusion --}}
<div style="margin-top:15px;padding:10px 14px;background:#f1f5f9;border-left:4px solid #0a7b3f;font-size:9px;color:#475569;line-height:1.6;">
    <p style="font-weight:bold;color:#0f172a;margin-bottom:3px;">📌 Synthèse exécutive</p>
    L'établissement {{ $etab?->nom }} a accueilli <b>{{ $nb_eleves }} élèves</b> pour l'année {{ $annee->libelle }}.
    Le taux de recouvrement des frais s'établit à <b>{{ $taux_recouvrement }}%</b>
    avec un résultat d'exploitation <b style="color:{{ $resultat >= 0 ? '#047857' : '#b91c1c' }};">{{ $resultat >= 0 ? 'bénéficiaire' : 'déficitaire' }} de {{ $money(abs($resultat)) }} F CFA</b>.
    Le ratio masse salariale / revenus est de <b>{{ $ratio_ms_revenus }}%</b>{{ $ratio_ms_revenus <= 65 ? ' (situation saine)' : ' (à surveiller)' }}.
</div>

<table style="width:100%;margin-top:25px;border-collapse:collapse;">
    <tr>
        <td style="width:50%;text-align:center;font-size:9px;color:#64748b;">
            <b style="text-transform:uppercase;">Le Comptable</b>
            <div style="border-top:1px dashed #94a3b8;margin-top:40px;padding-top:3px;font-size:8px;">Signature</div>
        </td>
        <td style="width:50%;text-align:center;font-size:9px;color:#64748b;">
            <b style="text-transform:uppercase;">Le Directeur</b>
            <div style="border-top:1px dashed #94a3b8;margin-top:40px;padding-top:3px;font-size:8px;">Signature & cachet</div>
        </td>
    </tr>
</table>

@endsection
