@extends('layouts.app')

@section('title', 'Paiements')
@section('page-title', 'Paiements & Scolarité')
@section('page-subtitle', 'Encaissement manuel + Wave — FCFA')

@section('content')
<div>
    {{-- Flash --}}
    @if(session('success'))
        <div class="mb-4 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            @foreach($errors->all() as $e) <p>• {{ $e }}</p> @endforeach
        </div>
    @endif

    {{-- Stats recouvrement --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-5 mb-8">
        <div class="stat-card border-l-4 border-brand-500">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Total attendu</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($recouvrement['total_du'] ?? 0, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-400">FCFA</span></p>
        </div>
        <div class="stat-card border-l-4 border-green-500">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Total encaissé</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($recouvrement['total_paye'] ?? 0, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-400">FCFA</span></p>
        </div>
        <div class="stat-card border-l-4 border-red-500">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Reste à recouvrer</p>
            <p class="text-2xl font-bold text-red-600">{{ number_format($recouvrement['reste'] ?? 0, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-400">FCFA</span></p>
        </div>
        <div class="stat-card border-l-4 border-gold-400">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Taux de recouvrement</p>
            <p class="text-2xl font-bold text-gold-600">{{ $recouvrement['taux'] ?? 0 }}%</p>
            <div class="w-full bg-gray-100 rounded-full h-2 mt-2">
                <div class="h-2 rounded-full bg-gold-400" style="width: {{ min(100, $recouvrement['taux_recouvrement'] ?? 0) }}%"></div>
            </div>
        </div>
    </div>

    @if(!empty($postes))
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-blue-100 p-4">
            <p class="text-[10px] uppercase font-bold text-blue-600 mb-2">Inscription</p>
            <div class="grid grid-cols-3 gap-2 text-sm">
                <div><span class="text-gray-400 text-xs">Attendu</span><p class="font-bold">{{ number_format($postes['total_inscription'], 0, ',', ' ') }}</p></div>
                <div><span class="text-gray-400 text-xs">Payé</span><p class="font-bold text-emerald-600">{{ number_format($postes['paye_inscription'], 0, ',', ' ') }}</p></div>
                <div><span class="text-gray-400 text-xs">Reste</span><p class="font-bold text-amber-600">{{ number_format($postes['reste_inscription'], 0, ',', ' ') }}</p></div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-purple-100 p-4">
            <p class="text-[10px] uppercase font-bold text-purple-600 mb-2">Scolarité</p>
            <div class="grid grid-cols-3 gap-2 text-sm">
                <div><span class="text-gray-400 text-xs">Attendu</span><p class="font-bold">{{ number_format($postes['total_scolarite'], 0, ',', ' ') }}</p></div>
                <div><span class="text-gray-400 text-xs">Payé</span><p class="font-bold text-emerald-600">{{ number_format($postes['paye_scolarite'], 0, ',', ' ') }}</p></div>
                <div><span class="text-gray-400 text-xs">Reste</span><p class="font-bold text-amber-600">{{ number_format($postes['reste_scolarite'], 0, ',', ' ') }}</p></div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modes acceptés --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold">W</div>
            <div>
                <p class="text-sm font-bold text-blue-900">Wave</p>
                <p class="text-xs {{ $waveActif ? 'text-blue-700' : 'text-gray-400' }}">
                    {{ $waveActif ? 'Activé' : 'Désactivé' }}
                </p>
            </div>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 flex items-center gap-3">
            <div class="w-10 h-10 bg-gray-700 rounded-lg flex items-center justify-center text-white font-bold">$</div>
            <div>
                <p class="text-sm font-bold text-gray-900">Espèces</p>
                <p class="text-xs text-gray-500">Manuel</p>
            </div>
        </div>
        <div class="bg-purple-50 border border-purple-200 rounded-xl p-3 flex items-center gap-3">
            <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center text-white font-bold">CH</div>
            <div>
                <p class="text-sm font-bold text-purple-900">Chèque</p>
                <p class="text-xs text-purple-700">Manuel</p>
            </div>
        </div>
        <div class="bg-teal-50 border border-teal-200 rounded-xl p-3 flex items-center gap-3">
            <div class="w-10 h-10 bg-teal-600 rounded-lg flex items-center justify-center text-white font-bold">VR</div>
            <div>
                <p class="text-sm font-bold text-teal-900">Virement</p>
                <p class="text-xs text-teal-700">Manuel</p>
            </div>
        </div>
    </div>

    {{-- 🔎 Confirmation rapide pré-reçu (saisir la référence apportée par le parent) --}}
    <div class="bg-gradient-to-r from-amber-50 via-amber-50/60 to-yellow-50 border-2 border-dashed border-amber-300 rounded-2xl p-4 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex items-center gap-3 flex-1">
                <div class="w-11 h-11 rounded-xl bg-amber-500 text-white flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-extrabold text-amber-900">Confirmer un pré-reçu</p>
                    <p class="text-xs text-amber-700 mt-0.5">Le parent vous présente un pré-reçu Wave ? Saisissez la référence ci-contre pour le retrouver.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('paiements.find-by-reference') }}" class="flex gap-2 w-full sm:w-auto">
                @csrf
                <input type="text" name="reference" required placeholder="PAY-XXX-..." class="input text-sm font-mono flex-1 sm:w-64 border-amber-300 focus:border-amber-500 focus:ring-amber-500"/>
                <button class="px-4 py-2 rounded-xl bg-amber-600 text-white text-sm font-bold hover:bg-amber-700 whitespace-nowrap">
                    Rechercher
                </button>
            </form>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex flex-wrap items-center gap-3 mb-6">
        <a href="{{ route('paiements.create') }}" class="btn-primary flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Nouveau paiement
        </a>
        <a href="{{ route('finances.wave') }}" class="btn-secondary flex items-center gap-2">Paramètres Wave</a>
        <a href="{{ route('paiements.export', request()->query()) }}" class="btn-secondary flex items-center gap-2">Exporter CSV</a>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('paiements.index') }}" class="bg-white rounded-2xl border border-gray-100 p-4 mb-4 grid grid-cols-1 sm:grid-cols-6 gap-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Réf, élève, matricule…" class="input col-span-2 text-sm"/>
        <select name="mode" class="input text-sm">
            <option value="">Tous modes</option>
            @foreach(['especes'=>'Espèces','cheque'=>'Chèque','virement'=>'Virement','wave'=>'Wave','orange_money'=>'Orange Money','mtn_money'=>'MTN','moov_money'=>'Moov','carte_bancaire'=>'Carte'] as $k => $v)
                <option value="{{ $k }}" @selected(request('mode') === $k)>{{ $v }}</option>
            @endforeach
        </select>
        <select name="statut" class="input text-sm">
            <option value="">Tous statuts</option>
            @foreach(['confirme'=>'Confirmé','en_attente'=>'En attente','echoue'=>'Échoué','annule'=>'Annulé','rembourse'=>'Remboursé'] as $k => $v)
                <option value="{{ $k }}" @selected(request('statut') === $k)>{{ $v }}</option>
            @endforeach
        </select>
        <select name="canal" class="input text-sm">
            <option value="">Canal</option>
            <option value="manuel" @selected(request('canal') === 'manuel')>Manuel</option>
            <option value="wave" @selected(request('canal') === 'wave')>Wave</option>
        </select>
        @if($classes->isNotEmpty())
        <select name="classe_id" class="input text-sm">
            <option value="">Toutes classes</option>
            @foreach($classes as $c)
                <option value="{{ $c->id }}" @selected(request('classe_id') == $c->id)>{{ $c->nom }}</option>
            @endforeach
        </select>
        @endif
        <input type="date" name="date_debut" value="{{ request('date_debut') }}" class="input text-sm"/>
        <input type="date" name="date_fin" value="{{ request('date_fin') }}" class="input text-sm"/>
        <div class="col-span-1 sm:col-span-6 flex gap-2">
            <button type="submit" class="btn-primary text-sm">Filtrer</button>
            <a href="{{ route('paiements.index') }}" class="btn-secondary text-sm">Réinitialiser</a>
        </div>
    </form>

    {{-- Tableau --}}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Référence</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Élève</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Classe</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Montant</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-blue-600 uppercase">Inscription</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-purple-600 uppercase">Scolarité</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Mode</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Statut</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paiements as $p)
                <tr class="table-row hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm">
                        <p class="font-mono text-gray-700">{{ $p->reference }}</p>
                        @if($p->numero_recu)
                            <p class="text-xs text-green-600 font-semibold">{{ $p->numero_recu }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-sm font-semibold">{{ $p->eleve?->prenom }} {{ $p->eleve?->nom }}</p>
                        <p class="text-xs text-gray-400">{{ $p->eleve?->matricule_interne ?? '' }}</p>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $p->inscription?->classe?->nom ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm font-bold text-gray-900 text-right">{{ number_format($p->montant, 0, ',', ' ') }} F</td>
                    <td class="px-4 py-3 text-sm text-right text-blue-700">{{ number_format($p->montant_inscription ?? 0, 0, ',', ' ') }}</td>
                    <td class="px-4 py-3 text-sm text-right text-purple-700">{{ number_format($p->montant_scolarite ?? 0, 0, ',', ' ') }}</td>
                    <td class="px-4 py-3">
                        @php $modeColors = [
                            'orange_money'=>'bg-orange-100 text-orange-700',
                            'mtn_money'=>'bg-yellow-100 text-yellow-700',
                            'moov_money'=>'bg-indigo-100 text-indigo-700',
                            'wave'=>'bg-blue-100 text-blue-700',
                            'especes'=>'bg-gray-100 text-gray-700',
                            'cheque'=>'bg-purple-100 text-purple-700',
                            'virement'=>'bg-teal-100 text-teal-700',
                            'carte_bancaire'=>'bg-pink-100 text-pink-700',
                        ]; @endphp
                        <span class="badge {{ $modeColors[$p->mode] ?? 'bg-gray-100 text-gray-700' }}">{{ str_replace('_', ' ', ucfirst($p->mode)) }}</span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $p->date_paiement?->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        @if($p->statut === 'confirme')
                            <span class="badge badge-green">Confirmé</span>
                        @elseif($p->statut === 'en_attente')
                            <span class="badge badge-yellow">En attente</span>
                        @elseif($p->statut === 'annule')
                            <span class="badge bg-gray-200 text-gray-700">Annulé</span>
                        @else
                            <span class="badge badge-red">{{ ucfirst($p->statut) }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center text-sm">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('paiements.show', $p) }}" class="text-brand-600 hover:underline text-xs font-semibold">Voir</a>
                            @if($p->statut === 'confirme')
                                <a href="{{ route('paiements.recu', $p) }}" class="text-green-600 hover:underline text-xs font-semibold">Reçu</a>
                            @endif
                            @if($p->statut === 'en_attente')
                                <form method="POST" action="{{ route('paiements.confirmer', $p) }}" class="inline">
                                    @csrf
                                    <button class="text-emerald-600 hover:underline text-xs font-semibold">Confirmer</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="px-6 py-12 text-center text-gray-400 text-sm">Aucun paiement trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-50">{{ $paiements->links() }}</div>
    </div>
</div>
@endsection
