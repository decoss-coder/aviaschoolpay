@extends('layouts.app')
@section('title', 'Conseils de classe')
@section('page-title', 'Conseils de classe')

@section('content')
<div class="space-y-6" x-data="{ modal: false }">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-700 rounded-xl flex items-center justify-center shadow-card-violet">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <h2 class="font-display text-2xl font-extrabold text-gray-900">Conseils de classe</h2>
                <p class="text-xs text-gray-500">Planification et convocations officielles</p>
            </div>
        </div>
        <button @click="modal = true" class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-indigo-500 to-purple-700 text-white shadow-card-violet hover:shadow-lg flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Planifier un conseil
        </button>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-extrabold text-gray-900">Conseils planifiés</h3>
        </div>
        @if($conseils->isEmpty())
            <div class="px-5 py-16 text-center">
                <p class="text-4xl mb-3">👥</p>
                <p class="font-bold text-gray-800">Aucun conseil planifié</p>
                <button @click="modal = true" class="mt-3 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-bold rounded-xl">Planifier le premier</button>
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-bold">Classe</th>
                        <th class="px-5 py-3 text-left font-bold">Trimestre</th>
                        <th class="px-5 py-3 text-left font-bold">Date</th>
                        <th class="px-5 py-3 text-left font-bold">Heure</th>
                        <th class="px-5 py-3 text-left font-bold">Lieu</th>
                        <th class="px-5 py-3 text-center font-bold">Statut</th>
                        <th class="px-5 py-3 text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($conseils as $c)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-bold text-gray-900">{{ $c->classe?->nom }}</td>
                            <td class="px-5 py-3 text-xs">{{ $c->trimestre?->libelle }}</td>
                            <td class="px-5 py-3 text-xs">{{ $c->date_conseil?->locale('fr')->isoFormat('ddd D MMM YYYY') }}</td>
                            <td class="px-5 py-3 text-xs">{{ substr($c->heure_debut, 0, 5) }}</td>
                            <td class="px-5 py-3 text-xs text-gray-700">{{ $c->lieu }}</td>
                            <td class="px-5 py-3 text-center">
                                @php $sb = ['planifie' => 'bg-blue-100 text-blue-700', 'tenu' => 'bg-emerald-100 text-emerald-700', 'reporte' => 'bg-amber-100 text-amber-700', 'annule' => 'bg-gray-100 text-gray-500'][$c->statut]; @endphp
                                <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold {{ $sb }}">{{ ucfirst($c->statut) }}</span>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('documents.convocation.pdf', ['conseil_id' => $c->id]) }}" target="_blank" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">📄 Convocation</a>
                                <span class="text-gray-300 mx-1">·</span>
                                <form method="POST" action="{{ route('conseils-classe.destroy', $c->id) }}" class="inline" onsubmit="return confirm('Supprimer ?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-bold text-red-600">🗑</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-gray-100">{{ $conseils->links() }}</div>
        @endif
    </div>

    <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="modal=false">
        <form method="POST" action="{{ route('conseils-classe.store') }}" class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            @csrf
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-extrabold text-gray-900">Planifier un conseil de classe</h3>
                <button type="button" @click="modal=false" class="text-gray-400 text-2xl">&times;</button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Classe *</label>
                        <select name="classe_id" required class="w-full rounded-xl border-gray-200 text-sm">
                            <option value="">—</option>
                            @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->nom }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Trimestre *</label>
                        <select name="trimestre_id" required class="w-full rounded-xl border-gray-200 text-sm">
                            <option value="">—</option>
                            @foreach($trimestres as $t)<option value="{{ $t->id }}">{{ $t->libelle }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Date *</label>
                        <input type="date" name="date_conseil" required value="{{ now()->addDays(7)->format('Y-m-d') }}" class="w-full rounded-xl border-gray-200 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Heure début *</label>
                        <input type="time" name="heure_debut" required value="15:00" class="w-full rounded-xl border-gray-200 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Heure fin</label>
                        <input type="time" name="heure_fin" value="18:00" class="w-full rounded-xl border-gray-200 text-sm" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Lieu *</label>
                    <input name="lieu" required placeholder="Salle des professeurs" class="w-full rounded-xl border-gray-200 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Ordre du jour *</label>
                    <textarea name="ordre_du_jour" rows="6" required placeholder="1. Présentation des résultats du trimestre&#10;2. Analyse des moyennes par matière&#10;3. Cas particuliers (élèves en difficulté)&#10;4. Recommandations pédagogiques&#10;5. Divers" class="w-full rounded-xl border-gray-200 text-sm font-mono"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Participants (libre)</label>
                    <textarea name="participants" rows="2" placeholder="Tous les enseignants de la classe, le PP, 2 délégués élèves, 1 délégué parent" class="w-full rounded-xl border-gray-200 text-sm"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-2 justify-end">
                <button type="button" @click="modal=false" class="px-4 py-2 text-sm font-bold text-gray-600">Annuler</button>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white text-sm font-bold rounded-xl">Planifier</button>
            </div>
        </form>
    </div>
</div>

@push('styles')<style>[x-cloak]{display:none!important}</style>@endpush
@endsection
