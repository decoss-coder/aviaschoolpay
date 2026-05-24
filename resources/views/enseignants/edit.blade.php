@extends('layouts.app')

@section('title', 'Modifier ' . $enseignant->nom_complet)
@section('page-title', 'Modifier l\'enseignant')
@section('page-subtitle', 'Compte utilisateur lié automatiquement')

@section('content')
@include('partials.rh-admin-nav')

<div class="max-w-5xl mx-auto">
    <form method="POST" action="{{ route('enseignants.update', $enseignant) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <a href="{{ route('enseignants.show', $enseignant) }}" class="text-sm font-semibold text-gray-500 hover:text-brand-600">← Retour à la fiche</a>

        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <b>Veuillez corriger :</b>
                <ul class="list-disc list-inside mt-1">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-5 py-4 text-sm text-emerald-900">
            Le compte utilisateur est géré automatiquement. Il n’y a plus de compte à sélectionner.
        </div>

        <div class="bg-white rounded-2xl border border-brand-100 p-6 shadow-card-brand">
            <h3 class="font-display text-lg font-extrabold text-gray-900 mb-4">Identité</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="text-xs font-bold uppercase">Nom *</label><input name="nom" value="{{ old('nom', $enseignant->nom) }}" required class="w-full px-3 py-2.5 border rounded-xl"></div>
                <div><label class="text-xs font-bold uppercase">Prénom *</label><input name="prenom" value="{{ old('prenom', $enseignant->prenom) }}" required class="w-full px-3 py-2.5 border rounded-xl"></div>
                <div><label class="text-xs font-bold uppercase">Sexe *</label><select name="sexe" required class="w-full px-3 py-2.5 border rounded-xl"><option value="M" {{ old('sexe', $enseignant->sexe) === 'M' ? 'selected' : '' }}>Masculin</option><option value="F" {{ old('sexe', $enseignant->sexe) === 'F' ? 'selected' : '' }}>Féminin</option></select></div>
                <div><label class="text-xs font-bold uppercase">Date de naissance</label><input type="date" name="date_naissance" value="{{ old('date_naissance', optional($enseignant->date_naissance)->format('Y-m-d')) }}" class="w-full px-3 py-2.5 border rounded-xl"></div>
                <div><label class="text-xs font-bold uppercase">Matricule MENA</label><input name="matricule_mena" value="{{ old('matricule_mena', $enseignant->matricule_mena) }}" class="w-full px-3 py-2.5 border rounded-xl"></div>
                <div><label class="text-xs font-bold uppercase">Photo</label><input type="file" name="photo" accept="image/*" class="w-full px-3 py-2.5 border rounded-xl"></div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-blue-100 p-6 shadow-card-blue">
            <h3 class="font-display text-lg font-extrabold text-gray-900 mb-4">Contact</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="text-xs font-bold uppercase">Téléphone *</label><input name="telephone" value="{{ old('telephone', $enseignant->telephone) }}" required class="w-full px-3 py-2.5 border rounded-xl"></div>
                <div><label class="text-xs font-bold uppercase">Téléphone 2</label><input name="telephone_2" value="{{ old('telephone_2', $enseignant->telephone_2) }}" class="w-full px-3 py-2.5 border rounded-xl"></div>
                <div class="md:col-span-2"><label class="text-xs font-bold uppercase">Email</label><input type="email" name="email" value="{{ old('email', $enseignant->email) }}" class="w-full px-3 py-2.5 border rounded-xl"></div>
                <div class="md:col-span-2"><label class="text-xs font-bold uppercase">Adresse</label><textarea name="adresse" rows="2" class="w-full px-3 py-2.5 border rounded-xl">{{ old('adresse', $enseignant->adresse) }}</textarea></div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gold-200 p-6 shadow-card-gold">
            <h3 class="font-display text-lg font-extrabold text-gray-900 mb-4">Matières et informations professionnelles</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="text-xs font-bold uppercase">Diplôme</label><input name="diplome_plus_eleve" value="{{ old('diplome_plus_eleve', $enseignant->diplome_plus_eleve) }}" class="w-full px-3 py-2.5 border rounded-xl"></div>
                <div><label class="text-xs font-bold uppercase">Statut *</label><select name="statut" required class="w-full px-3 py-2.5 border rounded-xl">@foreach($statutsDisponibles as $item)<option value="{{ $item }}" {{ old('statut', $enseignant->statut) === $item ? 'selected' : '' }}>{{ ucfirst($item) }}</option>@endforeach</select></div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold uppercase">Matières enseignées</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 mt-2">
                        @foreach($matieresDisponibles as $matiere)
                            <label class="flex items-center gap-2 rounded-xl border bg-gold-50/40 px-3 py-2 text-sm"><input type="checkbox" name="matieres[]" value="{{ $matiere }}" {{ in_array($matiere, $matieresSelectionnees ?? [], true) ? 'checked' : '' }}> {{ $matiere }}</label>
                        @endforeach
                    </div>
                </div>
                <div class="md:col-span-2"><label class="text-xs font-bold uppercase">Autre spécialité / précision</label><input name="specialite" value="{{ old('specialite', $enseignant->specialite) }}" class="w-full px-3 py-2.5 border rounded-xl"></div>
                <div><label class="text-xs font-bold uppercase">Date de prise de fonction</label><input type="date" name="date_prise_fonction" value="{{ old('date_prise_fonction', optional($enseignant->date_prise_fonction)->format('Y-m-d')) }}" class="w-full px-3 py-2.5 border rounded-xl"></div>
                <div><label class="text-xs font-bold uppercase">Salaire de base</label><input type="number" name="salaire_base" value="{{ old('salaire_base', $enseignant->salaire_base) }}" min="0" class="w-full px-3 py-2.5 border rounded-xl"></div>
                <div><label class="text-xs font-bold uppercase">Banque</label><input name="banque" value="{{ old('banque', $enseignant->banque) }}" class="w-full px-3 py-2.5 border rounded-xl"></div>
                <div><label class="text-xs font-bold uppercase">Numéro de compte</label><input name="numero_compte" value="{{ old('numero_compte', $enseignant->numero_compte) }}" class="w-full px-3 py-2.5 border rounded-xl"></div>
            </div>
        </div>

        <div class="flex justify-between">
            <a href="{{ route('enseignants.show', $enseignant) }}" class="px-5 py-2.5 bg-white border rounded-xl font-bold">Annuler</a>
            <button class="px-6 py-2.5 bg-brand-600 text-white rounded-xl font-bold">Enregistrer</button>
        </div>
    </form>
</div>
@endsection
