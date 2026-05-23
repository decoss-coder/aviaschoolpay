@extends('layouts.app')

@section('title', 'Assistant IA Emploi du temps')
@section('page-title', 'Assistant IA Emploi du temps')
@section('page-subtitle', 'Référentiel, politique privée, vacataires et contraintes sélectionnables')

@section('content')
@include('partials.rh-admin-nav')

<div class="space-y-6">
    <form method="POST" action="{{ route('emploi-du-temps.assistant.scenarios.store') }}" class="bg-white rounded-2xl border border-brand-100 p-6 shadow-card-brand space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Année scolaire</label>
                <select name="annee_scolaire_id" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                    @foreach($annees as $annee)
                        <option value="{{ $annee->id }}">{{ $annee->libelle }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Mode</label>
                <select name="mode_generation" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                    <option value="strict_officiel">Officiel strict</option>
                    <option value="prive_equilibre">Privé équilibré</option>
                    <option value="prive_contraint">Privé contraint</option>
                    <option value="provisoire_vacataires">Provisoire vacataires</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Politique privée</label>
                <select name="policy_id" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                    <option value="">Aucune</option>
                    @foreach($policies as $policy)
                        <option value="{{ $policy->id }}">{{ $policy->nom }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Portée</label>
                <select name="portee" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl">
                    <option value="globale">Globale</option>
                    <option value="classes_selectionnees">Classes sélectionnées</option>
                    <option value="enseignants_selectionnes">Enseignants sélectionnés</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Nom du scénario</label>
            <input type="text" name="nom" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl" placeholder="Ex : Génération provisoire vacataires trimestre 1">
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div>
                <p class="text-xs font-bold uppercase text-gray-600 mb-2">Classes</p>
                <div class="max-h-64 overflow-auto border border-brand-100 rounded-xl p-3 space-y-2">
                    @foreach($classes as $classe)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="scope_classes[]" value="{{ $classe->id }}">
                            <span>{{ $classe->nom }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="text-xs font-bold uppercase text-gray-600 mb-2">Enseignants</p>
                <div class="max-h-64 overflow-auto border border-brand-100 rounded-xl p-3 space-y-2">
                    @foreach($enseignants as $enseignant)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="scope_enseignants[]" value="{{ $enseignant->id }}">
                            <span>{{ $enseignant->nom_complet }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="text-xs font-bold uppercase text-gray-600 mb-2">Contraintes disponibles</p>
                <div class="max-h-64 overflow-auto border border-brand-100 rounded-xl p-3 space-y-2 text-sm">
                    @foreach($constraints as $constraint)
                        <div class="flex items-start justify-between gap-2 border-b border-gray-100 pb-2">
                            <div>
                                <p class="font-bold text-gray-800">{{ $constraint->libelle }}</p>
                                <p class="text-xs text-gray-500">{{ $constraint->categorie }}</p>
                            </div>
                            <span class="text-xs text-gray-400">{{ $constraint->code }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button class="px-6 py-2.5 rounded-xl bg-brand-600 text-white font-bold">
                Créer le scénario
            </button>
        </div>
    </form>
</div>
@endsection