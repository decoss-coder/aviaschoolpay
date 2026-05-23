@extends('layouts.app')
@section('title', 'Choisir une école')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gold-100 text-gold-600 mb-4">
            <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        </div>
        <h1 class="font-display text-3xl font-extrabold text-gray-900">Choisissez votre école</h1>
        <p class="text-gray-500 mt-2 text-sm">Vous enseignez dans plusieurs établissements — sélectionnez celui où vous voulez travailler maintenant.</p>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium mb-5">{{ session('success') }}</div>
    @endif

    <div class="grid gap-3 sm:grid-cols-2">
        @foreach($enseignants as $ens)
        @php $etab = $ens->etablissement; $active = $activeId == $etab->id; @endphp
        <form method="POST" action="{{ route('ecole.switcher.select') }}">
            @csrf
            <input type="hidden" name="etablissement_id" value="{{ $etab->id }}">
            <button type="submit"
                    class="w-full text-left bg-white rounded-2xl shadow-card border-2 p-5 transition
                           {{ $active ? 'border-gold-500 ring-2 ring-gold-200' : 'border-gray-100 hover:border-gold-300 hover:shadow-card-brand' }}">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-gray-900 text-base truncate">{{ $etab->nom }}</h3>
                        @if($etab->adresse)
                        <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $etab->adresse }}</p>
                        @endif
                    </div>
                    @if($active)
                    <span class="text-xs font-bold bg-gold-100 text-gold-700 px-2 py-0.5 rounded-full">Actuelle</span>
                    @endif
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <span class="bg-gray-100 px-2 py-0.5 rounded-full font-semibold">
                        {{ ucfirst($ens->statut) }}
                    </span>
                    @if($ens->specialite)
                    <span class="bg-brand-50 text-brand-700 px-2 py-0.5 rounded-full font-semibold">
                        {{ $ens->specialite }}
                    </span>
                    @endif
                </div>
            </button>
        </form>
        @endforeach
    </div>

    @if($enseignants->isEmpty())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm text-center">
        Aucune école active pour votre compte. Contactez votre administrateur.
    </div>
    @endif
</div>
@endsection
