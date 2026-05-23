@extends('layouts.app')
@section('title', 'Mes notes')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-4">

    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('mon-espace-eleve.dashboard') }}" class="hover:text-brand-600">Tableau de bord</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">Mes notes</span>
    </div>

    <h1 class="font-display text-2xl font-extrabold text-gray-900">Mes notes & moyennes</h1>

    {{-- Filtre trimestre --}}
    <div class="flex gap-2 flex-wrap">
        @foreach($trimestres as $t)
        <a href="?trimestre_id={{ $t->id }}"
           class="px-4 py-2 rounded-full text-sm font-bold transition
                  {{ $trimId == $t->id ? 'bg-brand-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
            {{ $t->libelle }}
        </a>
        @endforeach
    </div>

    {{-- Synthèse par matière --}}
    @if($moyennes->isEmpty() && $notes->isEmpty())
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-12 text-center">
            <p class="text-gray-400">Aucune note publiée pour cette période.</p>
        </div>
    @else

    @php
        // Grouper les notes par matière
        $notesParMatiere = $notes->groupBy(fn ($n) => $n->evaluation->matiere_id);
    @endphp

    <div class="space-y-4">
        @foreach($notesParMatiere as $matiereId => $notesMat)
        @php
            $matiere = $notesMat->first()?->evaluation?->matiere;
            $moyenne = $moyennes->get($matiereId);
            $mn = $moyenne?->moyenne;
            $color = $mn >= 14 ? 'green' : ($mn >= 10 ? 'amber' : 'red');
        @endphp
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-brand-50 to-white">
                <div>
                    <h2 class="font-bold text-gray-900">{{ $matiere?->nom }}</h2>
                    <p class="text-xs text-gray-500">{{ $matiere?->code }}</p>
                </div>
                @if($mn !== null)
                <div class="text-right">
                    <p class="text-[10px] font-bold text-gray-400 uppercase">Moyenne</p>
                    <p class="text-2xl font-extrabold text-{{ $color }}-700">{{ number_format($mn, 2) }}<span class="text-sm text-gray-400">/20</span></p>
                </div>
                @endif
            </div>

            @if($moyenne?->appreciation)
            <div class="px-5 py-2 bg-amber-50 text-xs text-amber-800 italic border-b border-amber-100">
                💬 {{ $moyenne->appreciation }}
            </div>
            @endif

            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Évaluation</th>
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase">Type</th>
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase">Coef.</th>
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase">Note</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 uppercase">Sujet</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($notesMat as $n)
                    @php
                        $note = (float) $n->note;
                        $bareme = (float) ($n->evaluation->note_sur ?? 20);
                        $note20 = $bareme > 0 ? ($note / $bareme) * 20 : $note;
                        $noteColor = $note20 >= 14 ? 'green' : ($note20 >= 10 ? 'amber' : 'red');
                    @endphp
                    <tr>
                        <td class="px-3 py-2 text-xs">{{ $n->evaluation->date_evaluation?->format('d/m/Y') }}</td>
                        <td class="px-3 py-2 font-semibold text-sm">{{ $n->evaluation->titre }}</td>
                        <td class="px-3 py-2 text-center text-xs">{{ $n->evaluation->typeEvaluation?->code ?? $n->evaluation->typeEvaluation?->nom }}</td>
                        <td class="px-3 py-2 text-center text-xs">{{ $n->evaluation->coefficient }}</td>
                        <td class="px-3 py-2 text-center">
                            @if($n->absent)
                                <span class="text-xs bg-gray-100 text-gray-500 font-bold px-2 py-1 rounded-full">ABS</span>
                            @elseif($n->dispense)
                                <span class="text-xs bg-blue-100 text-blue-700 font-bold px-2 py-1 rounded-full">DISP</span>
                            @else
                                <span class="font-bold text-{{ $noteColor }}-700">{{ number_format($note, 2) }}<span class="text-xs text-gray-400">/{{ rtrim(rtrim((string) $bareme, '0'), '.') }}</span></span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right">
                            @if($n->evaluation->fichier_sujet_path)
                            <a href="{{ route('mon-espace-eleve.evaluation.sujet', $n->evaluation) }}"
                               class="text-xs font-bold text-blue-600 hover:underline">📄 Télécharger</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach

        {{-- Moyennes saisies directement (sans notes détaillées) --}}
        @php $moyennesSansNotes = $moyennes->reject(fn($m) => isset($notesParMatiere[$m->matiere_id])); @endphp
        @if($moyennesSansNotes->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Autres moyennes</h2>
            </div>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-50">
                    @foreach($moyennesSansNotes as $m)
                    @php $color = $m->moyenne >= 14 ? 'green' : ($m->moyenne >= 10 ? 'amber' : 'red'); @endphp
                    <tr>
                        <td class="px-5 py-3 font-semibold">{{ $m->matiere?->nom }}</td>
                        <td class="px-5 py-3 text-xs text-gray-500 italic">{{ $m->appreciation }}</td>
                        <td class="px-5 py-3 text-right">
                            <span class="text-lg font-extrabold text-{{ $color }}-700">{{ number_format($m->moyenne, 2) }}<span class="text-xs text-gray-400">/20</span></span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endif
</div>
@endsection
