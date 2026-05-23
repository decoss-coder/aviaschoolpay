@extends('layouts.app')
@section('title', 'Évaluations · ' . $classe->nom)

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8 space-y-5" x-data="{ showForm: false }">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('mon-espace.classes') }}" class="hover:text-brand-600 font-medium">Mes classes</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">{{ $classe->nom }}</span>
        <span>/</span>
        <span>Évaluations</span>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between">
        <h1 class="font-display text-2xl font-extrabold text-gray-900">Évaluations — {{ $classe->nom }}</h1>
        <button @click="showForm = !showForm"
                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-4 py-2 rounded-xl transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nouvelle évaluation
        </button>
    </div>

    {{-- Formulaire nouvelle éval --}}
    <div x-show="showForm" x-cloak x-transition
         class="bg-white rounded-2xl shadow-card border border-blue-100 p-5">
        <h2 class="font-bold text-gray-800 mb-4 text-sm uppercase tracking-wide">Nouvelle évaluation</h2>
        <form method="POST" action="{{ route('mon-espace.evaluations.store', $classe) }}"
              enctype="multipart/form-data"
              class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @csrf
            <div class="sm:col-span-3">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Titre *</label>
                <input type="text" name="titre" placeholder="Ex: Devoir n°1 – Équations" required
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Matière *</label>
                <select name="matiere_id" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                    <option value="">— Choisir —</option>
                    @foreach($matieres as $m)
                    <option value="{{ $m->id }}">{{ $m->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Type *</label>
                <select name="type_evaluation_id" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                    <option value="">— Choisir —</option>
                    @foreach($typesEval as $t)
                    <option value="{{ $t->id }}">{{ $t->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Trimestre *</label>
                <select name="trimestre_id" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                    @foreach($trimestres as $t)
                    <option value="{{ $t->id }}" {{ $t->id == $trimId ? 'selected' : '' }}>{{ $t->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Date *</label>
                <input type="date" name="date_evaluation" required value="{{ now()->format('Y-m-d') }}"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Note sur *</label>
                <input type="number" name="note_sur" value="20" min="1" max="100" required
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Coefficient *</label>
                <input type="number" name="coefficient" value="1" min="0.5" max="10" step="0.5" required
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
            </div>
            <div class="sm:col-span-3">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Description</label>
                <textarea name="description" rows="2" placeholder="Chapitres évalués, consignes…"
                          class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none resize-none"></textarea>
            </div>

            {{-- Fichiers sujet + corrigé --}}
            <div class="sm:col-span-3 grid grid-cols-1 sm:grid-cols-2 gap-3 border-t border-gray-100 pt-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">📄 Sujet (PDF/Word/image — partagé aux élèves)</label>
                    <input type="file" name="fichier_sujet" accept=".pdf,.doc,.docx,.jpg,.png"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">✅ Corrigé (optionnel)</label>
                    <input type="file" name="fichier_corrige" accept=".pdf,.doc,.docx,.jpg,.png"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs">
                </div>
            </div>

            <div class="sm:col-span-3 flex justify-end gap-3">
                <button type="button" @click="showForm = false"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">Annuler</button>
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-5 py-2 rounded-xl transition">
                    Créer l'évaluation
                </button>
            </div>
        </form>
    </div>

    {{-- Filtre trimestre --}}
    <div class="flex gap-2 flex-wrap">
        <a href="{{ route('mon-espace.evaluations', $classe) }}"
           class="px-3 py-1.5 rounded-full text-xs font-bold transition {{ !$trimId ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
            Tous
        </a>
        @foreach($trimestres as $t)
        <a href="{{ route('mon-espace.evaluations', ['classe' => $classe, 'trimestre' => $t->id]) }}"
           class="px-3 py-1.5 rounded-full text-xs font-bold transition {{ $trimId == $t->id ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
            {{ $t->libelle }}
        </a>
        @endforeach
    </div>

    {{-- Liste --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Évaluations ({{ $evaluations->count() }})</h2>
        </div>
        @if($evaluations->isEmpty())
            <div class="px-5 py-10 text-center text-sm text-gray-400">Aucune évaluation pour ce filtre.</div>
        @else
            <div class="divide-y divide-gray-50">
                @foreach($evaluations as $ev)
                @php
                    $statColors = ['brouillon'=>'bg-gray-100 text-gray-600','en_saisie'=>'bg-blue-100 text-blue-700','cloturee'=>'bg-orange-100 text-orange-700','validee'=>'bg-green-100 text-green-700'];
                    $statLabels = ['brouillon'=>'Brouillon','en_saisie'=>'En saisie','cloturee'=>'Clôturée','validee'=>'Validée'];
                @endphp
                <div class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50/60 transition">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-gray-800 truncate">{{ $ev->titre }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ $ev->matiere->code ?? '' }} ·
                            {{ $ev->typeEvaluation->nom ?? '' }} ·
                            coef. {{ $ev->coefficient }} ·
                            {{ \Carbon\Carbon::parse($ev->date_evaluation)->format('d/m/Y') }}
                        </p>
                    </div>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full flex-shrink-0 {{ $statColors[$ev->statut] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $statLabels[$ev->statut] ?? $ev->statut }}
                    </span>
                    <a href="{{ route('mon-espace.notes', $ev) }}"
                       class="text-xs bg-blue-600 hover:bg-blue-700 text-white font-bold px-3 py-1.5 rounded-lg transition flex-shrink-0">
                        Saisir les notes
                    </a>
                </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection
