@extends('layouts.app')
@section('title', 'Devoirs · ' . $classe->nom)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8 space-y-5" x-data="{ showForm: false }">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('mon-espace.classes') }}" class="hover:text-brand-600 font-medium">Mes classes</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">{{ $classe->nom }}</span>
        <span>/</span>
        <span>Devoirs & Exercices</span>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between">
        <h1 class="font-display text-2xl font-extrabold text-gray-900">Devoirs — {{ $classe->nom }}</h1>
        <button @click="showForm = !showForm"
                class="bg-gold-500 hover:bg-gold-600 text-white text-sm font-bold px-4 py-2 rounded-xl transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nouveau devoir
        </button>
    </div>

    {{-- Formulaire --}}
    <div x-show="showForm" x-cloak x-transition
         class="bg-white rounded-2xl shadow-card border border-gold-100 p-5">
        <h2 class="font-bold text-gray-800 mb-4 text-sm uppercase tracking-wide">Nouveau devoir / exercice</h2>
        <form method="POST" action="{{ route('mon-espace.devoirs.store', $classe) }}"
              enctype="multipart/form-data"
              class="space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Titre *</label>
                    <input type="text" name="titre" placeholder="Ex: Exercice n°5 sur les fractions" required
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-gold-300 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Type *</label>
                    <select name="type" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-gold-300 outline-none">
                        <option value="devoir">Devoir</option>
                        <option value="exercice">Exercice</option>
                        <option value="tp">TP</option>
                        <option value="projet">Projet</option>
                        <option value="lecture">Lecture</option>
                        <option value="interrogation">Interrogation</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Matière *</label>
                    <select name="matiere_id" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-gold-300 outline-none">
                        @foreach($matieres as $m)
                        <option value="{{ $m->id }}">{{ $m->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Date publication *</label>
                    <input type="date" name="date_publication" value="{{ now()->format('Y-m-d') }}" required
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-gold-300 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Date limite</label>
                    <input type="date" name="date_limite"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-gold-300 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Description / Consignes</label>
                <textarea name="description" rows="3" placeholder="Détaillez le travail attendu, les chapitres concernés…"
                          class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-gold-300 outline-none resize-none"></textarea>
            </div>

            {{-- Fichiers sujet + corrigé --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 border-t border-gray-100 pt-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">📄 Sujet à distribuer (PDF/Word/image)</label>
                    <input type="file" name="fichier_sujet" accept=".pdf,.doc,.docx,.jpg,.png"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">✅ Corrigé (optionnel)</label>
                    <input type="file" name="fichier_corrige" accept=".pdf,.doc,.docx,.jpg,.png"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs">
                </div>
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="publie" value="1" checked
                           class="w-4 h-4 text-gold-500 rounded border-gray-300 focus:ring-gold-300">
                    <span class="text-sm font-semibold text-gray-700">Publier immédiatement</span>
                </label>
                <div class="flex gap-3">
                    <button type="button" @click="showForm = false"
                            class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">Annuler</button>
                    <button type="submit"
                            class="bg-gold-500 hover:bg-gold-600 text-white text-sm font-bold px-5 py-2 rounded-xl transition">
                        Publier le devoir
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Liste --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Devoirs publiés ({{ $devoirs->count() }})</h2>
        </div>
        @if($devoirs->isEmpty())
            <div class="px-5 py-10 text-center text-sm text-gray-400">Aucun devoir pour cette classe.</div>
        @else
            <div class="divide-y divide-gray-50">
                @foreach($devoirs as $d)
                @php
                    $typeColors = ['devoir'=>'bg-blue-100 text-blue-700','exercice'=>'bg-brand-100 text-brand-700','tp'=>'bg-purple-100 text-purple-700','projet'=>'bg-orange-100 text-orange-700','lecture'=>'bg-gray-100 text-gray-600','interrogation'=>'bg-red-100 text-red-700'];
                @endphp
                <div class="flex items-start gap-4 px-5 py-4 hover:bg-gray-50/60 transition group">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $typeColors[$d->type] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($d->type) }}
                            </span>
                            <span class="text-xs font-bold text-gray-500">{{ $d->matiere->code ?? '' }}</span>
                            @if(!$d->publie)
                            <span class="text-xs font-bold bg-gray-200 text-gray-500 px-2 py-0.5 rounded-full">Brouillon</span>
                            @endif
                        </div>
                        <p class="font-semibold text-sm text-gray-800">{{ $d->titre }}</p>
                        @if($d->description)
                        <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ $d->description }}</p>
                        @endif
                        <p class="text-[11px] text-gray-400 mt-1">
                            Publié le {{ $d->date_publication->format('d/m/Y') }}
                            @if($d->date_limite)
                                · À rendre avant le <strong class="text-orange-600">{{ $d->date_limite->format('d/m/Y') }}</strong>
                            @endif
                        </p>
                    </div>
                    <form method="POST" action="{{ route('mon-espace.devoirs.destroy', $d) }}"
                          onsubmit="return confirm('Supprimer ce devoir ?')"
                          class="opacity-0 group-hover:opacity-100 transition flex-shrink-0">
                        @csrf @method('DELETE')
                        <button class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection
