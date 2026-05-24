@extends('layouts.app')

@section('title', 'Statut élève requis')
@section('page-title', 'Statut élève requis')
@section('page-subtitle', 'Choisir AFF ou NAFF avant de continuer')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="fixed inset-0 bg-gray-950/40 backdrop-blur-sm z-40"></div>

    <div class="relative z-50 bg-white rounded-3xl border border-amber-200 shadow-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-amber-50 via-white to-brand-50 p-6 border-b border-amber-100">
            <div class="flex items-start gap-4">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-400 to-gold-500 flex items-center justify-center shadow-gold-glow text-white font-extrabold text-xl">!</div>
                <div class="min-w-0">
                    <h2 class="font-display text-2xl font-extrabold text-gray-900">Statut obligatoire avant de continuer</h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Pour calculer correctement l’inscription, la scolarité et les paiements, il faut préciser si l’élève est affecté ou non affecté.
                    </p>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-5">
            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                <p class="text-[11px] uppercase tracking-wider text-gray-500 font-bold">Élève concerné</p>
                <p class="font-display text-xl font-extrabold text-gray-900 mt-1">{{ $eleve->prenom }} {{ $eleve->nom }}</p>
                <p class="text-sm text-gray-500 mt-1">
                    Matricule interne : <b>{{ $eleve->matricule_interne }}</b>
                    @if($eleve->matricule_desps)
                        · DESPS : <b>{{ $eleve->matricule_desps }}</b>
                    @endif
                </p>
            </div>

            <form method="POST" action="{{ route('eleves.statut-required.update', $eleve) }}" class="space-y-5">
                @csrf
                @method('PATCH')
                <input type="hidden" name="redirect" value="{{ $redirect }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="cursor-pointer rounded-2xl border-2 border-emerald-100 bg-emerald-50/50 p-5 hover:border-emerald-300 transition-all has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-100">
                        <input type="radio" name="statut_eleve" value="AFF" required class="sr-only">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-extrabold">AFF</div>
                            <div>
                                <p class="font-extrabold text-gray-900">Affecté</p>
                                <p class="text-xs text-gray-600 mt-1">L’élève paie uniquement les frais d’inscription selon la grille tarifaire.</p>
                            </div>
                        </div>
                    </label>

                    <label class="cursor-pointer rounded-2xl border-2 border-amber-100 bg-amber-50/50 p-5 hover:border-amber-300 transition-all has-[:checked]:border-amber-500 has-[:checked]:bg-amber-100">
                        <input type="radio" name="statut_eleve" value="NAFF" required class="sr-only">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-600 text-white flex items-center justify-center font-extrabold">NAFF</div>
                            <div>
                                <p class="font-extrabold text-gray-900">Non affecté</p>
                                <p class="text-xs text-gray-600 mt-1">L’élève paie les frais d’inscription et la scolarité annuelle.</p>
                            </div>
                        </div>
                    </label>
                </div>

                @error('statut_eleve')
                    <p class="text-sm text-red-600 font-semibold">{{ $message }}</p>
                @enderror

                <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900 font-semibold">
                    Ce choix mettra à jour les calculs sur la fiche élève, le paiement, le point inscription/scolarité et les impayés.
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('eleves.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-700 text-sm font-bold hover:bg-gray-50">Retour à la liste</a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-brand-500 to-brand-700 text-white text-sm font-extrabold shadow-brand-glow">
                        Enregistrer le statut et continuer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
