@extends('layouts.app')
@section('title', 'Vérification OCR · ' . $classe->nom)

@section('content')
@php
    $rows = $extracted['eleves'] ?? [];
    $conf = $extracted['confidence'] ?? 0;
    $jours = [];
    for ($i = 0; $i < 6; $i++) {
        $d = $semaine->copy()->addDays($i);
        $jours[] = ['date' => $d->toDateString(), 'libelle' => $d->locale('fr')->isoFormat('ddd D/MM')];
    }
@endphp

<div class="max-w-7xl mx-auto px-4 py-8 space-y-5">

    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('mon-espace.cahier-appel.index', $classe) }}" class="hover:text-brand-600">{{ $classe->nom }} — Cahier d'appel</a>
        <span>/</span>
        <span>Vérification OCR</span>
    </div>

    <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="font-display text-2xl font-extrabold text-gray-900">Vérifiez les présences extraites</h1>
        <span class="text-xs font-bold px-3 py-1 rounded-full
            {{ $conf >= 70 ? 'bg-green-100 text-green-700' : ($conf >= 40 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
            Confiance IA : {{ $conf }}%
        </span>
    </div>

    @if(!empty($extracted['notes_extraction']))
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 text-sm">
            <b>💡 Note de l'IA :</b> {{ $extracted['notes_extraction'] }}
        </div>
    @endif

    @if(empty($rows))
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
            <b>Aucune donnée extraite.</b>
            <a href="{{ route('mon-espace.cahier-appel.import-ocr.form', $classe) }}"
               class="ml-3 bg-purple-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg">Recommencer</a>
        </div>
    @else
    <form method="POST" action="{{ route('mon-espace.cahier-appel.import-ocr.confirm', $classe) }}" class="space-y-5">
        @csrf
        <input type="hidden" name="semaine" value="{{ $semaine->toDateString() }}">
        <input type="hidden" name="image_path" value="{{ $imagePath }}">

        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Présences extraites — semaine du {{ $semaine->format('d/m/Y') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Matricule OCR</th>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Match</th>
                            @foreach($jours as $j)
                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase">{{ $j['libelle'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($rows as $row)
                        <tr class="{{ $row['eleve_id'] ? '' : 'bg-red-50' }}">
                            <td class="px-3 py-2 font-mono font-bold">{{ $row['matricule_ocr'] }}</td>
                            <td class="px-3 py-2">
                                @if($row['eleve_id'])
                                    <span class="text-green-700 text-xs">✓ {{ $row['nom_match'] }}</span>
                                @else
                                    <span class="text-red-600 text-xs font-bold">⚠ Non trouvé</span>
                                @endif
                            </td>
                            @foreach($jours as $j)
                                @php $statut = $row['jours'][$j['date']] ?? ''; @endphp
                                <td class="px-2 py-2 text-center">
                                    <select name="presences[{{ $row['matricule_ocr'] }}][{{ $j['date'] }}]"
                                            {{ $row['eleve_id'] ? '' : 'disabled' }}
                                            class="text-xs font-bold rounded-md border border-gray-200 px-1 py-1
                                                  {{ $statut === 'present' ? 'bg-green-50 text-green-700' : '' }}
                                                  {{ $statut === 'absent' ? 'bg-red-50 text-red-700' : '' }}
                                                  {{ $statut === 'retard' ? 'bg-amber-50 text-amber-700' : '' }}
                                                  {{ in_array($statut, ['excuse','dispense']) ? 'bg-blue-50 text-blue-700' : '' }}
                                                  disabled:opacity-30">
                                        <option value="" {{ !$statut ? 'selected' : '' }}>—</option>
                                        <option value="present"  {{ $statut === 'present' ? 'selected' : '' }}>P</option>
                                        <option value="absent"   {{ $statut === 'absent' ? 'selected' : '' }}>A</option>
                                        <option value="retard"   {{ $statut === 'retard' ? 'selected' : '' }}>R</option>
                                        <option value="excuse"   {{ $statut === 'excuse' ? 'selected' : '' }}>E</option>
                                        <option value="dispense" {{ $statut === 'dispense' ? 'selected' : '' }}>D</option>
                                    </select>
                                </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('mon-espace.cahier-appel.import-ocr.form', $classe) }}"
               class="px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100">Recommencer</a>
            <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white text-sm font-bold px-6 py-2.5 rounded-xl">
                ✓ Confirmer et enregistrer
            </button>
        </div>
    </form>
    @endif
</div>
@endsection
