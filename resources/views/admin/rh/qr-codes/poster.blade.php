<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>QR Codes Pointage</title>
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; color: #111; }

    .page { page-break-after: always; width: 100%; height: 100vh; padding: 25mm; position: relative; }
    .page:last-child { page-break-after: auto; }

    .ecole { text-align: center; font-size: 11pt; color: #6b7280; margin-bottom: 8mm; }
    .ecole-nom { font-weight: bold; color: #1e40af; font-size: 14pt; text-transform: uppercase; letter-spacing: 1px; }

    .qr-box { border: 3px solid #1e40af; border-radius: 12px; padding: 8mm; text-align: center; }
    .qr-box .salle-nom { font-size: 26pt; font-weight: bold; color: #1e293b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4mm; }
    .qr-box .batiment { font-size: 11pt; color: #64748b; margin-bottom: 6mm; }
    .qr-box .qr-image { display: block; margin: 0 auto; }
    .qr-box .footer { margin-top: 6mm; font-size: 9pt; color: #94a3b8; }
    .qr-box .code { font-family: monospace; font-size: 7pt; color: #cbd5e1; margin-top: 2mm; }

    /* Format 1 par page (gros) */
    .format-1 .qr-box { width: 160mm; height: 220mm; margin: 0 auto; }
    .format-1 .qr-image { width: 130mm; height: 130mm; }

    /* Format 4 par page (grille 2x2) */
    .grid-4 { display: table; width: 100%; height: 230mm; }
    .grid-4 .row { display: table-row; }
    .grid-4 .cell { display: table-cell; width: 50%; vertical-align: top; padding: 4mm; }
    .grid-4 .qr-box { height: 110mm; padding: 4mm; }
    .grid-4 .salle-nom { font-size: 16pt; margin-bottom: 2mm; }
    .grid-4 .batiment { font-size: 8pt; margin-bottom: 3mm; }
    .grid-4 .qr-image { width: 60mm; height: 60mm; }
    .grid-4 .footer { font-size: 7pt; }
</style>
</head>
<body>

@if($format == 1)
    {{-- 1 QR par page, gros format --}}
    @foreach($salles as $item)
    <div class="page format-1">
        <div class="ecole">
            <div class="ecole-nom">{{ $etab->nom ?? '—' }}</div>
            <div>Pointage Enseignants — QR Code officiel</div>
        </div>
        <div class="qr-box">
            <div class="salle-nom">{{ $item['salle']->nom }}</div>
            @if($item['salle']->batiment)
            <div class="batiment">Bâtiment : {{ $item['salle']->batiment }}</div>
            @endif
            <img src="{{ $item['qr_base64'] }}" class="qr-image" alt="QR Code">
            <div class="footer">📱 Scannez avec votre application AviaSchoolPay pour pointer</div>
            <div class="code">{{ substr($item['qr']->code_unique, 0, 16) }}…</div>
        </div>
    </div>
    @endforeach
@else
    {{-- 4 QR par page, grille 2x2 --}}
    @foreach($salles->chunk(4) as $chunk)
    <div class="page">
        <div class="ecole">
            <div class="ecole-nom">{{ $etab->nom ?? '—' }}</div>
            <div>Pointage Enseignants — QR Codes officiels</div>
        </div>
        <div class="grid-4">
            <div class="row">
                @foreach($chunk->take(2) as $item)
                <div class="cell">
                    <div class="qr-box">
                        <div class="salle-nom">{{ $item['salle']->nom }}</div>
                        @if($item['salle']->batiment)
                        <div class="batiment">{{ $item['salle']->batiment }}</div>
                        @endif
                        <img src="{{ $item['qr_base64'] }}" class="qr-image" alt="QR">
                        <div class="footer">📱 AviaSchoolPay</div>
                    </div>
                </div>
                @endforeach
                @for($i = $chunk->take(2)->count(); $i < 2; $i++)<div class="cell"></div>@endfor
            </div>
            <div class="row">
                @foreach($chunk->skip(2)->take(2) as $item)
                <div class="cell">
                    <div class="qr-box">
                        <div class="salle-nom">{{ $item['salle']->nom }}</div>
                        @if($item['salle']->batiment)
                        <div class="batiment">{{ $item['salle']->batiment }}</div>
                        @endif
                        <img src="{{ $item['qr_base64'] }}" class="qr-image" alt="QR">
                        <div class="footer">📱 AviaSchoolPay</div>
                    </div>
                </div>
                @endforeach
                @for($i = $chunk->skip(2)->take(2)->count(); $i < 2; $i++)<div class="cell"></div>@endfor
            </div>
        </div>
    </div>
    @endforeach
@endif

@if($salles->isEmpty())
<div style="text-align: center; padding: 60mm 20mm; color: #94a3b8;">
    <h1 style="color: #ef4444; font-size: 18pt; margin-bottom: 8mm;">Aucun QR à imprimer</h1>
    <p>Sélectionnez au moins une salle ayant un QR actif.</p>
</div>
@endif

</body>
</html>
