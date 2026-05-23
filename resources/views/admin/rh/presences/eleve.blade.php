@extends('layouts.app')
@section('title', 'Présences · ' . $eleve->nom)

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-4">

    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('admin.rh.presences.dashboard') }}" class="hover:text-brand-600">Présences</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">{{ $eleve->nom }} {{ $eleve->prenom }}</span>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif

    {{-- Fiche élève --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 px-5 py-4 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-extrabold text-lg">
            {{ strtoupper(substr($eleve->prenom, 0, 1)) }}{{ strtoupper(substr($eleve->nom, 0, 1)) }}
        </div>
        <div class="flex-1 min-w-0">
            <h1 class="font-display text-xl font-extrabold text-gray-900">{{ strtoupper($eleve->nom) }} {{ $eleve->prenom }}</h1>
            <p class="text-xs text-gray-500 mt-0.5">
                <span class="font-mono font-bold">{{ $eleve->matricule_desps ?: $eleve->matricule_interne }}</span>
                · {{ $eleve->classe?->nom }}
                · {{ $eleve->sexe === 'M' ? 'Garçon' : 'Fille' }}
                @if($eleve->date_naissance) · {{ $eleve->date_naissance->age }} ans @endif
            </p>
        </div>
    </div>

    {{-- Cumuls --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="bg-white rounded-2xl shadow-card border border-green-100 p-4 text-center">
            <p class="text-[10px] font-bold text-green-600 uppercase">Présences</p>
            <p class="text-2xl font-extrabold text-green-700 mt-1">{{ $cumul['present'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-red-100 p-4 text-center">
            <p class="text-[10px] font-bold text-red-600 uppercase">Absences totales</p>
            <p class="text-2xl font-extrabold text-red-700 mt-1">{{ $cumul['absent'] }}</p>
            <p class="text-[10px] text-gray-400 mt-1">{{ $cumul['absences_non_justifiees'] }} non just.</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-amber-100 p-4 text-center">
            <p class="text-[10px] font-bold text-amber-600 uppercase">Retards</p>
            <p class="text-2xl font-extrabold text-amber-700 mt-1">{{ $cumul['retard'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-blue-100 p-4 text-center">
            <p class="text-[10px] font-bold text-blue-600 uppercase">Excusés</p>
            <p class="text-2xl font-extrabold text-blue-700 mt-1">{{ $cumul['excuse'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-purple-100 p-4 text-center">
            <p class="text-[10px] font-bold text-purple-600 uppercase">Dispensés</p>
            <p class="text-2xl font-extrabold text-purple-700 mt-1">{{ $cumul['dispense'] }}</p>
        </div>
    </div>

    {{-- Liste des présences --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Historique complet</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase">Créneau</th>
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase">Statut</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Matière</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Enseignant</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Motif / Justification</th>
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase">Traité par</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($presences as $p)
                    @php
                        $color = [
                            'present'  => 'bg-green-100 text-green-700',
                            'absent'   => 'bg-red-100 text-red-700',
                            'retard'   => 'bg-amber-100 text-amber-700',
                            'excuse'   => 'bg-blue-100 text-blue-700',
                            'dispense' => 'bg-purple-100 text-purple-700',
                        ][$p->statut] ?? 'bg-gray-100 text-gray-500';
                    @endphp
                    <tr>
                        <td class="px-3 py-2 text-xs whitespace-nowrap">{{ $p->date?->format('d/m/Y') }}</td>
                        <td class="px-3 py-2 text-center text-xs">
                            @if($p->creneau)
                                {{ substr($p->creneau->heure_debut,0,5) }}–{{ substr($p->creneau->heure_fin,0,5) }}
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $color }}">{{ strtoupper($p->statut) }}</span>
                            @if($p->justifie)
                                <span class="text-[9px] font-bold text-green-700">just.</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs">{{ $p->matiere?->code ?? '—' }}</td>
                        <td class="px-3 py-2 text-xs">{{ $p->enseignant?->prenom }} {{ $p->enseignant?->nom }}</td>
                        <td class="px-3 py-2 text-xs">
                            @if($p->motif)<b>{{ $p->motif }}</b>@endif
                            @if($p->justification) <span class="text-gray-500">— {{ $p->justification }}</span> @endif
                        </td>
                        <td class="px-3 py-2 text-center text-xs">
                            @if($p->traite_at)
                                <p class="font-bold">{{ $p->traitePar?->prenom }}</p>
                                <p class="text-[9px] text-gray-400">{{ $p->traite_at->format('d/m H:i') }}</p>
                            @else
                                <span class="text-orange-500">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-3 py-8 text-center text-sm text-gray-400">Aucune entrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-gray-100">{{ $presences->links() }}</div>
    </div>
</div>
@endsection
