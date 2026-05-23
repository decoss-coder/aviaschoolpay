@extends('layouts.app')
@section('title', 'Notes — ' . $eleve->prenom . ' ' . $eleve->nom)

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8 space-y-6">

    {{-- Fil d'Ariane --}}
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('mon-espace-parent.dashboard') }}" class="hover:text-emerald-600 font-medium">Espace parent</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="font-semibold text-gray-900">{{ $eleve->prenom }} {{ strtoupper($eleve->nom) }} — Notes</span>
    </div>

    {{-- Sélecteur de trimestre --}}
    @if($trimestres->isNotEmpty())
    <div class="flex gap-2 flex-wrap">
        @foreach($trimestres as $t)
            <a href="{{ request()->fullUrlWithQuery(['trimestre_id' => $t->id]) }}"
               class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors
                      {{ $t->id == $trimId ? 'bg-emerald-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-emerald-300' }}">
                {{ $t->libelle }}
            </a>
        @endforeach
    </div>
    @endif

    {{-- Tableau des moyennes --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Moyennes par matière</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Matière</th>
                        <th class="px-4 py-3 text-center font-semibold">Coeff.</th>
                        <th class="px-4 py-3 text-center font-semibold">Moyenne /20</th>
                        <th class="px-4 py-3 text-center font-semibold">Rang</th>
                        <th class="px-4 py-3 text-left font-semibold hidden md:table-cell">Appréciation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($moyennes as $moy)
                        @php
                            $val = $moy->moyenne;
                            $color = $val === null ? 'text-gray-400'
                                   : ($val >= 16 ? 'text-emerald-600' : ($val >= 10 ? 'text-blue-700' : 'text-red-600'));
                        @endphp
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $moy->matiere->nom }}</td>
                            <td class="px-4 py-3 text-center text-gray-500">{{ $moy->matiere->coefficient ?? 1 }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold {{ $color }} text-base">
                                    {{ $val !== null ? number_format($val, 2) : '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-500">
                                {{ $moy->rang ? $moy->rang . 'ᵉ' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs hidden md:table-cell">
                                {{ $moy->appreciation ?? '' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                Aucune moyenne disponible pour ce trimestre.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Notes détaillées --}}
    @if($notes->isNotEmpty())
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Détail des notes</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Matière</th>
                        <th class="px-4 py-3 text-left font-semibold hidden sm:table-cell">Type</th>
                        <th class="px-4 py-3 text-center font-semibold">Note /20</th>
                        <th class="px-4 py-3 text-center font-semibold hidden md:table-cell">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($notes->sortByDesc(fn($n) => $n->evaluation?->date_evaluation) as $note)
                        @php $val = $note->note; @endphp
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-2.5 font-medium text-gray-900">
                                {{ $note->evaluation?->matiere?->nom ?? '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-gray-500 hidden sm:table-cell">
                                {{ $note->evaluation?->typeEvaluation?->libelle ?? '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-center font-bold
                                {{ $val === null ? 'text-gray-400' : ($val >= 10 ? 'text-blue-700' : 'text-red-600') }}">
                                {{ $val !== null ? number_format($val, 2) : 'Abs.' }}
                            </td>
                            <td class="px-4 py-2.5 text-center text-gray-400 text-xs hidden md:table-cell">
                                {{ $note->evaluation?->date_evaluation?->format('d/m/Y') ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Lien bulletin --}}
    <div class="flex gap-3">
        <a href="{{ route('mon-espace-parent.dashboard') }}"
           class="flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-600 hover:border-gray-300">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Retour
        </a>
    </div>

</div>
@endsection
