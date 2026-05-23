<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Cartes élèves — {{ $annee->libelle ?? '' }}</title>
    <style>
        @page { margin: 10mm; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 9px; }

        .carte-grid { width: 100%; }
        .carte-grid td { width: 50%; padding: 4mm; vertical-align: top; }

        .carte {
            border: 1.5px solid #0a7b3f;
            border-radius: 8px;
            padding: 8px;
            background: linear-gradient(to bottom right, #ffffff, #f0fdf4);
            height: 230px;
            position: relative;
            overflow: hidden;
            page-break-inside: avoid;
        }
        .carte .accent {
            position: absolute; top: 0; left: 0; right: 0; height: 6px;
            background: linear-gradient(to right, #0a7b3f, #10b981, #E8A817);
        }
        .carte .header {
            padding-top: 8px;
            text-align: center;
            border-bottom: 1px solid #d1fae5;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .carte .etab-nom { font-size: 9px; font-weight: bold; color: #0a7b3f; }
        .carte .annee { font-size: 7px; color: #64748b; }

        .carte-body { display: table; width: 100%; }
        .carte-body .left { display: table-cell; width: 28%; vertical-align: top; padding-right: 6px; }
        .carte-body .right { display: table-cell; width: 72%; vertical-align: top; }

        .photo-box {
            width: 60px; height: 75px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            text-align: center;
            line-height: 75px;
            font-size: 18px;
            color: #94a3b8;
            overflow: hidden;
            margin: 0 auto;
        }
        .photo-box img { max-width: 100%; max-height: 100%; object-fit: cover; }

        .nom { font-size: 11px; font-weight: bold; color: #0a7b3f; text-transform: uppercase; line-height: 1.15; }
        .prenom { font-size: 9px; color: #1f2937; font-weight: bold; }

        .infos { margin-top: 4px; font-size: 7.5px; line-height: 1.4; color: #475569; }
        .infos b { color: #0f172a; }
        .infos .mat { font-family: DejaVu Sans Mono, monospace; background: #f0fdf4; padding: 1px 3px; border-radius: 2px; }

        .qr-zone {
            text-align: center;
            padding-top: 6px;
            border-top: 1px dashed #cbd5e1;
            margin-top: 6px;
        }
        .qr-zone img { width: 60px; height: 60px; }
        .qr-zone .qr-label { font-size: 6.5px; color: #94a3b8; margin-top: 2px; }

        .footer-carte {
            position: absolute; bottom: 4px; left: 8px; right: 8px;
            text-align: center;
            font-size: 6.5px;
            color: #94a3b8;
            border-top: 1px solid #f0fdf4;
            padding-top: 2px;
        }

        .badge-classe {
            display: inline-block;
            background: #0a7b3f;
            color: white;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

@php
    use SimpleSoftwareIO\QrCode\Facades\QrCode;
    $genererQr = function($texte) {
        try {
            $svg = QrCode::format('svg')->size(120)->margin(1)->errorCorrection('M')->generate($texte);
            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        } catch (\Throwable $e) { return null; }
    };

    // 2 cartes par ligne
    $chunks = $inscriptions->chunk(2);
@endphp

@foreach($chunks as $ligne)
<table class="carte-grid">
    <tr>
        @foreach($ligne as $insc)
            @php
                $eleve = $insc->eleve;
                $qrText = json_encode([
                    'm' => $eleve->matricule_interne ?? '',
                    'n' => trim(($eleve->prenom ?? '').' '.($eleve->nom ?? '')),
                    'c' => $insc->classe?->nom ?? '',
                    'a' => $insc->anneeScolaire?->libelle ?? '',
                ]);
                $qrData = $genererQr($qrText);
                $photoPath = $eleve->photo_path ? storage_path('app/public/'.$eleve->photo_path) : null;
                $photoExists = $photoPath && file_exists($photoPath);
            @endphp
            <td>
                <div class="carte">
                    <div class="accent"></div>
                    <div class="header">
                        <div class="etab-nom">{{ $etab?->nom ?? '' }}</div>
                        <div class="annee">CARTE D'ÉLÈVE · {{ $annee->libelle }}</div>
                    </div>
                    <div class="carte-body">
                        <div class="left">
                            <div class="photo-box">
                                @if($photoExists)
                                    <img src="{{ $photoPath }}" alt="" />
                                @else
                                    {{ $eleve->sexe === 'F' ? '👧' : '👦' }}
                                @endif
                            </div>
                        </div>
                        <div class="right">
                            <div class="nom">{{ $eleve->nom }}</div>
                            <div class="prenom">{{ $eleve->prenom }}</div>
                            <div style="margin-top:3px;"><span class="badge-classe">{{ $insc->classe?->nom }}</span></div>
                            <div class="infos">
                                <div>Mat. <span class="mat">{{ $eleve->matricule_interne ?? '—' }}</span></div>
                                <div>{{ $eleve->sexe === 'M' ? 'Masculin' : 'Féminin' }} · Né(e) {{ $eleve->date_naissance?->format('d/m/Y') ?? '—' }}</div>
                                @if($eleve->contact_urgence_tel)
                                <div>📞 {{ $eleve->contact_urgence_tel }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="qr-zone">
                        @if($qrData)
                            <img src="{{ $qrData }}" alt="QR" />
                        @endif
                        <div class="qr-label">Scanner pour vérifier</div>
                    </div>
                    <div class="footer-carte">
                        Valable {{ $annee->libelle }} · Document officiel — propriété de l'établissement
                    </div>
                </div>
            </td>
        @endforeach
        @if($ligne->count() < 2)
            <td></td>
        @endif
    </tr>
</table>
@endforeach

</body>
</html>
