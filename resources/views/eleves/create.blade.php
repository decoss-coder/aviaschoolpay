{{--
    IMPORTANT : Pour que ce formulaire fonctionne, le controller EleveWebController@create
    doit passer les variables suivantes à la vue :

    public function create(Request $request) {
        $etab = $request->user()->etablissement;
        $annee = $etab->anneesScolaires()->where('en_cours', true)->first();
        $classes = \App\Models\Classe::where('etablissement_id', $etab->id)
            ->where('annee_scolaire_id', $annee->id)
            ->with('niveau')->orderBy('nom')->get();
        $plans = \App\Models\PlanPaiement::where('etablissement_id', $etab->id)->get();
        $nationalites = ['Ivoirienne', 'Française', 'Burkinabé', 'Malienne', 'Ghanéenne', 'Autre'];
        return view('eleves.create', compact('classes', 'plans', 'nationalites'));
    }
--}}
@extends('layouts.app')

@section('title', 'Nouvelle inscription')
@section('page-title', 'Nouvelle inscription')
@section('page-subtitle', 'Enregistrer un nouvel élève pour l\'année 2025-2026')

@section('content')
<div x-data="eleveForm()" class="max-w-5xl mx-auto">

    <form method="POST" action="{{ route('eleves.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- BREADCRUMB + RETOUR --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('eleves.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour à la liste
            </a>
        </div>

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
                {{-- Photo upload --}}
                <div class="lg:col-span-3">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-2">Photo</label>
                    <div class="relative">
                        <div class="aspect-square bg-gradient-to-br from-brand-50 to-brand-100/50 border-2 border-dashed border-brand-200 rounded-2xl flex items-center justify-center overflow-hidden cursor-pointer hover:border-brand-400 hover:bg-brand-50 transition-colors group"
                             @click="$refs.photoInput.click()">
                            <template x-if="photoPreview">
                                <img :src="photoPreview" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!photoPreview">
                                <div class="text-center p-4">
                                    <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center mx-auto mb-2 shadow-sm group-hover:shadow-brand-glow transition-shadow">
                                        <svg class="w-6 h-6 text-brand-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <p class="text-[11px] font-bold text-brand-600">Cliquez pour ajouter</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">JPG, PNG — 2 Mo max</p>
                                </div>
                            </template>
                        </div>
                        <input type="file" name="photo" x-ref="photoInput" @change="previewPhoto($event)" accept="image/*" class="hidden">
                    </div>
                </div>

                {{-- Fields --}}
                <div class="lg:col-span-9 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nom <span class="text-red-500">*</span></label>
                        <input type="text" name="nom" required
                               class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Prénom(s) <span class="text-red-500">*</span></label>
                        <input type="text" name="prenom" required
                               class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Sexe <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="relative flex items-center justify-center gap-2 px-3 py-2.5 bg-white border border-brand-100 rounded-xl cursor-pointer hover:border-blue-300 transition-all has-[:checked]:bg-gradient-to-br has-[:checked]:from-blue-50 has-[:checked]:to-blue-100/50 has-[:checked]:border-blue-300 has-[:checked]:shadow-sm">
                                <input type="radio" name="sexe" value="M" required class="sr-only peer">
                                <span class="w-5 h-5 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-xs font-bold">♂</span>
                                <span class="text-sm font-semibold text-gray-700 peer-checked:text-blue-700">Garçon</span>
                            </label>
                            <label class="relative flex items-center justify-center gap-2 px-3 py-2.5 bg-white border border-brand-100 rounded-xl cursor-pointer hover:border-pink-300 transition-all has-[:checked]:bg-gradient-to-br has-[:checked]:from-pink-50 has-[:checked]:to-pink-100/50 has-[:checked]:border-pink-300 has-[:checked]:shadow-sm">
                                <input type="radio" name="sexe" value="F" class="sr-only peer">
                                <span class="w-5 h-5 rounded-full bg-gradient-to-br from-pink-400 to-pink-600 flex items-center justify-center text-white text-xs font-bold">♀</span>
                                <span class="text-sm font-semibold text-gray-700 peer-checked:text-pink-700">Fille</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Date de naissance <span class="text-red-500">*</span></label>
                        <input type="date" name="date_naissance" required
                               class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Lieu de naissance</label>
                        <input type="text" name="lieu_naissance" placeholder="Abidjan, Côte d'Ivoire"
                               class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nationalité</label>
                        <select name="nationalite"
                                class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm cursor-pointer">
                            <option value="">Sélectionner...</option>
                            @foreach(($nationalites ?? ['Ivoirienne', 'Française', 'Burkinabé', 'Malienne', 'Ghanéenne', 'Autre']) as $nat)
                                <option value="{{ $nat }}">{{ $nat }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- SECTION 2 : CONTACT & ADRESSE --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-blue-50/30 rounded-2xl border border-blue-100/60 shadow-card-blue p-6">
            <div class="absolute -top-10 -left-10 w-40 h-40 bg-blue-200/20 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-blue-100/60">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-sm shadow-blue-500/30">
                    <span class="font-display text-white font-extrabold text-sm">2</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Contact & adresse</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Coordonnées de l'élève</p>
                </div>
            </div>

            <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Adresse</label>
                    <input type="text" name="adresse" placeholder="Quartier, commune, ville"
                           class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Téléphone</label>
                    <input type="tel" name="telephone" placeholder="+225 XX XX XX XX XX"
                           class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Email</label>
                    <input type="email" name="email" placeholder="eleve@exemple.com"
                           class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- SECTION 3 : PARENT / TUTEUR --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-violet-50/30 rounded-2xl border border-violet-100/60 shadow-card-violet p-6">
            <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-violet-200/20 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-violet-100/60">
                <div class="w-10 h-10 bg-gradient-to-br from-violet-400 to-purple-600 rounded-xl flex items-center justify-center shadow-sm shadow-violet-500/30">
                    <span class="font-display text-white font-extrabold text-sm">3</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Parent / Tuteur principal</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Personne à contacter en priorité</p>
                </div>
            </div>

            <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nom complet <span class="text-red-500">*</span></label>
                    <input type="text" name="parent_nom" required
                           class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Lien de parenté <span class="text-red-500">*</span></label>
                    <select name="parent_lien" required
                            class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm cursor-pointer">
                        <option value="">Sélectionner...</option>
                        <option value="pere">Père</option>
                        <option value="mere">Mère</option>
                        <option value="tuteur">Tuteur légal</option>
                        <option value="oncle">Oncle</option>
                        <option value="tante">Tante</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Téléphone <span class="text-red-500">*</span></label>
                    <input type="tel" name="parent_telephone" required placeholder="+225 XX XX XX XX XX"
                           class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Email</label>
                    <input type="email" name="parent_email" placeholder="parent@exemple.com"
                           class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Profession</label>
                    <input type="text" name="parent_profession"
                           class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">CNI / Pièce d'identité</label>
                    <input type="text" name="parent_cni"
                           class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- SECTION 4 : INSCRIPTION & SCOLARITÉ --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/40 rounded-2xl border border-gold-200/60 shadow-card-gold p-6">
            <div class="absolute -top-10 -right-10 w-48 h-48 bg-gold-200/25 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-gold-200/60">
                <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-xl flex items-center justify-center shadow-gold-glow">
                    <span class="font-display text-white font-extrabold text-sm">4</span>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Inscription & scolarité</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Classe et plan de paiement</p>
                </div>
            </div>

            <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Classe <span class="text-red-500">*</span></label>
                    <select name="classe_id" required x-model="selectedClasse" @change="updateScolarite()"
                            class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm cursor-pointer">
                        <option value="">Sélectionner une classe...</option>
                        @foreach($classes ?? [] as $classe)
                            <option value="{{ $classe->id }}" data-montant="{{ $classe->scolarite_annuelle ?? 0 }}">
                                {{ $classe->code }} — {{ $classe->niveau->code ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Matricule DESPS</label>
                    <input type="text" name="matricule_desps" placeholder="Laisser vide si non attribué"
                           class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-mono placeholder:text-gray-400 focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Scolarité annuelle (FCFA)</label>
                    <div class="relative">
                        <input type="number" name="montant_brut" x-model="scolariteAnnuelle" readonly
                               class="w-full px-3 py-2.5 pr-12 bg-gradient-to-br from-gold-50 to-white border border-gold-200 rounded-xl text-sm font-bold text-gold-700 focus:outline-none shadow-sm">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-gold-600">FCFA</span>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Réduction (%)</label>
                    <input type="number" name="reduction" x-model="reduction" min="0" max="100" value="0"
                           class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Plan de paiement</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        @php
                            $plansDefaults = [
                                ['v' => 'comptant', 'l' => 'Comptant', 'd' => '1 versement'],
                                ['v' => 'trimestriel', 'l' => 'Trimestriel', 'd' => '3 versements'],
                                ['v' => 'mensuel', 'l' => 'Mensuel', 'd' => '9 mensualités'],
                                ['v' => 'personnalise', 'l' => 'Personnalisé', 'd' => 'À définir'],
                            ];
                        @endphp
                        @foreach(($plans ?? $plansDefaults) as $plan)
                            @php
                                $value = is_array($plan) ? $plan['v'] : $plan->id;
                                $label = is_array($plan) ? $plan['l'] : $plan->nom;
                                $desc = is_array($plan) ? $plan['d'] : $plan->description;
                            @endphp
                            <label class="relative cursor-pointer">
                                <input type="radio" name="plan_paiement" value="{{ $value }}" class="sr-only peer">
                                <div class="p-3 bg-white border border-gold-200 rounded-xl text-center hover:border-gold-400 transition-all peer-checked:bg-gradient-to-br peer-checked:from-gold-50 peer-checked:to-gold-100/50 peer-checked:border-gold-400 peer-checked:shadow-gold-glow">
                                    <p class="text-[12px] font-bold text-gray-800">{{ $label }}</p>
                                    <p class="text-[10px] text-gray-500 mt-0.5">{{ $desc }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Récap montant --}}
                <div class="md:col-span-2 mt-2 relative overflow-hidden bg-gradient-to-br from-brand-500 via-brand-600 to-brand-700 rounded-xl p-4 shadow-brand-glow">
                    <div class="absolute -top-6 -right-6 w-20 h-20 bg-gold-400/20 rounded-full blur-xl"></div>
                    <div class="relative flex items-center justify-between">
                        <div>
                            <p class="text-[10px] text-brand-100 font-bold uppercase tracking-wider">Montant net à payer</p>
                            <p class="font-display text-2xl font-extrabold text-white mt-1">
                                <span x-text="formatAmount(scolariteNette)">0</span>
                                <span class="text-sm font-medium text-brand-100">FCFA</span>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-brand-100">Après réduction</p>
                            <p class="text-xs font-bold text-gold-300" x-text="reduction + '%'">0%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════ --}}
        {{-- ACTIONS --}}
        {{-- ════════════════════════════════════════════════════ --}}
        <div class="flex items-center justify-between gap-3 pt-2">
            <a href="{{ route('eleves.index') }}"
               class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">
                Annuler
            </a>
            <div class="flex items-center gap-2">
                <button type="submit" name="action" value="draft"
                        class="px-5 py-2.5 bg-white border border-brand-200 text-brand-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-brand-50 transition-all">
                    Enregistrer comme brouillon
                </button>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Valider l'inscription
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function eleveForm() {
    return {
        photoPreview: null,
        selectedClasse: '',
        scolariteAnnuelle: 0,
        reduction: 0,
        get scolariteNette() {
            return Math.round(this.scolariteAnnuelle * (1 - this.reduction / 100));
        },
        previewPhoto(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => this.photoPreview = e.target.result;
                reader.readAsDataURL(file);
            }
        },
        updateScolarite() {
            const select = document.querySelector('select[name="classe_id"]');
            const opt = select.options[select.selectedIndex];
            this.scolariteAnnuelle = parseInt(opt.dataset.montant || 0);
        },
        formatAmount(n) {
            return new Intl.NumberFormat('fr-FR').format(n);
        }
    }
}
</script>
@endpush