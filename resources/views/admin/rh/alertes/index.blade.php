@extends('layouts.app')

@section('title', 'Alertes RH')
@section('page-title', 'Alertes de pointage')
@section('page-subtitle', 'Traitement et supervision des anomalies')

@section('content')
@include('partials.rh-admin-nav')

<div class="space-y-6">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-brand-100 rounded-2xl p-4 shadow-card-brand">
            <p class="text-xs font-bold uppercase text-gray-500">Total</p>
            <p class="mt-2 text-3xl font-extrabold text-gray-900">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white border border-red-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-red-600">Non lues</p>
            <p class="mt-2 text-3xl font-extrabold text-red-700">{{ $stats['non_lues'] }}</p>
        </div>
        <div class="bg-white border border-amber-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-amber-600">À traiter</p>
            <p class="mt-2 text-3xl font-extrabold text-amber-700">{{ $stats['non_traitees'] }}</p>
        </div>
        <div class="bg-white border border-red-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-red-700">Critiques</p>
            <p class="mt-2 text-3xl font-extrabold text-red-800">{{ $stats['critiques'] }}</p>
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-2">
        <input type="date" name="date" value="{{ request('date', $date) }}" class="px-3 py-2.5 border border-brand-100 rounded-xl bg-white">
        <select name="enseignant_id" class="px-3 py-2.5 border border-brand-100 rounded-xl bg-white">
            <option value="">Tous enseignants</option>
            @foreach($enseignants as $enseignant)
                <option value="{{ $enseignant->id }}" @selected(request('enseignant_id') == $enseignant->id)>
                    {{ $enseignant->nom_complet }}
                </option>
            @endforeach
        </select>
        <select name="gravite" class="px-3 py-2.5 border border-brand-100 rounded-xl bg-white">
            <option value="">Toutes gravités</option>
            <option value="info" @selected(request('gravite') === 'info')>Info</option>
            <option value="warning" @selected(request('gravite') === 'warning')>Warning</option>
            <option value="critique" @selected(request('gravite') === 'critique')>Critique</option>
        </select>
        <select name="traitee" class="px-3 py-2.5 border border-brand-100 rounded-xl bg-white">
            <option value="">Tous états</option>
            <option value="0" @selected(request('traitee') === '0')>Non traitées</option>
            <option value="1" @selected(request('traitee') === '1')>Traitées</option>
        </select>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Recherche..." class="px-3 py-2.5 border border-brand-100 rounded-xl bg-white">
        <button class="px-4 py-2.5 bg-white border border-brand-100 rounded-xl text-sm font-bold shadow-sm">Filtrer</button>
    </form>

    <div class="bg-white border border-brand-100 rounded-2xl overflow-hidden shadow-card-brand">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-brand-50/60 border-b border-brand-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Enseignant</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Gravité</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Message</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">État</th>
                        <th class="px-4 py-3 text-right text-xs font-extrabold text-brand-700 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-50">
                    @forelse($alertes as $alerte)
                        <tr class="hover:bg-brand-50/30">
                            <td class="px-4 py-3 font-bold text-gray-900">{{ $alerte->enseignant->nom_complet ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $alerte->type_alerte_libelle }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold
                                    {{ $alerte->gravite === 'critique' ? 'bg-red-100 text-red-700' :
                                       ($alerte->gravite === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700') }}">
                                    {{ $alerte->gravite_libelle }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $alerte->message }}</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $alerte->lue ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $alerte->lue ? 'Lue' : 'Non lue' }}
                                    </span>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $alerte->traitee ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $alerte->traitee ? 'Traitée' : 'À traiter' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.rh.alertes.show', $alerte) }}"
                                       class="px-3 py-2 bg-white border border-brand-100 rounded-lg text-xs font-bold text-brand-700">
                                        Voir
                                    </a>

                                    @if(!$alerte->lue)
                                        <form method="POST" action="{{ route('admin.rh.alertes.lire', $alerte) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="px-3 py-2 bg-white border border-blue-100 rounded-lg text-xs font-bold text-blue-700">
                                                Marquer lue
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-400">Aucune alerte trouvée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-4 border-t border-brand-100">
            {{ $alertes->links() }}
        </div>
    </div>
</div>
@endsection