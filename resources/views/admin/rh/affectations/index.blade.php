@extends('layouts.app')

@section('title', 'Affectations')
@section('page-title', 'Affectations enseignants')
@section('page-subtitle', 'Classes, matières et volumes horaires')

@section('content')
@include('partials.rh-admin-nav')

<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Enseignant, classe, matière..."
                   class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm shadow-sm">

            <select name="annee_scolaire_id" onchange="this.form.submit()"
                    class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm shadow-sm">
                <option value="">Toutes les années</option>
                @foreach($annees as $annee)
                    <option value="{{ $annee->id }}" @selected(request('annee_scolaire_id') == $annee->id)>
                        {{ $annee->libelle }}
                    </option>
                @endforeach
            </select>

            <select name="active" onchange="this.form.submit()"
                    class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm shadow-sm">
                <option value="">Tous états</option>
                <option value="1" @selected(request('active') === '1')>Actives</option>
                <option value="0" @selected(request('active') === '0')>Inactives</option>
            </select>

            <button class="px-4 py-2.5 bg-white border border-brand-100 rounded-xl text-sm font-bold shadow-sm">
                Filtrer
            </button>
        </form>

        <a href="{{ route('admin.rh.affectations.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white rounded-xl text-sm font-bold shadow-brand-glow">
            Nouvelle affectation
        </a>
    </div>

    <div class="bg-white border border-brand-100 rounded-2xl overflow-hidden shadow-card-brand">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-brand-50/60 border-b border-brand-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Enseignant</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Classe</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Matière</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Année</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">VH / semaine</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">PP</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">État</th>
                        <th class="px-4 py-3 text-right text-xs font-extrabold text-brand-700 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-50">
                    @forelse($affectations as $affectation)
                        <tr class="hover:bg-brand-50/30">
                            <td class="px-4 py-3 font-bold text-gray-900">{{ $affectation->enseignant->nom_complet ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $affectation->classe->nom ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $affectation->matiere->nom ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $affectation->anneeScolaire->libelle ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ number_format((float) $affectation->volume_horaire_hebdo, 1, ',', ' ') }}</td>
                            <td class="px-4 py-3">
                                @if($affectation->est_professeur_principal)
                                    <span class="inline-flex rounded-full bg-blue-100 text-blue-700 px-2.5 py-1 text-xs font-bold">Oui</span>
                                @else
                                    <span class="text-gray-400 text-sm">Non</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $affectation->active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $affectation->active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.rh.affectations.edit', $affectation) }}"
                                       class="px-3 py-2 bg-white border border-brand-100 rounded-lg text-xs font-bold text-brand-700">
                                        Modifier
                                    </a>

                                    <form action="{{ route('admin.rh.affectations.destroy', $affectation) }}" method="POST" onsubmit="return confirm('Supprimer cette affectation ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="px-3 py-2 bg-white border border-red-100 rounded-lg text-xs font-bold text-red-700">
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-gray-400">Aucune affectation trouvée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-4 border-t border-brand-100">
            {{ $affectations->links() }}
        </div>
    </div>
</div>
@endsection