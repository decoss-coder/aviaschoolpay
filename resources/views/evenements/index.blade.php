@extends('layouts.app')
@section('title', 'Calendrier scolaire')
@section('page-title', 'Calendrier scolaire')
@section('page-subtitle', 'Événements, vacances, examens, fêtes')

@section('content')
@php
    $typeIcons = ['rentree' => '🎒', 'vacances' => '🏖', 'examen' => '📝', 'conseil_classe' => '👥', 'reunion_parents' => '🤝', 'fete' => '🎉', 'sortie' => '🚌', 'ferie' => '🇨🇮', 'autre' => '📌'];
    $typeColors = ['rentree' => '#10b981', 'vacances' => '#3b82f6', 'examen' => '#f59e0b', 'conseil_classe' => '#8b5cf6', 'reunion_parents' => '#0ea5e9', 'fete' => '#ec4899', 'sortie' => '#06b6d4', 'ferie' => '#ef4444', 'autre' => '#64748b'];
@endphp

<div class="space-y-6" x-data="{ modal: false }">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-700 rounded-xl flex items-center justify-center shadow-card-gold">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <h2 class="font-display text-2xl font-extrabold text-gray-900">Calendrier scolaire</h2>
                <p class="text-xs text-gray-500">{{ $annee?->libelle ?? 'Aucune année active' }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            @if($annee)
                <a href="{{ route('documents.calendrier.pdf') }}" target="_blank" class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-white border border-gray-200 text-gray-700 hover:border-amber-300">📄 PDF calendrier</a>
            @endif
            <button @click="modal = true" class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-amber-500 to-orange-700 text-white shadow-card-gold flex items-center gap-2">+ Nouvel événement</button>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-extrabold text-gray-900">Événements ({{ $evenements->count() }})</h3>
        </div>
        @if($evenements->isEmpty())
            <div class="px-5 py-16 text-center">
                <p class="text-4xl mb-3">📅</p>
                <p class="font-bold text-gray-800">Aucun événement</p>
                <p class="text-xs text-gray-500 mt-1">Ajoutez rentrée, vacances, examens, fêtes...</p>
            </div>
        @else
        <div class="divide-y divide-gray-100">
            @foreach($evenements as $e)
                @php $col = $typeColors[$e->couleur ?? $e->type] ?? '#64748b'; @endphp
                <div class="px-5 py-4 flex items-start gap-4 hover:bg-gray-50 border-l-4" style="border-left-color: {{ $col }};">
                    <span class="text-2xl">{{ $typeIcons[$e->type] ?? '📌' }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-bold text-gray-900">{{ $e->titre }}</p>
                            <span class="inline-flex px-2 py-0.5 rounded-lg text-xs font-bold" style="background: {{ $col }}20; color: {{ $col }}">{{ str_replace('_', ' ', $e->type) }}</span>
                            @if($e->publie)<span class="inline-flex px-2 py-0.5 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-700">✓ Publié</span>@else<span class="inline-flex px-2 py-0.5 rounded-lg text-xs font-bold bg-gray-100 text-gray-500">Brouillon</span>@endif
                        </div>
                        <p class="text-xs text-gray-600 mt-1">
                            📅 {{ $e->date_debut->locale('fr')->isoFormat('ddd D MMM YYYY') }}
                            @if($e->date_fin && $e->date_fin->ne($e->date_debut)) → {{ $e->date_fin->locale('fr')->isoFormat('ddd D MMM YYYY') }}@endif
                            @if($e->lieu) · 📍 {{ $e->lieu }}@endif
                        </p>
                        @if($e->description)<p class="text-xs text-gray-700 mt-1">{{ $e->description }}</p>@endif
                    </div>
                    <div class="flex flex-col gap-1">
                        <form method="POST" action="{{ route('evenements.publier', $e->id) }}">@csrf
                            <button class="text-xs font-bold text-{{ $e->publie ? 'gray' : 'emerald' }}-600">{{ $e->publie ? 'Masquer' : '✓ Publier' }}</button>
                        </form>
                        <form method="POST" action="{{ route('evenements.destroy', $e->id) }}" onsubmit="return confirm('Supprimer ?')">
                            @csrf @method('DELETE')
                            <button class="text-xs font-bold text-red-600">🗑</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    </div>

    <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="modal=false">
        <form method="POST" action="{{ route('evenements.store') }}" class="bg-white rounded-2xl shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
            @csrf
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-extrabold text-gray-900">Nouvel événement</h3>
                <button type="button" @click="modal=false" class="text-gray-400 text-2xl">&times;</button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Titre *</label>
                    <input name="titre" required class="w-full rounded-xl border-gray-200 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Type *</label>
                    <select name="type" required class="w-full rounded-xl border-gray-200 text-sm">
                        @foreach(['rentree' => '🎒 Rentrée', 'vacances' => '🏖 Vacances', 'examen' => '📝 Examen', 'conseil_classe' => '👥 Conseil de classe', 'reunion_parents' => '🤝 Réunion parents', 'fete' => '🎉 Fête', 'sortie' => '🚌 Sortie', 'ferie' => '🇨🇮 Jour férié', 'autre' => '📌 Autre'] as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Date début *</label>
                        <input type="date" name="date_debut" required class="w-full rounded-xl border-gray-200 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Date fin (facultatif)</label>
                        <input type="date" name="date_fin" class="w-full rounded-xl border-gray-200 text-sm" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Lieu (facultatif)</label>
                    <input name="lieu" class="w-full rounded-xl border-gray-200 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Description</label>
                    <textarea name="description" rows="3" class="w-full rounded-xl border-gray-200 text-sm"></textarea>
                </div>
                <label class="flex items-center gap-2 p-3 bg-emerald-50 rounded-xl">
                    <input type="checkbox" name="publie" value="1" checked class="rounded" />
                    <span class="text-sm font-semibold text-emerald-900">✓ Publier immédiatement (visible dans le calendrier annuel)</span>
                </label>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-2 justify-end">
                <button type="button" @click="modal=false" class="px-4 py-2 text-sm font-bold text-gray-600">Annuler</button>
                <button type="submit" class="px-6 py-2 bg-amber-600 text-white text-sm font-bold rounded-xl">Ajouter</button>
            </div>
        </form>
    </div>
</div>

@push('styles')<style>[x-cloak]{display:none!important}</style>@endpush
@endsection
