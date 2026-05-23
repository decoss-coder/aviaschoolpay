@extends('layouts.app')
@section('title', 'Mes évaluations')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-4">

    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('mon-espace-eleve.dashboard') }}" class="hover:text-brand-600">Tableau de bord</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">Évaluations</span>
    </div>

    <h1 class="font-display text-2xl font-extrabold text-gray-900">Évaluations à venir & récentes</h1>

    @if($evaluations->isEmpty())
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-12 text-center">
            <p class="text-gray-400">Aucune évaluation programmée.</p>
        </div>
    @else
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Évaluation</th>
                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Matière</th>
                    <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase">Type</th>
                    <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase">Période</th>
                    <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 uppercase">Documents</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($evaluations as $e)
                @php
                    $isFutur = $e->date_evaluation > today();
                    $statut = $isFutur ? 'À venir' : ($e->notes_publiees ? 'Notes publiées' : 'En cours');
                @endphp
                <tr class="hover:bg-gray-50/50">
                    <td class="px-3 py-2 text-xs whitespace-nowrap">
                        {{ $e->date_evaluation?->format('d/m/Y') }}
                        @if($isFutur) <span class="block text-[10px] text-green-600 font-bold">à venir</span> @endif
                    </td>
                    <td class="px-3 py-2">
                        <p class="font-semibold text-sm text-gray-800">{{ $e->titre }}</p>
                        @if($e->description) <p class="text-xs text-gray-400 line-clamp-1">{{ $e->description }}</p> @endif
                    </td>
                    <td class="px-3 py-2 text-xs">{{ $e->matiere?->nom }}</td>
                    <td class="px-3 py-2 text-center text-xs">{{ $e->typeEvaluation?->nom }}</td>
                    <td class="px-3 py-2 text-center text-xs">{{ $e->trimestre?->libelle }}</td>
                    <td class="px-3 py-2 text-right whitespace-nowrap">
                        @if($e->fichier_sujet_path)
                        <a href="{{ route('mon-espace-eleve.evaluation.sujet', $e) }}"
                           class="text-xs font-bold text-blue-600 hover:underline mr-2">📄 Sujet</a>
                        @endif
                        @if($e->fichier_corrige_path && $e->notes_publiees)
                        <a href="{{ route('mon-espace-eleve.evaluation.corrige', $e) }}"
                           class="text-xs font-bold text-green-600 hover:underline">✅ Corrigé</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div>{{ $evaluations->links() }}</div>
    @endif
</div>
@endsection
