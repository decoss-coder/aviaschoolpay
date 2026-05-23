{{-- 
    IMPORTANT : Pour que ce formulaire fonctionne, le controller EleveWebController@edit
    doit passer les variables suivantes :

    public function edit(Request $request, $id) {
        $etab = $request->user()->etablissement;
        $annee = $etab->anneesScolaires()->where('en_cours', true)->first();

        $eleve = \App\Models\Eleve::where('etablissement_id', $etab->id)
            ->with(['parents', 'classe.niveau'])
            ->findOrFail($id);

        $classes = collect();
        if ($etab && $annee) {
            $classes = \App\Models\Classe::where('etablissement_id', $etab->id)
                ->where('annee_scolaire_id', $annee->id)
                ->with('niveau')
                ->orderBy('niveau_id')
                ->orderBy('nom')
                ->get();
        }

        $nationalites = ['Ivoirienne', 'Française', 'Burkinabé', 'Malienne', 'Ghanéenne', 'Autre'];
        return view('eleves.edit', compact('eleve', 'classes', 'nationalites'));
    }
--}}
@extends('layouts.app')

@section('title', 'Modifier ' . $eleve->prenom . ' ' . $eleve->nom)
@section('page-title', 'Modifier l\'élève')
@section('page-subtitle', $eleve->nom . ' ' . $eleve->prenom . ' — Matricule ' . $eleve->matricule_interne)

@section('content')
@php
    $parent = $eleve->parents->first();
    $classeActuelle = $eleve->classe;
    $estPreInscrit = ($eleve->statut ?? null) === 'pre_inscrit';

    $classesCollection = collect($classes ?? []);

    $classesParNiveau = $classesCollection
        ->sortBy(function ($c) {
            $ordre = $c->niveau->ordre ?? 999;
            $niveau = $c->niveau->libelle ?? ($c->niveau->code ?? 'Sans niveau');
            $nomClasse = $c->nom ?? '';
            return sprintf('%03d-%s-%s', $ordre, $niveau, $nomClasse);
        })
        ->groupBy(function ($c) {
            return $c->niveau->libelle ?? ($c->niveau->code ?? 'Sans niveau');
        });

    $parentNomComplet = old(
        'parent_nom',
        $parent?->nom_complet
            ?? trim(($parent->prenom ?? '') . ' ' . ($parent->nom ?? ''))
    );

    $parentLien = old('parent_lien', $parent->lien ?? $parent->lien_parente ?? '');
@endphp

<div x-data="eleveEditForm()" class="max-w-5xl mx-auto">

    <form method="POST" action="{{ route('eleves.update', $eleve) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- HEADER AVEC ACTIONS DANGER --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="flex items-center justify-between gap-4 mb-4">
            <a href="{{ route('eleves.show', $eleve) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour à la fiche
            </a>

            <button type="button" @click="if(confirm('Radier cet élève ? Cette action peut être annulée.')) document.getElementById('delete-form').submit()"
                    class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-red-200 text-red-600 text-[12px] font-bold rounded-lg hover:bg-red-50 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                Radier l'élève
            </button>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- BANDEAU STATUT PRÉ-INSCRIT (si applicable) --}}
        {{-- ════════════════════════════════════════════════════ --}}
        @if($estPreInscrit)
        <div class="relative overflow-hidden bg-gradient-to-r from-gold-50 via-gold-100/40 to-gold-50 border border-gold-200 rounded-2xl p-4 mb-6 shadow-card-gold">
            <div class="absolute -top-4 -right-4 w-20 h-20 bg-gold-200/40 rounded-full blur-2xl"></div>
            <div class="relative flex items-start gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-xl flex items-center justify-center flex-shrink-0 shadow-gold-glow">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-extrabold text-gold-800">Élève pré-inscrit</p>
                    <p class="text-[12px] text-gold-700 mt-0.5">Cet élève a été importé mais pas encore officiellement inscrit. Il passera au statut « inscrit » dès le premier paiement de scolarité.</p>
                </div>
            </div>
        </div>
        @endif

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- SECTION 1 : PHOTO + IDENTITÉ --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand p-6">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-200/20 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-brand-100/60">
                <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center shadow-brand-glow">
                    <span class="font-display text-white font-extrabold text-sm">1</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Identité de l'élève</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Informations personnelles et état civil</p>
                </div>
            </div>

            <div class="relative grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div class="lg:col-span-3">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-2">Photo</label>
                    <div class="relative">
                        <div class="aspect-square bg-gradient-to-br from-brand-50 to-brand-100/50 border-2 border-dashed border-brand-200 rounded-2xl flex items-center justify-center overflow-hidden cursor-pointer hover:border-brand-400 transition-colors group"
                             @click="$refs.photoInput.click()">
                            <template x-if="photoPreview">
                                <img :src="photoPreview" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!photoPreview">
                                @if($eleve->photo_path)
                                    <img src="{{ asset('storage/' . $eleve->photo_path) }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br {{ $eleve->sexe === 'F' ? 'from-pink-400 to-pink-600' : 'from-blue-400 to-blue-600' }} flex items-center justify-center">
                                        <span class="font-display text-4xl font-extrabold text-white">
                                            {{ strtoupper(substr($eleve->prenom, 0, 1)) }}{{ strtoupper(substr($eleve->nom, 0, 1)) }}
                                        </span>
                                    </div>
                                @endif
                            </template>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-2 text-center">Cliquez pour changer</p>
                        <input type="file" name="photo" x-ref="photoInput" @change="previewPhoto($event)" accept="image/*" class="hidden">
                    </div>
                </div>

                <div class="lg:col-span-9 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nom <span class="text-red-500">*</span></label>
                        <input type="text" name="nom" value="{{ old('nom', $eleve->nom) }}" required
                               class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Prénom(s) <span class="text-red-500">*</span></label>
                        <input type="text" name="prenom" value="{{ old('prenom', $eleve->prenom) }}" required
                               class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Sexe <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="relative flex items-center justify-center gap-2 px-3 py-2.5 bg-white border border-brand-100 rounded-xl cursor-pointer hover:border-blue-300 transition-all has-[:checked]:bg-gradient-to-br has-[:checked]:from-blue-50 has-[:checked]:to-blue-100/50 has-[:checked]:border-blue-300">
                                <input type="radio" name="sexe" value="M" {{ old('sexe', $eleve->sexe) === 'M' ? 'checked' : '' }} class="sr-only peer" required>
                                <span class="w-5 h-5 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-xs font-bold">♂</span>
                                <span class="text-sm font-semibold text-gray-700 peer-checked:text-blue-700">Garçon</span>
                            </label>
                            <label class="relative flex items-center justify-center gap-2 px-3 py-2.5 bg-white border border-brand-100 rounded-xl cursor-pointer hover:border-pink-300 transition-all has-[:checked]:bg-gradient-to-br has-[:checked]:from-pink-50 has-[:checked]:to-pink-100/50 has-[:checked]:border-pink-300">
                                <input type="radio" name="sexe" value="F" {{ old('sexe', $eleve->sexe) === 'F' ? 'checked' : '' }} class="sr-only peer">
                                <span class="w-5 h-5 rounded-full bg-gradient-to-br from-pink-400 to-pink-600 flex items-center justify-center text-white text-xs font-bold">♀</span>
                                <span class="text-sm font-semibold text-gray-700 peer-checked:text-pink-700">Fille</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Date de naissance <span class="text-gray-400 normal-case">(optionnel)</span>
                        </label>
                        <input type="date" name="date_naissance" value="{{ old('date_naissance', $eleve->date_naissance?->format('Y-m-d')) }}"
                               class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Lieu de naissance</label>
                        <input type="text" name="lieu_naissance" value="{{ old('lieu_naissance', $eleve->lieu_naissance) }}"
                               class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nationalité</label>
                        <select name="nationalite"
                                class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                            <option value="">Sélectionner...</option>
                            @foreach(($nationalites ?? ['Ivoirienne', 'Française', 'Burkinabé', 'Malienne', 'Ghanéenne', 'Autre']) as $nat)
                                <option value="{{ $nat }}" {{ old('nationalite', $eleve->nationalite) === $nat ? 'selected' : '' }}>{{ $nat }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- SECTION 2 : CONTACT --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-blue-50/30 rounded-2xl border border-blue-100/60 shadow-card-blue p-6">
            <div class="absolute -top-10 -left-10 w-40 h-40 bg-blue-200/20 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-blue-100/60">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-sm shadow-blue-500/30">
                    <span class="font-display text-white font-extrabold text-sm">2</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Contact & adresse</h3>
                </div>
            </div>

            <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Adresse</label>
                    <input type="text" name="adresse" value="{{ old('adresse', $eleve->adresse) }}"
                           class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none shadow-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Téléphone</label>
                    <input type="tel" name="telephone" value="{{ old('telephone', $eleve->contact_urgence_tel) }}"
                           class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none shadow-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email', $eleve->email) }}"
                           class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none shadow-sm">
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- SECTION 3 : PARENT --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-violet-50/30 rounded-2xl border border-violet-100/60 shadow-card-violet p-6">
            <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-violet-200/20 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-violet-100/60">
                <div class="w-10 h-10 bg-gradient-to-br from-violet-400 to-purple-600 rounded-xl flex items-center justify-center shadow-sm shadow-violet-500/30">
                    <span class="font-display text-white font-extrabold text-sm">3</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Parent / Tuteur principal</h3>
                    <p class="text-xs text-violet-600 mt-0.5">Un compte parent est créé automatiquement (mot de passe initial : 0000).</p>
                    @if($estPreInscrit && !$parent)
                        <p class="text-xs text-gold-600 mt-0.5">À compléter avant l'inscription officielle</p>
                    @endif
                </div>
            </div>

            <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Nom complet
                        @if(!$estPreInscrit)<span class="text-red-500">*</span>@endif
                    </label>
                    <input type="text" name="parent_nom" value="{{ $parentNomComplet }}" {{ $estPreInscrit ? '' : 'required' }}
                           class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none shadow-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Lien de parenté
                        @if(!$estPreInscrit)<span class="text-red-500">*</span>@endif
                    </label>
                    <select name="parent_lien" {{ $estPreInscrit ? '' : 'required' }}
                            class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none shadow-sm cursor-pointer">
                        <option value="">Sélectionner...</option>
                        <option value="pere" {{ $parentLien === 'pere' ? 'selected' : '' }}>Père</option>
                        <option value="mere" {{ $parentLien === 'mere' ? 'selected' : '' }}>Mère</option>
                        <option value="tuteur" {{ $parentLien === 'tuteur' ? 'selected' : '' }}>Tuteur légal</option>
                        <option value="oncle" {{ $parentLien === 'oncle' ? 'selected' : '' }}>Oncle</option>
                        <option value="tante" {{ $parentLien === 'tante' ? 'selected' : '' }}>Tante</option>
                        <option value="autre" {{ $parentLien === 'autre' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Téléphone
                        @if(!$estPreInscrit)<span class="text-red-500">*</span>@endif
                    </label>
                    <input type="tel" name="parent_telephone" value="{{ old('parent_telephone', $parent->telephone ?? '') }}" {{ $estPreInscrit ? '' : 'required' }}
                           class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none shadow-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Email</label>
                    <input type="email" name="parent_email" value="{{ old('parent_email', $parent->email ?? '') }}"
                           class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none shadow-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Profession</label>
                    <input type="text" name="parent_profession" value="{{ old('parent_profession', $parent->profession ?? '') }}"
                           class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none shadow-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">CNI / Pièce d'identité</label>
                    <input type="text" name="parent_cni" value="{{ old('parent_cni', $parent->cni ?? '') }}"
                           class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none shadow-sm">
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- SECTION 4 : INSCRIPTION & CLASSE --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/40 rounded-2xl border border-gold-200/60 shadow-card-gold p-6">
            <div class="absolute -top-10 -right-10 w-48 h-48 bg-gold-200/25 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-5 pb-4 border-b border-gold-200/60">
                <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-xl flex items-center justify-center shadow-gold-glow">
                    <span class="font-display text-white font-extrabold text-sm">4</span>
                </div>
                <div class="flex-1">
                    <h3 class="font-display text-base font-extrabold text-gray-900">Inscription & classe</h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        @if($classeActuelle)
                            Actuellement dans <span class="font-bold text-brand-700">{{ $classeActuelle->nom }}</span>
                            @if($classeActuelle->niveau)
                                <span class="text-brand-600">({{ $classeActuelle->niveau->libelle ?? $classeActuelle->niveau->code }})</span>
                            @endif
                        @elseif($estPreInscrit)
                            Pas encore affecté à une classe
                        @else
                            Aucune classe définie
                        @endif
                    </p>
                </div>
            </div>

            @if($classeActuelle)
            @php
                $effectifActuel = (int) ($classeActuelle->effectif ?? 0);
                $capaciteActuelle = (int) ($classeActuelle->capacite ?? 0);
            @endphp
            <div class="relative overflow-hidden bg-gradient-to-br from-brand-50 via-white to-brand-50/40 border border-brand-200/60 rounded-xl p-4 mb-5">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-brand-400 via-brand-500 to-brand-600"></div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center shadow-brand-glow flex-shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.15em] mb-0.5">Classe actuelle</p>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-display text-lg font-extrabold text-gray-900">{{ $classeActuelle->nom }}</span>
                            @if($classeActuelle->niveau)
                                <span class="text-sm font-bold text-brand-600">
                                    {{ $classeActuelle->niveau->libelle ?? $classeActuelle->niveau->code }}
                                </span>
                            @endif
                            <span class="inline-flex items-center text-[11px] font-bold text-brand-700 bg-brand-100 border border-brand-200/60 px-2 py-0.5 rounded-full">
                                @if($capaciteActuelle > 0)
                                    {{ $effectifActuel }}/{{ $capaciteActuelle }} élèves
                                @else
                                    {{ $effectifActuel }} élève(s)
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        {{ $classeActuelle ? 'Changer de classe' : 'Affecter à une classe' }}
                        @if(!$estPreInscrit)<span class="text-red-500">*</span>@endif
                    </label>

                    <select name="classe_id" {{ $estPreInscrit ? '' : 'required' }}
                            class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none shadow-sm cursor-pointer">
                        @if($estPreInscrit)
                            <option value="">— Aucune classe (rester pré-inscrit) —</option>
                        @else
                            <option value="">Sélectionner une classe...</option>
                        @endif

                        @forelse($classesParNiveau as $niveauLibelle => $classesDuNiveau)
                            <optgroup label="{{ $niveauLibelle }}">
                                @foreach($classesDuNiveau as $classe)
                                    @php
                                        $effectif = (int) ($classe->effectif ?? 0);
                                        $capacite = (int) ($classe->capacite ?? 0);
                                        $estActuelle = (int) old('classe_id', $eleve->classe_id) === (int) $classe->id;
                                        $estPleine = $capacite > 0 && $effectif >= $capacite && !$estActuelle;
                                    @endphp
                                    <option value="{{ $classe->id }}"
                                            {{ (int) old('classe_id', $eleve->classe_id) === (int) $classe->id ? 'selected' : '' }}
                                            {{ $estPleine ? 'disabled' : '' }}>
                                        {{ $classe->nom }}
                                        @if($capacite > 0)
                                            ({{ $effectif }}/{{ $capacite }} places)
                                        @else
                                            ({{ $effectif }} élève(s))
                                        @endif
                                        @if($estPleine) — COMPLÈTE @endif
                                        @if($estActuelle) — Classe actuelle @endif
                                    </option>
                                @endforeach
                            </optgroup>
                        @empty
                            <option value="" disabled>Aucune classe disponible</option>
                        @endforelse
                    </select>

                    @if($classesCollection->isEmpty())
                        <p class="text-[11px] text-gold-700 font-medium mt-1.5">
                            <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            Aucune classe créée.
                            <a href="{{ route('classes.create') }}" class="font-bold underline">Créer une classe</a>
                        </p>
                    @endif
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Matricule DESPS <span class="text-gray-400 normal-case">(optionnel)</span>
                    </label>
                    <input type="text" name="matricule_desps" value="{{ old('matricule_desps', $eleve->matricule_desps) }}"
                           placeholder="ex : 15195226N"
                           class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-mono focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none shadow-sm">
                    <p class="text-[10px] text-gray-400 mt-1">8 chiffres + 1 lettre majuscule</p>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- ACTIONS --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="flex items-center justify-between gap-3 pt-2">
            <a href="{{ route('eleves.show', $eleve) }}"
               class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">
                Annuler
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                Enregistrer les modifications
            </button>
        </div>
    </form>

    <form id="delete-form" method="POST" action="{{ route('eleves.destroy', $eleve) }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection

@push('scripts')
<script>
function eleveEditForm() {
    return {
        photoPreview: null,
        previewPhoto(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => this.photoPreview = e.target.result;
                reader.readAsDataURL(file);
            }
        }
    }
}
</script>
@endpush