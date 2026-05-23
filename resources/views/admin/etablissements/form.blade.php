@extends('layouts.app')
@php $isEdit = $etablissement->exists; @endphp
@section('title', $isEdit ? 'Modifier établissement' : 'Nouvel établissement')
@section('page-title', $isEdit ? 'Modifier l\'établissement' : 'Nouvel établissement')
@section('page-subtitle', $isEdit ? $etablissement->nom : 'Enregistrer un nouvel établissement sur la plateforme Avia')

@section('content')
<div class="max-w-5xl mx-auto">

    <form method="POST"
          action="{{ $isEdit ? route('admin.etablissements.update', $etablissement) : route('admin.etablissements.store') }}"
          class="space-y-6">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Retour --}}
        <div class="flex items-center justify-between mb-4">
            <a href="{{ $isEdit ? route('admin.etablissements.show', $etablissement) : route('admin.etablissements.index') }}"
               class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour
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

        {{-- ═══════════════ SECTION 1 : IDENTITÉ (brand) ═══════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand p-6">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-200/20 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-brand-100/60">
                <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center shadow-brand-glow">
                    <span class="font-display text-white font-extrabold text-sm">1</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Identité de l'établissement</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Nom officiel, codes administratifs, type</p>
                </div>
            </div>

            <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nom officiel <span class="text-red-500">*</span></label>
                    <input type="text" name="nom" value="{{ old('nom', $etablissement->nom) }}" required maxlength="200"
                           placeholder="Ex : Collège Notre Dame de Sinfra"
                           class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Code DESPS <span class="text-red-500">*</span></label>
                    <input type="text" name="code_desps" value="{{ old('code_desps', $etablissement->code_desps) }}" required maxlength="20"
                           placeholder="01.501.481.A"
                           class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm font-mono placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Sigle</label>
                    <input type="text" name="sigle" value="{{ old('sigle', $etablissement->sigle) }}" maxlength="20"
                           placeholder="CNDS"
                           class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm font-mono uppercase placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Type <span class="text-red-500">*</span></label>
                    <select name="type" required
                            class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm cursor-pointer">
                        @foreach(['prescolaire' => '🧸 Préscolaire', 'primaire' => '📚 Primaire', 'secondaire' => '🎓 Secondaire (collège)', 'lycee' => '🏛 Lycée', 'mixte' => '🏫 Mixte'] as $val => $lbl)
                            <option value="{{ $val }}" @selected(old('type', $etablissement->type) === $val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Statut juridique <span class="text-red-500">*</span></label>
                    <select name="statut_juridique" required
                            class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm cursor-pointer">
                        @foreach([
                            'public'              => '🏛 Public',
                            'prive_laic'          => '🏢 Privé laïc',
                            'prive_confessionnel' => '⛪ Privé confessionnel',
                            'communautaire'       => '🤝 Communautaire',
                        ] as $val => $lbl)
                            <option value="{{ $val }}" @selected(old('statut_juridique', $etablissement->statut_juridique) === $val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- ═══════════════ SECTION 2 : LOCALISATION (bleu) ═══════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-blue-50/30 rounded-2xl border border-blue-100/60 shadow-card-blue p-6">
            <div class="absolute -top-10 -left-10 w-40 h-40 bg-blue-200/20 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-blue-100/60">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-sm shadow-blue-500/30">
                    <span class="font-display text-white font-extrabold text-sm">2</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Adresse & contact</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Localisation et coordonnées de l'établissement</p>
                </div>
            </div>

            <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Adresse <span class="text-red-500">*</span></label>
                    <input type="text" name="adresse" value="{{ old('adresse', $etablissement->adresse) }}" required maxlength="300"
                           placeholder="Quartier, rue, B.P."
                           class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Ville <span class="text-red-500">*</span></label>
                    <input type="text" name="ville" value="{{ old('ville', $etablissement->ville) }}" required maxlength="100"
                           placeholder="Abidjan"
                           class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Commune</label>
                    <input type="text" name="commune" value="{{ old('commune', $etablissement->commune) }}" maxlength="100"
                           placeholder="Cocody, Yopougon..."
                           class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Région</label>
                    <input type="text" name="region" value="{{ old('region', $etablissement->region) }}" maxlength="100"
                           placeholder="Lagunes, Bélier..."
                           class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">DRENA</label>
                    <input type="text" name="drena" value="{{ old('drena', $etablissement->drena) }}" maxlength="100"
                           placeholder="Direction Régionale Éducation Nationale"
                           class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">DDENA</label>
                    <input type="text" name="ddena" value="{{ old('ddena', $etablissement->ddena) }}" maxlength="100"
                           placeholder="Direction Départementale"
                           class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Téléphone <span class="text-red-500">*</span></label>
                    <input type="tel" name="telephone" value="{{ old('telephone', $etablissement->telephone) }}" required maxlength="20"
                           placeholder="+225 XX XX XX XX XX"
                           class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email', $etablissement->email) }}"
                           placeholder="contact@etablissement.ci"
                           class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                </div>
            </div>
        </div>

        {{-- ═══════════════ SECTION 3 : DIRECTION / COMPTE DE CONNEXION (violet) ═══════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-violet-50/30 rounded-2xl border border-violet-100/60 shadow-card-violet p-6">
            <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-violet-200/20 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-violet-100/60">
                <div class="w-10 h-10 bg-gradient-to-br from-violet-400 to-purple-600 rounded-xl flex items-center justify-center shadow-sm shadow-violet-500/30">
                    <span class="font-display text-white font-extrabold text-sm">3</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">
                        @if($isEdit) Direction & statut @else Compte de connexion direction @endif
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        @if($isEdit)
                            Responsable et activation de l'établissement
                        @else
                            Un compte sera créé pour permettre au directeur de se connecter (mot de passe par défaut <b class="text-violet-700">0000</b> à changer obligatoirement)
                        @endif
                    </p>
                </div>
            </div>

            @if(! $isEdit)
                {{-- ─── Création : compte directeur complet ─── --}}
                <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nom du directeur <span class="text-red-500">*</span></label>
                        <input type="text" name="directeur_nom" value="{{ old('directeur_nom') }}" required maxlength="100"
                               placeholder="KOUASSI"
                               class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm uppercase placeholder:text-gray-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Prénom du directeur <span class="text-red-500">*</span></label>
                        <input type="text" name="directeur_prenom" value="{{ old('directeur_prenom') }}" required maxlength="100"
                               placeholder="Jean-Marie"
                               class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Email (identifiant de connexion) <span class="text-red-500">*</span></label>
                        <input type="email" name="directeur_email" value="{{ old('directeur_email') }}" required maxlength="120"
                               placeholder="directeur@etablissement.ci"
                               class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Téléphone du directeur <span class="text-red-500">*</span></label>
                        <input type="tel" name="directeur_telephone" value="{{ old('directeur_telephone') }}" required maxlength="20"
                               placeholder="+225 XX XX XX XX XX"
                               class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Rôle du compte</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            @foreach([
                                'directeur'         => ['Directeur', '👔'],
                                'directeur_adjoint' => ['Directeur adjoint', '👤'],
                                'gestionnaire'      => ['Gestionnaire', '📊'],
                            ] as $val => [$lbl, $emoji])
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="directeur_role" value="{{ $val }}" @checked(old('directeur_role', 'directeur') === $val) class="sr-only peer">
                                    <div class="p-3 bg-white border border-violet-100 rounded-xl text-center hover:border-violet-300 transition-all peer-checked:bg-gradient-to-br peer-checked:from-violet-50 peer-checked:to-violet-100/50 peer-checked:border-violet-400 peer-checked:shadow-sm">
                                        <p class="text-xl">{{ $emoji }}</p>
                                        <p class="text-[12px] font-bold text-gray-800 mt-0.5">{{ $lbl }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="md:col-span-2 rounded-xl bg-gradient-to-br from-violet-100 to-violet-50 border border-violet-200 p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-violet-600 flex items-center justify-center text-white font-bold flex-shrink-0">🔐</div>
                            <div class="flex-1 text-sm">
                                <p class="font-bold text-violet-900">Identifiants de connexion auto-générés</p>
                                <p class="text-violet-800 mt-1">
                                    <b>Login</b> : email saisi ci-dessus<br>
                                    <b>Mot de passe initial</b> : <span class="font-mono font-extrabold text-violet-900 bg-white px-2 py-0.5 rounded">0000</span><br>
                                    <span class="text-xs italic">→ Le directeur sera <b>obligatoirement</b> redirigé vers la page de changement de mot de passe à sa première connexion.</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                {{-- ─── Édition : info établissement seulement (le directeur géré via /utilisateurs) ─── --}}
                <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Directeur (info établissement)</label>
                        <input type="text" name="directeur_nom" value="{{ old('directeur_nom', $etablissement->directeur_nom) }}" maxlength="200"
                               placeholder="Nom et prénoms du directeur"
                               class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Téléphone du directeur</label>
                        <input type="tel" name="directeur_telephone" value="{{ old('directeur_telephone', $etablissement->directeur_telephone) }}" maxlength="20"
                               placeholder="+225 XX XX XX XX XX"
                               class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                    </div>

                    <div class="md:col-span-2 text-[11px] text-gray-500 italic bg-gray-50 border border-gray-200 rounded-xl p-3">
                        💡 Pour modifier les comptes utilisateurs (directeur, gestionnaire...), accédez à
                        <a href="{{ route('admin.etablissements.show', $etablissement) }}" class="text-violet-600 hover:underline font-bold">la fiche établissement</a>.
                    </div>
                </div>
            @endif

            {{-- Switch actif (toujours visible) --}}
            <div class="relative mt-4 pt-4 border-t border-violet-100/60">
                <label class="flex items-center gap-3 p-3 bg-white border border-violet-100 rounded-xl cursor-pointer hover:border-violet-300 transition-all">
                    <input type="checkbox" name="actif" value="1" @checked(old('actif', $etablissement->actif ?? true)) class="w-4 h-4 rounded border-violet-300 text-violet-600 focus:ring-violet-200">
                    <span class="text-sm font-semibold text-gray-700">
                        ✅ Établissement actif
                        <span class="block text-[11px] text-gray-500 font-normal mt-0.5">Désactivez pour suspendre l'accès des utilisateurs à cet établissement</span>
                    </span>
                </label>
            </div>
        </div>

        @unless($isEdit)
        {{-- ═══════════════ SECTION 4 : PREMIÈRE ANNÉE (or) ═══════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/40 rounded-2xl border border-gold-200/60 shadow-card-gold p-6">
            <div class="absolute -bottom-10 -left-10 w-48 h-48 bg-gold-200/25 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-gold-200/60">
                <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-xl flex items-center justify-center shadow-gold-glow">
                    <span class="font-display text-white font-extrabold text-sm">4</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Première année scolaire</h3>
                    <p class="text-xs text-gray-500 mt-0.5">L'année sera créée automatiquement en activation</p>
                </div>
            </div>

            <div class="relative grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Libellé</label>
                    <input type="text" name="annee_libelle" value="{{ old('annee_libelle', now()->year.'-'.(now()->year+1)) }}" maxlength="20"
                           class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-bold text-gold-700 focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Date de début</label>
                    <input type="date" name="annee_date_debut" value="{{ old('annee_date_debut', now()->format('Y').'-09-01') }}"
                           class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Date de fin</label>
                    <input type="date" name="annee_date_fin" value="{{ old('annee_date_fin', (now()->year+1).'-06-30') }}"
                           class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                </div>
            </div>
        </div>
        @endunless

        <div class="flex items-center justify-between gap-3 pt-2">
            <a href="{{ route('admin.etablissements.index') }}"
               class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">
                Annuler
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ $isEdit ? 'Enregistrer les modifications' : 'Créer l\'établissement' }}
            </button>
        </div>
    </form>
</div>
@endsection
