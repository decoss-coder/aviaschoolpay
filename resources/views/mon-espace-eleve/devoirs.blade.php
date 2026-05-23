@extends('layouts.app')
@section('title', 'Mes devoirs')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 space-y-4">

    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('mon-espace-eleve.dashboard') }}" class="hover:text-brand-600">Tableau de bord</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">Devoirs & exercices</span>
    </div>

    <h1 class="font-display text-2xl font-extrabold text-gray-900">Devoirs & exercices</h1>

    @if($devoirs->isEmpty())
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-12 text-center">
            <p class="text-gray-400">Aucun devoir publié.</p>
        </div>
    @else
    <div class="space-y-3">
        @foreach($devoirs as $d)
        @php
            $typeColors = ['devoir'=>'bg-blue-100 text-blue-700','exercice'=>'bg-brand-100 text-brand-700','tp'=>'bg-purple-100 text-purple-700','projet'=>'bg-orange-100 text-orange-700','lecture'=>'bg-gray-100 text-gray-600','interrogation'=>'bg-red-100 text-red-700'];
            $retard = $d->date_limite && $d->date_limite->isPast();
        @endphp
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-5 hover:shadow-lg transition">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $typeColors[$d->type] ?? 'bg-gray-100' }}">
                            {{ ucfirst($d->type) }}
                        </span>
                        <span class="text-xs font-bold text-gray-500">{{ $d->matiere?->code }}</span>
                        @if($d->enseignant)
                        <span class="text-xs text-gray-400">· {{ $d->enseignant->prenom }} {{ $d->enseignant->nom }}</span>
                        @endif
                    </div>
                    <h3 class="font-bold text-gray-900">{{ $d->titre }}</h3>
                    @if($d->description)
                    <p class="text-sm text-gray-600 mt-1">{{ $d->description }}</p>
                    @endif
                    <p class="text-xs text-gray-400 mt-2">
                        Publié le {{ $d->date_publication->format('d/m/Y') }}
                        @if($d->date_limite)
                            · À rendre avant le
                            <strong class="{{ $retard ? 'text-red-600' : 'text-orange-600' }}">{{ $d->date_limite->format('d/m/Y') }}</strong>
                            @if($retard) <span class="text-red-600 font-bold">(en retard)</span> @endif
                        @endif
                    </p>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-100 flex flex-wrap gap-2">
                @if($d->fichier_path)
                <a href="{{ route('mon-espace-eleve.devoirs.sujet', $d) }}"
                   class="bg-gold-100 hover:bg-gold-200 text-gold-700 text-sm font-bold px-4 py-2 rounded-lg flex items-center gap-2">
                    📄 Télécharger le sujet
                </a>
                @endif
                @if($d->fichier_corrige_path)
                <a href="{{ route('mon-espace-eleve.devoirs.corrige', $d) }}"
                   class="bg-green-100 hover:bg-green-200 text-green-700 text-sm font-bold px-4 py-2 rounded-lg flex items-center gap-2">
                    ✅ Corrigé
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <div>{{ $devoirs->links() }}</div>
    @endif
</div>
@endsection
