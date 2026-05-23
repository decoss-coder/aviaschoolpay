@extends('layouts.app')
@section('title', 'Présences — Liste')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-4" x-data="{ justifyOpen: null }">

    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('admin.rh.dashboard') }}" class="hover:text-brand-600">RH</a>
        <span>/</span>
        <a href="{{ route('admin.rh.presences.dashboard') }}" class="hover:text-brand-600">Présences</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">Liste</span>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif

    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="font-display text-2xl font-extrabold text-gray-900">Toutes les présences</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $presences->total() }} entrée(s)</p>
        </div>
    </div>

    {{-- Filtres --}}
    <form method="GET" class="bg-white rounded-2xl shadow-card border border-gray-100 p-4">
        <div class="grid grid-cols-2 sm:grid-cols-6 gap-3">
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Date début</label>
                <input type="date" name="date_debut" value="{{ request('date_debut') }}"
                       class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-xs">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Date fin</label>
                <input type="date" name="date_fin" value="{{ request('date_fin') }}"
                       class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-xs">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Classe</label>
                <select name="classe_id" class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-xs">
                    <option value="">Toutes</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ request('classe_id') == $c->id ? 'selected' : '' }}>{{ $c->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Statut</label>
                <select name="statut" class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-xs">
                    <option value="">Tous</option>
                    @foreach(['present'=>'Présent','absent'=>'Absent','retard'=>'Retard','excuse'=>'Excusé','dispense'=>'Dispensé'] as $k=>$v)
                        <option value="{{ $k }}" {{ request('statut')===$k?'selected':'' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Traité ?</label>
                <select name="traite" class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-xs">
                    <option value="">Tous</option>
                    <option value="0" {{ request('traite')==='0'?'selected':'' }}>Non traités</option>
                    <option value="1" {{ request('traite')==='1'?'selected':'' }}>Traités</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Recherche</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, matricule..."
                       class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-xs">
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-3">
            <a href="{{ route('admin.rh.presences.index') }}"
               class="text-xs font-semibold text-gray-500 px-3 py-1.5">Réinitialiser</a>
            <button type="submit"
                    class="bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold px-4 py-1.5 rounded-lg">
                Filtrer
            </button>
        </div>
    </form>

    {{-- Tableau --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Élève</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Classe</th>
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase">Créneau</th>
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase">Statut</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Saisi par</th>
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase">Justifié</th>
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase">Traité</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($presences as $p)
                    @php
                        $color = [
                            'present'  => 'bg-green-100 text-green-700',
                            'absent'   => 'bg-red-100 text-red-700',
                            'retard'   => 'bg-amber-100 text-amber-700',
                            'excuse'   => 'bg-blue-100 text-blue-700',
                            'dispense' => 'bg-purple-100 text-purple-700',
                        ][$p->statut] ?? 'bg-gray-100 text-gray-500';
                    @endphp
                    <tr class="hover:bg-gray-50/40">
                        <td class="px-3 py-2 text-xs whitespace-nowrap">{{ $p->date?->format('d/m/Y') }}</td>
                        <td class="px-3 py-2">
                            <a href="{{ route('admin.rh.presences.eleve', $p->eleve_id) }}" class="hover:text-brand-700">
                                <p class="font-mono font-bold text-xs">{{ $p->eleve?->matricule_desps ?: $p->eleve?->matricule_interne }}</p>
                                <p class="font-semibold text-xs">{{ strtoupper($p->eleve?->nom) }} {{ $p->eleve?->prenom }}</p>
                            </a>
                        </td>
                        <td class="px-3 py-2 text-xs">{{ $p->classe?->nom }}</td>
                        <td class="px-3 py-2 text-center text-xs">
                            @if($p->creneau)
                                {{ substr($p->creneau->heure_debut,0,5) }}–{{ substr($p->creneau->heure_fin,0,5) }}
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $color }}">
                                {{ strtoupper($p->statut) }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-500">{{ $p->saisiePar?->prenom }} {{ $p->saisiePar?->nom }}</td>
                        <td class="px-3 py-2 text-center">
                            @if($p->justifie)
                                <span class="text-green-600 text-xs font-bold">✓</span>
                            @elseif($p->statut === 'absent')
                                <span class="text-red-500 text-xs font-bold">✗</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center text-xs">
                            @if($p->traite_at)
                                <span class="text-green-600 font-bold">✓</span>
                                <p class="text-[9px] text-gray-400">{{ $p->traite_at->format('d/m H:i') }}</p>
                            @else
                                <span class="text-orange-500 font-bold">À traiter</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right">
                            @if($p->statut === 'absent')
                            <button @click="justifyOpen = {{ $p->id }}"
                                    class="text-xs font-bold text-brand-600 hover:text-brand-800 px-2 py-1">
                                Traiter
                            </button>
                            @endif
                        </td>
                    </tr>

                    {{-- Modal justification --}}
                    @if($p->statut === 'absent')
                    <tr x-show="justifyOpen === {{ $p->id }}" x-cloak>
                        <td colspan="9" class="bg-amber-50 px-5 py-4">
                            <form method="POST" action="{{ route('admin.rh.presences.justifier', $p) }}"
                                  class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                                @csrf @method('PATCH')
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Justifiée ?</label>
                                    <select name="justifie" class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-sm">
                                        <option value="1" {{ $p->justifie ? 'selected' : '' }}>Oui — justifiée</option>
                                        <option value="0" {{ !$p->justifie ? 'selected' : '' }}>Non — injustifiée</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Motif</label>
                                    <input type="text" name="motif" value="{{ $p->motif }}" maxlength="255"
                                           placeholder="Ex: Maladie, RDV médical..."
                                           class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-sm">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Commentaire</label>
                                    <input type="text" name="justification" value="{{ $p->justification }}" maxlength="2000"
                                           class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-sm">
                                </div>
                                <div class="sm:col-span-4 flex justify-end gap-2">
                                    <button type="button" @click="justifyOpen = null"
                                            class="text-xs font-semibold text-gray-500 px-3 py-1.5">Annuler</button>
                                    <button type="submit"
                                            class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold px-4 py-1.5 rounded-lg">
                                        Enregistrer
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    @endif

                    @empty
                    <tr><td colspan="9" class="px-3 py-8 text-center text-sm text-gray-400">Aucune entrée trouvée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-gray-100">{{ $presences->links() }}</div>
    </div>
</div>
@endsection
