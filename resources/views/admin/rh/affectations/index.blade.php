@extends('layouts.app')

@section('title', 'Affectations')
@section('page-title', 'Affectations enseignants')
@section('page-subtitle', 'Classes, matières et volumes horaires')

@section('content')
@include('partials.rh-admin-nav')

@php
    $affectationGroups = $affectations->getCollection()
        ->groupBy(fn ($affectation) => $affectation->enseignant_id ?: 'sans-enseignant')
        ->values();
@endphp

<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
            <p class="font-extrabold mb-2">Corrige les champs signalés.</p>
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <div class="xl:col-span-2 bg-white border border-brand-100 rounded-2xl p-5 shadow-card-brand">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-lg font-extrabold text-gray-900">Affectation groupée</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Sélectionne plusieurs enseignants et plusieurs classes. Le système crée une affectation pour chaque combinaison enseignant × classe.
                    </p>
                </div>
                <span class="inline-flex rounded-full bg-brand-50 text-brand-700 px-3 py-1 text-xs font-extrabold">EDT IA</span>
            </div>

            <form method="POST" action="{{ route('admin.rh.affectations.bulk-store') }}" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @csrf

                <div>
                    <label class="block text-xs font-extrabold text-brand-700 uppercase mb-2">Enseignants à affecter</label>
                    <select name="enseignant_ids[]" multiple size="10"
                            class="w-full rounded-xl border border-brand-100 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-200">
                        @foreach($enseignants as $enseignant)
                            <option value="{{ $enseignant->id }}" @selected(collect(old('enseignant_ids', []))->contains($enseignant->id))>
                                {{ $enseignant->nom_complet ?? trim(($enseignant->prenom ?? '').' '.($enseignant->nom ?? '')) }} — {{ $enseignant->specialite ?: 'Sans spécialité' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-400">Ctrl/Cmd + clic pour sélectionner plusieurs enseignants.</p>
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-brand-700 uppercase mb-2">Classes concernées</label>
                    <select name="classe_ids[]" multiple size="10"
                            class="w-full rounded-xl border border-brand-100 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-brand-200">
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id }}" @selected(collect(old('classe_ids', []))->contains($classe->id))>
                                {{ $classe->nom }} @if($classe->niveau) — {{ $classe->niveau->libelle }} @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-400">Exemple : sélectionner toutes les 6e pour un même enseignant/matière.</p>
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-brand-700 uppercase mb-2">Matière</label>
                    <select name="matiere_id" class="w-full rounded-xl border border-brand-100 bg-white px-3 py-2.5 text-sm shadow-sm">
                        <option value="">Sélectionner une matière</option>
                        @foreach($matieres as $matiere)
                            <option value="{{ $matiere->id }}" @selected(old('matiere_id') == $matiere->id)>
                                {{ $matiere->nom }} @if($matiere->code) ({{ $matiere->code }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-brand-700 uppercase mb-2">Année scolaire</label>
                    <select name="annee_scolaire_id" class="w-full rounded-xl border border-brand-100 bg-white px-3 py-2.5 text-sm shadow-sm">
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id }}" @selected((int) old('annee_scolaire_id', $anneeCourante?->id) === (int) $annee->id)>
                                {{ $annee->libelle }} @if($annee->en_cours) — en cours @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-brand-700 uppercase mb-2">Volume hebdomadaire</label>
                    <input type="number" step="0.5" min="0.5" max="20" name="volume_horaire_hebdo" value="{{ old('volume_horaire_hebdo', 2) }}"
                           class="w-full rounded-xl border border-brand-100 bg-white px-3 py-2.5 text-sm shadow-sm">
                </div>

                <div class="flex items-center gap-4 pt-7">
                    <label class="inline-flex items-center gap-2 text-sm font-bold text-gray-700">
                        <input type="checkbox" name="active" value="1" checked class="rounded border-brand-200 text-brand-600">
                        Active
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm font-bold text-gray-700">
                        <input type="checkbox" name="est_professeur_principal" value="1" class="rounded border-brand-200 text-brand-600">
                        Professeur principal
                    </label>
                </div>

                <div class="lg:col-span-2 flex justify-end">
                    <button class="inline-flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white rounded-xl text-sm font-extrabold shadow-brand-glow">
                        Créer les affectations groupées
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-red-50 border border-red-200 rounded-2xl p-5 shadow-sm">
            <h2 class="text-lg font-extrabold text-red-800">Vider les affectations</h2>
            <p class="text-sm text-red-700 mt-2">
                Supprime uniquement les affectations de cette école. Les enseignants, classes, matières et élèves restent conservés.
            </p>

            <form method="POST" action="{{ route('admin.rh.affectations.clear') }}" class="mt-4 space-y-3" onsubmit="return confirm('Confirmer la suppression des affectations sélectionnées ?')">
                @csrf
                @method('DELETE')

                <div>
                    <label class="block text-xs font-extrabold text-red-700 uppercase mb-2">Année à vider</label>
                    <select name="annee_scolaire_id" class="w-full rounded-xl border border-red-200 bg-white px-3 py-2.5 text-sm shadow-sm">
                        <option value="">Toutes les années</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id }}" @selected((int)($anneeCourante?->id) === (int)$annee->id)>
                                {{ $annee->libelle }} @if($annee->en_cours) — en cours @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-red-700 uppercase mb-2">Confirmation</label>
                    <input type="text" name="confirm_clear" placeholder="VIDER-AFFECTATIONS"
                           class="w-full rounded-xl border border-red-200 bg-white px-3 py-2.5 text-sm shadow-sm">
                </div>

                <button class="w-full px-4 py-2.5 rounded-xl bg-red-600 text-white text-sm font-extrabold hover:bg-red-700">
                    Vider les affectations
                </button>
            </form>
        </div>
    </div>

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
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-brand-100 text-brand-700 rounded-xl text-sm font-bold shadow-sm">
            Affectation simple
        </a>
    </div>

    <div class="bg-white border border-brand-100 rounded-2xl overflow-hidden shadow-card-brand">
        <div class="px-5 py-4 border-b border-brand-100 bg-brand-50/40 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
            <div>
                <h2 class="text-lg font-extrabold text-gray-900">Liste groupée par enseignant</h2>
                <p class="text-sm text-gray-500">Clique sur une ligne pour afficher les classes, matières, volumes et actions.</p>
            </div>
            <span class="text-xs font-extrabold text-brand-700 uppercase">{{ $affectationGroups->count() }} enseignant(s) sur cette page</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-brand-50/60 border-b border-brand-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Enseignant</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Affectations</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Classes</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Matières</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">Année</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">VH total</th>
                        <th class="px-4 py-3 text-left text-xs font-extrabold text-brand-700 uppercase">État</th>
                        <th class="px-4 py-3 text-right text-xs font-extrabold text-brand-700 uppercase">Détails</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-50">
                    @forelse($affectationGroups as $groupIndex => $group)
                        @php
                            $first = $group->first();
                            $enseignant = $first?->enseignant;
                            $enseignantName = $enseignant?->nom_complet ?? trim(($enseignant->prenom ?? '').' '.($enseignant->nom ?? '')) ?: '—';
                            $detailId = 'affectation-detail-'.$groupIndex.'-'.$first?->enseignant_id;
                            $classesList = $group->pluck('classe.nom')->filter()->unique()->values();
                            $matieresList = $group->pluck('matiere.nom')->filter()->unique()->values();
                            $anneesList = $group->pluck('anneeScolaire.libelle')->filter()->unique()->values();
                            $activeCount = $group->where('active', true)->count();
                            $inactiveCount = $group->count() - $activeCount;
                            $totalVolume = $group->sum(fn ($a) => (float) $a->volume_horaire_hebdo);
                        @endphp

                        <tr class="cursor-pointer hover:bg-brand-50/40 transition" data-affectation-toggle="{{ $detailId }}">
                            <td class="px-4 py-4 font-extrabold text-gray-900 whitespace-nowrap">
                                {{ $enseignantName }}
                                @if($enseignant?->specialite)
                                    <div class="text-xs font-semibold text-gray-400 mt-1">{{ $enseignant->specialite }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-gray-700">
                                <span class="inline-flex rounded-full bg-brand-50 text-brand-700 px-2.5 py-1 text-xs font-extrabold">
                                    {{ $group->count() }} ligne(s)
                                </span>
                            </td>
                            <td class="px-4 py-4 text-gray-700 max-w-xs">
                                {{ $classesList->take(4)->join(', ') ?: '—' }}
                                @if($classesList->count() > 4)
                                    <span class="text-gray-400">+{{ $classesList->count() - 4 }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-gray-700 max-w-xs">
                                {{ $matieresList->take(3)->join(', ') ?: '—' }}
                                @if($matieresList->count() > 3)
                                    <span class="text-gray-400">+{{ $matieresList->count() - 3 }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-gray-700">{{ $anneesList->join(', ') ?: '—' }}</td>
                            <td class="px-4 py-4 text-gray-700 font-bold">{{ number_format($totalVolume, 1, ',', ' ') }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full bg-emerald-100 text-emerald-700 px-2.5 py-1 text-xs font-bold">{{ $activeCount }} active(s)</span>
                                @if($inactiveCount > 0)
                                    <span class="inline-flex rounded-full bg-gray-100 text-gray-600 px-2.5 py-1 text-xs font-bold ml-1">{{ $inactiveCount }} inactive(s)</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right">
                                <button type="button" data-affectation-label="{{ $detailId }}" class="px-3 py-2 bg-white border border-brand-100 rounded-lg text-xs font-bold text-brand-700">
                                    Voir détails
                                </button>
                            </td>
                        </tr>

                        <tr id="{{ $detailId }}" class="hidden bg-brand-50/20">
                            <td colspan="8" class="px-4 py-4">
                                <div class="rounded-2xl border border-brand-100 bg-white overflow-hidden">
                                    <table class="w-full text-sm">
                                        <thead class="bg-white border-b border-brand-100">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-extrabold text-brand-700 uppercase">Classe</th>
                                                <th class="px-3 py-2 text-left text-xs font-extrabold text-brand-700 uppercase">Matière</th>
                                                <th class="px-3 py-2 text-left text-xs font-extrabold text-brand-700 uppercase">Année</th>
                                                <th class="px-3 py-2 text-left text-xs font-extrabold text-brand-700 uppercase">VH / semaine</th>
                                                <th class="px-3 py-2 text-left text-xs font-extrabold text-brand-700 uppercase">PP</th>
                                                <th class="px-3 py-2 text-left text-xs font-extrabold text-brand-700 uppercase">État</th>
                                                <th class="px-3 py-2 text-right text-xs font-extrabold text-brand-700 uppercase">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-brand-50">
                                            @foreach($group as $affectation)
                                                <tr class="hover:bg-brand-50/30">
                                                    <td class="px-3 py-2 font-bold text-gray-800">{{ $affectation->classe->nom ?? '—' }}</td>
                                                    <td class="px-3 py-2 text-gray-700">{{ $affectation->matiere->nom ?? '—' }}</td>
                                                    <td class="px-3 py-2 text-gray-700">{{ $affectation->anneeScolaire->libelle ?? '—' }}</td>
                                                    <td class="px-3 py-2 text-gray-700">{{ number_format((float) $affectation->volume_horaire_hebdo, 1, ',', ' ') }}</td>
                                                    <td class="px-3 py-2">
                                                        @if($affectation->est_professeur_principal)
                                                            <span class="inline-flex rounded-full bg-blue-100 text-blue-700 px-2.5 py-1 text-xs font-bold">Oui</span>
                                                        @else
                                                            <span class="text-gray-400 text-sm">Non</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $affectation->active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                                            {{ $affectation->active ? 'Active' : 'Inactive' }}
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        <div class="flex justify-end gap-2" onclick="event.stopPropagation()">
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
                                            @endforeach
                                        </tbody>
                                    </table>
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

<script>
    document.querySelectorAll('[data-affectation-toggle]').forEach((row) => {
        row.addEventListener('click', () => {
            const targetId = row.getAttribute('data-affectation-toggle');
            const detailRow = document.getElementById(targetId);
            const label = document.querySelector(`[data-affectation-label="${targetId}"]`);

            if (!detailRow) return;

            detailRow.classList.toggle('hidden');
            if (label) {
                label.textContent = detailRow.classList.contains('hidden') ? 'Voir détails' : 'Masquer détails';
            }
        });
    });
</script>
@endsection
