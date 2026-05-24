@extends('layouts.app')

@section('title', 'Nouvelle affectation')
@section('page-title', 'Nouvelle affectation')
@section('page-subtitle', 'Lier un enseignant à une classe et à une discipline déjà déclarée')

@section('content')
@include('partials.rh-admin-nav')

<div class="max-w-5xl mx-auto">
    <form method="POST" action="{{ route('admin.rh.affectations.store') }}" class="space-y-6">
        @csrf

        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('admin.rh.affectations.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour aux affectations
            </a>
        </div>

        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-bold mb-1">Veuillez corriger les erreurs :</p>
                <ul class="list-disc list-inside text-xs space-y-0.5">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-2xl border border-blue-100 bg-blue-50 px-5 py-4 text-sm text-blue-900">
            <b>Important :</b> ici, on n’ajoute pas une nouvelle matière. On précise seulement quelle discipline déjà rattachée à l’enseignant est affectée à une classe.
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand p-6">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-200/20 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-brand-100/60">
                <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center shadow-brand-glow">
                    <span class="font-display text-white font-extrabold text-sm">1</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Enseignant, classe & discipline</h3>
                    <p class="text-xs text-gray-500 mt-0.5">On affecte une discipline déclarée sur la fiche enseignant à une classe précise</p>
                </div>
            </div>

            <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Enseignant <span class="text-red-500">*</span></label>
                    <select name="enseignant_id" id="enseignant_id" required class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm cursor-pointer">
                        <option value="" data-disciplines="">Sélectionner...</option>
                        @foreach($enseignants as $enseignant)
                            <option value="{{ $enseignant->id }}" data-disciplines="{{ e($enseignant->specialite ?? '') }}" @selected(old('enseignant_id') == $enseignant->id)>
                                {{ $enseignant->nom_complet }}
                                @if($enseignant->specialite)
                                    — {{ $enseignant->specialite }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('enseignant_id')<p class="text-[11px] text-rose-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Classe <span class="text-red-500">*</span></label>
                    <select name="classe_id" required class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm cursor-pointer">
                        <option value="">Sélectionner...</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id }}" @selected(old('classe_id') == $classe->id)>{{ $classe->nom }}</option>
                        @endforeach
                    </select>
                    @error('classe_id')<p class="text-[11px] text-rose-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Discipline à affecter <span class="text-red-500">*</span></label>
                    <select name="matiere_id" id="matiere_id" required class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm cursor-pointer">
                        <option value="" data-matiere-name="">Sélectionner...</option>
                        @foreach($matieres as $matiere)
                            <option value="{{ $matiere->id }}" data-matiere-name="{{ e($matiere->nom) }}" @selected(old('matiere_id') == $matiere->id)>{{ $matiere->nom }}</option>
                        @endforeach
                    </select>
                    <p id="discipline-help" class="text-[11px] text-gray-500 mt-1">Après choix de l’enseignant, la liste est filtrée selon ses disciplines quand elles correspondent aux matières paramétrées.</p>
                    @error('matiere_id')<p class="text-[11px] text-rose-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Année scolaire <span class="text-red-500">*</span></label>
                    <select name="annee_scolaire_id" required class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm cursor-pointer">
                        <option value="">Sélectionner...</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id }}" @selected(old('annee_scolaire_id') == $annee->id)>{{ $annee->libelle }}</option>
                        @endforeach
                    </select>
                    @error('annee_scolaire_id')<p class="text-[11px] text-rose-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/40 rounded-2xl border border-gold-200/60 shadow-card-gold p-6">
            <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-gold-200/25 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-gold-200/60">
                <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-xl flex items-center justify-center shadow-gold-glow">
                    <span class="font-display text-white font-extrabold text-sm">2</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Paramètres de l'affectation</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Volume horaire et rôle dans la classe</p>
                </div>
            </div>

            <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Volume horaire hebdo</label>
                    <div class="relative">
                        <input type="number" step="0.5" min="0.5" max="60" name="volume_horaire_hebdo" value="{{ old('volume_horaire_hebdo', 2) }}" class="w-full px-3 py-2.5 pr-12 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-gold-600">h</span>
                    </div>
                    @error('volume_horaire_hebdo')<p class="text-[11px] text-rose-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-3 bg-white border border-gold-200 rounded-xl cursor-pointer hover:border-gold-300 transition-all">
                        <input type="checkbox" name="est_professeur_principal" value="1" @checked(old('est_professeur_principal')) class="w-4 h-4 rounded border-gold-300 text-gold-500 focus:ring-gold-200">
                        <span class="text-sm font-semibold text-gray-700">⭐ Professeur principal</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-white border border-gold-200 rounded-xl cursor-pointer hover:border-gold-300 transition-all">
                        <input type="checkbox" name="active" value="1" @checked(old('active', true)) class="w-4 h-4 rounded border-gold-300 text-gold-500 focus:ring-gold-200">
                        <span class="text-sm font-semibold text-gray-700">✅ Affectation active</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 pt-2">
            <a href="{{ route('admin.rh.affectations.index') }}" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">Annuler</a>
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Enregistrer
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const teacherSelect = document.getElementById('enseignant_id');
    const matterSelect = document.getElementById('matiere_id');
    const help = document.getElementById('discipline-help');

    if (!teacherSelect || !matterSelect) return;

    const normalize = (value) => (value || '')
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();

    const splitDisciplines = (value) => normalize(value)
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean);

    const filterMatieres = () => {
        const selectedTeacher = teacherSelect.options[teacherSelect.selectedIndex];
        const disciplines = splitDisciplines(selectedTeacher?.dataset?.disciplines || '');
        let visibleCount = 0;

        Array.from(matterSelect.options).forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const name = normalize(option.dataset.matiereName || option.textContent);
            const visible = disciplines.length === 0 || disciplines.some((discipline) => name === discipline || name.includes(discipline) || discipline.includes(name));
            option.hidden = !visible;
            if (visible) visibleCount++;
        });

        if (matterSelect.selectedOptions.length && matterSelect.selectedOptions[0].hidden) {
            matterSelect.value = '';
        }

        if (help) {
            help.textContent = disciplines.length === 0
                ? 'Cet enseignant n’a pas encore de disciplines renseignées : toutes les matières restent proposées.'
                : (visibleCount > 0 ? 'Liste filtrée selon les disciplines de l’enseignant.' : 'Aucune matière paramétrée ne correspond exactement aux disciplines de cet enseignant. Vérifiez les disciplines ou le paramétrage des matières.');
        }
    };

    teacherSelect.addEventListener('change', filterMatieres);
    filterMatieres();
})();
</script>
@endpush
