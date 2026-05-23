@extends('layouts.app')
@section('title', 'OCR Cahier d\'appel · ' . $classe->nom)

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8 space-y-5">

    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('mon-espace.classes') }}" class="hover:text-brand-600">Mes classes</a>
        <span>/</span>
        <a href="{{ route('mon-espace.cahier-appel.index', $classe) }}" class="hover:text-brand-600">{{ $classe->nom }}</a>
        <span>/</span>
        <span>Import photo (OCR)</span>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <h1 class="font-display text-2xl font-extrabold text-gray-900">Importer le cahier d'appel depuis une photo</h1>

    <div class="bg-purple-50 border border-purple-100 text-purple-800 text-sm rounded-xl p-4">
        <p class="font-bold mb-1">📷 Conseils :</p>
        <ul class="list-disc pl-5 space-y-0.5 text-purple-700">
            <li>Photographiez la feuille à plat sous bonne lumière</li>
            <li>Les matricules imprimés servent à matcher les élèves</li>
            <li>Utilisez des lettres bien formées dans les cases (P, A, R, E, D)</li>
            <li>Vous validerez le résultat avant enregistrement</li>
        </ul>
    </div>

    <form method="POST" action="{{ route('mon-espace.cahier-appel.import-ocr.preview', $classe) }}"
          enctype="multipart/form-data"
          class="bg-white rounded-2xl shadow-card border border-gray-100 p-6 space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Semaine du *</label>
            <input type="date" name="semaine" value="{{ now()->startOfWeek()->toDateString() }}" required
                   class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 outline-none">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Photo ou PDF (.jpg/.png/.pdf, max 10 MB) *</label>
            <input type="file" name="image" accept="image/jpeg,image/png,application/pdf" required
                   class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
        </div>

        <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
            <a href="{{ route('mon-espace.cahier-appel.index', $classe) }}"
               class="px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100">Annuler</a>
            <button type="submit"
                    class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-bold px-6 py-2.5 rounded-xl">
                Lancer l'extraction IA
            </button>
        </div>
    </form>
</div>
@endsection
