<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Bulletins en lot — {{ $classe->nom }}</title>
@php $perPage = (int) $disposition; @endphp
<style>
    @page { margin: 0; }
    html, body { margin: 0; }
    body { font-family: DejaVu Sans, sans-serif; color: #000; line-height: 1.2;
           padding: @if($perPage === 1) 15mm @else 10mm @endif;
           @if($perPage === 1) font-size: 8.5pt; @elseif($perPage === 2) font-size: 7pt; @else font-size: 6pt; @endif }
    .bulletin-wrap { padding-bottom: 2mm; }
    .bulletin-wrap + .bulletin-wrap { margin-top: 3mm; padding-top: 3mm; border-top: 2px dashed #999; }
</style>
</head>
<body>
@foreach($bulletins as $i => $b)
    @php $eleve = $b['eleve']; $generale = $b['generale']; $moyennes = $b['moyennes']; @endphp
    <div class="bulletin-wrap" @if(($i + 1) % $perPage === 0 && !$loop->last) style="page-break-after: always;" @endif>
        @include('admin.rh.bulletins.pdf-body')
    </div>
@endforeach
</body>
</html>
