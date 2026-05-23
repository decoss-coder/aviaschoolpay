@extends('layouts.app')
@section('title', 'Listes de fournitures')
@section('page-title', 'Listes de fournitures')

@section('content')
<div class="space-y-6" x-data="{ modal: false }">
    @if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>@endif
    @if(session('error') || $errors->any())<div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-teal-500 to-emerald-700 rounded-xl flex items-center justify-center shadow-card-brand">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div>
                <h2 class="font-display text-2xl font-extrabold text-gray-900">Listes de fournitures</h2>
                <p class="text-xs text-gray-500">{{ $annee?->libelle ?? 'Aucune année active' }}</p>
            </div>
        </div>
        <button @click="modal = true" class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-teal-500 to-emerald-700 text-white shadow-card-brand flex items-center gap-2">+ Nouvelle liste</button>
    </div>

    @if($listes->isEmpty() && $classes->isNotEmpty())
        <div class="rounded-2xl border-2 border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
            💡 Aucune liste créée. Vous pouvez en créer une par classe, et les enseignants peuvent aussi en gérer via l'app mobile.
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        @foreach($listes as $l)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
                <div class="h-1 bg-gradient-to-r from-teal-400 to-emerald-700"></div>
                <div class="p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase text-gray-400">{{ $l->classe?->nom }}</p>
                            <h3 class="font-display text-lg font-extrabold text-gray-900 mt-1">{{ $l->titre }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $l->items_count }} fourniture(s) · créée par {{ $l->creePar?->name ?? '—' }}</p>
                        </div>
                        @if($l->publie)
                            <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-700">✓ Publiée</span>
                        @else
                            <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-700">Brouillon</span>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-gray-100">
                        <a href="{{ route('fournitures.show', $l->id) }}" class="px-3 py-1.5 bg-teal-600 text-white text-xs font-bold rounded-xl">Éditer</a>
                        <a href="{{ route('fournitures.pdf', $l->id) }}" target="_blank" class="px-3 py-1.5 bg-blue-600 text-white text-xs font-bold rounded-xl">📄 PDF</a>
                        <form method="POST" action="{{ route('fournitures.publier', $l->id) }}" class="inline">@csrf
                            <button class="px-3 py-1.5 bg-{{ $l->publie ? 'gray' : 'emerald' }}-600 text-white text-xs font-bold rounded-xl">{{ $l->publie ? 'Masquer' : '✓ Publier' }}</button>
                        </form>
                        <form method="POST" action="{{ route('fournitures.destroy', $l->id) }}" class="inline" onsubmit="return confirm('Supprimer la liste ?')">@csrf @method('DELETE')
                            <button class="px-3 py-1.5 bg-red-100 text-red-700 text-xs font-bold rounded-xl">🗑</button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="modal=false">
        <form method="POST" action="{{ route('fournitures.store') }}" class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
            @csrf
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-extrabold text-gray-900">Nouvelle liste de fournitures</h3>
                <button type="button" @click="modal=false" class="text-gray-400 text-2xl">&times;</button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Classe *</label>
                    <select name="classe_id" required class="w-full rounded-xl border-gray-200 text-sm">
                        <option value="">— Sélectionner —</option>
                        @foreach($classes as $c)
                            @php $deja = $listes->where('classe_id', $c->id)->first(); @endphp
                            <option value="{{ $c->id }}" @if($deja) disabled @endif>{{ $c->nom }}@if($deja) (déjà créée)@endif</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Titre (facultatif)</label>
                    <input name="titre" placeholder="Liste de fournitures" class="w-full rounded-xl border-gray-200 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Notes (facultatif)</label>
                    <textarea name="notes" rows="3" placeholder="Remarques générales..." class="w-full rounded-xl border-gray-200 text-sm"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-2 justify-end">
                <button type="button" @click="modal=false" class="px-4 py-2 text-sm font-bold text-gray-600">Annuler</button>
                <button type="submit" class="px-6 py-2 bg-teal-600 text-white text-sm font-bold rounded-xl">Créer</button>
            </div>
        </form>
    </div>
</div>

@push('styles')<style>[x-cloak]{display:none!important}</style>@endpush
@endsection
