@extends('layouts.app')
@section('title', 'Import OCR · ' . $classe->nom)

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8 space-y-5">

    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('mon-espace.classes') }}" class="hover:text-brand-600 font-medium">Mes classes</a>
        <span>/</span>
        <a href="{{ route('mon-espace.feuille-de-note.index', $classe) }}" class="hover:text-brand-600 font-medium">{{ $classe->nom }}</a>
        <span>/</span>
        <span>Import photo (OCR)</span>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <h1 class="font-display text-2xl font-extrabold text-gray-900">Importer depuis une photo</h1>

    <div class="bg-purple-50 border border-purple-100 text-purple-800 text-sm rounded-xl p-4">
        <p class="font-bold mb-1">📷 Conseils pour une bonne extraction :</p>
        <ul class="list-disc pl-5 space-y-0.5 text-purple-700">
            <li>Photographiez la feuille à plat, sans pliures ni reflets</li>
            <li>Bonne lumière, le tableau doit occuper tout le cadre</li>
            <li>Notes écrites lisiblement (chiffres bien formés)</li>
            <li>Les <b>matricules imprimés</b> servent de référence pour matcher les élèves</li>
            <li>Vous pourrez <b>vérifier et corriger</b> avant enregistrement</li>
        </ul>
    </div>

    <form method="POST" action="{{ route('mon-espace.feuille-de-note.import-ocr.preview', $classe) }}"
          enctype="multipart/form-data"
          class="bg-white rounded-2xl shadow-card border border-gray-100 p-6 space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Matière concernée *</label>
            <select name="matiere_id" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 outline-none">
                @foreach($matieres as $m)
                    <option value="{{ $m->id }}">{{ $m->nom }} ({{ $m->code }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Photo ou PDF de la feuille (.jpg/.png/.pdf, max 10 MB) *</label>
            <input type="file" name="image" accept="image/jpeg,image/png,application/pdf" required
                   class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
        </div>

        <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
            <a href="{{ route('mon-espace.feuille-de-note.index', $classe) }}"
               class="px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">Annuler</a>
            <button type="submit"
                    class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-bold px-6 py-2.5 rounded-xl transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Lancer l'extraction IA
            </button>
        </div>
        <p class="text-xs text-gray-400 italic">L'extraction peut prendre 15-45 secondes selon la qualité de l'image.</p>
    </form>
</div>
@endsection
