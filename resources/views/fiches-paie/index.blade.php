@extends('layouts.app')
@section('title', 'Fiches de paie')
@section('page-title', 'Fiches de paie')
@section('page-subtitle', 'Paie des enseignants basée sur les pointages')

@section('content')
@php
    $money = fn($v) => number_format((float) $v, 0, ',', ' ');
    $statutBadge = [
        'brouillon' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => 'Brouillon'],
        'validee'   => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Validée'],
        'payee'     => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'label' => 'Payée'],
        'annulee'   => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Annulée'],
    ];
@endphp

<div class="space-y-6" x-data="{ modalGenerer: false, modalParam: false, modalEns: null, preview: null, loading: false }">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error') || $errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-teal-500 to-cyan-700 rounded-xl flex items-center justify-center shadow-card-blue">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Paie</p>
                <h2 class="font-display text-2xl font-extrabold text-gray-900">Fiches de paie enseignants</h2>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <form method="GET" class="flex items-center gap-2">
                <input type="month" name="mois" value="{{ $mois }}" onchange="this.form.submit()"
                       class="rounded-xl border-gray-200 text-sm focus:border-teal-400 focus:ring-teal-100" />
            </form>
            <button @click="modalGenerer = true" class="px-4 py-2.5 text-sm font-bold rounded-xl bg-gradient-to-r from-teal-500 to-cyan-700 text-white shadow-card-blue hover:shadow-lg flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Générer une fiche
            </button>
        </div>
    </div>

    {{-- KPIs --}}
    <section class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-card">
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Fiches du mois</p>
            <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $stats['nb_total'] }}</p>
            <p class="text-xs text-gray-500">{{ $stats['nb_brouillon'] }} brouillons · {{ $stats['nb_validees'] }} validées · {{ $stats['nb_payees'] }} payées</p>
        </div>
        <div class="bg-white rounded-2xl border border-blue-100 p-4 shadow-card-blue">
            <p class="text-xs font-bold uppercase text-blue-600 tracking-wider">Total brut</p>
            <p class="text-2xl font-extrabold text-blue-700 mt-1">{{ $money($stats['total_brut']) }} <span class="text-xs text-gray-400 font-bold">F</span></p>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-100 p-4 shadow-card">
            <p class="text-xs font-bold uppercase text-emerald-600 tracking-wider">Total net</p>
            <p class="text-2xl font-extrabold text-emerald-700 mt-1">{{ $money($stats['total_net']) }} <span class="text-xs text-gray-400 font-bold">F</span></p>
        </div>
        <div class="bg-white rounded-2xl border border-rose-100 p-4 shadow-card">
            <p class="text-xs font-bold uppercase text-rose-600 tracking-wider">Cotis. + Impôts</p>
            <p class="text-xl font-extrabold text-rose-700 mt-1">{{ $money($stats['total_cotisations'] + $stats['total_impots']) }} F</p>
        </div>
        <div class="bg-white rounded-2xl border border-amber-100 p-4 shadow-card-gold">
            <p class="text-xs font-bold uppercase text-amber-600 tracking-wider">Heures totales</p>
            <p class="text-2xl font-extrabold text-amber-700 mt-1">{{ number_format($stats['total_heures'], 1, ',', ' ') }} <span class="text-xs text-gray-400 font-bold">h</span></p>
        </div>
    </section>

    {{-- Génération en masse --}}
    @if($enseignantsSansFiche->isNotEmpty())
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 flex items-center justify-between gap-4 flex-wrap">
        <div>
            <p class="font-bold text-amber-900">⚠ {{ $enseignantsSansFiche->count() }} enseignant(s) sans fiche pour {{ \Carbon\Carbon::parse($mois.'-01')->locale('fr')->isoFormat('MMMM YYYY') }}</p>
            <p class="text-xs text-amber-800 mt-0.5">Vous pouvez les générer en une fois (chacun avec ses heures réelles de pointage).</p>
        </div>
        <form method="POST" action="{{ route('fiches-paie.generer-tous') }}">
            @csrf
            <input type="hidden" name="mois" value="{{ $mois }}" />
            <button class="px-4 py-2 bg-amber-600 text-white text-sm font-bold rounded-xl hover:bg-amber-700">⚡ Générer pour tous</button>
        </form>
    </div>
    @endif

    {{-- Tableau --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-extrabold text-gray-900">Fiches de paie — {{ \Carbon\Carbon::parse($mois.'-01')->locale('fr')->isoFormat('MMMM YYYY') }}</h3>
            <span class="text-xs font-semibold text-gray-500">{{ $fiches->count() }} fiche(s)</span>
        </div>

        @if($fiches->isEmpty())
            <div class="px-5 py-16 text-center">
                <p class="text-4xl mb-3">📋</p>
                <p class="font-bold text-gray-800">Aucune fiche pour ce mois</p>
                <p class="text-sm text-gray-500 mt-1 mb-4">Générez les fiches à partir des pointages enregistrés.</p>
                <button @click="modalGenerer = true" class="inline-flex items-center px-4 py-2 bg-teal-600 text-white text-sm font-bold rounded-xl">Générer une fiche</button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-5 py-3 text-left font-bold">Réf</th>
                            <th class="px-5 py-3 text-left font-bold">Enseignant</th>
                            <th class="px-5 py-3 text-center font-bold">Type</th>
                            <th class="px-5 py-3 text-right font-bold">Heures</th>
                            <th class="px-5 py-3 text-right font-bold">Brut</th>
                            <th class="px-5 py-3 text-right font-bold">Net</th>
                            <th class="px-5 py-3 text-center font-bold">Statut</th>
                            <th class="px-5 py-3 text-right font-bold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($fiches as $f)
                            @php $b = $statutBadge[$f->statut] ?? $statutBadge['brouillon']; @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 font-mono text-xs text-gray-600">{{ $f->reference }}</td>
                                <td class="px-5 py-3">
                                    <div class="font-semibold text-gray-900">{{ $f->enseignant?->prenom }} {{ strtoupper($f->enseignant?->nom ?? '') }}</div>
                                    <div class="text-xs text-gray-500">{{ $f->enseignant?->matricule_mena ?: 'sans matricule' }}</div>
                                </td>
                                <td class="px-5 py-3 text-center text-xs font-bold">
                                    @php
                                        $tc = ['fixe' => 'bg-blue-100 text-blue-700', 'horaire' => 'bg-amber-100 text-amber-700', 'mixte' => 'bg-violet-100 text-violet-700'][$f->type_remuneration] ?? 'bg-gray-100';
                                    @endphp
                                    <span class="inline-flex px-2 py-1 rounded-lg {{ $tc }}">{{ ucfirst($f->type_remuneration) }}</span>
                                </td>
                                <td class="px-5 py-3 text-right text-xs text-gray-700">
                                    <span class="font-bold">{{ number_format($f->heures_travaillees, 1, ',', ' ') }}h</span>
                                    @if($f->nb_retards > 0) <span class="text-amber-600">· {{ $f->nb_retards }} retard(s)</span>@endif
                                </td>
                                <td class="px-5 py-3 text-right font-bold text-blue-700">{{ $money($f->salaire_brut) }} F</td>
                                <td class="px-5 py-3 text-right font-extrabold text-emerald-700">{{ $money($f->salaire_net) }} F</td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold {{ $b['bg'] }} {{ $b['text'] }}">{{ $b['label'] }}</span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('fiches-paie.show', $f->id) }}" class="text-teal-600 hover:text-teal-800 text-xs font-bold">Voir</a>
                                    <span class="text-gray-300">·</span>
                                    <a href="{{ route('fiches-paie.pdf', $f->id) }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs font-bold">📄 PDF</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Enseignants sans fiche (paramétrage) --}}
    @if($enseignantsSansFiche->isNotEmpty())
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-extrabold text-gray-900">Paramétrage rémunération — Enseignants</h3>
            <p class="text-xs text-gray-500 mt-0.5">Configurez le taux horaire avant de générer la fiche</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-bold">Enseignant</th>
                        <th class="px-5 py-3 text-center font-bold">Statut</th>
                        <th class="px-5 py-3 text-center font-bold">Type rém.</th>
                        <th class="px-5 py-3 text-right font-bold">Salaire base</th>
                        <th class="px-5 py-3 text-right font-bold">Taux horaire</th>
                        <th class="px-5 py-3 text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($enseignantsSansFiche as $e)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <div class="font-semibold text-gray-900">{{ $e->prenom }} {{ strtoupper($e->nom) }}</div>
                                <div class="text-xs text-gray-500">{{ $e->matricule_mena ?: 'sans matricule' }} · {{ ucfirst($e->statut) }}</div>
                            </td>
                            <td class="px-5 py-3 text-center text-xs font-bold text-gray-700">{{ ucfirst($e->statut) }}</td>
                            <td class="px-5 py-3 text-center">
                                @php $tcc = ['fixe' => 'bg-blue-100 text-blue-700', 'horaire' => 'bg-amber-100 text-amber-700', 'mixte' => 'bg-violet-100 text-violet-700'][$e->type_remuneration ?? 'fixe']; @endphp
                                <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold {{ $tcc }}">{{ ucfirst($e->type_remuneration ?? 'fixe') }}</span>
                            </td>
                            <td class="px-5 py-3 text-right text-xs">{{ $money($e->salaire_base) }} F</td>
                            <td class="px-5 py-3 text-right text-xs">
                                @if($e->taux_horaire > 0)
                                    <b class="text-amber-700">{{ $money($e->taux_horaire) }} F/h</b>
                                @else
                                    <span class="text-gray-400 italic">non défini</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <button @click="modalEns = {{ json_encode($e) }}; modalParam = true"
                                        class="text-xs font-bold text-teal-600 hover:text-teal-800">⚙ Configurer</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Modal génération fiche --}}
    <div x-show="modalGenerer" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="modalGenerer = false">
        <form method="POST" action="{{ route('fiches-paie.generer') }}" class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
            @csrf
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-extrabold text-gray-900">Générer une fiche de paie</h3>
                <button type="button" @click="modalGenerer = false" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-6 space-y-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Enseignant *</label>
                    <select name="enseignant_id" required class="w-full rounded-xl border-gray-200 text-sm focus:border-teal-400">
                        <option value="">— Sélectionner —</option>
                        @foreach($enseignantsSansFiche as $e)
                            <option value="{{ $e->id }}">{{ $e->prenom }} {{ strtoupper($e->nom) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Mois *</label>
                    <input type="month" name="mois" value="{{ $mois }}" required class="w-full rounded-xl border-gray-200 text-sm focus:border-teal-400" />
                </div>
                <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-100">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Primes (F)</label>
                        <input type="number" name="primes" min="0" value="0" class="w-full rounded-xl border-gray-200 text-sm focus:border-teal-400" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Indemnités (F)</label>
                        <input type="number" name="indemnites" min="0" value="0" class="w-full rounded-xl border-gray-200 text-sm focus:border-teal-400" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Avances (F)</label>
                        <input type="number" name="avances" min="0" value="0" class="w-full rounded-xl border-gray-200 text-sm focus:border-teal-400" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Autres retenues (F)</label>
                        <input type="number" name="retenues" min="0" value="0" class="w-full rounded-xl border-gray-200 text-sm focus:border-teal-400" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Observations</label>
                    <textarea name="observations" rows="2" class="w-full rounded-xl border-gray-200 text-sm focus:border-teal-400"></textarea>
                </div>
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 text-xs text-blue-800">
                    💡 Le salaire de base + heures travaillées (depuis pointage) seront calculés automatiquement. Cotisations CNPS 5,5% + IUTS 1,5% appliqués sur le brut.
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-2 justify-end">
                <button type="button" @click="modalGenerer = false" class="px-4 py-2 text-sm font-bold text-gray-600">Annuler</button>
                <button type="submit" class="px-6 py-2 bg-teal-600 text-white text-sm font-bold rounded-xl">Générer la fiche</button>
            </div>
        </form>
    </div>

    {{-- Modal paramétrage rémunération --}}
    <div x-show="modalParam && modalEns" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="modalParam = false">
        <form x-show="modalEns" method="POST" :action="modalEns ? '/enseignants/' + modalEns.id + '/parametrer-remuneration' : '#'" class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
            @csrf
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-gray-900">⚙ Paramétrer rémunération</h3>
                    <p class="text-xs text-gray-500" x-text="modalEns ? modalEns.prenom + ' ' + modalEns.nom.toUpperCase() : ''"></p>
                </div>
                <button type="button" @click="modalParam = false" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Type de rémunération *</label>
                    <select name="type_remuneration" required class="w-full rounded-xl border-gray-200 text-sm focus:border-teal-400" x-bind:value="modalEns?.type_remuneration ?? 'fixe'">
                        <option value="fixe">Fixe (salaire base seulement)</option>
                        <option value="horaire">Horaire (heures × taux uniquement)</option>
                        <option value="mixte">Mixte (base + heures × taux)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Salaire de base (F)</label>
                    <input type="number" name="salaire_base" min="0" required x-bind:value="modalEns?.salaire_base ?? 0" class="w-full rounded-xl border-gray-200 text-sm focus:border-teal-400" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Taux horaire (F/h)</label>
                    <input type="number" name="taux_horaire" min="0" required x-bind:value="modalEns?.taux_horaire ?? 0" class="w-full rounded-xl border-gray-200 text-sm focus:border-teal-400" />
                    <p class="text-xs text-gray-500 mt-1">Ex : 2 500 F/h pour un vacataire</p>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Heures contractuelles mensuelles</label>
                    <input type="number" step="0.5" name="heures_contractuelles_mois" min="0" max="300" x-bind:value="modalEns?.heures_contractuelles_mois ?? ''" class="w-full rounded-xl border-gray-200 text-sm focus:border-teal-400" placeholder="Ex: 80" />
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-2 justify-end">
                <button type="button" @click="modalParam = false" class="px-4 py-2 text-sm font-bold text-gray-600">Annuler</button>
                <button type="submit" class="px-6 py-2 bg-teal-600 text-white text-sm font-bold rounded-xl">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

@push('styles')<style>[x-cloak]{display:none!important}</style>@endpush
@endsection
