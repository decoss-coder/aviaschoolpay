@extends('layouts.app')
@section('title', 'Centre de documents')
@section('page-title', 'Centre de documents')
@section('page-subtitle', 'Téléchargements officiels — élèves, finances, notes, paie')

@php
    $totalEleves = $annee
        ? \App\Models\Inscription::where('etablissement_id', $etab->id)
            ->where('annee_scolaire_id', $annee->id)
            ->where('statut', 'validee')
            ->get(['eleve_id', 'classe_id'])
        : collect();
    $tousEleves = $totalEleves->isNotEmpty()
        ? \App\Models\Eleve::whereIn('id', $totalEleves->pluck('eleve_id'))
            ->orderBy('classe_id')->orderBy('nom')->orderBy('prenom')
            ->get(['id', 'nom', 'prenom', 'matricule_interne'])
        : collect();
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-gradient-to-br from-slate-700 to-slate-900 rounded-xl flex items-center justify-center shadow-card">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2"/></svg>
        </div>
        <div>
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Direction</p>
            <h2 class="font-display text-2xl font-extrabold text-gray-900">Documents & téléchargements</h2>
        </div>
    </div>

    @if(! $annee)
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            ⚠ Aucune année scolaire active. <a href="{{ route('admin.annees.index') }}" class="underline font-bold">Configurez une année</a>.
        </div>
    @endif

    <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-900">
        <p class="font-bold">ℹ Aperçu / Téléchargement</p>
        <p>Chaque document s'ouvre en aperçu dans un nouvel onglet. Cochez « Télécharger » pour récupérer le fichier directement.</p>
    </div>

    {{-- ═══════════════ ÉLÈVES & INSCRIPTIONS ═══════════════ --}}
    <section>
        <h3 class="font-display text-lg font-extrabold text-gray-900 mb-3">📚 Élèves & inscriptions</h3>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- Liste élèves --}}
            <form method="GET" action="{{ route('documents.eleves.pdf') }}" target="_blank"
                  class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden hover:shadow-card-hover transition">
                <div class="h-1 bg-gradient-to-r from-blue-400 to-blue-600"></div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-2">
                        <span class="text-2xl">📋</span>
                        <div>
                            <h4 class="font-bold text-gray-900">Liste complète des élèves</h4>
                            <p class="text-xs text-gray-500">Avec matricule, sexe, date naissance, classe</p>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase text-gray-400">Filtre (facultatif)</label>
                        <select name="classe_id" class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-blue-400">
                            <option value="">Toutes les classes</option>
                            @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->nom }}</option>@endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="download" value="1" class="rounded"> Télécharger
                        </label>
                        <a href="{{ route('documents.eleves.csv') }}" target="_blank" class="ml-auto px-2 py-2 bg-emerald-600 text-white text-xs font-bold rounded-lg" title="Export Excel/CSV">📊 CSV</a>
                        <button class="px-4 py-2 bg-blue-600 text-white text-sm font-bold rounded-xl">📄 PDF</button>
                    </div>
                </div>
            </form>

            {{-- Non soldés --}}
            <form method="GET" action="{{ route('documents.non-soldes.pdf') }}" target="_blank"
                  class="bg-white rounded-2xl border border-rose-100 shadow-card overflow-hidden hover:shadow-card-hover transition">
                <div class="h-1 bg-gradient-to-r from-rose-400 to-rose-600"></div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-2">
                        <span class="text-2xl">💸</span>
                        <div>
                            <h4 class="font-bold text-gray-900">Élèves non soldés</h4>
                            <p class="text-xs text-gray-500">Dus, payés, restes par élève — à relancer</p>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase text-gray-400">Filtre (facultatif)</label>
                        <select name="classe_id" class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-rose-400">
                            <option value="">Toutes les classes</option>
                            @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->nom }}</option>@endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="download" value="1" class="rounded"> Télécharger
                        </label>
                        <a href="{{ route('documents.non-soldes.csv') }}" target="_blank" class="ml-auto px-2 py-2 bg-emerald-600 text-white text-xs font-bold rounded-lg" title="Export Excel/CSV">📊 CSV</a>
                        <button class="px-4 py-2 bg-rose-600 text-white text-sm font-bold rounded-xl">📄 PDF</button>
                    </div>
                </div>
            </form>

            {{-- Annuaire parents --}}
            <form method="GET" action="{{ route('documents.annuaire.pdf') }}" target="_blank"
                  class="bg-white rounded-2xl border border-emerald-100 shadow-card overflow-hidden hover:shadow-card-hover transition">
                <div class="h-1 bg-gradient-to-r from-emerald-400 to-emerald-600"></div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-2">
                        <span class="text-2xl">📞</span>
                        <div>
                            <h4 class="font-bold text-gray-900">Annuaire parents</h4>
                            <p class="text-xs text-gray-500">Élèves + contact urgence (parent) pour communication</p>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase text-gray-400">Filtre (facultatif)</label>
                        <select name="classe_id" class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-emerald-400">
                            <option value="">Toutes les classes</option>
                            @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->nom }}</option>@endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="download" value="1" class="rounded"> Télécharger
                        </label>
                        <button class="ml-auto px-4 py-2 bg-emerald-600 text-white text-sm font-bold rounded-xl">📄 Générer</button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    {{-- ═══════════════ NOTES & PÉDAGOGIE ═══════════════ --}}
    <section>
        <h3 class="font-display text-lg font-extrabold text-gray-900 mb-3">📊 Notes & pédagogie</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            {{-- Nappe moyennes --}}
            <form method="GET" action="{{ route('documents.nappe.pdf') }}" target="_blank"
                  class="bg-white rounded-2xl border border-violet-100 shadow-card-violet overflow-hidden hover:shadow-card-hover transition">
                <div class="h-1 bg-gradient-to-r from-violet-400 to-violet-600"></div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-2">
                        <span class="text-2xl">📊</span>
                        <div>
                            <h4 class="font-bold text-gray-900">Nappe des moyennes par classe</h4>
                            <p class="text-xs text-gray-500">Matrice élèves × matières + moyenne générale + rang</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-400">Classe *</label>
                            <select name="classe_id" required class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-violet-400">
                                <option value="">— Sélectionner —</option>
                                @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->nom }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-400">Trimestre *</label>
                            <select name="trimestre_id" required class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-violet-400">
                                <option value="">— Sélectionner —</option>
                                @foreach($trimestres as $t)<option value="{{ $t->id }}">{{ $t->libelle }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="download" value="1" class="rounded"> Télécharger
                        </label>
                        <button class="ml-auto px-4 py-2 bg-violet-600 text-white text-sm font-bold rounded-xl">📄 Générer (paysage)</button>
                    </div>
                </div>
            </form>

            {{-- Synthèse niveau --}}
            <form method="GET" action="{{ route('documents.synthese-niveau.pdf') }}" target="_blank"
                  class="bg-white rounded-2xl border border-indigo-100 shadow-card overflow-hidden hover:shadow-card-hover transition">
                <div class="h-1 bg-gradient-to-r from-indigo-400 to-indigo-600"></div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-2">
                        <span class="text-2xl">📈</span>
                        <div>
                            <h4 class="font-bold text-gray-900">Synthèse par niveau</h4>
                            <p class="text-xs text-gray-500">Comparatif moyennes inter-classes d'un même niveau</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-400">Niveau *</label>
                            <select name="niveau_id" required class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-indigo-400">
                                <option value="">— Sélectionner —</option>
                                @foreach($niveaux as $n)<option value="{{ $n->id }}">{{ $n->libelle }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-400">Trimestre *</label>
                            <select name="trimestre_id" required class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-indigo-400">
                                <option value="">— Sélectionner —</option>
                                @foreach($trimestres as $t)<option value="{{ $t->id }}">{{ $t->libelle }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="download" value="1" class="rounded"> Télécharger
                        </label>
                        <button class="ml-auto px-4 py-2 bg-indigo-600 text-white text-sm font-bold rounded-xl">📄 Générer</button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    {{-- ═══════════════ ENSEIGNANTS & PAIE ═══════════════ --}}
    <section>
        <h3 class="font-display text-lg font-extrabold text-gray-900 mb-3">🎓 Enseignants & paie</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            {{-- Liste enseignants --}}
            <form method="GET" action="{{ route('documents.enseignants.pdf') }}" target="_blank"
                  class="bg-white rounded-2xl border border-teal-100 shadow-card overflow-hidden hover:shadow-card-hover transition">
                <div class="h-1 bg-gradient-to-r from-teal-400 to-teal-600"></div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-2">
                        <span class="text-2xl">👥</span>
                        <div>
                            <h4 class="font-bold text-gray-900">Liste enseignants + affectations</h4>
                            <p class="text-xs text-gray-500">Tous les enseignants actifs avec leurs classes/matières</p>
                        </div>
                    </div>
                    <div class="bg-teal-50 rounded-xl p-3 text-xs text-teal-800">
                        💡 Inclut : matricule MENA, statut, téléphone, salaire base, affectations actives.
                    </div>
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="download" value="1" class="rounded"> Télécharger
                        </label>
                        <button class="ml-auto px-4 py-2 bg-teal-600 text-white text-sm font-bold rounded-xl">📄 Générer</button>
                    </div>
                </div>
            </form>

            {{-- Récap paie --}}
            <form method="GET" action="{{ route('documents.recap-paie.pdf') }}" target="_blank"
                  class="bg-white rounded-2xl border border-amber-100 shadow-card-gold overflow-hidden hover:shadow-card-hover transition">
                <div class="h-1 bg-gradient-to-r from-amber-400 to-amber-600"></div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-2">
                        <span class="text-2xl">💼</span>
                        <div>
                            <h4 class="font-bold text-gray-900">Récap paie du mois</h4>
                            <p class="text-xs text-gray-500">Synthèse fiches de paie : heures, brut, net, retenues</p>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase text-gray-400">Mois</label>
                        <input type="month" name="mois" value="{{ now()->format('Y-m') }}"
                               class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-amber-400" />
                    </div>
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="download" value="1" class="rounded"> Télécharger
                        </label>
                        <a href="{{ route('documents.recap-paie.csv', ['mois' => now()->format('Y-m')]) }}" target="_blank" class="ml-auto px-2 py-2 bg-emerald-600 text-white text-xs font-bold rounded-lg" title="Export Excel/CSV">📊 CSV</a>
                        <button class="px-4 py-2 bg-amber-600 text-white text-sm font-bold rounded-xl">📄 PDF</button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    {{-- ═══════════════ BULLETINS & DOCUMENTS INDIVIDUELS ═══════════════ --}}
    <section>
        <h3 class="font-display text-lg font-extrabold text-gray-900 mb-3">🎓 Documents individuels élève</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            {{-- Bulletin individuel --}}
            <form method="GET" action="{{ route('documents.bulletin.pdf') }}" target="_blank"
                  class="bg-white rounded-2xl border border-cyan-100 shadow-card-blue overflow-hidden hover:shadow-card-hover transition">
                <div class="h-1 bg-gradient-to-r from-cyan-400 to-cyan-600"></div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-2">
                        <span class="text-2xl">📋</span>
                        <div>
                            <h4 class="font-bold text-gray-900">Bulletin individuel</h4>
                            <p class="text-xs text-gray-500">Avec graphique radar des matières + stats classe</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-400">Élève *</label>
                            <select name="eleve_id" required class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-cyan-400">
                                <option value="">— Sélectionner —</option>
                                @foreach($tousEleves as $e)
                                    <option value="{{ $e->id }}">{{ $e->prenom }} {{ $e->nom }} · {{ $e->matricule_interne }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-400">Trimestre *</label>
                            <select name="trimestre_id" required class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-cyan-400">
                                <option value="">— Sélectionner —</option>
                                @foreach($trimestres as $t)<option value="{{ $t->id }}">{{ $t->libelle }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="download" value="1" class="rounded"> Télécharger
                        </label>
                        <button class="ml-auto px-4 py-2 bg-cyan-600 text-white text-sm font-bold rounded-xl">📄 Générer</button>
                    </div>
                </div>
            </form>

            {{-- Certificat scolarité --}}
            <form method="GET" action="{{ route('documents.certificat.pdf') }}" target="_blank"
                  class="bg-white rounded-2xl border border-emerald-100 shadow-card overflow-hidden hover:shadow-card-hover transition">
                <div class="h-1 bg-gradient-to-r from-emerald-400 to-brand-600"></div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-2">
                        <span class="text-2xl">🎓</span>
                        <div>
                            <h4 class="font-bold text-gray-900">Certificat de scolarité</h4>
                            <p class="text-xs text-gray-500">Document officiel signable — N° automatique</p>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase text-gray-400">Élève *</label>
                        <select name="eleve_id" required class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-emerald-400">
                            <option value="">— Sélectionner —</option>
                            @foreach($tousEleves as $e)
                                <option value="{{ $e->id }}">{{ $e->prenom }} {{ $e->nom }} · {{ $e->matricule_interne }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="bg-emerald-50 rounded-xl p-2 text-xs text-emerald-800">
                        💡 Inclut : identité complète, classe, dates, signatures, cachet
                    </div>
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="download" value="1" class="rounded"> Télécharger
                        </label>
                        <button class="ml-auto px-4 py-2 bg-emerald-600 text-white text-sm font-bold rounded-xl">📄 Générer</button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    {{-- ═══════════════ MÉRITE & SUIVI PÉDAGOGIQUE ═══════════════ --}}
    <section>
        <h3 class="font-display text-lg font-extrabold text-gray-900 mb-3">🏆 Mérite & suivi pédagogique</h3>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- Tableau d'honneur --}}
            <form method="GET" action="{{ route('documents.honneur.pdf') }}" target="_blank"
                  class="bg-white rounded-2xl border border-amber-100 shadow-card-gold overflow-hidden hover:shadow-card-hover transition">
                <div class="h-1 bg-gradient-to-r from-amber-400 to-yellow-500"></div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-2">
                        <span class="text-2xl">🏆</span>
                        <div>
                            <h4 class="font-bold text-gray-900">Tableau d'honneur</h4>
                            <p class="text-xs text-gray-500">Top élèves par classe (médailles 🥇🥈🥉)</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-400">Classe *</label>
                            <select name="classe_id" required class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-amber-400">
                                <option value="">—</option>
                                @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->nom }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-400">Trim. *</label>
                            <select name="trimestre_id" required class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-amber-400">
                                <option value="">—</option>
                                @foreach($trimestres as $t)<option value="{{ $t->id }}">{{ $t->numero }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-400">Top</label>
                            <select name="top" class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-amber-400">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="3">3</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="download" value="1" class="rounded"> Télécharger
                        </label>
                        <button class="ml-auto px-4 py-2 bg-amber-600 text-white text-sm font-bold rounded-xl">📄 Générer</button>
                    </div>
                </div>
            </form>

            {{-- Élèves en difficulté --}}
            <form method="GET" action="{{ route('documents.difficulte.pdf') }}" target="_blank"
                  class="bg-white rounded-2xl border border-red-100 shadow-card overflow-hidden hover:shadow-card-hover transition">
                <div class="h-1 bg-gradient-to-r from-red-400 to-red-600"></div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-2">
                        <span class="text-2xl">⚠</span>
                        <div>
                            <h4 class="font-bold text-gray-900">Élèves en difficulté</h4>
                            <p class="text-xs text-gray-500">Moyennes &lt; seuil + recommandations</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-400">Trim. *</label>
                            <select name="trimestre_id" required class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-red-400">
                                <option value="">—</option>
                                @foreach($trimestres as $t)<option value="{{ $t->id }}">{{ $t->numero }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-400">Classe</label>
                            <select name="classe_id" class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-red-400">
                                <option value="">Toutes</option>
                                @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->nom }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-400">Seuil</label>
                            <input type="number" step="0.5" name="seuil" value="10" min="0" max="20" class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-red-400" />
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="download" value="1" class="rounded"> Télécharger
                        </label>
                        <button class="ml-auto px-4 py-2 bg-red-600 text-white text-sm font-bold rounded-xl">📄 Générer</button>
                    </div>
                </div>
            </form>

            {{-- Carnet de présence --}}
            <form method="GET" action="{{ route('documents.carnet.pdf') }}" target="_blank"
                  class="bg-white rounded-2xl border border-violet-100 shadow-card-violet overflow-hidden hover:shadow-card-hover transition">
                <div class="h-1 bg-gradient-to-r from-violet-400 to-violet-600"></div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-2">
                        <span class="text-2xl">📅</span>
                        <div>
                            <h4 class="font-bold text-gray-900">Carnet de présence (vide)</h4>
                            <p class="text-xs text-gray-500">À imprimer · semaine de 6 jours (matin/soir)</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-400">Classe *</label>
                            <select name="classe_id" required class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-violet-400">
                                <option value="">—</option>
                                @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->nom }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-400">Semaine</label>
                            <input type="date" name="debut_semaine" value="{{ now()->startOfWeek()->format('Y-m-d') }}" class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-violet-400" />
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="download" value="1" class="rounded"> Télécharger
                        </label>
                        <button class="ml-auto px-4 py-2 bg-violet-600 text-white text-sm font-bold rounded-xl">📄 PDF paysage</button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    {{-- ═══════════════ DOCUMENTS OFFICIELS & VIE SCOLAIRE ═══════════════ --}}
    <section>
        <h3 class="font-display text-lg font-extrabold text-gray-900 mb-3">🎫 Documents officiels & vie scolaire</h3>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- Cartes élève PDF --}}
            <form method="GET" action="{{ route('documents.cartes.pdf') }}" target="_blank"
                  class="bg-white rounded-2xl border border-emerald-100 shadow-card overflow-hidden hover:shadow-card-hover transition">
                <div class="h-1 bg-gradient-to-r from-emerald-400 to-emerald-700"></div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-2">
                        <span class="text-2xl">🎫</span>
                        <div>
                            <h4 class="font-bold text-gray-900">Cartes d'élève avec QR</h4>
                            <p class="text-xs text-gray-500">2 cartes/page A4 · photo + matricule + QR</p>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase text-gray-400">Classe (facultatif)</label>
                        <select name="classe_id" class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-emerald-400">
                            <option value="">Tous les élèves</option>
                            @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->nom }}</option>@endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="download" value="1" class="rounded"> Télécharger
                        </label>
                        <button class="ml-auto px-4 py-2 bg-emerald-600 text-white text-sm font-bold rounded-xl">📄 Générer</button>
                    </div>
                </div>
            </form>

            {{-- Calendrier scolaire --}}
            <form method="GET" action="{{ route('documents.calendrier.pdf') }}" target="_blank"
                  class="bg-white rounded-2xl border border-amber-100 shadow-card-gold overflow-hidden hover:shadow-card-hover transition">
                <div class="h-1 bg-gradient-to-r from-amber-400 to-orange-600"></div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-2">
                        <span class="text-2xl">📅</span>
                        <div>
                            <h4 class="font-bold text-gray-900">Calendrier scolaire annuel</h4>
                            <p class="text-xs text-gray-500">Trimestres + événements groupés par mois</p>
                        </div>
                    </div>
                    <div class="bg-amber-50 rounded-xl p-2 text-xs text-amber-800">
                        💡 Configurez les événements via <a href="{{ route('evenements.index') }}" class="underline font-bold">Calendrier scolaire</a> (menu).
                    </div>
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="download" value="1" class="rounded"> Télécharger
                        </label>
                        <button class="ml-auto px-4 py-2 bg-amber-600 text-white text-sm font-bold rounded-xl">📄 Générer</button>
                    </div>
                </div>
            </form>

            {{-- Convocation conseil de classe --}}
            <a href="{{ route('conseils-classe.index') }}"
               class="bg-white rounded-2xl border border-indigo-100 shadow-card-violet overflow-hidden hover:shadow-card-hover transition block">
                <div class="h-1 bg-gradient-to-r from-indigo-400 to-purple-700"></div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-2">
                        <span class="text-2xl">👥</span>
                        <div>
                            <h4 class="font-bold text-gray-900">Convocations conseils de classe</h4>
                            <p class="text-xs text-gray-500">Planifier + générer convocation PDF</p>
                        </div>
                    </div>
                    <div class="bg-indigo-50 rounded-xl p-2 text-xs text-indigo-800">
                        💡 Planifiez la date + ordre du jour, et téléchargez la convocation officielle.
                    </div>
                    <div class="pt-2 border-t border-gray-100">
                        <span class="inline-flex items-center text-sm font-bold text-indigo-600">→ Ouvrir la planification</span>
                    </div>
                </div>
            </a>
        </div>
    </section>

    {{-- ═══════════════ DOCUMENTS GROUPÉS / SYNTHÈSE ═══════════════ --}}
    <section>
        <h3 class="font-display text-lg font-extrabold text-gray-900 mb-3">📦 Documents en lot & synthèses</h3>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- Bulletins en lot --}}
            <form method="GET" action="{{ route('documents.bulletins-classe.pdf') }}" target="_blank"
                  class="bg-white rounded-2xl border border-cyan-100 shadow-card-blue overflow-hidden hover:shadow-card-hover transition">
                <div class="h-1 bg-gradient-to-r from-cyan-400 to-cyan-700"></div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-2">
                        <span class="text-2xl">📋</span>
                        <div>
                            <h4 class="font-bold text-gray-900">Bulletins en lot par classe</h4>
                            <p class="text-xs text-gray-500">Tous les bulletins d'une classe en un seul PDF</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-400">Classe *</label>
                            <select name="classe_id" required class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-cyan-400">
                                <option value="">— Sélectionner —</option>
                                @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->nom }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-400">Trimestre *</label>
                            <select name="trimestre_id" required class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-cyan-400">
                                <option value="">—</option>
                                @foreach($trimestres as $t)<option value="{{ $t->id }}">{{ $t->libelle }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div class="bg-cyan-50 rounded-xl p-2 text-xs text-cyan-800">
                        💡 1 page = 1 bulletin · signatures incluses
                    </div>
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="download" value="1" class="rounded"> Télécharger
                        </label>
                        <button class="ml-auto px-4 py-2 bg-cyan-600 text-white text-sm font-bold rounded-xl">📄 Générer</button>
                    </div>
                </div>
            </form>

            {{-- Attestation paiement --}}
            <form method="GET" action="{{ route('documents.attestation.pdf') }}" target="_blank"
                  class="bg-white rounded-2xl border border-emerald-100 shadow-card overflow-hidden hover:shadow-card-hover transition">
                <div class="h-1 bg-gradient-to-r from-emerald-400 to-emerald-700"></div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-2">
                        <span class="text-2xl">💵</span>
                        <div>
                            <h4 class="font-bold text-gray-900">Attestation de paiement</h4>
                            <p class="text-xs text-gray-500">Document officiel récap des versements d'un élève</p>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase text-gray-400">Élève *</label>
                        <select name="eleve_id" required class="w-full mt-1 rounded-xl border-gray-200 text-sm focus:border-emerald-400">
                            <option value="">— Sélectionner —</option>
                            @foreach($tousEleves as $e)
                                <option value="{{ $e->id }}">{{ $e->prenom }} {{ $e->nom }} · {{ $e->matricule_interne }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="bg-emerald-50 rounded-xl p-2 text-xs text-emerald-800">
                        💡 Liste tous les paiements + badge SOLDÉ/RESTE
                    </div>
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="download" value="1" class="rounded"> Télécharger
                        </label>
                        <button class="ml-auto px-4 py-2 bg-emerald-600 text-white text-sm font-bold rounded-xl">📄 Générer</button>
                    </div>
                </div>
            </form>

            {{-- Récap annuel école --}}
            <form method="GET" action="{{ route('documents.recap-annuel.pdf') }}" target="_blank"
                  class="bg-white rounded-2xl border border-brand-200 shadow-card-brand overflow-hidden hover:shadow-card-hover transition">
                <div class="h-1 bg-gradient-to-r from-brand-400 to-brand-700"></div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-2">
                        <span class="text-2xl">📈</span>
                        <div>
                            <h4 class="font-bold text-gray-900">Récap annuel école</h4>
                            <p class="text-xs text-gray-500">Bilan exécutif complet de l'année</p>
                        </div>
                    </div>
                    <div class="bg-brand-50 rounded-xl p-3 text-xs text-brand-800">
                        💡 Inclut : effectifs par niveau · finances année · résultat · masse salariale · ratio MS/CA · taux réussite · synthèse exécutive
                    </div>
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                        <label class="text-xs text-gray-600 flex items-center gap-1">
                            <input type="checkbox" name="download" value="1" class="rounded"> Télécharger
                        </label>
                        <button class="ml-auto px-4 py-2 bg-gradient-to-r from-brand-500 to-brand-700 text-white text-sm font-bold rounded-xl shadow-brand-glow">📄 Générer bilan</button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    {{-- ═══════════════ AUTRES RAPPORTS FINANCIERS ═══════════════ --}}
    <section>
        <h3 class="font-display text-lg font-extrabold text-gray-900 mb-3">💰 Rapports financiers complémentaires</h3>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
            <p class="text-sm text-gray-600 mb-3">Retrouvez l'ensemble des rapports financiers détaillés (paiements, bilan scolarité, mensuel, trimestriel) dans le centre de rapports dédié :</p>
            <a href="{{ route('rapports.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-brand-500 to-brand-700 text-white text-sm font-bold rounded-xl shadow-brand-glow hover:shadow-lg transition">
                💼 Ouvrir le centre de rapports financiers →
            </a>
        </div>
    </section>
</div>
@endsection
