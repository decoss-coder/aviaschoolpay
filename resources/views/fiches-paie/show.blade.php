@extends('layouts.app')
@section('title', 'Fiche ' . $fiche->reference)
@section('page-title', 'Fiche de paie ' . $fiche->reference)

@section('content')
@php
    $money = fn($v) => number_format((float) $v, 0, ',', ' ');
    $statutBadge = [
        'brouillon' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => 'Brouillon'],
        'validee'   => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Validée'],
        'payee'     => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'label' => 'Payée'],
        'annulee'   => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Annulée'],
    ];
    $b = $statutBadge[$fiche->statut];
@endphp

<div class="space-y-6" x-data="{ modalPayee: false }">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between">
        <a href="{{ route('fiches-paie.index', ['mois' => $fiche->mois]) }}" class="text-sm font-semibold text-gray-500 hover:text-teal-600">← Retour aux fiches</a>
        <a href="{{ route('fiches-paie.pdf', $fiche->id) }}" target="_blank" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-700 text-white text-sm font-bold rounded-xl shadow-card-blue">📄 Télécharger PDF</a>
    </div>

    {{-- Hero --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-6 py-5 bg-gradient-to-r from-teal-50 via-white to-cyan-50 border-b border-gray-100 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <p class="font-mono text-xs font-bold text-gray-500">{{ $fiche->reference }}</p>
                <h2 class="font-display text-2xl font-extrabold text-gray-900 mt-1">{{ $fiche->enseignant->prenom }} {{ strtoupper($fiche->enseignant->nom) }}</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Période : <b>{{ \Carbon\Carbon::parse($fiche->periode_debut)->format('d/m/Y') }}</b> →
                    <b>{{ \Carbon\Carbon::parse($fiche->periode_fin)->format('d/m/Y') }}</b>
                </p>
            </div>
            <div class="flex flex-col items-end gap-2">
                <span class="inline-flex px-3 py-1.5 rounded-xl text-xs font-extrabold {{ $b['bg'] }} {{ $b['text'] }}">{{ $b['label'] }}</span>
                @if($fiche->statut === 'brouillon')
                    <form method="POST" action="{{ route('fiches-paie.valider', $fiche->id) }}">@csrf
                        <button class="px-3 py-1.5 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-700">✓ Valider</button>
                    </form>
                @elseif($fiche->statut === 'validee')
                    <button @click="modalPayee = true" class="px-3 py-1.5 bg-blue-600 text-white text-xs font-bold rounded-xl hover:bg-blue-700">💵 Marquer payée</button>
                @endif
                @if($fiche->statut !== 'payee')
                    <form method="POST" action="{{ route('fiches-paie.destroy', $fiche->id) }}" onsubmit="return confirm('Supprimer la fiche ?')">
                        @csrf @method('DELETE')
                        <button class="text-xs text-red-600 hover:text-red-800 font-bold">🗑 Supprimer</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- KPIs --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-gray-100">
            <div class="p-5">
                <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Salaire de base</p>
                <p class="text-xl font-extrabold text-gray-900 mt-1">{{ $money($fiche->salaire_base) }} F</p>
                <p class="text-xs text-gray-500">{{ ucfirst($fiche->type_remuneration) }}</p>
            </div>
            <div class="p-5">
                <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Heures travaillées</p>
                <p class="text-xl font-extrabold text-amber-700 mt-1">{{ number_format($fiche->heures_travaillees, 1, ',', ' ') }} h</p>
                <p class="text-xs text-gray-500">{{ $money($fiche->taux_horaire) }} F/h → {{ $money($fiche->montant_horaire) }} F</p>
            </div>
            <div class="p-5">
                <p class="text-xs font-bold uppercase text-blue-600 tracking-wider">Salaire brut</p>
                <p class="text-xl font-extrabold text-blue-700 mt-1">{{ $money($fiche->salaire_brut) }} F</p>
                <p class="text-xs text-gray-500">Primes : {{ $money($fiche->primes + $fiche->indemnites) }} F</p>
            </div>
            <div class="p-5 bg-emerald-50">
                <p class="text-xs font-bold uppercase text-emerald-700 tracking-wider">Salaire net</p>
                <p class="text-2xl font-extrabold text-emerald-700 mt-1">{{ $money($fiche->salaire_net) }} F</p>
                <p class="text-xs text-emerald-600">à verser</p>
            </div>
        </div>
    </div>

    {{-- Pointage stats --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-emerald-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-emerald-600 tracking-wider">Jours travaillés</p>
            <p class="text-2xl font-extrabold text-emerald-700 mt-1">{{ $fiche->nb_jours_travailles }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-amber-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-amber-600 tracking-wider">Retards</p>
            <p class="text-2xl font-extrabold text-amber-700 mt-1">{{ $fiche->nb_retards }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-red-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-red-600 tracking-wider">Jours absents (ouvrés)</p>
            <p class="text-2xl font-extrabold text-red-700 mt-1">{{ $fiche->nb_jours_absents }}</p>
        </div>
    </div>

    {{-- Détail calculs + Détail journalier --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
            <h3 class="font-extrabold text-gray-900 mb-3">Détail du calcul</h3>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-100">
                    <tr><td class="py-2 text-gray-600">Salaire de base</td><td class="py-2 text-right font-bold">{{ $money($fiche->salaire_base) }} F</td></tr>
                    <tr><td class="py-2 text-gray-600">{{ number_format($fiche->heures_travaillees, 1, ',', ' ') }} h × {{ $money($fiche->taux_horaire) }} F/h</td><td class="py-2 text-right font-bold">{{ $money($fiche->montant_horaire) }} F</td></tr>
                    @if($fiche->primes > 0)<tr><td class="py-2 text-gray-600">Primes</td><td class="py-2 text-right font-bold text-emerald-700">+{{ $money($fiche->primes) }} F</td></tr>@endif
                    @if($fiche->indemnites > 0)<tr><td class="py-2 text-gray-600">Indemnités</td><td class="py-2 text-right font-bold text-emerald-700">+{{ $money($fiche->indemnites) }} F</td></tr>@endif
                    <tr class="border-t-2 border-gray-300"><td class="py-2 font-bold text-blue-700">SALAIRE BRUT</td><td class="py-2 text-right font-extrabold text-blue-700">{{ $money($fiche->salaire_brut) }} F</td></tr>
                    <tr><td class="py-2 text-gray-600">CNPS (5,5%)</td><td class="py-2 text-right font-bold text-red-700">−{{ $money($fiche->cotisations_sociales) }} F</td></tr>
                    <tr><td class="py-2 text-gray-600">IUTS (1,5%)</td><td class="py-2 text-right font-bold text-red-700">−{{ $money($fiche->impots) }} F</td></tr>
                    @if($fiche->avances > 0)<tr><td class="py-2 text-gray-600">Avances</td><td class="py-2 text-right font-bold text-red-700">−{{ $money($fiche->avances) }} F</td></tr>@endif
                    @if($fiche->retenues > 0)<tr><td class="py-2 text-gray-600">Autres retenues</td><td class="py-2 text-right font-bold text-red-700">−{{ $money($fiche->retenues) }} F</td></tr>@endif
                    <tr class="border-t-2 border-emerald-500 bg-emerald-50"><td class="py-3 font-extrabold text-emerald-800">SALAIRE NET À VERSER</td><td class="py-3 text-right font-extrabold text-lg text-emerald-800">{{ $money($fiche->salaire_net) }} F</td></tr>
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
            <h3 class="font-extrabold text-gray-900 mb-3">Détail journalier (pointage)</h3>
            @if($journalier->isEmpty())
                <p class="text-sm text-gray-500 italic">Aucun pointage enregistré ce mois.</p>
            @else
                <div class="overflow-x-auto max-h-96 overflow-y-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50 text-[10px] uppercase text-gray-500 sticky top-0">
                            <tr>
                                <th class="px-3 py-2 text-left">Date</th>
                                <th class="px-3 py-2 text-center">Arrivée</th>
                                <th class="px-3 py-2 text-center">Départ</th>
                                <th class="px-3 py-2 text-right">Heures</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($journalier as $j)
                                <tr class="{{ ! $j['complet'] ? 'bg-amber-50' : '' }}">
                                    <td class="px-3 py-2 font-mono">{{ \Carbon\Carbon::parse($j['date'])->format('d/m') }}</td>
                                    <td class="px-3 py-2 text-center {{ $j['retard'] ? 'text-amber-700 font-bold' : '' }}">{{ $j['arrivee'] ? substr($j['arrivee'], 0, 5) : '—' }}</td>
                                    <td class="px-3 py-2 text-center">{{ $j['depart'] ? substr($j['depart'], 0, 5) : '—' }}</td>
                                    <td class="px-3 py-2 text-right font-bold {{ $j['complet'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ number_format($j['heures'], 2, ',', '') }}h</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if($fiche->observations)
        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 text-sm text-blue-900">
            <p class="font-bold mb-1">Observations</p>
            <p>{{ $fiche->observations }}</p>
        </div>
    @endif

    {{-- Modal marquer payée --}}
    <div x-show="modalPayee" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="modalPayee = false">
        <form method="POST" action="{{ route('fiches-paie.payer', $fiche->id) }}" class="bg-white rounded-2xl shadow-xl w-full max-w-md">
            @csrf
            <div class="px-6 py-4 border-b border-gray-100"><h3 class="font-extrabold text-gray-900">Confirmer le paiement</h3></div>
            <div class="p-6 space-y-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Date du paiement *</label>
                    <input type="date" name="date_paiement_effectif" value="{{ now()->format('Y-m-d') }}" required class="w-full rounded-xl border-gray-200 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Mode *</label>
                    <select name="mode_paiement" required class="w-full rounded-xl border-gray-200 text-sm">
                        <option value="virement">Virement bancaire</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="cheque">Chèque</option>
                        <option value="especes">Espèces</option>
                    </select>
                </div>
                <div class="bg-emerald-50 rounded-xl p-3 text-xs text-emerald-800">
                    Montant à verser : <b>{{ $money($fiche->salaire_net) }} F</b>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-2 justify-end">
                <button type="button" @click="modalPayee = false" class="px-4 py-2 text-sm font-bold text-gray-600">Annuler</button>
                <button type="submit" class="px-6 py-2 bg-emerald-600 text-white text-sm font-bold rounded-xl">Confirmer le paiement</button>
            </div>
        </form>
    </div>
</div>

@push('styles')<style>[x-cloak]{display:none!important}</style>@endpush
@endsection
