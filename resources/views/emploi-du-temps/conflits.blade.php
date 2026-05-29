@extends('layouts.app')

@section('title', 'Conflits emploi du temps')
@section('page-title', 'Conflits emploi du temps')
@section('page-subtitle', 'Détection automatique des collisions')

@section('content')
@include('partials.rh-admin-nav')

<div class="space-y-6">
    <form method="GET" class="flex flex-wrap gap-2">
        <select name="annee_scolaire_id" class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl">
            <option value="">Toutes les années</option>
            @foreach($annees as $annee)
                <option value="{{ $annee->id }}" @selected(request('annee_scolaire_id') == $annee->id)>
                    {{ $annee->libelle }}
                </option>
            @endforeach
        </select>

        <button class="px-4 py-2.5 bg-white border border-brand-100 rounded-xl text-sm font-bold shadow-sm">
            Analyser
        </button>
    </form>

    @forelse($conflits as $conflit)
        <div class="bg-white border border-red-100 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs uppercase tracking-wider font-bold text-red-600">Conflit {{ ucfirst($conflit['type']) }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $conflit['label'] ?? $conflit['key'] }}</p>
                </div>
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold bg-red-100 text-red-700">
                    {{ count($conflit['items']) }} collisions
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-red-100">
                        <tr>
                            <th class="py-2 text-left text-xs font-extrabold uppercase text-red-700">Jour</th>
                            <th class="py-2 text-left text-xs font-extrabold uppercase text-red-700">Créneau</th>
                            <th class="py-2 text-left text-xs font-extrabold uppercase text-red-700">Classe</th>
                            <th class="py-2 text-left text-xs font-extrabold uppercase text-red-700">Matière</th>
                            <th class="py-2 text-left text-xs font-extrabold uppercase text-red-700">Enseignant</th>
                            <th class="py-2 text-left text-xs font-extrabold uppercase text-red-700">Salle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-red-50">
                        @foreach($conflit['items'] as $item)
                            <tr>
                                <td class="py-3 text-sm text-gray-700">{{ ucfirst($item->jour) }}</td>
                                <td class="py-3 text-sm text-gray-700">{{ $item->creneau->libelle ?? '—' }}</td>
                                <td class="py-3 text-sm font-bold text-gray-900">{{ $item->classe->nom ?? '—' }}</td>
                                <td class="py-3 text-sm text-gray-700">{{ $item->matiere->nom ?? '—' }}</td>
                                <td class="py-3 text-sm text-gray-700">{{ $item->enseignant->nom_complet ?? '—' }}</td>
                                <td class="py-3 text-sm text-gray-700">{{ $item->salle->nom ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="bg-white border border-emerald-100 rounded-2xl p-8 shadow-sm text-center">
            <p class="text-lg font-extrabold text-emerald-700">Aucun conflit détecté</p>
            <p class="text-sm text-gray-500 mt-2">La structure actuelle est cohérente.</p>
        </div>
    @endforelse
</div>
@endsection
