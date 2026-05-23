@extends('layouts.app')

@section('title', 'Nouvel enseignant')
@section('page-title', 'Créer un enseignant')
@section('page-subtitle', 'Ajouter un nouveau membre de l’équipe pédagogique')

@section('content')
@include('partials.rh-admin-nav')
<div x-data="enseignantForm()" class="max-w-6xl mx-auto">
    <form method="POST" action="{{ route('enseignants.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Retour --}}
        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('enseignants.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour à la liste
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">

                {{-- ═══════════════ SECTION 1 : COMPTE & IDENTITÉ (brand) ═══════════════ --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand p-6">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-200/20 rounded-full blur-3xl"></div>

                    <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-brand-100/60">
                        <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center shadow-brand-glow">
                            <span class="font-display text-white font-extrabold text-sm">1</span>
                        </div>
                        <div>
                            <h3 class="font-display text-base font-extrabold text-gray-900">Compte & identité</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Rattachement utilisateur et état civil</p>
                        </div>
                    </div>

                    <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Compte utilisateur <span class="text-red-500">*</span></label>
                            <select name="user_id" required class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm cursor-pointer">
                                <option value="">Sélectionner un utilisateur...</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name ?? (($user->prenom ?? '') . ' ' . ($user->nom ?? '')) ?: ($user->email ?? ('Utilisateur #' . $user->id)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nom <span class="text-red-500">*</span></label>
                            <input type="text" name="nom" x-model="nom" value="{{ old('nom') }}" required class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Prénom <span class="text-red-500">*</span></label>
                            <input type="text" name="prenom" x-model="prenom" value="{{ old('prenom') }}" required class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Sexe <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="relative flex items-center justify-center gap-2 px-3 py-2.5 bg-white border border-brand-100 rounded-xl cursor-pointer hover:border-blue-300 transition-all has-[:checked]:bg-gradient-to-br has-[:checked]:from-blue-50 has-[:checked]:to-blue-100/50 has-[:checked]:border-blue-300 has-[:checked]:shadow-sm">
                                    <input type="radio" name="sexe" value="M" x-model="sexe" required class="sr-only peer" {{ old('sexe') === 'M' ? 'checked' : '' }}>
                                    <span class="w-5 h-5 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-xs font-bold">♂</span>
                                    <span class="text-sm font-semibold text-gray-700 peer-checked:text-blue-700">Masculin</span>
                                </label>
                                <label class="relative flex items-center justify-center gap-2 px-3 py-2.5 bg-white border border-brand-100 rounded-xl cursor-pointer hover:border-pink-300 transition-all has-[:checked]:bg-gradient-to-br has-[:checked]:from-pink-50 has-[:checked]:to-pink-100/50 has-[:checked]:border-pink-300 has-[:checked]:shadow-sm">
                                    <input type="radio" name="sexe" value="F" x-model="sexe" class="sr-only peer" {{ old('sexe') === 'F' ? 'checked' : '' }}>
                                    <span class="w-5 h-5 rounded-full bg-gradient-to-br from-pink-400 to-pink-600 flex items-center justify-center text-white text-xs font-bold">♀</span>
                                    <span class="text-sm font-semibold text-gray-700 peer-checked:text-pink-700">Féminin</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Date de naissance</label>
                            <input type="date" name="date_naissance" value="{{ old('date_naissance') }}" class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Matricule MENA</label>
                            <input type="text" name="matricule_mena" value="{{ old('matricule_mena') }}" maxlength="30" placeholder="MENA-XXXX" class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm font-mono placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Photo</label>
                            <input type="file" name="photo" accept="image/*" class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm shadow-sm file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-brand-100 file:text-brand-700 hover:file:bg-brand-200 cursor-pointer">
                        </div>
                    </div>
                </div>

                {{-- ═══════════════ SECTION 2 : CONTACT (bleu) ═══════════════ --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-blue-50/30 rounded-2xl border border-blue-100/60 shadow-card-blue p-6">
                    <div class="absolute -top-10 -left-10 w-40 h-40 bg-blue-200/20 rounded-full blur-3xl"></div>

                    <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-blue-100/60">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-sm shadow-blue-500/30">
                            <span class="font-display text-white font-extrabold text-sm">2</span>
                        </div>
                        <div>
                            <h3 class="font-display text-base font-extrabold text-gray-900">Contact</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Coordonnées de l'enseignant</p>
                        </div>
                    </div>

                    <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Téléphone <span class="text-red-500">*</span></label>
                            <input type="text" name="telephone" value="{{ old('telephone') }}" required maxlength="20" placeholder="+225 XX XX XX XX XX" class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Téléphone 2</label>
                            <input type="text" name="telephone_2" value="{{ old('telephone_2') }}" maxlength="20" placeholder="+225 XX XX XX XX XX" class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="prof@exemple.com" class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Adresse</label>
                            <textarea name="adresse" rows="2" placeholder="Quartier, commune, ville" class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm resize-none">{{ old('adresse') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- ═══════════════ SECTION 3 : INFOS PRO (or) ═══════════════ --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/40 rounded-2xl border border-gold-200/60 shadow-card-gold p-6">
                    <div class="absolute -bottom-10 -right-10 w-48 h-48 bg-gold-200/25 rounded-full blur-3xl"></div>

                    <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-gold-200/60">
                        <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-xl flex items-center justify-center shadow-gold-glow">
                            <span class="font-display text-white font-extrabold text-sm">3</span>
                        </div>
                        <div>
                            <h3 class="font-display text-base font-extrabold text-gray-900">Informations professionnelles</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Diplôme, statut, rémunération</p>
                        </div>
                    </div>

                    <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Diplôme le plus élevé</label>
                            <input type="text" name="diplome_plus_eleve" value="{{ old('diplome_plus_eleve') }}" maxlength="100" placeholder="Master, Licence, BTS..." class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm placeholder:text-gray-400 focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Spécialité</label>
                            <input type="text" name="specialite" x-model="specialite" list="liste-specialites" value="{{ old('specialite') }}" maxlength="100" placeholder="Mathématiques, Français..." class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm placeholder:text-gray-400 focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                            <datalist id="liste-specialites">
                                @foreach($specialitesDisponibles as $item)
                                    <option value="{{ $item }}"></option>
                                @endforeach
                            </datalist>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Statut <span class="text-red-500">*</span></label>
                            <select name="statut" x-model="statut" required class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm cursor-pointer">
                                @foreach($statutsDisponibles as $item)
                                    <option value="{{ $item }}" {{ old('statut', 'titulaire') === $item ? 'selected' : '' }}>
                                        {{ ucfirst($item) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Date de prise de fonction</label>
                            <input type="date" name="date_prise_fonction" value="{{ old('date_prise_fonction') }}" class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Salaire de base (FCFA)</label>
                            <div class="relative">
                                <input type="number" name="salaire_base" value="{{ old('salaire_base') }}" min="0" step="1" placeholder="0" class="w-full px-3 py-2.5 pr-14 bg-white border border-gold-200 rounded-xl text-sm font-bold text-gold-700 placeholder:text-gray-400 focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                                <span class="absolute right-3 top-2.5 text-xs font-bold text-gold-600">FCFA</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Banque</label>
                            <input type="text" name="banque" value="{{ old('banque') }}" maxlength="100" class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm placeholder:text-gray-400 focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Numéro de compte</label>
                            <input type="text" name="numero_compte" value="{{ old('numero_compte') }}" maxlength="50" class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-mono placeholder:text-gray-400 focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ APERÇU (carte brand sticky) ═══════════════ --}}
            <div class="lg:col-span-1">
                <div class="sticky top-20">
                    <div class="relative overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-brand-800 rounded-2xl shadow-brand-glow p-5 text-white">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-gold-300 to-gold-500"></div>
                        <div class="absolute -top-6 -right-6 w-24 h-24 bg-gold-400/20 rounded-full blur-xl"></div>
                        <p class="relative text-[10px] text-brand-100 font-bold uppercase tracking-[0.15em] mb-3">Aperçu</p>
                        <h3 class="relative font-display text-2xl font-extrabold leading-tight" x-text="(prenom || 'Prénom') + ' ' + (nom || 'Nom')"></h3>
                        <p class="relative text-[11px] text-brand-100 mt-2" x-text="specialite || 'Spécialité non renseignée'"></p>
                        <p class="relative text-[11px] text-brand-100 mt-1" x-text="statut || 'Statut non renseigné'"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 pt-2">
            <a href="{{ route('enseignants.index') }}"
               class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">
                Annuler
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                Créer l'enseignant
            </button>
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
