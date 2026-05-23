@extends('layouts.app')
@section('title', 'Présences — ' . $eleve->prenom . ' ' . $eleve->nom)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8 space-y-6">

    {{-- Fil d'Ariane --}}
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('mon-espace-parent.dashboard') }}" class="hover:text-emerald-600 font-medium">Espace parent</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="font-semibold text-gray-900">{{ $eleve->prenom }} {{ strtoupper($eleve->nom) }} — Présences</span>
    </div>

    {{-- Compteurs --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-card border border-gray-100 text-center">
            <p class="text-3xl font-extrabold text-red-600">{{ $stats['absences'] }}</p>
            <p class="text-xs text-gray-500 mt-1 font-medium">Absence{{ $stats['absences'] > 1 ? 's' : '' }}</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-card border border-gray-100 text-center">
            <p class="text-3xl font-extrabold text-amber-600">{{ $stats['retards'] }}</p>
            <p class="text-xs text-gray-500 mt-1 font-medium">Retard{{ $stats['retards'] > 1 ? 's' : '' }}</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-card border border-gray-100 text-center">
            <p class="text-3xl font-extrabold text-emerald-600">{{ $stats['justifies'] }}</p>
            <p class="text-xs text-gray-500 mt-1 font-medium">Justifié{{ $stats['justifies'] > 1 ? 's' : '' }}</p>
        </div>
    </div>

    {{-- Liste des absences --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Historique</h2>
        </div>
        @if($absences->isEmpty())
            <div class="px-6 py-10 text-center text-gray-400 text-sm">
                Aucune absence ou retard enregistré.
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Date</th>
                        <th class="px-4 py-3 text-left font-semibold hidden sm:table-cell">Matière</th>
                        <th class="px-4 py-3 text-center font-semibold">Statut</th>
                        <th class="px-4 py-3 text-center font-semibold">Justifié</th>
                        <th class="px-4 py-3 text-left font-semibold hidden md:table-cell">Motif</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($absences as $absence)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-4 py-3 font-medium text-gray-900">
                            {{ $absence->date?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 hidden sm:table-cell">
                            {{ $absence->matiere?->nom ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                {{ $absence->statut === 'absent' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $absence->statut === 'absent' ? 'Absent' : 'Retard' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($absence->justifie)
                                <span class="text-emerald-600">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-xs hidden md:table-cell">
                            {{ $absence->justification ?? $absence->motif ?? '' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $absences->links() }}
        @endif
    </div>

    <div class="flex gap-3">
        <a href="{{ route('mon-espace-parent.dashboard') }}"
           class="flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-600 hover:border-gray-300">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Retour
        </a>
    </div>

</div>
@endsection
