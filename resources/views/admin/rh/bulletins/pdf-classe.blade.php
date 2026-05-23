<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Bulletins — {{ $classe->nom }}</title>
<style>
    @page { margin: 8mm 10mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #000; line-height: 1.25; }
    .page-break { page-break-after: always; }
</style>
</head>
<body>
@foreach($bulletins as $i => $b)
    @php $eleve = $b['eleve']; $generale = $b['generale']; $moyennes = $b['moyennes']; @endphp
    <div @if(!$loop->last) class="page-break" @endif>
        @include('admin.rh.bulletins.pdf-body')
    </div>
@endforeach
</body>
</html>
