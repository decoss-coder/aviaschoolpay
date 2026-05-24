{{-- ============================================================
     BULLETIN DES NOTES — modèle professionnel compact
     Variables attendues : $etab, $annee, $trimestre, $classe, $eleve, $generale, $moyennes
     ============================================================ --}}
@php
    $fmt = fn($v, $dec = 2) => $v !== null && $v !== '' ? number_format((float) $v, $dec, ',', ' ') : '—';
    $fmt0 = fn($v) => $v !== null && $v !== '' ? number_format((float) $v, 0, ',', ' ') : '—';
    $rang = fn($r) => $r ? $r.'e' : '—';

    $appreciation = function ($moy) {
        if ($moy === null || $moy === '') return 'NC';
        $moy = (float) $moy;
        if ($moy >= 16) return 'TRÈS BIEN';
        if ($moy >= 14) return 'BIEN';
        if ($moy >= 12) return 'ASSEZ BIEN';
        if ($moy >= 10) return 'PASSABLE';
        if ($moy >= 8) return 'INSUFFISANT';
        return 'FAIBLE';
    };

    $mentionLabels = [
        'felicitations'    => 'TH',
        'tableau_honneur'  => 'TH',
        'encouragements'   => 'Encouragements',
        'avertissement'    => 'Avertissement travail',
        'blame'            => 'Blâme',
        'aucune'           => '—',
    ];

    $moyennesParId = collect($moyennes)->keyBy('matiere_id');
    $effectif = (int) ($generale->effectif_classe ?: ($classe->effectif ?? 0));

    $annuelle = \App\Models\MoyenneAnnuelle::where('eleve_id', $eleve->id)
        ->where('annee_scolaire_id', $annee?->id)
        ->first();

    $matieres = \App\Models\Matiere::where('etablissement_id', $etab->id)
        ->whereNull('parent_matiere_id')
        ->where('active', true)
        ->with(['sousDisciplines' => fn($q) => $q->where('active', true)->orderBy('ordre')->orderBy('nom')])
        ->orderBy('ordre')->orderBy('nom')
        ->get();

    $enseignants = \Illuminate\Support\Facades\DB::table('affectations')
        ->join('enseignants', 'enseignants.id', '=', 'affectations.enseignant_id')
        ->where('affectations.classe_id', $classe?->id)
        ->where('affectations.annee_scolaire_id', $annee?->id)
        ->select('affectations.matiere_id', 'enseignants.nom', 'enseignants.prenom')
        ->get()
        ->groupBy('matiere_id')
        ->map(fn($rows) => $rows->map(fn($r) => trim(($r->prenom ? mb_substr($r->prenom, 0, 1).'. ' : '').$r->nom))->unique()->join(', '));

    $groups = [
        'LETTRES' => [],
        'SCIENCES' => [],
        'AUTRES' => [],
    ];

    foreach ($matieres as $mat) {
        $groupe = mb_strtolower((string) ($mat->groupe ?? ''));
        if (str_contains($groupe, 'scient')) {
            $groups['SCIENCES'][] = $mat;
        } elseif (str_contains($groupe, 'litt') || in_array(strtoupper((string) $mat->code), ['FR','ANG','ANGL','HG','ESP','ALL','PHILO'], true)) {
            $groups['LETTRES'][] = $mat;
        } else {
            $groups['AUTRES'][] = $mat;
        }
    }

    $photoSrc = null;
    if (!empty($eleve->photo_path)) {
        $raw = ltrim($eleve->photo_path, '/');
        $candidates = [
            public_path($raw),
            public_path('storage/'.$raw),
            storage_path('app/public/'.$raw),
        ];
        foreach ($candidates as $candidate) {
            if ($candidate && file_exists($candidate)) { $photoSrc = $candidate; break; }
        }
    }

    $totalCoef = 0;
    $totalPoints = 0;
    $totalAnnuelPoints = 0;
    $hasAnnuel = false;

    $decision = $annuelle?->decision_finale;
    if (!$decision) {
        $baseMoy = $annuelle?->moyenne_annuelle ?? $generale->moyenne_generale;
        $decision = $baseMoy !== null && (float) $baseMoy >= 10 ? 'Admis(e) / Passage en classe supérieure' : 'Redouble en cas de non orientation';
    }
@endphp

<style>
    .bulletin-pro { width: 100%; font-family: DejaVu Sans, sans-serif; color: #050505; font-size: 7.2pt; line-height: 1.12; }
    .bulletin-pro table { width: 100%; border-collapse: collapse; }
    .bulletin-pro td, .bulletin-pro th { border: 1px solid #000; padding: 1.5px 2px; vertical-align: middle; }
    .top-title { text-align:center; font-weight:800; font-size:16pt; letter-spacing:.3px; }
    .top-subtitle { text-align:center; font-weight:700; font-size:10pt; margin-top:1px; }
    .ministry { font-size:7.3pt; font-weight:800; text-align:center; text-transform:uppercase; line-height:1.15; }
    .school-name { font-size:10.5pt; font-weight:800; text-transform:uppercase; letter-spacing:.2px; }
    .student-name { font-size:13pt; font-weight:800; color:#1d4e89; text-transform:uppercase; }
    .info-label { font-size:6.7pt; }
    .info-value { font-size:8.6pt; font-weight:800; }
    .photo-cell { width:23mm; text-align:center; }
    .photo-box { width:20mm; height:23mm; border:1px solid #ddd; object-fit:cover; }
    .photo-placeholder { width:20mm; height:23mm; border:1px solid #bbb; background:#f3f4f6; display:inline-block; line-height:23mm; color:#777; font-size:6pt; }
    .notes th { font-size:6.4pt; font-weight:800; text-align:center; text-transform:uppercase; background:#f5f5f5; }
    .notes td { font-size:7.1pt; }
    .discipline { font-weight:800; }
    .sub-discipline { font-style:italic; padding-left:10px !important; font-size:6.7pt !important; }
    .num { text-align:center; font-weight:700; white-space:nowrap; }
    .app { font-weight:800; font-size:6.6pt !important; text-transform:uppercase; }
    .prof { font-size:6.2pt !important; font-weight:700; }
    .bilan-row td { font-weight:800; background:#f7f7f7; text-transform:uppercase; }
    .total-row td { font-weight:900; font-size:7.5pt; }
    .footer-title { font-weight:900; text-transform:uppercase; font-size:7pt; text-align:center; }
    .big-mark { font-size:15pt; font-weight:900; text-align:center; }
    .small { font-size:6.5pt; }
    .decision { font-size:11pt; font-weight:900; font-style:italic; text-align:center; color:#6b0000; line-height:1.15; }
    .check-box { display:inline-block; width:8px; height:8px; border:1px solid #000; margin-right:3px; vertical-align:middle; }
    .checked { font-size:8pt; font-weight:900; }
    .no-border td { border:0 !important; }
</style>

<div class="bulletin-pro">
    <table style="margin-bottom:2px; border:0;">
        <tr class="no-border">
            <td style="width:45%; text-align:center; border:0 !important;">
                <div class="ministry">MINISTÈRE DE L'ÉDUCATION NATIONALE ET DE L'ALPHABÉTISATION</div>
                <div class="ministry">DIRECTION RÉGIONALE {{ strtoupper($etab->drena ?: ($etab->region ?: $etab->ville ?: '')) }}</div>
            </td>
            <td style="width:55%; border:0 !important;">
                <div class="top-title">BULLETIN DES NOTES</div>
                <div class="top-subtitle">{{ strtoupper($trimestre->libelle ?? ($trimestre->numero.'e Trimestre')) }} - {{ $annee->libelle ?? '—' }}</div>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td style="width:66%; text-align:center;">
                <span class="info-label">Etab. :</span>
                <span class="school-name">{{ $etab->nom ?? '—' }}</span><br>
                <span class="info-label">Adres. :</span> {{ $etab->adresse ?? '—' }}
                &nbsp;&nbsp; <b>Tél.</b> {{ $etab->telephone ?? '—' }}
            </td>
            <td style="width:34%;">
                <b>Code</b> : {{ $etab->code_desps ?? '—' }}<br>
                <b>Statut</b> : {{ ucfirst(str_replace('_', ' ', $etab->statut_juridique ?? 'privé')) }}<br>
                <span class="small">{{ $etab->email ?? '' }}</span>
            </td>
        </tr>
    </table>

    <table style="margin-top:2px; margin-bottom:2px;">
        <tr>
            <td style="width:47%; border-right:0;">
                <div class="student-name">{{ strtoupper(trim(($eleve->prenom ?? '').' '.($eleve->nom ?? ''))) }}</div>
                <span class="info-label">Matricule.</span>
                <span class="info-value">{{ $eleve->matricule_desps ?: $eleve->matricule_interne ?: '—' }}</span>
                &nbsp;&nbsp;&nbsp;
                <span class="info-label">Classe:</span>
                <span class="info-value">{{ $classe->nom ?? '—' }}</span><br>
                <span class="info-label">Effectif:</span>
                <span class="info-value">{{ $effectif ?: '—' }}</span>
            </td>
            <td style="width:31%; border-left:0; border-right:0;">
                <span class="info-label">Sexe:</span> <b>{{ strtoupper($eleve->sexe ?? '—') }}</b><br>
                <span class="info-label">Né(e) le:</span> <b>{{ $eleve->date_naissance?->format('d/m/Y') ?? '—' }}</b><br>
                <span class="info-label">À:</span> <b>{{ strtoupper($eleve->lieu_naissance ?? '—') }}</b><br>
                <span class="info-label">Nationalité:</span> {{ $eleve->nationalite ?? '—' }}
            </td>
            <td style="width:10%; border-left:0; border-right:0;">
                <span class="info-label">Redub:</span> <b>{{ $eleve->redoublant ? 'oui' : 'non' }}</b><br>
                <span class="info-label">Regime:</span> <b>Non bo</b><br>
                <span class="info-label">Interne:</span> <b>non</b><br>
                <span class="info-label">Affecté(e):</span> <b>{{ $eleve->estAffecte() ? 'oui' : 'non' }}</b>
            </td>
            <td class="photo-cell">
                @if($photoSrc)
                    <img src="{{ $photoSrc }}" class="photo-box">
                @else
                    <span class="photo-placeholder">PHOTO</span>
                @endif
            </td>
        </tr>
    </table>

    <table class="notes">
        <thead>
            <tr>
                <th style="width:26%;" rowspan="2">DISCIPLINES</th>
                <th colspan="4">{{ strtoupper($trimestre->libelle ?? ($trimestre->numero.'e TRIMESTRE')) }}</th>
                <th colspan="2">Annuel</th>
                <th style="width:12%;" rowspan="2">APPRECIATION</th>
                <th style="width:17%;" rowspan="2">NOM ET SIGNATURE DU PROF.</th>
            </tr>
            <tr>
                <th style="width:7%;">MOY/20</th>
                <th style="width:6%;">Coef.</th>
                <th style="width:8%;">M. COEF</th>
                <th style="width:7%;">RANG</th>
                <th style="width:7%;">Moy</th>
                <th style="width:7%;">RANG</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groups as $groupName => $groupMatieres)
                @continue(empty($groupMatieres))
                @php $gCoef = 0; $gPts = 0; $gAnnPts = 0; $gHasAnn = false; @endphp

                @foreach($groupMatieres as $mat)
                    @php
                        $mObj = $moyennesParId->get($mat->id);
                        $moy = $mObj?->moyenne !== null ? (float) $mObj->moyenne : null;
                        $coef = (float) ($mat->coefficient_defaut ?: 1);
                        $pts = $moy !== null ? $moy * $coef : null;
                        $matAnn = $moy; // si moyenne annuelle matière non disponible, on affiche la moyenne courante comme repère
                        $app = $mObj?->appreciation ?: $appreciation($moy);
                        $prof = $enseignants[$mat->id] ?? '';

                        if ($pts !== null) { $gCoef += $coef; $gPts += $pts; $totalCoef += $coef; $totalPoints += $pts; }
                        if ($matAnn !== null && $pts !== null) { $gAnnPts += $matAnn * $coef; $gHasAnn = true; $hasAnnuel = true; $totalAnnuelPoints += $matAnn * $coef; }
                    @endphp
                    <tr>
                        <td class="discipline">{{ $mat->nom }}</td>
                        <td class="num">{{ $fmt($moy) }}</td>
                        <td class="num">{{ $fmt0($coef) }}</td>
                        <td class="num">{{ $fmt($pts) }}</td>
                        <td class="num">{{ $rang($mObj?->rang_classe) }}</td>
                        <td class="num">{{ $fmt($matAnn) }}</td>
                        <td class="num">{{ $rang($mObj?->rang_classe) }}</td>
                        <td class="app">{{ $app }}</td>
                        <td class="prof">{{ $prof }}</td>
                    </tr>

                    @foreach($mat->sousDisciplines as $sub)
                        @php
                            $sObj = $moyennesParId->get($sub->id);
                            $sMoy = $sObj?->moyenne !== null ? (float) $sObj->moyenne : null;
                        @endphp
                        <tr>
                            <td class="sub-discipline">{{ $sub->nom }}</td>
                            <td class="num">{{ $fmt($sMoy) }}</td>
                            <td class="num">{{ $fmt0($sub->coefficient_defaut ?: 1) }}</td>
                            <td class="num">{{ $sMoy !== null ? $fmt($sMoy * (float)($sub->coefficient_defaut ?: 1)) : '—' }}</td>
                            <td class="num">{{ $rang($sObj?->rang_classe) }}</td>
                            <td class="num">{{ $fmt($sMoy) }}</td>
                            <td class="num">{{ $rang($sObj?->rang_classe) }}</td>
                            <td class="app">{{ $sObj?->appreciation ?: $appreciation($sMoy) }}</td>
                            <td class="prof"></td>
                        </tr>
                    @endforeach
                @endforeach

                <tr class="bilan-row">
                    <td>BILAN {{ $groupName }}</td>
                    <td class="num">{{ $gCoef > 0 ? $fmt($gPts / $gCoef) : '—' }}</td>
                    <td class="num">{{ $fmt0($gCoef) }}</td>
                    <td class="num">{{ $fmt($gPts) }}</td>
                    <td></td>
                    <td class="num">{{ $gHasAnn && $gCoef > 0 ? $fmt($gAnnPts / $gCoef) : '—' }}</td>
                    <td colspan="3"></td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td>TOTAUX</td>
                <td class="num">{{ $fmt($generale->moyenne_generale) }}</td>
                <td class="num">{{ $fmt0($totalCoef ?: $generale->total_coefficients) }}</td>
                <td class="num">{{ $fmt($totalPoints ?: $generale->total_points) }}</td>
                <td class="num">{{ $rang($generale->rang) }}</td>
                <td class="num">{{ $fmt($annuelle?->moyenne_annuelle ?? ($hasAnnuel && $totalCoef > 0 ? $totalAnnuelPoints / $totalCoef : null)) }}</td>
                <td class="num">{{ $rang($annuelle?->rang_annuel) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <table style="margin-top:2px;">
        <tr>
            <td style="width:34%;">
                <div class="footer-title">BILAN DU {{ $trimestre->numero ?? '' }}e TRIMESTRE</div>
                <table class="no-border" style="margin-top:2px;">
                    <tr>
                        <td style="width:35%; text-align:center; border:0 !important;"><div class="big-mark">{{ $fmt($generale->moyenne_generale, 1) }}</div></td>
                        <td style="width:65%; border:0 !important;">Rg : <b>{{ $generale->rang ?? '—' }}</b> / <b>{{ $effectif ?: '—' }}</b></td>
                    </tr>
                </table>
                <div style="border-top:1px solid #000; margin:2px 0;"></div>
                <b>Distinctions / Sanctions</b><br>
                <span class="check-box">{{ in_array($generale->mention, ['felicitations','tableau_honneur'], true) ? '✓' : '' }}</span> TH + Fél.
                &nbsp;&nbsp; <span class="check-box">{{ $generale->mention === 'avertissement' ? '✓' : '' }}</span> Blâme Travail<br>
                <span class="check-box">{{ $generale->mention === 'tableau_honneur' ? '✓' : '' }}</span> TH
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span class="check-box">{{ $generale->mention === 'blame' ? '✓' : '' }}</span> Blâme conduite
                <div style="border-top:1px solid #000; margin:2px 0;"></div>
                <b>{{ $generale->total_absences ?? 0 }}</b> H d'abs. dont <b>{{ $generale->absences_justifiees ?? 0 }}</b> non just.<br>
                Moy. mini : <b>{{ $fmt($generale->moyenne_dernier) }}</b> &nbsp;&nbsp; Moy. Classe : <b>{{ $fmt($generale->moyenne_classe) }}</b><br>
                Moy. maxi : <b>{{ $fmt($generale->moyenne_premier) }}</b>
            </td>
            <td style="width:28%;">
                <div class="footer-title">Récapitulatif</div>
                <table style="margin-top:2px;">
                    <tr><th>Moy.</th><th>C. M.</th><th>Rg</th></tr>
                    <tr><td class="num">{{ $fmt($generale->moyenne_generale,1) }}</td><td class="num">{{ $fmt($generale->total_points) }}</td><td class="num">{{ $rang($generale->rang) }}</td></tr>
                    <tr><td class="num">{{ $fmt($annuelle?->moyenne_annuelle,1) }}</td><td class="num">—</td><td class="num">{{ $rang($annuelle?->rang_annuel) }}</td></tr>
                </table>
                <div class="footer-title" style="margin-top:5px;">Décision de fin d'année</div>
                <div class="decision">{{ $decision }}</div>
            </td>
            <td style="width:38%;">
                <div class="footer-title">BILAN DE FIN D'ANNÉE</div>
                <table class="no-border" style="margin-top:2px;">
                    <tr>
                        <td style="width:35%; text-align:center; border:0 !important;"><div class="big-mark">{{ $fmt($annuelle?->moyenne_annuelle, 2) }}</div></td>
                        <td style="width:65%; border:0 !important;">Rg : <b>{{ $annuelle?->rang_annuel ?? '—' }}</b> / <b>{{ $effectif ?: '—' }}</b></td>
                    </tr>
                </table>
                <div style="border-top:1px solid #000; margin:4px 0 8px;"></div>
                <div class="footer-title">VISA DU CHEF D'ÉTABLISSEMENT</div>
                <div style="height:28px; text-align:center; font-style:italic; padding-top:6px;">Fait à {{ $etab->ville ?? '—' }}, le {{ now()->format('d/m/Y') }}</div>
                <div style="height:34px;"></div>
                <div style="text-align:center; font-weight:800;">{{ strtoupper($etab->directeur_nom ?? 'LE DIRECTEUR DES ÉTUDES') }}</div>
            </td>
        </tr>
    </table>

    <div class="small" style="margin-top:4px;">&lt;{{ strtoupper($eleve->nom_complet) }}&gt; - {{ $classe->nom ?? '—' }} - {{ $trimestre->libelle ?? 'Période' }} {{ $annee->libelle ?? '' }}</div>
</div>
