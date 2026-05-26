{{-- BULLETIN DES NOTES — modèle compact type officiel --}}
@php
    $fmt = fn($v, $dec = 2) => $v !== null && $v !== '' ? number_format((float) $v, $dec, ',', ' ') : '—';
    $fmt0 = fn($v) => $v !== null && $v !== '' ? number_format((float) $v, 0, ',', ' ') : '—';
    $rang = fn($r) => $r ? $r.'e' : '—';
    $norm = function ($v) {
        $v = strtolower(trim((string) $v));
        $v = strtr($v, ['à'=>'a','á'=>'a','â'=>'a','ä'=>'a','ç'=>'c','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o','ù'=>'u','û'=>'u','ü'=>'u']);
        return preg_replace('/\s+/', ' ', $v) ?: '';
    };
    $isFrancais = fn($m) => str_contains($norm(($m->code ?? '').' '.($m->nom ?? '')), 'francais') || in_array($norm($m->code ?? ''), ['fr','fra'], true);
    $isPremierCycle = function () use ($classe, $norm) {
        $classe?->loadMissing('niveau');
        $n = $classe?->niveau;
        if (!$n) return false;
        $cycle = $norm($n->cycle ?? '');
        if (in_array($cycle, ['premier_cycle','premier cycle','college'], true)) return true;
        $txt = $norm(($n->code ?? '').' '.($n->libelle ?? '').' '.($classe->nom ?? ''));
        return preg_match('/(^|\s)(6|5|4|3)\s*(e|eme)?(\s|$)/', $txt) === 1;
    };
    $appreciation = function ($moy) {
        if ($moy === null || $moy === '') return 'NC';
        $moy = (float) $moy;
        if ($moy >= 16) return 'TRES BIEN';
        if ($moy >= 14) return 'BIEN';
        if ($moy >= 12) return 'ASSEZ BIEN';
        if ($moy >= 10) return 'PASSABLE';
        if ($moy >= 8) return 'INSUFFISANT';
        return 'FAIBLE';
    };

    $moyennesParId = collect($moyennes)->keyBy('matiere_id');
    $effectif = (int) ($generale->effectif_classe ?: ($classe->effectif ?? 0));
    $annuelle = \App\Models\MoyenneAnnuelle::where('eleve_id', $eleve->id)->where('annee_scolaire_id', $annee?->id)->first();

    $rootIds = \Illuminate\Support\Facades\DB::table('affectations')
        ->where('classe_id', $classe?->id)
        ->where('annee_scolaire_id', $annee?->id)
        ->where('active', true)
        ->pluck('matiere_id')
        ->unique()
        ->values();

    $matieres = \App\Models\Matiere::whereIn('id', $rootIds)
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
        ->map(fn($rows) => $rows->map(fn($r) => trim(($r->prenom ? substr($r->prenom, 0, 1).'. ' : '').$r->nom))->unique()->join(', '));

    $groups = ['LETTRES' => [], 'SCIENCES' => [], 'AUTRES' => []];
    foreach ($matieres as $mat) {
        $g = $norm($mat->groupe ?? '');
        $code = strtoupper((string) $mat->code);
        if (str_contains($g, 'scient') || in_array($code, ['MATH','PC','SVT','SP','PHYS','CHIM'], true)) $groups['SCIENCES'][] = $mat;
        elseif (str_contains($g, 'litt') || in_array($code, ['FR','FRANCAIS','ANG','ANGL','HG','ESP','ALL','PHILO'], true) || $isFrancais($mat)) $groups['LETTRES'][] = $mat;
        else $groups['AUTRES'][] = $mat;
    }

    $photoSrc = null;
    if (!empty($eleve->photo_path)) {
        foreach ([public_path(ltrim($eleve->photo_path, '/')), public_path('storage/'.ltrim($eleve->photo_path, '/')), storage_path('app/public/'.ltrim($eleve->photo_path, '/'))] as $p) {
            if ($p && file_exists($p)) { $photoSrc = $p; break; }
        }
    }

    $totalCoef = 0; $totalPoints = 0;
    $decision = $annuelle?->decision_finale ?: (((float)($annuelle?->moyenne_annuelle ?? $generale->moyenne_generale) >= 10) ? 'Admis(e)' : 'Redouble en cas de non orientation');

    // Récapitulatif T1/T2/T3 : tous les trimestres de l'année + moyennes générales de l'élève
    $tousTrimestres = \App\Models\Trimestre::where('annee_scolaire_id', $annee?->id)
        ->orderBy('numero')->get();
    $generalesParTrim = \App\Models\MoyenneGenerale::where('eleve_id', $eleve->id)
        ->whereIn('trimestre_id', $tousTrimestres->pluck('id'))
        ->get()->keyBy('trimestre_id');

    // Date du jour en français
    $moisFr = [1=>'janvier',2=>'février',3=>'mars',4=>'avril',5=>'mai',6=>'juin',7=>'juillet',8=>'août',9=>'septembre',10=>'octobre',11=>'novembre',12=>'décembre'];
    $today = now();
    $dateVisaFr = $today->day.' '.$moisFr[(int) $today->month].' '.$today->year;

    // Suffixe ordinal du trimestre (1er, 2è, 3è...)
    $numT = (int) ($trimestre->numero ?? 1);
    $suffixT = $numT === 1 ? 'er' : 'è';
@endphp

<style>
    /* === Palette professionnelle === */
    /* Primary navy: #1e3a5f | Accent: #2563eb | Surface: #f1f5f9 | Soft: #e2e8f0 */

    .bulletin { width:100%; font-family: DejaVu Sans, sans-serif; color:#0f172a; font-size:7.3pt; line-height:1.15; border:1.5px solid #1e3a5f; padding:0; background:#fff; }
    .bulletin table { width:100%; border-collapse:collapse; }
    .bulletin td, .bulletin th { border:0.75px solid #475569; padding:2.5px 4px; vertical-align:middle; }
    .no-border, .no-border td { border:0 !important; }

    /* === Bandeau supérieur navy === */
    .head-band { background:#1e3a5f; color:#fff; padding:3mm 4mm; }
    .head-band table, .head-band td { border:0 !important; color:#fff; background:transparent; }
    .ministry { font-size:7.4pt; font-weight:700; text-transform:uppercase; line-height:1.2; letter-spacing:.3px; opacity:.95; }
    .title { font-family: DejaVu Serif, serif; font-size:19pt; font-weight:900; letter-spacing:1px; text-align:right; }
    .sub-title { font-size:10pt; font-weight:700; text-align:right; margin-top:1mm; letter-spacing:.5px; opacity:.95; }

    /* === Encadré établissement === */
    .etab-block { background:#f8fafc; }
    .etab-block .school { font-family: DejaVu Serif, serif; font-size:11pt; font-weight:900; text-transform:uppercase; color:#1e3a5f; letter-spacing:.3px; }

    /* === Bloc élève === */
    .eleve-block { background:#fff; }
    .name { font-size:13.5pt; font-weight:900; color:#1e3a5f; text-transform:uppercase; letter-spacing:.4px; padding:1mm 0 1.5mm; }
    .label { font-size:6.6pt; color:#475569; text-transform:uppercase; letter-spacing:.3px; }
    .value { font-size:8.5pt; font-weight:900; color:#0f172a; }
    .dot { padding:0 2px; display:inline-block; font-weight:900; color:#0f172a; }
    .photo { width:24mm; height:28mm; object-fit:cover; border:1.5px solid #1e3a5f; }
    .photo-empty { width:24mm; height:28mm; border:1.5px dashed #94a3b8; display:inline-block; text-align:center; line-height:28mm; color:#94a3b8; font-size:6.5pt; background:#f8fafc; }

    /* === Tableau notes === */
    .notes thead th { background:#1e3a5f; color:#fff; font-size:6.4pt; text-align:center; font-weight:900; text-transform:uppercase; padding:2.5px 2px; letter-spacing:.3px; border-color:#1e3a5f; }
    .notes td { font-size:7.1pt; }
    .disc { font-weight:900; color:#0f172a; }
    .subdisc { font-family: DejaVu Serif, serif; font-style:italic; padding-left:10px !important; color:#475569; }
    .num { text-align:center; font-weight:700; white-space:nowrap; }
    .app { font-size:6.5pt !important; font-weight:900; text-transform:uppercase; color:#1e3a5f; }
    .prof { font-size:6.4pt !important; font-weight:700; color:#334155; }
    .bilan td { font-weight:900; text-transform:uppercase; background:#dbeafe; color:#1e3a5f; font-size:7pt; letter-spacing:.3px; }
    .totaux td { font-weight:900; background:#1e3a5f; color:#fff; font-size:7.4pt; letter-spacing:.5px; border-color:#1e3a5f; }
    .totaux td.num { color:#fff; }

    /* === Footer === */
    .foot-title { text-align:center; font-weight:900; text-transform:uppercase; font-size:7pt; color:#fff; background:#1e3a5f; padding:1.5mm 1mm; letter-spacing:.4px; margin:-2.5px -4px 2mm; }
    .big { font-size:18pt; font-weight:900; text-align:center; color:#1e3a5f; line-height:1; }
    .rg-line { text-align:center; font-size:8pt; color:#475569; }
    .rg-line b { color:#1e3a5f; font-size:9pt; }
    .divider { border-top:1px dashed #cbd5e1; margin:2mm 0; }
    .section-lbl { font-weight:900; color:#1e3a5f; font-size:7pt; text-transform:uppercase; letter-spacing:.3px; margin-bottom:1mm; }
    .box { display:inline-block; width:9px; height:9px; border:1.2px solid #1e3a5f; margin-right:3px; vertical-align:middle; text-align:center; line-height:7px; color:#1e3a5f; font-weight:900; }
    .decision { text-align:center; font-family: DejaVu Serif, serif; font-style:italic; font-weight:900; color:#b91c1c; font-size:10pt; line-height:1.2; padding:2mm 1mm; background:#fef2f2; border-left:3px solid #b91c1c; }
    .small { font-size:6.2pt; color:#64748b; text-align:center; padding:1.5mm 1mm; background:#f1f5f9; border-top:0.75px solid #cbd5e1; }
    .eleve-row td { padding:1.5px 4px !important; line-height:1.2; }
    .visa-zone { text-align:center; padding:2mm 1mm 1mm; }
    .visa-date { font-style:italic; color:#475569; font-size:7pt; margin-bottom:8mm; }
    .visa-role { font-weight:900; color:#1e3a5f; text-transform:uppercase; font-size:7pt; letter-spacing:.4px; }
</style>

<div class="bulletin">
    <div class="head-band">
        <table>
            <tr>
                <td style="width:55%; text-align:left; vertical-align:middle;">
                    <div class="ministry">Ministère de l'Éducation Nationale<br>et de l'Alphabétisation</div>
                    <div class="ministry" style="margin-top:1mm;">Direction Régionale {{ strtoupper($etab->drena ?: ($etab->region ?: $etab->ville ?: '')) }}</div>
                </td>
                <td style="width:45%; text-align:right; vertical-align:middle;">
                    <div class="title">BULLETIN DES NOTES</div>
                    <div class="sub-title">{{ $trimestre->libelle ?? ($trimestre->numero.'e Trimestre') }} · {{ $annee->libelle ?? '—' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="etab-block">
        <tr>
            <td style="width:66%;">
                <span class="label">Établissement</span><br>
                <span class="school">{{ $etab->nom ?? '—' }}</span><br>
                <span class="label">Adresse</span> · {{ $etab->adresse ?? '—' }} &nbsp;·&nbsp; <span class="label">Tél.</span> {{ $etab->telephone ?? '—' }}
            </td>
            <td style="width:34%;">
                <b>Code :</b> {{ $etab->code_desps ?? $etab->code ?? '—' }}<br>
                <b>Statut:</b> {{ ucfirst(str_replace('_', ' ', $etab->statut_juridique ?? 'Privé')) }}<br>
                <span class="small">{{ $etab->email ?? '' }}</span>
            </td>
        </tr>
    </table>

    <table class="eleve-block">
        <tr>
            <td colspan="3" style="border-bottom:0;">
                <div class="name">{{ strtoupper(trim(($eleve->prenom ?? '').' '.($eleve->nom ?? ''))) }}</div>
            </td>
            <td rowspan="5" style="width:25mm; text-align:center;">
                @if($photoSrc)<img src="{{ $photoSrc }}" class="photo">@else<span class="photo-empty">PHOTO</span>@endif
            </td>
        </tr>
        <tr class="eleve-row">
            <td style="width:30%; border-top:0; border-bottom:0;">
                <span class="label">Matricule:</span> <span class="dot">{{ $eleve->matricule_desps ?: $eleve->matricule_interne ?: '' }}</span>
            </td>
            <td style="width:30%; border-top:0; border-bottom:0;">
                <span class="label">Sexe:</span> <span class="dot" style="min-width:8mm;">{{ strtoupper($eleve->sexe ?? '') }}</span>
            </td>
            <td style="width:25%; border-top:0; border-bottom:0;">
                <span class="label">Redoub.:</span> <span class="dot" style="min-width:10mm;">{{ $eleve->redoublant ? 'oui' : 'non' }}</span>
            </td>
        </tr>
        <tr class="eleve-row">
            <td style="border-top:0; border-bottom:0;"></td>
            <td style="border-top:0; border-bottom:0;">
                <span class="label">Né(e) le:</span> <span class="dot">{{ $eleve->date_naissance?->format('d/m/Y') ?? '' }}</span>
            </td>
            <td style="border-top:0; border-bottom:0;">
                <span class="label">Régime:</span> <span class="dot" style="min-width:12mm;">Non bo</span>
            </td>
        </tr>
        <tr class="eleve-row">
            <td style="border-top:0; border-bottom:0;">
                <span class="label">Classe:</span> <span class="dot">{{ $classe->nom ?? '' }}</span>
            </td>
            <td style="border-top:0; border-bottom:0;">
                <span class="label">À:</span> <span class="dot">{{ strtoupper($eleve->lieu_naissance ?? '') }}</span>
            </td>
            <td style="border-top:0; border-bottom:0;">
                <span class="label">Interne:</span> <span class="dot" style="min-width:10mm;">non</span>
            </td>
        </tr>
        <tr class="eleve-row">
            <td style="border-top:0;">
                <span class="label">Effectif:</span> <span class="dot" style="min-width:12mm;">{{ $effectif ?: '' }}</span>
            </td>
            <td style="border-top:0;">
                <span class="label">Nationalité:</span> <span class="dot">{{ $eleve->nationalite ?? '' }}</span>
            </td>
            <td style="border-top:0;">
                <span class="label">Affecté(e):</span> <span class="dot" style="min-width:10mm;">{{ method_exists($eleve, 'estAffecte') && $eleve->estAffecte() ? 'Oui' : 'Non' }}</span>
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
                <th>MOY./20</th><th>Coef.</th><th>M. COEF</th><th>RANG</th><th>Moy Ann</th><th>RANG</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groups as $groupName => $groupMatieres)
                @continue(empty($groupMatieres))
                @php $gCoef = 0; $gPts = 0; @endphp
                @foreach($groupMatieres as $mat)
                    @php
                        $subs = ($isPremierCycle() && $isFrancais($mat)) ? $mat->sousDisciplines : collect();
                        $mObj = $moyennesParId->get($mat->id);
                        $moy = $mObj?->moyenne !== null ? (float) $mObj->moyenne : null;
                        if ($moy === null && $subs->isNotEmpty()) {
                            $sp = 0; $sw = 0;
                            foreach ($subs as $sd) {
                                $sdObj = $moyennesParId->get($sd->id);
                                if ($sdObj?->moyenne !== null) { $w = (float)($sd->poids_dans_parent ?: 1); $sp += (float)$sdObj->moyenne * $w; $sw += $w; }
                            }
                            if ($sw > 0) $moy = round($sp / $sw, 2);
                        }
                        $coef = (float) ($mat->coefficient_defaut ?: 1);
                        $pts = $moy !== null ? $moy * $coef : null;
                        if ($pts !== null) { $gCoef += $coef; $gPts += $pts; $totalCoef += $coef; $totalPoints += $pts; }
                        $prof = $enseignants[$mat->id] ?? '';
                    @endphp
                    <tr>
                        <td class="disc">{{ $mat->nom }}</td>
                        <td class="num">{{ $fmt($moy) }}</td>
                        <td class="num">{{ $fmt0($coef) }}</td>
                        <td class="num">{{ $fmt($pts) }}</td>
                        <td class="num">{{ $rang($mObj?->rang_classe) }}</td>
                        <td class="num">{{ $fmt($moy) }}</td>
                        <td class="num">{{ $rang($mObj?->rang_classe) }}</td>
                        <td class="app">{{ $mObj?->appreciation ?: $appreciation($moy) }}</td>
                        <td class="prof">{{ $prof }}</td>
                    </tr>
                    @foreach($subs as $sub)
                        @php $sObj = $moyennesParId->get($sub->id); $sMoy = $sObj?->moyenne !== null ? (float)$sObj->moyenne : null; @endphp
                        <tr>
                            <td class="subdisc">{{ $sub->nom }}</td>
                            <td class="num">{{ $fmt($sMoy) }}</td>
                            <td class="num">{{ $fmt0($sub->poids_dans_parent ?: 1) }}</td>
                            <td class="num">{{ $sMoy !== null ? $fmt($sMoy * (float)($sub->poids_dans_parent ?: 1)) : '—' }}</td>
                            <td class="num">{{ $rang($sObj?->rang_classe) }}</td>
                            <td class="num">{{ $fmt($sMoy) }}</td>
                            <td class="num">{{ $rang($sObj?->rang_classe) }}</td>
                            <td class="app">{{ $sObj?->appreciation ?: $appreciation($sMoy) }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                @endforeach
                <tr class="bilan">
                    <td>BILAN {{ $groupName }}</td>
                    <td class="num">{{ $gCoef > 0 ? $fmt($gPts / $gCoef) : '—' }}</td>
                    <td class="num">{{ $fmt0($gCoef) }}</td>
                    <td class="num">{{ $fmt($gPts) }}</td>
                    <td></td><td class="num">{{ $gCoef > 0 ? $fmt($gPts / $gCoef) : '—' }}</td><td colspan="3"></td>
                </tr>
            @endforeach
            <tr class="totaux">
                <td>TOTAUX</td>
                <td class="num"></td>
                <td class="num">{{ $fmt0($totalCoef ?: $generale->total_coefficients) }}</td>
                <td class="num">{{ $fmt($totalPoints ?: $generale->total_points) }}</td>
                <td class="num"></td>
                <td class="num"></td>
                <td class="num"></td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <table style="margin-top:2px;">
        <tr>
            <td style="width:34%;">
                <div class="foot-title">Bilan du {{ $numT }}{{ $suffixT }} Trimestre</div>
                <div class="big">{{ $fmt($generale->moyenne_generale, 2) }}<span style="font-size:9pt; color:#64748b;"> /20</span></div>
                <div class="rg-line">Rang : <b>{{ $generale->rang ?? '—' }}</b> / {{ $effectif ?: '—' }}</div>
                <div class="divider"></div>
                <div class="section-lbl">Distinctions / Sanctions</div>
                <div style="font-size:6.8pt; line-height:1.4;">
                    <span class="box">{{ in_array($generale->mention, ['felicitations','tableau_honneur'], true) ? '✓' : '' }}</span>TH + Fél.
                    &nbsp;&nbsp;<span class="box">{{ $generale->mention === 'avertissement' ? '✓' : '' }}</span>Blâme Travail<br>
                    <span class="box">{{ $generale->mention === 'tableau_honneur' ? '✓' : '' }}</span>TH
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="box">{{ $generale->mention === 'blame' ? '✓' : '' }}</span>Blâme conduite
                </div>
                <div class="divider"></div>
                <div class="section-lbl">Absences & statistiques</div>
                <div style="font-size:6.8pt; line-height:1.5;">
                    <b style="color:#1e3a5f;">{{ $generale->total_absences ?? 0 }}h</b> d'abs. · <b style="color:#1e3a5f;">{{ $generale->absences_justifiees ?? 0 }}</b> non just.<br>
                    Min <b>{{ $fmt($generale->moyenne_dernier) }}</b> · Moy <b>{{ $fmt($generale->moyenne_classe) }}</b> · Max <b>{{ $fmt($generale->moyenne_premier) }}</b>
                </div>
            </td>
            <td style="width:28%;">
                <div class="foot-title">Récapitulatif</div>
                <table>
                    <tr><th></th><th>Moy.</th><th>C.</th><th>M. C.</th><th>Rg</th></tr>
                    @foreach($tousTrimestres as $t)
                        @php
                            $gT = $generalesParTrim->get($t->id);
                            $coefT = (float) ($t->coefficient ?: 1);
                            $mcT = $gT && $gT->moyenne_generale !== null ? (float) $gT->moyenne_generale * $coefT : null;
                        @endphp
                        <tr>
                            <td class="num"><b>{{ $t->numero }}{{ $t->numero == 1 ? 'er' : 'è' }} T.</b></td>
                            <td class="num">{{ $gT ? $fmt($gT->moyenne_generale) : '—' }}</td>
                            <td class="num">{{ $fmt0($coefT) }}</td>
                            <td class="num">{{ $fmt($mcT) }}</td>
                            <td class="num">{{ $gT ? $rang($gT->rang) : '—' }}</td>
                        </tr>
                    @endforeach
                </table>
                <div class="foot-title" style="margin-top:3mm;">Décision de fin d'année</div>
                <div class="decision">{{ $decision }}</div>
            </td>
            <td style="width:38%;">
                <div class="foot-title">Bilan de fin d'année</div>
                <div class="big">{{ $fmt($annuelle?->moyenne_annuelle, 2) }}<span style="font-size:9pt; color:#64748b;"> /20</span></div>
                <div class="rg-line">Rang : <b>{{ $annuelle?->rang_annuel ?? '—' }}</b> / {{ $effectif ?: '—' }}</div>
                <div class="divider"></div>
                <div class="section-lbl" style="text-align:center;">Visa du Chef d'Établissement</div>
                <div class="visa-zone">
                    <div class="visa-date">Fait à {{ $etab->ville ?? '—' }}, le {{ $dateVisaFr }}</div>
                    <div class="visa-role">Le Directeur des Études</div>
                    <div style="font-size:6.8pt; color:#475569; margin-top:1mm;">{{ $etab->directeur_nom ?? '' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="small">{{ strtoupper($eleve->nom_complet) }} · {{ $classe->nom ?? '—' }} · {{ $trimestre->libelle ?? 'Période' }} {{ $annee->libelle ?? '' }} · Document généré le {{ $dateVisaFr }}</div>
</div>
