@extends('layouts.app')
@section('title', 'QR Codes de pointage')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8 space-y-5">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('admin.rh.dashboard') }}" class="hover:text-brand-600 font-medium">RH</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">QR Codes de pointage</span>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-extrabold text-gray-900">QR Codes de pointage</h1>
            <p class="text-sm text-gray-500 mt-1">Générez les QR Codes à imprimer et coller dans chaque salle. Les profs les scannent depuis leur portail pour pointer leurs séances.</p>
            @if($annee)
                <p class="text-xs text-gray-400 mt-1">📅 Année en cours : <span class="font-bold text-brand-700">{{ $annee->libelle }}</span> · Les salles sont des ressources physiques persistantes (non liées à l'année).</p>
            @else
                <p class="text-xs text-red-600 mt-1">⚠️ Aucune année scolaire active — créez ou activez une année dans <a href="{{ route('admin.annees-scolaires.index') }}" class="underline font-bold">Années scolaires</a>.</p>
            @endif
        </div>
        <form method="POST" action="{{ route('admin.rh.qr-codes.generate-all') }}">
            @csrf
            <button type="submit"
                    onclick="return confirm('Générer un QR pour toutes les salles qui n\'en ont pas ?')"
                    class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition flex items-center gap-2 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Générer pour les salles sans QR
            </button>
        </form>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-4">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Salles actives</p>
            <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $stats['total_salles'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-green-100 p-4">
            <p class="text-xs font-bold text-green-600 uppercase tracking-wide">Avec QR actif</p>
            <p class="text-2xl font-extrabold text-green-700 mt-1">{{ $stats['avec_qr'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-red-100 p-4">
            <p class="text-xs font-bold text-red-600 uppercase tracking-wide">Sans QR</p>
            <p class="text-2xl font-extrabold text-red-700 mt-1">{{ $stats['sans_qr'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-blue-100 p-4">
            <p class="text-xs font-bold text-blue-600 uppercase tracking-wide">QR actifs (total)</p>
            <p class="text-2xl font-extrabold text-blue-700 mt-1">{{ $stats['total_qr_actifs'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-teal-100 p-4">
            <p class="text-xs font-bold text-teal-600 uppercase tracking-wide">Profs actifs (année)</p>
            <p class="text-2xl font-extrabold text-teal-700 mt-1">{{ $stats['enseignants_actifs'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-violet-100 p-4">
            <p class="text-xs font-bold text-violet-600 uppercase tracking-wide">Pointages (année)</p>
            <p class="text-2xl font-extrabold text-violet-700 mt-1">{{ number_format($stats['pointages_annee'], 0, ',', ' ') }}</p>
        </div>
    </div>

    {{-- Sélection multi + PDF d'impression --}}
    <form method="GET" action="{{ route('admin.rh.qr-codes.pdf-poster') }}" target="_blank"
          x-data="{ selected: [], all: false }">

        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-3">
                <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Liste des salles</h2>
                <div class="flex items-center gap-3">
                    <select name="format" class="text-xs font-semibold rounded-lg border border-gray-200 px-2 py-1.5">
                        <option value="1">1 QR par page A4 (grand)</option>
                        <option value="4">4 QR par page A4</option>
                    </select>
                    <button type="submit"
                            :disabled="selected.length === 0"
                            class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2 rounded-lg transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Imprimer la sélection (<span x-text="selected.length"></span>)
                    </button>
                </div>
            </div>

            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-4 py-3 text-left w-10">
                            <input type="checkbox"
                                   @change="all = $event.target.checked;
                                            selected = all ? Array.from(document.querySelectorAll('[data-salle-id]')).map(e => e.dataset.salleId) : []"
                                   class="w-4 h-4 rounded">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Salle</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Bâtiment</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Capacité</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">QR actif</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($salles as $salle)
                    <tr class="hover:bg-gray-50/60 transition">
                        <td class="px-4 py-3">
                            @if($salle->qrCodeActif)
                                <input type="checkbox" name="salles[]" value="{{ $salle->id }}"
                                       :data-salle-id="'{{ $salle->id }}'"
                                       data-salle-id="{{ $salle->id }}"
                                       @change="selected = $event.target.checked ? [...selected, '{{ $salle->id }}'] : selected.filter(s => s !== '{{ $salle->id }}')"
                                       class="w-4 h-4 rounded">
                            @endif
                        </td>
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ $salle->nom }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $salle->batiment ?? '—' }}</td>
                        <td class="px-4 py-3 text-center text-gray-500">{{ $salle->capacite ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($salle->qrCodeActif)
                                <span class="text-xs font-bold bg-green-100 text-green-700 px-2 py-1 rounded-full">✓ Actif</span>
                            @else
                                <span class="text-xs font-bold bg-red-100 text-red-700 px-2 py-1 rounded-full">✗ Aucun</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if($salle->qrCodeActif)
                                <a href="{{ route('admin.rh.qr-codes.image', $salle->qrCodeActif) }}" target="_blank"
                                   class="text-xs font-bold text-blue-600 hover:text-blue-800 px-2 py-1">Voir</a>
                                <form method="POST" action="{{ route('admin.rh.qr-codes.regenerate', $salle) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            onclick="return confirm('Régénérer le QR de cette salle ? L\'ancien sera désactivé.')"
                                            class="text-xs font-bold text-amber-600 hover:text-amber-800 px-2 py-1">Régénérer</button>
                                </form>
                                <form method="POST" action="{{ route('admin.rh.qr-codes.deactivate', $salle->qrCodeActif) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            onclick="return confirm('Désactiver ce QR ?')"
                                            class="text-xs font-bold text-red-500 hover:text-red-700 px-2 py-1">Désactiver</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.rh.qr-codes.regenerate', $salle) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="text-xs font-bold bg-brand-100 text-brand-700 hover:bg-brand-200 px-3 py-1.5 rounded-lg transition">+ Générer</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">Aucune salle active.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 text-sm">
        <p class="font-bold mb-1">📋 Mode d'emploi :</p>
        <ol class="list-decimal pl-5 space-y-0.5">
            <li>Générer un QR pour chaque salle ci-dessus (ou cliquer "Générer pour les salles sans QR")</li>
            <li>Sélectionner les salles puis "Imprimer la sélection" pour télécharger un PDF avec les QR</li>
            <li>Imprimer le PDF, découper et coller chaque QR dans la salle correspondante</li>
            <li>Les profs scannent le QR depuis leur portail (<i>Mon espace → Pointage</i>) pour marquer leur présence</li>
        </ol>
    </div>
</div>
@endsection
