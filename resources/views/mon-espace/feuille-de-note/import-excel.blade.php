@extends('layouts.app')
@section('title', 'Import Excel · ' . $classe->nom)

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8 space-y-5">

    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('mon-espace.classes') }}" class="hover:text-brand-600 font-medium">Mes classes</a>
        <span>/</span>
        <a href="{{ route('mon-espace.feuille-de-note.index', $classe) }}" class="hover:text-brand-600 font-medium">{{ $classe->nom }}</a>
        <span>/</span>
        <span>Import Excel</span>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <h1 class="font-display text-2xl font-extrabold text-gray-900">Importer un Excel rempli</h1>

    <div class="bg-blue-50 border border-blue-100 text-blue-800 text-sm rounded-xl p-4">
        <p class="font-bold mb-1">📌 Pré-requis :</p>
        <ul class="list-disc pl-5 space-y-0.5 text-blue-700">
            <li>Le fichier doit suivre le format du template téléchargé sur la page précédente</li>
            <li>Les colonnes vides (sans <i>titre d'évaluation</i>) seront ignorées</li>
            <li>Les matricules doivent correspondre aux élèves de la classe</li>
            <li>Une <b>nouvelle évaluation</b> est créée par colonne remplie</li>
        </ul>
    </div>

    <form method="POST" action="{{ route('mon-espace.feuille-de-note.import-excel', $classe) }}"
          enctype="multipart/form-data"
          class="bg-white rounded-2xl shadow-card border border-gray-100 p-6 space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Matière concernée *</label>
            <select name="matiere_id" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                @foreach($matieres as $m)
                    <option value="{{ $m->id }}">{{ $m->nom }} ({{ $m->code }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Fichier Excel rempli (.xlsx) *</label>
            <input type="file" name="fichier" accept=".xlsx,.xls" required
                   class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
        </div>

        <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
            <a href="{{ route('mon-espace.feuille-de-note.index', $classe) }}"
               class="px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">Annuler</a>
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-6 py-2.5 rounded-xl transition">
                Importer et enregistrer
            </button>
        </div>
    </form>
</div>
@endsection
