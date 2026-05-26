<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Bulletin — {{ $eleve->nom }} {{ $eleve->prenom }}</title>
<style>
    @page { margin: 0; }
    html, body { margin: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 7.2pt; color: #000; line-height: 1.08; padding: 15mm; }
</style>
</head>
<body>
@include('admin.rh.bulletins.pdf-body')
</body>
</html>
