@extends('layouts.app')

@section('title', 'Modifier ' . $eleve->prenom . ' ' . $eleve->nom)
@section('page-title', 'Modifier l\'élève')
@section('page-subtitle', $eleve->nom . ' ' . $eleve->prenom . ' — Matricule ' . $eleve->matricule_interne)

@section('content')
@php
    $parent = $eleve->parents->first();
    $parentNomComplet = old('parent_nom', $parent?->nom_complet ?? trim(($parent->prenom ?? '').' '.($parent->nom ?? '')));
    $parentLien = old('parent_lien', $parent->lien ?? $parent->lien_parente ?? '');
@endphp

<div x-data="eleveEditForm()" class="max-w-5xl mx-auto">
    <form method="POST" action="{{ route('eleves.update', $eleve) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="flex items-center justify-between gap-4 mb-4">
            <a href="{{ route('eleves.show', $eleve) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour à la fiche
            </a>
            <button type="button" @click="if(confirm('Radier cet élève ? Cette action peut être annulée.')) document.getElementById('delete-form').submit()" class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-red-200 text-red-600 text-[12px] font-bold rounded-lg hover:bg-red-50 transition-all">Radier l'élève</button>
        </div>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-4 text-sm font-semibold">Corrige les champs signalés avant d’enregistrer.</div>
        @endif

        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand p-6">
            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-brand-100/60">
                <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center shadow-brand-glow"><span class="font-display text-white font-extrabold text-sm">1</span></div>
                <div><h3 class="font-display text-base font-extrabold text-gray-900">Identité de l'élève</h3><p class="text-xs text-gray-500 mt-0.5">Informations personnelles</p></div>
            </div>
            <div class="relative grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div class="lg:col-span-3">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-2">Photo</label>
                    <div class="aspect-square bg-gradient-to-br from-brand-50 to-brand-100/50 border-2 border-dashed border-brand-200 rounded-2xl flex items-center justify-center overflow-hidden cursor-pointer hover:border-brand-400 transition-colors" @click="$refs.photoInput.click()">
                        <template x-if="photoPreview"><img :src="photoPreview" class="w-full h-full object-cover"></template>
                        <template x-if="!photoPreview">
                            @if($eleve->photo_path)
                                <img src="{{ asset('storage/' . $eleve->photo_path) }}" class="w-full h-full object-cover">
                            @else
                                <span class="font-display text-4xl font-extrabold text-brand-700">{{ strtoupper(substr($eleve->prenom,0,1)) }}{{ strtoupper(substr($eleve->nom,0,1)) }}</span>
                            @endif
                        </template>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-2 text-center">Cliquez pour changer</p>
                    <input type="file" name="photo" x-ref="photoInput" @change="previewPhoto($event)" accept="image/*" class="hidden">
                </div>
                <div class="lg:col-span-9 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nom <span class="text-red-500">*</span></label><input type="text" name="nom" value="{{ old('nom', $eleve->nom) }}" required class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm"></div>
                    <div><label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Prénom(s) <span class="text-red-500">*</span></label><input type="text" name="prenom" value="{{ old('prenom', $eleve->prenom) }}" required class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm"></div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Sexe <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="relative flex items-center justify-center gap-2 px-3 py-2.5 bg-white border border-brand-100 rounded-xl cursor-pointer hover:border-blue-300 transition-all has-[:checked]:bg-blue-50 has-[:checked]:border-blue-300"><input type="radio" name="sexe" value="M" required @checked(old('sexe', $eleve->sexe)==='M') class="sr-only peer"><span class="text-sm font-semibold text-gray-700 peer-checked:text-blue-700">Garçon</span></label>
                            <label class="relative flex items-center justify-center gap-2 px-3 py-2.5 bg-white border border-brand-100 rounded-xl cursor-pointer hover:border-pink-300 transition-all has-[:checked]:bg-pink-50 has-[:checked]:border-pink-300"><input type="radio" name="sexe" value="F" @checked(old('sexe', $eleve->sexe)==='F') class="sr-only peer"><span class="text-sm font-semibold text-gray-700 peer-checked:text-pink-700">Fille</span></label>
                        </div>
                    </div>
                    <div><label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Date de naissance</label><input type="date" name="date_naissance" value="{{ old('date_naissance', $eleve->date_naissance?->format('Y-m-d')) }}" class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm"></div>
                    <div><label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Lieu de naissance</label><input type="text" name="lieu_naissance" value="{{ old('lieu_naissance', $eleve->lieu_naissance) }}" class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm"></div>
                    <div><label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nationalité</label><select name="nationalite" class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer"><option value="">Sélectionner...</option>@foreach(($nationalites ?? ['Ivoirienne','Française','Burkinabé','Malienne','Ghanéenne','Autre']) as $nat)<option value="{{ $nat }}" @selected(old('nationalite', $eleve->nationalite)===$nat)>{{ $nat }}</option>@endforeach</select></div>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-blue-50/30 rounded-2xl border border-blue-100/60 shadow-card-blue p-6">
            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-blue-100/60"><div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center"><span class="font-display text-white font-extrabold text-sm">2</span></div><div><h3 class="font-display text-base font-extrabold text-gray-900">Contact & adresse</h3></div></div>
            <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2"><label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Adresse</label><input type="text" name="adresse" value="{{ old('adresse', $eleve->adresse) }}" class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none shadow-sm"></div>
                <div><label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Téléphone</label><input type="tel" name="telephone" value="{{ old('telephone', $eleve->contact_urgence_tel) }}" class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none shadow-sm"></div>
                <div><label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Email</label><input type="email" name="email" value="{{ old('email', $eleve->email) }}" class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none shadow-sm"></div>
            </div>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-violet-50/30 rounded-2xl border border-violet-100/60 shadow-card-violet p-6">
            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-violet-100/60"><div class="w-10 h-10 bg-gradient-to-br from-violet-400 to-purple-600 rounded-xl flex items-center justify-center"><span class="font-display text-white font-extrabold text-sm">3</span></div><div><h3 class="font-display text-base font-extrabold text-gray-900">Parent / Tuteur principal</h3></div></div>
            <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nom complet</label><input type="text" name="parent_nom" value="{{ $parentNomComplet }}" class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none shadow-sm"></div>
                <div><label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Lien de parenté</label><select name="parent_lien" class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none shadow-sm cursor-pointer"><option value="">Sélectionner...</option><option value="pere" @selected($parentLien==='pere')>Père</option><option value="mere" @selected($parentLien==='mere')>Mère</option><option value="tuteur" @selected($parentLien==='tuteur')>Tuteur</option><option value="autre" @selected($parentLien==='autre')>Autre</option></select></div>
                <div><label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Téléphone</label><input type="tel" name="parent_telephone" value="{{ old('parent_telephone', $parent->telephone ?? '') }}" class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none shadow-sm"></div>
                <div><label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Email</label><input type="email" name="parent_email" value="{{ old('parent_email', $parent->email ?? '') }}" class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none shadow-sm"></div>
            </div>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/40 rounded-2xl border border-gold-200/60 shadow-card-gold p-6">
            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-gold-200/60"><div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-xl flex items-center justify-center shadow-gold-glow"><span class="font-display text-white font-extrabold text-sm">4</span></div><div><h3 class="font-display text-base font-extrabold text-gray-900">Statut, classe & matricule</h3><p class="text-xs text-gray-500 mt-0.5">Les frais sont gérés dans Finances → Tarifs.</p></div></div>
            <div class="relative grid grid-cols-1 md:grid-cols-3 gap-4">
                <div><label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Statut élève <span class="text-red-500">*</span></label><select name="statut_eleve" required class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none shadow-sm cursor-pointer"><option value="">Sélectionner...</option><option value="AFF" @selected(old('statut_eleve', $eleve->statut_eleve)==='AFF')>AFF — Affecté</option><option value="NAFF" @selected(old('statut_eleve', $eleve->statut_eleve)==='NAFF')>NAFF — Non affecté</option></select></div>
                <div><label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Classe</label><select name="classe_id" class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none shadow-sm cursor-pointer"><option value="">— Aucune classe / pré-inscrit —</option>@foreach($classes ?? [] as $classe)<option value="{{ $classe->id }}" @selected((string) old('classe_id', $eleve->classe_id) === (string) $classe->id)>{{ $classe->nom }} — {{ $classe->niveau->libelle ?? $classe->niveau->code ?? '' }}</option>@endforeach</select></div>
                <div><label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Matricule DESPS</label><input type="text" name="matricule_desps" value="{{ old('matricule_desps', $eleve->matricule_desps) }}" placeholder="ex : 15195226N" class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-mono focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none shadow-sm"><p class="text-[10px] text-gray-400 mt-1">Matricule interne : <b>{{ $eleve->matricule_interne }}</b></p></div>
            </div>
            <div class="mt-4 rounded-xl border border-brand-100 bg-brand-50/50 p-3 text-[12px] text-brand-800 font-semibold">Source des frais : <a href="{{ route('finances.tarifs') }}" class="underline font-extrabold">Finances → Tarifs</a>. Aucun montant n'est saisi sur la fiche élève.</div>
        </div>

        <div class="flex items-center justify-between gap-3 pt-2"><a href="{{ route('eleves.show', $eleve) }}" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">Annuler</a><button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">Enregistrer les modifications</button></div>
    </form>

    <form id="delete-form" method="POST" action="{{ route('eleves.destroy', $eleve) }}" class="hidden">@csrf @method('DELETE')<input type="hidden" name="confirm_delete" value="1"></form>
</div>
@endsection

@push('scripts')
<script>
function eleveEditForm(){return{photoPreview:null,previewPhoto(event){const file=event.target.files[0];if(!file)return;const reader=new FileReader();reader.onload=(e)=>this.photoPreview=e.target.result;reader.readAsDataURL(file);}}}
</script>
@endpush
