@extends('layouts.app')
@section('title', 'Feuille de note · ' . $classe->nom)

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8 space-y-5">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('mon-espace.classes') }}" class="hover:text-brand-600 font-medium">Mes classes</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">{{ $classe->nom }}</span>
        <span>/</span>
        <span>Feuille de note</span>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <h1 class="font-display text-2xl font-extrabold text-gray-900">Feuille de note — {{ $classe->nom }}</h1>
        <span class="text-sm font-semibold text-gray-500">{{ $eleves->count() }} élèves</span>
    </div>

    {{-- ── Bloc 1 : Générer (PDF / Excel) ────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-5 space-y-3">
        <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">1 · Générer une feuille</h2>
        <p class="text-xs text-gray-500">Imprimez une feuille papier à remplir au stylo, ou téléchargez le template Excel à compléter à l'ordinateur.</p>

        <form id="gen-form" class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2" method="GET">
            @csrf
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Matière *</label>
                <select name="matiere_id" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-300 outline-none">
                    @foreach($matieres as $m)
                        <option value="{{ $m->id }}">{{ $m->nom }} ({{ $m->code }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Nb colonnes notes</label>
                <input type="number" name="nb_colonnes" value="6" min="1" max="12"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-300 outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Orientation</label>
                <select name="orientation" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-300 outline-none">
                    <option value="landscape">Paysage (recommandé)</option>
                    <option value="portrait">Portrait (compact)</option>
                </select>
            </div>
            <div class="sm:col-span-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Titre PDF (optionnel)</label>
                <input type="text" name="titre_pdf" placeholder="FEUILLE DE NOTE" maxlength="100"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-300 outline-none">
            </div>
        </form>

        <div class="flex flex-wrap gap-3 pt-3 border-t border-gray-100">
            <button type="button" onclick="submitFeuilleDeNote('{{ route('mon-espace.feuille-de-note.pdf', $classe) }}')"
                    class="bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Télécharger en PDF (à imprimer)
            </button>
            <button type="button" onclick="submitFeuilleDeNote('{{ route('mon-espace.feuille-de-note.excel', $classe) }}')"
                    class="bg-green-600 hover:bg-green-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Télécharger le template Excel
            </button>
        </div>
    </div>

    {{-- ── Bloc 2 : Importer Excel ──────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-card border border-blue-100 p-5 space-y-3">
        <h2 class="font-bold text-blue-800 text-sm uppercase tracking-wide">2 · Importer un fichier Excel rempli</h2>
        <p class="text-xs text-gray-500">Une fois le template Excel rempli, importez-le ici pour créer automatiquement les évaluations et notes.</p>
        <a href="{{ route('mon-espace.feuille-de-note.import-excel.form', $classe) }}"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Importer un Excel
        </a>
    </div>

    {{-- ── Bloc 3 : Importer photo (OCR) ─────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-card border border-purple-100 p-5 space-y-3">
        <h2 class="font-bold text-purple-800 text-sm uppercase tracking-wide">3 · Importer depuis une photo (OCR)</h2>
        <p class="text-xs text-gray-500">Vous avez rempli la feuille papier au bic ? Prenez-la en photo et laissez l'IA extraire les notes. Vous validerez le résultat avant enregistrement.</p>
        <a href="{{ route('mon-espace.feuille-de-note.import-ocr.form', $classe) }}"
           class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3" stroke-width="2"/></svg>
            Importer depuis une photo
        </a>
    </div>
</div>

@push('scripts')
<script>
function submitFeuilleDeNote(action) {
    const form = document.getElementById('gen-form');
    form.action = action;
    form.method = 'GET';
    form.target = '_blank';
    form.submit();
}
</script>
@endpush
@endsection
