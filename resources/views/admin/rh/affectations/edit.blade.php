@extends('layouts.app')

@section('title', 'Modifier affectation')
@section('page-title', 'Modifier l\'affectation')
@section('page-subtitle', 'Mise à jour des paramètres')

@section('content')
@include('partials.rh-admin-nav')

<div class="max-w-5xl mx-auto">
    <form method="POST" action="{{ route('admin.rh.affectations.update', $affectation) }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Retour --}}
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

        {{-- ═══════════════ SECTION 1 : SÉLECTION (brand) ═══════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand p-6">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-200/20 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-brand-100/60">
                <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center shadow-brand-glow">
                    <span class="font-display text-white font-extrabold text-sm">1</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Enseignant, classe & matière</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Trio fondamental de l'affectation</p>
                </div>
            </div>

            <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Enseignant <span class="text-red-500">*</span></label>
                    <select name="enseignant_id" required class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm cursor-pointer">
                        @foreach($enseignants as $enseignant)
                            <option value="{{ $enseignant->id }}" @selected(old('enseignant_id', $affectation->enseignant_id) == $enseignant->id)>{{ $enseignant->nom_complet }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Classe <span class="text-red-500">*</span></label>
                    <select name="classe_id" required class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm cursor-pointer">
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id }}" @selected(old('classe_id', $affectation->classe_id) == $classe->id)>{{ $classe->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Matière <span class="text-red-500">*</span></label>
                    <select name="matiere_id" required class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm cursor-pointer">
                        @foreach($matieres as $matiere)
                            <option value="{{ $matiere->id }}" @selected(old('matiere_id', $affectation->matiere_id) == $matiere->id)>{{ $matiere->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Année scolaire <span class="text-red-500">*</span></label>
                    <select name="annee_scolaire_id" required class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm cursor-pointer">
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id }}" @selected(old('annee_scolaire_id', $affectation->annee_scolaire_id) == $annee->id)>{{ $annee->libelle }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- ═══════════════ SECTION 2 : PARAMÈTRES (or) ═══════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/40 rounded-2xl border border-gold-200/60 shadow-card-gold p-6">
            <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-gold-200/25 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-gold-200/60">
                <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-xl flex items-center justify-center shadow-gold-glow">
                    <span class="font-display text-white font-extrabold text-sm">2</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Paramètres de l'affectation</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Volume horaire et rôle</p>
                </div>
            </div>

            <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Volume horaire hebdo</label>
                    <div class="relative">
                        <input type="number" step="0.5" min="0.5" max="60" name="volume_horaire_hebdo" value="{{ old('volume_horaire_hebdo', $affectation->volume_horaire_hebdo) }}"
                               class="w-full px-3 py-2.5 pr-12 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-gold-600">h</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-3 bg-white border border-gold-200 rounded-xl cursor-pointer hover:border-gold-300 transition-all">
                        <input type="checkbox" name="est_professeur_principal" value="1" @checked(old('est_professeur_principal', $affectation->est_professeur_principal)) class="w-4 h-4 rounded border-gold-300 text-gold-500 focus:ring-gold-200">
                        <span class="text-sm font-semibold text-gray-700">⭐ Professeur principal</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-white border border-gold-200 rounded-xl cursor-pointer hover:border-gold-300 transition-all">
                        <input type="checkbox" name="active" value="1" @checked(old('active', $affectation->active)) class="w-4 h-4 rounded border-gold-300 text-gold-500 focus:ring-gold-200">
                        <span class="text-sm font-semibold text-gray-700">✅ Affectation active</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 pt-2">
            <a href="{{ route('admin.rh.affectations.index') }}"
               class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">
                Annuler
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Mettre à jour
            </button>
        </div>
    </form>
</div>
@endsection
