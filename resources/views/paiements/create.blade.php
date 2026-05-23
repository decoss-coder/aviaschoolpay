@extends('layouts.app')

@section('title', 'Nouveau paiement')
@section('page-title', 'Nouveau paiement')
@section('page-subtitle', 'Encaissement manuel ou via Wave')

@section('content')
<div class="max-w-4xl mx-auto space-y-5">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm">
        <a href="{{ route('finances.index') }}" class="text-brand-600 font-semibold hover:underline">Finances</a>
        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('paiements.index') }}" class="text-brand-600 font-semibold hover:underline">Paiements</a>
        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-900 font-bold">Nouveau</span>
    </nav>

    {{-- Erreurs --}}
    @if($errors->any())
        <div class="px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            @foreach($errors->all() as $e) <p>• {{ $e }}</p> @endforeach
        </div>
    @endif

    {{-- ──────────────  1. SÉLECTION ÉLÈVE  ────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                <h2 class="text-lg font-extrabold text-gray-900">Sélection de l'élève</h2>
                <p class="text-sm text-gray-500 mt-0.5">Choisissez une classe puis un élève pour enregistrer un paiement.</p>
            </div>
            @if($eleve)
                <a href="{{ route('paiements.create') }}" class="text-xs text-gray-500 font-bold hover:text-gray-700 hover:underline whitespace-nowrap">
                    ✕ Changer d'élève
                </a>
            @endif
        </div>

        <form method="GET" action="{{ route('paiements.create') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3"
              x-data="{ classeId: '{{ $classeId }}', eleveId: '{{ $eleve?->id }}' }">
            {{-- Classe --}}
            <div class="sm:col-span-1">
                <label class="text-[11px] uppercase tracking-wider text-gray-500 font-bold block mb-1.5">Classe</label>
                <select name="classe_id"
                        x-model="classeId"
                        @change="eleveId = ''; $el.form.submit()"
                        class="w-full rounded-xl border-gray-200 focus:border-brand-500 focus:ring-brand-500 text-sm">
                    <option value="">— Choisir une classe —</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" @selected($classeId == $c->id)>
                            {{ $c->niveau?->libelle ? '['.$c->niveau->libelle.'] ' : '' }}{{ $c->nom }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Élève --}}
            <div class="sm:col-span-2">
                <label class="text-[11px] uppercase tracking-wider text-gray-500 font-bold block mb-1.5">
                    Élève
                    @if($elevesClasse->isNotEmpty())
                        <span class="text-gray-400 normal-case">({{ $elevesClasse->count() }} élève{{ $elevesClasse->count() > 1 ? 's' : '' }})</span>
                    @endif
                </label>
                <select name="eleve_id"
                        x-model="eleveId"
                        @change="$el.form.submit()"
                        :disabled="!classeId"
                        :class="!classeId ? 'opacity-50 cursor-not-allowed' : ''"
                        class="w-full rounded-xl border-gray-200 focus:border-brand-500 focus:ring-brand-500 text-sm">
                    <option value="">
                        @if(! $classeId)
                            — Choisissez d'abord une classe —
                        @elseif($elevesClasse->isEmpty())
                            — Aucun élève dans cette classe —
                        @else
                            — Choisir un élève —
                        @endif
                    </option>
                    @foreach($elevesClasse as $e)
                        <option value="{{ $e->id }}" @selected($eleve && $eleve->id == $e->id)>
                            {{ $e->prenom }} {{ strtoupper($e->nom) }}
                            ({{ $e->matricule_interne }})
                            @if($e->statut_eleve) — {{ $e->statut_eleve }} @endif
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @if(! $eleve && $classes->isEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 text-sm text-amber-900">
            <p class="font-bold mb-1">⚠ Aucune classe configurée pour l'année en cours</p>
            <p class="text-xs">Créez d'abord les classes dans <a href="{{ route('classes.index') }}" class="underline font-bold">Classes</a> avant d'enregistrer un paiement.</p>
        </div>
    @endif

    @if($eleve)
        @php
            $isAff = $eleve->statut_eleve === 'AFF';
            $totalReste = (int) ($grille['total']['reste'] ?? 0);
            $totalMontant = (int) ($grille['total']['montant'] ?? 0);
            $totalPaye = (int) ($grille['total']['paye'] ?? 0);
            $tauxPaye = $totalMontant > 0 ? round(($totalPaye / $totalMontant) * 100, 1) : 0;
            $aucunMontantDu = $totalMontant <= 0;
            $estSolde = ! $aucunMontantDu && $totalReste <= 0;
        @endphp

        {{-- ──────────────  HEADER ÉLÈVE  ────────────── --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-5 flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-lg font-extrabold {{ $isAff ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ strtoupper(substr($eleve->prenom ?? '', 0, 1) . substr($eleve->nom ?? '', 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wider text-gray-400 font-bold">Élève</p>
                        <p class="text-lg font-extrabold text-gray-900">{{ $eleve->prenom }} {{ strtoupper($eleve->nom) }}</p>
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            <span class="text-xs font-mono text-gray-500">{{ $eleve->matricule_interne }}</span>
                            @if($eleve->classe)
                                <span class="text-xs text-gray-300">·</span>
                                <span class="text-xs font-bold text-gray-700">{{ $eleve->classe->nom }}</span>
                            @endif
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $isAff ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $resume['statut_eleve_libelle'] ?? $eleve->statut_eleve }}
                            </span>
                        </div>
                    </div>
                </div>
                <a href="{{ route('finances.eleve', $eleve) }}" class="text-xs text-brand-600 font-semibold hover:underline whitespace-nowrap">Fiche financière →</a>
            </div>

            @if(!empty($resume['message']))
                <div class="px-5 py-3 bg-blue-50 border-t border-blue-100 text-xs text-blue-900 flex items-start gap-2">
                    <svg class="w-4 h-4 flex-shrink-0 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ $resume['message'] }}</span>
                </div>
            @endif
        </div>

        {{-- ──────────────  2. GRILLE DE PAIEMENT  ────────────── --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-bold text-gray-900">Grille de paiement</h2>
                <p class="text-xs text-gray-500 mt-0.5">Montants attendus, déjà versés et solde restant — par poste.</p>
            </div>

            @if($aucunMontantDu)
                <div class="px-5 py-8 text-center">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-gray-100 flex items-center justify-center mb-3">
                        <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="font-bold text-gray-900">Aucun montant dû</p>
                    <p class="text-sm text-gray-500 mt-1">Cet élève n'a pas encore d'inscription validée avec un montant configuré, ou la grille tarifaire n'a pas été appliquée.</p>
                    <a href="{{ route('finances.tarifs') }}" class="inline-block mt-3 text-xs text-brand-600 font-bold underline">Configurer les grilles tarifaires</a>
                </div>
            @else
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50/50 text-[11px] uppercase tracking-wider text-gray-500">
                            <th class="px-5 py-2 text-left font-bold">Poste</th>
                            <th class="px-4 py-2 text-right font-bold">Montant</th>
                            <th class="px-4 py-2 text-right font-bold">Déjà payé</th>
                            <th class="px-4 py-2 text-right font-bold">Reste</th>
                            <th class="px-4 py-2 text-center font-bold">État</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        {{-- Ligne Inscription --}}
                        @if($grille['inscription']['applicable'])
                            @php $insReste = $grille['inscription']['reste']; @endphp
                            <tr class="hover:bg-gray-50/40 transition">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                        <span class="font-bold text-gray-900">{{ $grille['inscription']['libelle'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-gray-900">{{ number_format($grille['inscription']['montant'], 0, ',', ' ') }} F</td>
                                <td class="px-4 py-3 text-right text-emerald-700 font-semibold">{{ number_format($grille['inscription']['paye'], 0, ',', ' ') }} F</td>
                                <td class="px-4 py-3 text-right font-extrabold {{ $insReste > 0 ? 'text-amber-600' : 'text-emerald-600' }}">{{ number_format($insReste, 0, ',', ' ') }} F</td>
                                <td class="px-4 py-3 text-center">
                                    @if($insReste <= 0)
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">SOLDÉ</span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">DÛ</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                        {{-- Ligne Scolarité --}}
                        @if($grille['scolarite']['applicable'])
                            @php $scolReste = $grille['scolarite']['reste']; @endphp
                            <tr class="hover:bg-gray-50/40 transition">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                        <span class="font-bold text-gray-900">{{ $grille['scolarite']['libelle'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-gray-900">{{ number_format($grille['scolarite']['montant'], 0, ',', ' ') }} F</td>
                                <td class="px-4 py-3 text-right text-emerald-700 font-semibold">{{ number_format($grille['scolarite']['paye'], 0, ',', ' ') }} F</td>
                                <td class="px-4 py-3 text-right font-extrabold {{ $scolReste > 0 ? 'text-amber-600' : 'text-emerald-600' }}">{{ number_format($scolReste, 0, ',', ' ') }} F</td>
                                <td class="px-4 py-3 text-center">
                                    @if($scolReste <= 0)
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">SOLDÉ</span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">DÛ</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                        {{-- TOTAL --}}
                        <tr class="bg-gray-900 text-white">
                            <td class="px-5 py-3 font-extrabold uppercase tracking-wider text-xs">Total</td>
                            <td class="px-4 py-3 text-right font-extrabold">{{ number_format($totalMontant, 0, ',', ' ') }} F</td>
                            <td class="px-4 py-3 text-right text-emerald-300 font-extrabold">{{ number_format($totalPaye, 0, ',', ' ') }} F</td>
                            <td class="px-4 py-3 text-right text-amber-300 font-extrabold text-base">{{ number_format($totalReste, 0, ',', ' ') }} F</td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-xs font-bold">{{ $tauxPaye }}%</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="px-5 py-3 bg-gray-50/40">
                    <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full bg-gradient-to-r {{ $estSolde ? 'from-emerald-400 to-emerald-600' : 'from-brand-400 to-brand-600' }} transition-all duration-700" style="width: {{ min(100, $tauxPaye) }}%"></div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ──────────────  3. FORMULAIRE ENREGISTREMENT  ────────────── --}}
        @if($estSolde)
            {{-- État soldé : pas de formulaire --}}
            <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-6 text-center">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-100 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <p class="font-bold text-emerald-900">Cet élève a tout payé</p>
                <p class="text-sm text-emerald-700 mt-1">Aucun montant n'est dû. Consultez la <a href="{{ route('finances.eleve', $eleve) }}" class="underline font-bold">fiche financière</a> pour voir l'historique.</p>
            </div>
        @elseif(! $aucunMontantDu)
            <form method="POST" action="{{ route('paiements.store') }}" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5"
                  x-data="{ mode: '{{ old('mode', $paiementsManuelsActifs ? 'especes' : 'wave') }}', montant: {{ old('montant', $totalReste) }}, reste: {{ $totalReste }} }">
                @csrf
                <input type="hidden" name="eleve_id" value="{{ $eleve->id }}"/>

                {{-- Mode de paiement --}}
                <div>
                    <label class="text-[11px] uppercase tracking-wider text-gray-500 font-bold block mb-2">Mode de paiement</label>

                    @if(! $paiementsManuelsActifs && ! $waveActif)
                        <div class="px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
                            <p class="font-bold mb-1">⚠ Aucun moyen de paiement disponible</p>
                            <p class="text-xs">Les paiements manuels sont désactivés et Wave n'est pas configuré.
                                <a href="{{ route('finances.wave') }}" class="underline font-bold">Activer Wave</a>
                                ou réactiver les paiements manuels.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            @if($paiementsManuelsActifs)
                                @foreach([
                                    'especes'  => ['Espèces', 'gray', '€'],
                                    'cheque'   => ['Chèque', 'purple', 'CH'],
                                    'virement' => ['Virement', 'teal', 'VR'],
                                ] as $val => [$label, $color, $abbr])
                                    <label class="relative cursor-pointer block">
                                        <input type="radio" name="mode" value="{{ $val }}"
                                               x-model="mode"
                                               class="peer sr-only" required/>
                                        <div class="p-3 rounded-xl border-2 border-gray-200 hover:border-{{ $color }}-300 peer-checked:border-{{ $color }}-500 peer-checked:bg-{{ $color }}-50 transition flex flex-col items-center gap-1.5">
                                            <span class="w-9 h-9 rounded-lg bg-{{ $color }}-100 text-{{ $color }}-700 text-xs font-extrabold flex items-center justify-center">{{ $abbr }}</span>
                                            <span class="text-xs font-bold text-gray-700">{{ $label }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            @endif

                            @if($waveActif)
                                <label class="relative cursor-pointer block">
                                    <input type="radio" name="mode" value="wave"
                                           x-model="mode"
                                           class="peer sr-only" required/>
                                    <div class="p-3 rounded-xl border-2 border-blue-200 hover:border-blue-400 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition flex flex-col items-center gap-1.5">
                                        <span class="w-9 h-9 rounded-lg bg-blue-100 text-blue-700 text-xs font-extrabold flex items-center justify-center">W</span>
                                        <span class="text-xs font-bold text-gray-700">Wave</span>
                                        <span class="text-[9px] font-bold uppercase text-blue-600">En ligne</span>
                                    </div>
                                </label>
                            @endif
                        </div>

                        @if(! $paiementsManuelsActifs)
                            <p class="mt-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 flex items-start gap-2">
                                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Les paiements manuels sont désactivés. <strong>Seul Wave (paiement en ligne)</strong> est disponible pour ce paiement.</span>
                            </p>
                        @elseif(! $waveActif)
                            <p class="mt-3 text-xs text-blue-700 bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 flex items-start gap-2">
                                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Wave n'est pas activé. <a href="{{ route('finances.wave') }}" class="underline font-bold">Activer Wave</a> pour proposer le paiement en ligne aux parents.</span>
                            </p>
                        @endif
                    @endif
                </div>

                {{-- Poste ciblé --}}
                <div>
                    <label class="text-[11px] uppercase tracking-wider text-gray-500 font-bold block mb-2">Poste à régler</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="poste_cible" value="auto" class="peer sr-only" @checked(old('poste_cible', 'auto') === 'auto') required>
                            <div class="p-3 rounded-xl border-2 border-gray-200 peer-checked:border-brand-500 peer-checked:bg-brand-50 text-center">
                                <span class="text-xs font-bold">Automatique</span>
                                <p class="text-[10px] text-gray-500 mt-0.5">Inscription puis scolarité</p>
                            </div>
                        </label>
                        @if($grille['inscription']['applicable'] && $grille['inscription']['reste'] > 0)
                        <label class="cursor-pointer">
                            <input type="radio" name="poste_cible" value="inscription" class="peer sr-only" @checked(old('poste_cible') === 'inscription')>
                            <div class="p-3 rounded-xl border-2 border-blue-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 text-center">
                                <span class="text-xs font-bold text-blue-800">Inscription</span>
                                <p class="text-[10px] text-blue-600 mt-0.5">Reste {{ number_format($grille['inscription']['reste'], 0, ',', ' ') }} F</p>
                            </div>
                        </label>
                        @endif
                        @if($grille['scolarite']['applicable'] && $grille['scolarite']['reste'] > 0)
                        <label class="cursor-pointer">
                            <input type="radio" name="poste_cible" value="scolarite" class="peer sr-only" @checked(old('poste_cible') === 'scolarite')>
                            <div class="p-3 rounded-xl border-2 border-purple-200 peer-checked:border-purple-500 peer-checked:bg-purple-50 text-center">
                                <span class="text-xs font-bold text-purple-800">Scolarité</span>
                                <p class="text-[10px] text-purple-600 mt-0.5">Reste {{ number_format($grille['scolarite']['reste'], 0, ',', ' ') }} F</p>
                            </div>
                        </label>
                        @endif
                    </div>
                </div>

                {{-- Montant + date --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <div class="flex items-baseline justify-between mb-1.5">
                            <label class="text-[11px] uppercase tracking-wider text-gray-500 font-bold">Montant (FCFA)</label>
                            <button type="button"
                                    @click="montant = reste; $refs.montantInput.value = reste"
                                    class="text-[10px] text-brand-600 hover:underline font-bold">Tout solder ({{ number_format($totalReste, 0, ',', ' ') }})</button>
                        </div>
                        <input type="number" name="montant" min="100" max="{{ $totalReste }}" step="100" required
                               x-ref="montantInput"
                               x-model.number="montant"
                               value="{{ old('montant', $totalReste) }}"
                               class="w-full rounded-xl border-gray-200 focus:border-brand-500 focus:ring-brand-500 text-lg font-bold"/>
                        <p class="text-[10px] text-gray-400 mt-1">
                            Maximum :
                            <span class="font-bold text-gray-600">{{ number_format($totalReste, 0, ',', ' ') }} F</span>
                            <span x-show="montant > reste" class="text-red-600 font-bold ml-1">— Dépasse le reste !</span>
                        </p>
                    </div>
                    <div>
                        <label class="text-[11px] uppercase tracking-wider text-gray-500 font-bold block mb-1.5">Date</label>
                        <input type="date" name="date_paiement" value="{{ old('date_paiement', today()->toDateString()) }}"
                               max="{{ today()->toDateString() }}"
                               class="w-full rounded-xl border-gray-200 focus:border-brand-500 focus:ring-brand-500"/>
                    </div>
                </div>

                {{-- Observations --}}
                <div>
                    <label class="text-[11px] uppercase tracking-wider text-gray-500 font-bold block mb-1.5">Observations <span class="text-gray-300 normal-case">(optionnel)</span></label>
                    <textarea name="observations" rows="2" maxlength="500"
                              class="w-full rounded-xl border-gray-200 focus:border-brand-500 focus:ring-brand-500 text-sm"
                              placeholder="N° de chèque, banque émettrice, référence virement, etc.">{{ old('observations') }}</textarea>
                </div>

                {{-- Actions --}}
                <div class="flex flex-wrap items-center gap-3 pt-3 border-t border-gray-100">
                    <button type="submit"
                            :disabled="montant > reste || montant < 100"
                            :class="(montant > reste || montant < 100) ? 'opacity-50 cursor-not-allowed' : ''"
                            class="px-5 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-bold hover:bg-brand-700 transition shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="mode === 'wave' ? 'Générer le lien Wave' : 'Enregistrer le paiement'">Enregistrer le paiement</span>
                    </button>
                    <a href="{{ route('paiements.index') }}" class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-700 text-sm font-bold hover:bg-gray-50 transition">Annuler</a>

                    {{-- Info contextuelle --}}
                    <p class="text-xs text-gray-500 ml-auto" x-show="mode === 'wave'">
                        Un lien marchand Wave sera généré, à partager au parent.
                    </p>
                    <p class="text-xs text-gray-500 ml-auto" x-show="mode === 'especes' || mode === 'cheque' || mode === 'virement'">
                        Le paiement sera <strong>confirmé immédiatement</strong> avec génération du reçu.
                    </p>
                </div>
            </form>
        @endif
    @endif
</div>
@endsection
