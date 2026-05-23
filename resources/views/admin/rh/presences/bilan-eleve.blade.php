@extends('layouts.app')
@section('title', 'Bilan élève — ' . $eleve->prenom . ' ' . $eleve->nom)

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- En-tête --}}
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="font-display text-2xl font-extrabold text-gray-900">
                {{ $eleve->prenom }} {{ strtoupper($eleve->nom) }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                @if($eleve->matricule_desps)
                    <span class="inline-block bg-indigo-50 text-indigo-700 font-bold text-xs px-2 py-0.5 rounded">DSPS</span>
                    <span class="font-mono">{{ $eleve->matricule_desps }}</span>
                @endif
                @if($eleve->classe) · {{ $eleve->classe->nom ?? '' }} @endif
            </p>
        </div>
        <a href="{{ route('admin.rh.presences.bilan') }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-brand-600 px-3 py-2 rounded-lg border border-gray-200 hover:border-brand-300 bg-white">
            ← Bilans
        </a>
    </div>

    {{-- Stats principales --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-2xl shadow-card p-4 text-center">
            <p class="text-xs uppercase font-bold text-gray-500">Période</p>
            <p class="text-sm font-bold text-gray-900 mt-1">{{ $periode['label'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card p-4 text-center border-l-4 border-blue-400">
            <p class="text-xs uppercase font-bold text-gray-500">Total appels</p>
            <p class="text-2xl font-extrabold text-blue-600 mt-1">{{ $bilan['total'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card p-4 text-center border-l-4 border-red-400">
            <p class="text-xs uppercase font-bold text-gray-500">Absences</p>
            <p class="text-2xl font-extrabold text-red-600 mt-1">{{ $bilan['absents'] }}</p>
            <p class="text-xs text-gray-500">{{ $bilan['non_justifies'] }} non just.</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card p-4 text-center border-l-4 border-amber-400">
            <p class="text-xs uppercase font-bold text-gray-500">Retards</p>
            <p class="text-2xl font-extrabold text-amber-600 mt-1">{{ $bilan['retards'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card p-4 text-center border-l-4 border-purple-400">
            <p class="text-xs uppercase font-bold text-gray-500">Heures d'absence</p>
            <p class="text-2xl font-extrabold text-purple-700 mt-1">{{ number_format($bilan['heures_absence'], 1) }}h</p>
            <p class="text-xs text-gray-500">{{ $bilan['taux_absence'] }}% du total</p>
        </div>
    </div>

    {{-- Bilan par trimestre --}}
    @if($bilans_trimestres->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-bold text-gray-800">Bilan par trimestre — {{ $annee->libelle ?? '' }}</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-{{ min(4, max(1, $bilans_trimestres->count() + ($bilan_annee ? 1 : 0))) }} divide-y md:divide-y-0 md:divide-x divide-gray-100">
                @foreach($bilans_trimestres as $bt)
                    <div class="p-4">
                        <p class="text-xs uppercase font-bold text-gray-500">{{ $bt['trimestre']['libelle'] ?? 'T'.$bt['trimestre']['numero'] }}</p>
                        <div class="flex items-end gap-3 mt-2">
                            <div>
                                <p class="text-2xl font-extrabold text-red-600">{{ $bt['absents'] }}</p>
                                <p class="text-xs text-gray-500">absences</p>
                            </div>
                            <div class="text-right ml-auto">
                                <p class="text-sm font-bold text-purple-700">{{ number_format($bt['heures_absence'], 1) }}h</p>
                                <p class="text-xs text-gray-500">{{ $bt['retards'] }} retards</p>
                            </div>
                        </div>
                    </div>
                @endforeach
                @if($bilan_annee)
                    <div class="p-4 bg-gradient-to-br from-purple-50 to-indigo-50">
                        <p class="text-xs uppercase font-bold text-purple-700">Année complète</p>
                        <div class="flex items-end gap-3 mt-2">
                            <div>
                                <p class="text-2xl font-extrabold text-purple-700">{{ $bilan_annee['absents'] }}</p>
                                <p class="text-xs text-gray-500">total absences</p>
                            </div>
                            <div class="text-right ml-auto">
                                <p class="text-sm font-bold text-purple-700">{{ number_format($bilan_annee['heures_absence'], 1) }}h</p>
                                <p class="text-xs text-gray-500">{{ $bilan_annee['retards'] }} retards</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Distribution par matière --}}
    @if(!empty($bilan['par_matiere']))
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-bold text-gray-800">Absences par matière</h2>
            </div>
            <div class="p-5 space-y-2">
                @foreach($bilan['par_matiere'] as $m)
                    <div class="flex items-center gap-3">
                        <span class="w-20 text-sm font-bold text-gray-700">{{ $m['code'] ?? $m['nom'] }}</span>
                        <div class="flex-1 bg-gray-100 rounded-full h-6 overflow-hidden">
                            @php $max = collect($bilan['par_matiere'])->max('count'); @endphp
                            <div class="h-full bg-gradient-to-r from-red-400 to-red-600 flex items-center justify-end pr-2"
                                 style="width: {{ ($m['count'] / max(1, $max)) * 100 }}%">
                                <span class="text-xs font-bold text-white">{{ $m['count'] }}</span>
                            </div>
                        </div>
                        <span class="w-32 text-xs text-gray-500 truncate">{{ $m['nom'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Liste absences récentes --}}
    @if(!empty($bilan['absences_recentes']))
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-bold text-gray-800">Absences récentes (20 dernières)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">Créneau</th>
                            <th class="px-4 py-3 text-left">Matière</th>
                            <th class="px-4 py-3 text-left">Enseignant</th>
                            <th class="px-4 py-3 text-center">Justif.</th>
                            <th class="px-4 py-3 text-left">Motif</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($bilan['absences_recentes'] as $a)
                            <tr class="{{ $a['justifie'] ? '' : 'bg-red-50/30' }}">
                                <td class="px-4 py-3 text-gray-700">{{ \Carbon\Carbon::parse($a['date'])->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $a['creneau'] ?? $a['periode'] }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $a['matiere'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $a['enseignant'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($a['justifie'])
                                        <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-bold">Justifiée</span>
                                    @else
                                        <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-bold">Non just.</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">{{ $a['motif'] ?? $a['justification'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection
