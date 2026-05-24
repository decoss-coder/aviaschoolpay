@extends('layouts.app')

@section('title', 'Nouvel enseignant')
@section('page-title', 'Créer un enseignant')
@section('page-subtitle', 'Le compte utilisateur sera créé automatiquement')

@section('content')
@include('partials.rh-admin-nav')

<div x-data="enseignantForm()" class="max-w-6xl mx-auto">
    <form method="POST" action="{{ route('enseignants.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('enseignants.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
                ← Retour à la liste
            </a>
        </div>

        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-bold mb-1">Merci de corriger les erreurs suivantes :</p>
                <ul class="list-disc list-inside text-[12px] space-y-0.5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-5 py-4 text-sm text-emerald-900">
            <b>Compte utilisateur automatique :</b> renseignez l’identité et le contact de l’enseignant. Le compte lié sera créé automatiquement, sans sélection manuelle.
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl border border-brand-100/60 shadow-card-brand p-6">
                    <h3 class="font-display text-base font-extrabold text-gray-900 mb-4">Identité</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nom <span class="text-red-500">*</span></label>
                            <input type="text" name="nom" x-model="nom" value="{{ old('nom') }}" required class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Prénom <span class="text-red-500">*</span></label>
                            <input type="text" name="prenom" x-model="prenom" value="{{ old('prenom') }}" required class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Sexe <span class="text-red-500">*</span></label>
                            <select name="sexe" x-model="sexe" required class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm outline-none">
                                <option value="">Choisir...</option>
                                <option value="M" {{ old('sexe') === 'M' ? 'selected' : '' }}>Masculin</option>
                                <option value="F" {{ old('sexe') === 'F' ? 'selected' : '' }}>Féminin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Date de naissance</label>
                            <input type="date" name="date_naissance" value="{{ old('date_naissance') }}" class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Matricule MENA</label>
                            <input type="text" name="matricule_mena" value="{{ old('matricule_mena') }}" maxlength="30" class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Photo</label>
                            <input type="file" name="photo" accept="image/*" class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm">
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-blue-100/60 shadow-card-blue p-6">
                    <h3 class="font-display text-base font-extrabold text-gray-900 mb-4">Contact</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Téléphone <span class="text-red-500">*</span></label>
                            <input type="text" name="telephone" value="{{ old('telephone') }}" required maxlength="20" class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Téléphone 2</label>
                            <input type="text" name="telephone_2" value="{{ old('telephone_2') }}" maxlength="20" class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="Optionnel" class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm outline-none">
                            <p class="text-[11px] text-gray-500 mt-1">Si l’email est vide, une adresse technique interne sera générée.</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Adresse</label>
                            <textarea name="adresse" rows="2" class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm outline-none">{{ old('adresse') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gold-200/60 shadow-card-gold p-6">
                    <h3 class="font-display text-base font-extrabold text-gray-900 mb-4">Matières et informations professionnelles</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Diplôme le plus élevé</label>
                            <input type="text" name="diplome_plus_eleve" value="{{ old('diplome_plus_eleve') }}" maxlength="100" class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Statut <span class="text-red-500">*</span></label>
                            <select name="statut" x-model="statut" required class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm outline-none">
                                @foreach($statutsDisponibles as $item)
                                    <option value="{{ $item }}" {{ old('statut', 'titulaire') === $item ? 'selected' : '' }}>{{ ucfirst($item) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-2">Matières enseignées</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                @foreach($matieresDisponibles as $matiere)
                                    <label class="flex items-center gap-2 rounded-xl border border-gold-100 bg-gold-50/40 px-3 py-2 text-sm font-semibold text-gray-700">
                                        <input type="checkbox" name="matieres[]" value="{{ $matiere }}" {{ in_array($matiere, $matieresSelectionnees ?? [], true) ? 'checked' : '' }} class="rounded border-gold-300 text-brand-600">
                                        {{ $matiere }}
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-[11px] text-gray-500 mt-2">Liste initiale extraite du bulletin : Français, Histoire-Géographie, Anglais, Espagnol, Mathématiques, Physique-Chimie, SVT, EDHC, EPS, Arts Plastiques, Lecture, Conduite.</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Autre spécialité / précision</label>
                            <input type="text" name="specialite" x-model="specialite" value="{{ old('specialite') }}" maxlength="255" class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Date de prise de fonction</label>
                            <input type="date" name="date_prise_fonction" value="{{ old('date_prise_fonction') }}" class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Salaire de base (FCFA)</label>
                            <input type="number" name="salaire_base" value="{{ old('salaire_base') }}" min="0" step="1" class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Banque</label>
                            <input type="text" name="banque" value="{{ old('banque') }}" maxlength="100" class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Numéro de compte</label>
                            <input type="text" name="numero_compte" value="{{ old('numero_compte') }}" maxlength="50" class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="sticky top-20 bg-gradient-to-br from-brand-600 via-brand-700 to-brand-800 rounded-2xl shadow-brand-glow p-5 text-white">
                    <p class="text-[10px] text-brand-100 font-bold uppercase tracking-[0.15em] mb-3">Aperçu</p>
                    <h3 class="font-display text-2xl font-extrabold leading-tight" x-text="(prenom || 'Prénom') + ' ' + (nom || 'Nom')"></h3>
                    <p class="text-[11px] text-brand-100 mt-2" x-text="specialite || 'Matières / spécialité'"></p>
                    <p class="text-[11px] text-brand-100 mt-1" x-text="statut || 'Statut non renseigné'"></p>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 pt-2">
            <a href="{{ route('enseignants.index') }}" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">Annuler</a>
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow">Créer l'enseignant</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function enseignantForm() {
    return {
        nom: @json(old('nom', '')),
        prenom: @json(old('prenom', '')),
        sexe: @json(old('sexe', '')),
        specialite: @json(old('specialite', '')),
        statut: @json(old('statut', 'titulaire')),
    }
}
</script>
@endpush
