@extends('layouts.app')

@section('title', 'Pointages admin')
@section('page-title', 'Supervision des pointages')
@section('page-subtitle', 'Contrôle des scans et des présences enseignants')

@section('content')
@include('partials.rh-admin-nav')

<div class="space-y-6">
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
        <div class="bg-white border border-brand-100 rounded-2xl p-4 shadow-card-brand">
            <p class="text-xs font-bold uppercase text-gray-500">Total</p>
            <p class="mt-2 text-3xl font-extrabold text-gray-900">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white border border-emerald-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-emerald-600">Présents</p>
            <p class="mt-2 text-3xl font-extrabold text-emerald-700">{{ $stats['present'] }}</p>
        </div>
        <div class="bg-white border border-amber-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-amber-600">Retards</p>
            <p class="mt-2 text-3xl font-extrabold text-amber-700">{{ $stats['retard'] }}</p>
        </div>
        <div class="bg-white border border-red-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-red-600">Absents</p>
            <p class="mt-2 text-3xl font-extrabold text-red-700">{{ $stats['absent'] }}</p>
        </div>
        <div class="bg-white border border-orange-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-orange-600">Hors zone</p>
            <p class="mt-2 text-3xl font-extrabold text-orange-700">{{ $stats['hors_zone'] }}</p>
        </div>
        <div class="bg-white border border-violet-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-violet-600">Cahiers IA OK</p>
            <p class="mt-2 text-3xl font-extrabold text-violet-700">{{ $stats['cahier_valides'] ?? 0 }}</p>
            <p class="text-[10px] text-violet-500 mt-1">{{ $stats['cahier_envoyes'] ?? 0 }} photo(s)</p>
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
        <select name="statut" class="px-3 py-2.5 border border-brand-100 rounded-xl bg-white">
            <option value="">Tous statuts</option>
            <option value="present" @selected(request('statut') === 'present')>Présent</option>
            <option value="retard" @selected(request('statut') === 'retard')>Retard</option>
            <option value="absent" @selected(request('statut') === 'absent')>Absent</option>
            <option value="hors_zone" @selected(request('statut') === 'hors_zone')>Hors zone</option>
        </select>
        <select name="type_scan" class="px-3 py-2.5 border border-brand-100 rounded-xl bg-white">
            <option value="">Tous types</option>
            <option value="arrivee" @selected(request('type_scan') === 'arrivee')>Arrivée</option>
            <option value="depart" @selected(request('type_scan') === 'depart')>Départ</option>
        </select>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Recherche..." class="px-3 py-2.5 border border-brand-100 rounded-xl bg-white">
        <select name="cahier" class="px-3 py-2.5 border border-brand-100 rounded-xl bg-white">
            <option value="">Tous cahiers</option>
            <option value="valide" @selected(request('cahier') === 'valide')>Cahier validé IA</option>
            <option value="non_valide" @selected(request('cahier') === 'non_valide')>Cahier non validé</option>
            <option value="manquant" @selected(request('cahier') === 'manquant')>Sans photo</option>
        </select>
        <button class="px-4 py-2.5 bg-white border border-brand-100 rounded-xl text-sm font-bold shadow-sm">Filtrer</button>
    </form>

    <div class="bg-white border border-brand-100 rounded-2xl overflow-hidden shadow-card-brand">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-brand-50/60 border-b border-brand-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Enseignant</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Heure</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Méthode</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Statut</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Salle</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Cahier IA</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Alertes</th>
                        <th class="px-4 py-3 text-right text-xs font-extrabold text-brand-700 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-50">
                    @forelse($pointages as $pointage)
                        <tr class="hover:bg-brand-50/30">
                            <td class="px-4 py-3 font-bold text-gray-900">{{ $pointage->enseignant->nom_complet ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $pointage->heure_scan }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $pointage->type_scan_libelle }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $pointage->methode_libelle }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold
                                    {{ $pointage->statut === 'present' ? 'bg-emerald-100 text-emerald-700' :
                                       ($pointage->statut === 'retard' ? 'bg-amber-100 text-amber-700' :
                                       ($pointage->statut === 'hors_zone' ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700')) }}">
                                    {{ $pointage->statut_libelle }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $pointage->salle->nom ?? '—' }}</td>
                            <td class="px-4 py-3">@include('pointage.partials.cahier-texte-badge', ['pointage' => $pointage])</td>
                            <td class="px-4 py-3 text-gray-700">{{ $pointage->alertes->count() }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.rh.pointages.show', $pointage) }}"
                                   class="px-3 py-2 bg-white border border-brand-100 rounded-lg text-xs font-bold text-brand-700">
                                    Détail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-gray-400">Aucun pointage trouvé.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-4 border-t border-brand-100">
            {{ $pointages->links() }}
        </div>
    </div>
</div>
@endsection