@extends('layouts.app')

@section('title', 'Affectation rapide')
@section('page-title', 'Affectation rapide')
@section('page-subtitle', 'Affecter un enseignant à plusieurs classes en une seule opération')

@section('content')
@include('partials.rh-admin-nav')

@php
    $oldTeacher = old('enseignant_id');
    $oldYear = old('annee_scolaire_id');
@endphp

<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
        <a href="{{ route('admin.rh.affectations.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">← Retour aux affectations</a>
        <a href="{{ route('admin.rh.affectations.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">Mode une par une</a>
    </div>

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-bold mb-1">Veuillez corriger :</p>
            <ul class="list-disc list-inside text-xs space-y-0.5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.rh.affectations.rapide.store') }}" class="space-y-6" id="bulk-affectation-form">
        @csrf

        <div class="rounded-2xl border border-blue-100 bg-blue-50 px-5 py-4 text-sm text-blue-900">
            <b>Mode rapide :</b> choisissez un enseignant, puis cochez les classes concernées pour chaque discipline. Ces affectations serviront ensuite de base à l’emploi du temps.
        </div>

        <div class="bg-white rounded-2xl border border-brand-100 shadow-card-brand p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Enseignant <span class="text-red-500">*</span></label>
                    <select name="enseignant_id" id="enseignant_id" required class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm outline-none">
                        <option value="" data-disciplines="">Sélectionner...</option>
                        @foreach($enseignants as $enseignant)
                            <option value="{{ $enseignant->id }}" data-disciplines="{{ e($enseignant->specialite ?? '') }}" @selected((string)$oldTeacher === (string)$enseignant->id)>
                                {{ $enseignant->nom_complet }}{{ $enseignant->specialite ? ' — '.$enseignant->specialite : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Année scolaire <span class="text-red-500">*</span></label>
                    <select name="annee_scolaire_id" required class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm outline-none">
                        <option value="">Sélectionner...</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id }}" @selected((string)$oldYear === (string)$annee->id || (!$oldYear && !empty($annee->active)))>{{ $annee->libelle }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gold-200 shadow-card-gold p-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-5">
                <div>
                    <h3 class="font-display text-lg font-extrabold text-gray-900">Classes à affecter</h3>
                    <p class="text-xs text-gray-500 mt-1">Les disciplines visibles sont filtrées selon la fiche de l’enseignant.</p>
                </div>
                <button type="button" id="toggle-all-visible" class="px-4 py-2 bg-gold-50 border border-gold-200 text-gold-800 text-xs font-bold rounded-xl">Tout cocher visible</button>
            </div>

            <div id="no-teacher-message" class="rounded-xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-400">
                Sélectionnez d’abord un enseignant pour afficher ses disciplines.
            </div>

            <div id="no-discipline-message" class="hidden rounded-xl border border-dashed border-amber-200 bg-amber-50 p-6 text-center text-sm text-amber-700">
                Cet enseignant n’a aucune discipline renseignée sur sa fiche. Ajoutez ses disciplines dans la fiche enseignant avant l’affectation rapide.
            </div>

            <div class="space-y-5" id="discipline-list">
                @foreach($matieres as $matiere)
                    <div class="discipline-card hidden rounded-2xl border border-gold-100 bg-gold-50/30 p-4" data-matiere-name="{{ e($matiere->nom) }}">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <h4 class="font-display text-base font-extrabold text-gray-900">{{ $matiere->nom }}</h4>
                            <span class="text-[11px] text-gold-700 font-bold">{{ $classes->count() }} classe(s)</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                            @foreach($classes as $classe)
                                @php
                                    $oldRow = old("affectations.{$matiere->id}.{$classe->id}", []);
                                @endphp
                                <label class="flex flex-col gap-2 rounded-xl border border-gray-200 bg-white p-3 hover:border-brand-200 transition-all">
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" name="affectations[{{ $matiere->id }}][{{ $classe->id }}][selected]" value="1" class="row-check rounded border-gray-300" @checked(!empty($oldRow['selected']))>
                                        <span class="text-sm font-extrabold text-gray-800">{{ $classe->nom }}</span>
                                        <span class="text-[11px] text-gray-400">{{ $classe->niveau->libelle ?? $classe->niveau->code ?? '' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 pl-6">
                                        <input type="number" step="0.5" min="0.5" max="60" name="affectations[{{ $matiere->id }}][{{ $classe->id }}][volume_horaire_hebdo]" value="{{ $oldRow['volume_horaire_hebdo'] ?? 2 }}" class="w-24 px-2 py-1.5 border rounded-lg text-xs font-bold">
                                        <span class="text-xs text-gray-500">h/semaine</span>
                                    </div>
                                    <div class="pl-6">
                                        <label class="inline-flex items-center gap-2 text-[11px] font-semibold text-gray-500">
                                            <input type="checkbox" name="affectations[{{ $matiere->id }}][{{ $classe->id }}][est_professeur_principal]" value="1" @checked(!empty($oldRow['est_professeur_principal']))>
                                            Professeur principal
                                        </label>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 pt-2">
            <a href="{{ route('admin.rh.affectations.index') }}" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">Annuler</a>
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow">Enregistrer les affectations</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const teacherSelect = document.getElementById('enseignant_id');
    const cards = Array.from(document.querySelectorAll('.discipline-card'));
    const noTeacher = document.getElementById('no-teacher-message');
    const noDiscipline = document.getElementById('no-discipline-message');
    const toggleAll = document.getElementById('toggle-all-visible');

    const normalize = (value) => (value || '').toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    const splitDisciplines = (value) => normalize(value).split(',').map(item => item.trim()).filter(Boolean);

    function refresh() {
        const selected = teacherSelect.options[teacherSelect.selectedIndex];
        const raw = selected ? selected.dataset.disciplines || '' : '';
        const disciplines = splitDisciplines(raw);
        let visible = 0;

        if (!teacherSelect.value) {
            cards.forEach(card => card.classList.add('hidden'));
            noTeacher.classList.remove('hidden');
            noDiscipline.classList.add('hidden');
            return;
        }

        noTeacher.classList.add('hidden');

        cards.forEach(card => {
            const name = normalize(card.dataset.matiereName || '');
            const ok = disciplines.some(discipline => name === discipline || name.includes(discipline) || discipline.includes(name));
            card.classList.toggle('hidden', !ok);
            if (ok) visible++;
        });

        noDiscipline.classList.toggle('hidden', visible > 0 || disciplines.length > 0);
    }

    teacherSelect.addEventListener('change', refresh);
    refresh();

    if (toggleAll) {
        toggleAll.addEventListener('click', function () {
            document.querySelectorAll('.discipline-card:not(.hidden) .row-check').forEach(check => check.checked = true);
        });
    }
})();
</script>
@endpush
