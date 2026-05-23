@extends('layouts.app')
@section('title', 'Mon espace élève')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8 space-y-6">

    {{-- Bonjour --}}
    <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-700 flex items-center justify-center text-white font-extrabold text-2xl shadow-brand-glow flex-shrink-0">
            {{ strtoupper(substr($eleve->prenom ?? '?', 0, 1)) }}
        </div>
        <div>
            <h1 class="font-display text-2xl font-extrabold text-gray-900">
                Bonjour, {{ $eleve->prenom }} {{ strtoupper($eleve->nom) }}
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">
                <span class="font-mono font-bold">{{ $eleve->matricule_desps ?: $eleve->matricule_interne }}</span>
                · {{ $classe->nom }}
                @if($trimestreActuel) · {{ $trimestreActuel->libelle }} @endif
            </p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-card border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-extrabold text-gray-900">{{ $moyenneTotale ? number_format($moyenneTotale, 2) : '—' }}</p>
                    <p class="text-xs text-gray-500 font-medium">Moyenne {{ $trimestreActuel?->libelle ?? 'période' }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-card border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-extrabold text-gray-900">{{ $moyennes->count() }}</p>
                    <p class="text-xs text-gray-500 font-medium">Matières évaluées</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-card border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gold-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-extrabold text-gray-900">{{ $devoirs->count() }}</p>
                    <p class="text-xs text-gray-500 font-medium">Devoirs en cours</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-card border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-extrabold text-gray-900">{{ $nbAbsences }}</p>
                    <p class="text-xs text-gray-500 font-medium">Absences</p>
                </div>
            </div>
        </div>
    </div>

    {{-- 2 colonnes : dernières notes + devoirs récents --}}
    <div class="grid lg:grid-cols-2 gap-5">

        {{-- Dernières notes --}}
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-800">Mes dernières notes</h2>
                <a href="{{ route('mon-espace-eleve.notes') }}"
                   class="text-xs text-brand-600 hover:text-brand-700 font-semibold">Voir tout →</a>
            </div>

            @if($dernieresNotes->isEmpty())
                <div class="px-5 py-10 text-center">
                    <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    <p class="text-sm text-gray-400 font-medium">Aucune note publiée pour le moment.</p>
                </div>
            @else
                <div class="divide-y divide-gray-50">
                    @foreach($dernieresNotes as $n)
                    @php
                        $note   = (float) $n->note;
                        $bareme = (float) ($n->evaluation->note_sur ?? 20);
                        $note20 = $bareme > 0 ? ($note / $bareme) * 20 : $note;
                        $color  = $note20 >= 14 ? 'green' : ($note20 >= 10 ? 'amber' : 'red');
                    @endphp
                    <div class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50/60 transition">
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-800 text-sm truncate">{{ $n->evaluation->titre }}</p>
                            <p class="text-xs text-gray-500">{{ $n->evaluation->matiere?->nom }} · {{ $n->evaluation->typeEvaluation?->nom }}</p>
                        </div>
                        <span class="text-lg font-extrabold text-{{ $color }}-700 flex-shrink-0">
                            {{ number_format($note, 2) }}<span class="text-xs text-gray-400">/{{ rtrim(rtrim((string) $bareme, '0'), '.') }}</span>
                        </span>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Devoirs récents --}}
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-800">Devoirs récents</h2>
                <a href="{{ route('mon-espace-eleve.devoirs') }}"
                   class="text-xs text-gold-600 hover:text-gold-700 font-semibold">Voir tout →</a>
            </div>

            @if($devoirs->isEmpty())
                <div class="px-5 py-10 text-center">
                    <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    <p class="text-sm text-gray-400 font-medium">Aucun devoir publié.</p>
                </div>
            @else
                <div class="divide-y divide-gray-50">
                    @foreach($devoirs as $d)
                    <div class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50/60 transition">
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-800 text-sm truncate">{{ $d->titre }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $d->matiere?->code }} · {{ ucfirst($d->type) }}
                                @if($d->date_limite)
                                    · À rendre le <b class="text-orange-600">{{ $d->date_limite->format('d/m/Y') }}</b>
                                @endif
                            </p>
                        </div>
                        @if($d->fichier_path)
                        <a href="{{ route('mon-espace-eleve.devoirs.sujet', $d) }}"
                           class="text-xs font-bold text-gold-600 hover:text-gold-700 flex-shrink-0">📄 Sujet</a>
                        @endif
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Moyennes par matière (si présentes) --}}
    @if($moyennes->isNotEmpty())
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-800">Mes moyennes — {{ $trimestreActuel?->libelle }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-5 py-2 text-left text-xs font-bold text-gray-500 uppercase">Matière</th>
                        <th class="px-5 py-2 text-center text-xs font-bold text-gray-500 uppercase">Moyenne</th>
                        <th class="px-5 py-2 text-left text-xs font-bold text-gray-500 uppercase">Appréciation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($moyennes as $m)
                    @php $color = $m->moyenne >= 14 ? 'green' : ($m->moyenne >= 10 ? 'amber' : 'red'); @endphp
                    <tr class="hover:bg-gray-50/60 transition">
                        <td class="px-5 py-3 font-semibold text-gray-800">{{ $m->matiere?->nom }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="text-base font-extrabold text-{{ $color }}-700">
                                {{ $m->moyenne !== null ? number_format($m->moyenne, 2) : '—' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-xs text-gray-600 italic">{{ $m->appreciation ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Accès rapides --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
        <a href="{{ route('mon-espace-eleve.notes') }}"
           class="bg-white rounded-2xl p-4 shadow-card border border-gray-100 hover:shadow-card-brand hover:border-brand-200 transition group text-center">
            <svg class="w-8 h-8 text-brand-400 group-hover:text-brand-600 mx-auto mb-2 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <p class="text-xs font-bold text-gray-700 group-hover:text-brand-700 transition">Mes notes & moyennes</p>
        </a>
        <a href="{{ route('mon-espace-eleve.devoirs') }}"
           class="bg-white rounded-2xl p-4 shadow-card border border-gray-100 hover:shadow-card-brand hover:border-gold-200 transition group text-center">
            <svg class="w-8 h-8 text-gold-400 group-hover:text-gold-600 mx-auto mb-2 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            <p class="text-xs font-bold text-gray-700 group-hover:text-gold-700 transition">Mes devoirs & sujets</p>
        </a>
        <a href="{{ route('mon-espace-eleve.evaluations') }}"
           class="bg-white rounded-2xl p-4 shadow-card border border-gray-100 hover:shadow-card-brand hover:border-blue-200 transition group text-center">
            <svg class="w-8 h-8 text-blue-400 group-hover:text-blue-600 mx-auto mb-2 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            <p class="text-xs font-bold text-gray-700 group-hover:text-blue-700 transition">Mes évaluations</p>
        </a>
    </div>

</div>
@endsection
