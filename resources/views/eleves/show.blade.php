@extends('layouts.app')

@section('title', $eleve->prenom . ' ' . $eleve->nom)
@section('page-title', $eleve->nom . ' ' . $eleve->prenom)
@section('page-subtitle', 'Matricule ' . $eleve->matricule_interne . ' — ' . ($eleve->classe?->nom ?? $eleve->inscriptionEnCours?->classe?->nom ?? 'Non inscrit'))

@section('content')
@php
    $classeCourante = $eleve->classe ?? $eleve->inscriptionEnCours?->classe;
    $niveauCourant = $classeCourante?->niveau;
    $classeLabel = $classeCourante?->nom ?? 'Non inscrit';
    $niveauLabel = $niveauCourant ? ($niveauCourant->libelle ?? $niveauCourant->code) : null;

    $annee = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement((int) $eleve->etablissement_id);
    $financeResume = \App\Services\Eleve\EleveScolariteService::resumePourEleve($eleve, $annee?->id);
    $grille = \App\Services\Finance\PaiementService::grilleDepuisResume($financeResume);

    $inscription = $grille['inscription'];
    $scolarite = $grille['scolarite'];
    $total = $grille['total'];

    $net = (int) ($total['montant'] ?? 0);
    $paye = (int) ($total['paye'] ?? 0);
    $reste = (int) ($total['reste'] ?? 0);
    $taux = $net > 0 ? round(($paye / $net) * 100) : 0;

    $estAff = $eleve->statut_eleve === 'AFF';
    $estNaff = $eleve->statut_eleve === 'NAFF';
    $statutLabel = $financeResume['statut_eleve_libelle'] ?? ($estAff ? 'Affecté' : ($estNaff ? 'Non affecté' : '—'));
    $modeFacturation = $estAff ? 'Inscription uniquement' : ($estNaff ? 'Inscription + scolarité' : 'Statut à compléter');

    $derniereMoyenne = $eleve->moyennesGenerales->sortByDesc('trimestre_id')->first();
    $parent = $eleve->parents->first();
    $paiementsConfirmes = $eleve->paiements->where('statut', 'confirme');
@endphp

<div x-data="{ activeTab: 'infos' }" class="space-y-6">
    <div class="relative overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-brand-800 rounded-2xl shadow-brand-glow">
        <div class="absolute top-0 right-0 bottom-0 w-1 bg-gradient-to-b from-gold-300 via-gold-400 to-gold-500"></div>
        <div class="relative p-6 lg:p-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center gap-6">
                <div class="relative flex-shrink-0">
                    <div class="w-24 h-24 lg:w-28 lg:h-28 rounded-2xl overflow-hidden ring-4 ring-white/20 shadow-2xl">
                        @if($eleve->photo_path)
                            <img src="{{ asset('storage/' . $eleve->photo_path) }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full bg-gradient-to-br {{ $eleve->sexe === 'F' ? 'from-pink-400 to-pink-600' : 'from-blue-400 to-blue-600' }} flex items-center justify-center">
                                <span class="font-display text-4xl font-extrabold text-white">{{ strtoupper(substr($eleve->prenom, 0, 1)) }}{{ strtoupper(substr($eleve->nom, 0, 1)) }}</span>
                            </div>
                        @endif
                    </div>
                    <span class="absolute -bottom-2 -right-2 px-2 py-1 bg-gradient-to-br from-gold-300 to-gold-500 text-brand-900 text-[10px] font-extrabold rounded-lg shadow-gold-glow">
                        {{ $eleve->sexe === 'F' ? 'F' : 'G' }} · {{ $eleve->age }} ans
                    </span>
                </div>

                <div class="flex-1 min-w-0">
                    <h1 class="font-display text-2xl lg:text-3xl font-extrabold text-white tracking-tight leading-tight">{{ $eleve->prenom }} {{ $eleve->nom }}</h1>
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white/15 backdrop-blur text-white text-[11px] font-bold rounded-full border border-white/20">• {{ $eleve->matricule_interne }}</span>
                        @if($eleve->matricule_desps)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gold-400/20 backdrop-blur text-gold-100 text-[11px] font-bold rounded-full border border-gold-300/40">DESPS · {{ $eleve->matricule_desps }}</span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-red-500/30 backdrop-blur text-red-100 text-[11px] font-bold rounded-full border border-red-300/40">Sans matricule DESPS</span>
                        @endif
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white/15 backdrop-blur text-white text-[11px] font-bold rounded-full border border-white/20">{{ $classeLabel }} @if($niveauLabel)<span class="text-brand-100">· {{ $niveauLabel }}</span>@endif</span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 {{ $estAff ? 'bg-emerald-400/20 text-emerald-100 border-emerald-300/40' : 'bg-amber-400/20 text-amber-100 border-amber-300/40' }} backdrop-blur text-[11px] font-bold rounded-full border">{{ $eleve->statut_eleve ?: '—' }} · {{ $statutLabel }}</span>
                    </div>
                    <p class="text-[12px] text-brand-100 mt-3">
                        Né{{ $eleve->sexe === 'F' ? 'e' : '' }} le <span class="font-bold text-white">{{ $eleve->date_naissance?->format('d/m/Y') ?? '—' }}</span>
                        @if($eleve->lieu_naissance) à <span class="font-bold text-white">{{ $eleve->lieu_naissance }}</span>@endif
                        @if($eleve->nationalite) · <span class="font-bold text-white">{{ $eleve->nationalite }}</span>@endif
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 lg:flex-col lg:items-stretch">
                    <a href="{{ route('paiements.create', ['eleve_id' => $eleve->id]) }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-gold-300 to-gold-500 text-brand-900 text-[13px] font-extrabold rounded-xl shadow-gold-glow hover:shadow-lg transition-all">Enregistrer paiement</a>
                    <a href="{{ route('eleves.edit', $eleve) }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white/15 backdrop-blur text-white text-[13px] font-bold rounded-xl border border-white/20 hover:bg-white/25 transition-all">Modifier</a>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($financeResume['message']))
        <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900 font-semibold">
            {{ $financeResume['message'] }}
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
        <div class="relative overflow-hidden bg-gradient-to-br from-white to-violet-50/50 rounded-2xl border border-violet-100/60 shadow-card-violet p-5">
            <div class="w-10 h-10 bg-gradient-to-br from-violet-400 to-purple-600 rounded-lg flex items-center justify-center shadow-sm shadow-violet-500/30 mb-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10"/></svg>
            </div>
            <p class="font-display text-2xl font-extrabold {{ $derniereMoyenne && $derniereMoyenne->moyenne_generale >= 10 ? 'text-brand-600' : 'text-gray-400' }}">{{ $derniereMoyenne ? number_format($derniereMoyenne->moyenne_generale, 2) : '—' }}<span class="text-sm text-gray-400 font-medium">/20</span></p>
            <p class="text-[11px] text-gray-500 font-medium mt-1">Moyenne actuelle{{ $derniereMoyenne ? ' · Rang ' . $derniereMoyenne->rang . 'e' : '' }}</p>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-blue-50/50 rounded-2xl border border-blue-100/60 shadow-card-blue p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center shadow-sm"><span class="text-white font-extrabold text-xs">INS</span></div>
                <span class="text-[10px] font-bold text-blue-700 bg-blue-100 border border-blue-200/60 px-2 py-0.5 rounded-full">{{ $inscription['reste'] <= 0 && $inscription['montant'] > 0 ? 'SOLDÉ' : 'DÛ' }}</span>
            </div>
            <p class="font-display text-xl font-extrabold text-gray-900">{{ number_format($inscription['paye'], 0, ',', ' ') }}<span class="text-xs text-gray-400 font-medium"> / {{ number_format($inscription['montant'], 0, ',', ' ') }} F</span></p>
            <p class="text-[11px] text-gray-500 font-medium mt-1">Inscription payée</p>
            <p class="text-[10px] text-blue-700 font-bold mt-2">Reste : {{ number_format($inscription['reste'], 0, ',', ' ') }} F</p>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-purple-50/50 rounded-2xl border border-purple-100/60 shadow-card p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg flex items-center justify-center shadow-sm"><span class="text-white font-extrabold text-xs">SCO</span></div>
                <span class="text-[10px] font-bold {{ $scolarite['applicable'] ? 'text-purple-700 bg-purple-100 border-purple-200/60' : 'text-gray-500 bg-gray-100 border-gray-200/60' }} border px-2 py-0.5 rounded-full">{{ $scolarite['applicable'] ? 'NAFF' : 'Non applicable' }}</span>
            </div>
            <p class="font-display text-xl font-extrabold text-gray-900">{{ number_format($scolarite['paye'], 0, ',', ' ') }}<span class="text-xs text-gray-400 font-medium"> / {{ number_format($scolarite['montant'], 0, ',', ' ') }} F</span></p>
            <p class="text-[11px] text-gray-500 font-medium mt-1">Scolarité payée</p>
            <p class="text-[10px] text-purple-700 font-bold mt-2">Reste : {{ number_format($scolarite['reste'], 0, ',', ' ') }} F</p>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white {{ $reste > 0 ? 'to-red-50/50 border-red-100/60' : 'to-brand-50/50 border-brand-100/60' }} rounded-2xl border shadow-card p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 {{ $reste > 0 ? 'bg-gradient-to-br from-red-400 to-red-600' : 'bg-gradient-to-br from-brand-400 to-brand-600' }} rounded-lg flex items-center justify-center shadow-sm"><span class="text-white font-extrabold text-xs">TOT</span></div>
                <span class="text-[10px] font-bold {{ $reste > 0 ? 'text-red-700 bg-red-100' : 'text-brand-700 bg-brand-100' }} border border-current/20 px-2 py-0.5 rounded-full">{{ $taux }}%</span>
            </div>
            <p class="font-display text-xl font-extrabold text-gray-900">{{ number_format($paye, 0, ',', ' ') }}<span class="text-xs text-gray-400 font-medium"> / {{ number_format($net, 0, ',', ' ') }} F</span></p>
            <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2 overflow-hidden"><div class="h-1.5 rounded-full {{ $reste > 0 ? 'bg-gradient-to-r from-red-400 to-red-600' : 'bg-gradient-to-r from-brand-400 to-brand-600' }}" style="width: {{ min($taux, 100) }}%"></div></div>
            <p class="text-[11px] text-gray-500 font-medium mt-2">Total payé</p>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-gold-50/50 rounded-2xl border border-gold-200/60 shadow-card-gold p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-lg flex items-center justify-center shadow-gold-glow"><span class="text-white font-extrabold text-xs">REST</span></div>
                <span class="text-[10px] font-bold text-gray-600 bg-white border border-gray-200 px-2 py-0.5 rounded-full">{{ $paiementsConfirmes->count() }} paiement(s)</span>
            </div>
            <p class="font-display text-2xl font-extrabold {{ $reste > 0 ? 'text-red-600' : 'text-brand-600' }}">{{ number_format($reste, 0, ',', ' ') }}<span class="text-xs text-gray-400 font-medium"> F</span></p>
            <p class="text-[11px] text-gray-500 font-medium mt-1">{{ $reste > 0 ? 'Reste à payer' : 'Tout est soldé' }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-brand-100/60 shadow-card-brand overflow-hidden">
        <div class="flex items-center gap-1 px-4 py-2 border-b border-brand-100/60 bg-gradient-to-r from-brand-50/60 via-white to-gold-50/30 overflow-x-auto">
            @foreach([
                ['id' => 'infos', 'label' => 'Informations'],
                ['id' => 'frais', 'label' => 'Point frais'],
                ['id' => 'parents', 'label' => 'Parents'],
                ['id' => 'moyennes', 'label' => 'Moyennes'],
                ['id' => 'paiements', 'label' => 'Paiements'],
            ] as $tab)
                <button @click="activeTab = '{{ $tab['id'] }}'" type="button" :class="activeTab === '{{ $tab['id'] }}' ? 'bg-gradient-to-r from-brand-500 to-brand-700 text-white shadow-brand-glow' : 'text-gray-600 hover:bg-brand-50 hover:text-brand-700'" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-[13px] font-bold transition-all whitespace-nowrap">{{ $tab['label'] }}</button>
            @endforeach
        </div>

        <div x-show="activeTab === 'infos'" class="p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-brand-50/30 border border-brand-100/60 rounded-xl"><dt class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Nom complet</dt><dd class="text-sm font-bold text-gray-900 mt-1">{{ $eleve->nom }} {{ $eleve->prenom }}</dd></div>
                <div class="p-4 bg-gold-50/30 border border-gold-100/60 rounded-xl"><dt class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Classe</dt><dd class="text-sm font-bold text-gray-900 mt-1">{{ $classeLabel }} @if($niveauLabel)<span class="text-brand-600">· {{ $niveauLabel }}</span>@endif</dd></div>
                <div class="p-4 bg-blue-50/30 border border-blue-100/60 rounded-xl"><dt class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Date de naissance</dt><dd class="text-sm font-bold text-gray-900 mt-1">{{ $eleve->date_naissance?->format('d/m/Y') ?? '—' }}{{ $eleve->age ? ' (' . $eleve->age . ' ans)' : '' }}</dd></div>
                <div class="p-4 bg-violet-50/30 border border-violet-100/60 rounded-xl"><dt class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Statut / facturation</dt><dd class="text-sm font-bold text-gray-900 mt-1">{{ $eleve->statut_eleve ?: '—' }} · {{ $modeFacturation }}</dd></div>
                <div class="p-4 bg-gray-50 border border-gray-200/60 rounded-xl md:col-span-2"><dt class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Adresse</dt><dd class="text-sm font-bold text-gray-900 mt-1">{{ $eleve->adresse ?? '—' }}</dd></div>
            </dl>
        </div>

        <div x-show="activeTab === 'frais'" x-cloak class="p-6">
            <div class="overflow-x-auto rounded-xl border border-gray-100">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-[10px] uppercase text-gray-500"><tr><th class="px-4 py-2 text-left">Poste</th><th class="px-4 py-2 text-right">Montant dû</th><th class="px-4 py-2 text-right">Payé</th><th class="px-4 py-2 text-right">Reste</th><th class="px-4 py-2 text-center">État</th></tr></thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr><td class="px-4 py-3 font-bold">{{ $inscription['libelle'] }}</td><td class="px-4 py-3 text-right">{{ number_format($inscription['montant'],0,',',' ') }} F</td><td class="px-4 py-3 text-right text-emerald-700 font-bold">{{ number_format($inscription['paye'],0,',',' ') }} F</td><td class="px-4 py-3 text-right font-extrabold text-blue-700">{{ number_format($inscription['reste'],0,',',' ') }} F</td><td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $inscription['reste'] <= 0 && $inscription['montant'] > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $inscription['reste'] <= 0 && $inscription['montant'] > 0 ? 'SOLDÉ' : 'À régler' }}</span></td></tr>
                        <tr><td class="px-4 py-3 font-bold">Scolarité annuelle</td><td class="px-4 py-3 text-right">{{ number_format($scolarite['montant'],0,',',' ') }} F</td><td class="px-4 py-3 text-right text-emerald-700 font-bold">{{ number_format($scolarite['paye'],0,',',' ') }} F</td><td class="px-4 py-3 text-right font-extrabold text-purple-700">{{ number_format($scolarite['reste'],0,',',' ') }} F</td><td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $scolarite['applicable'] ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-500' }}">{{ $scolarite['applicable'] ? 'Applicable' : 'Non applicable AFF' }}</span></td></tr>
                        <tr class="bg-gray-900 text-white"><td class="px-4 py-3 font-extrabold">TOTAL</td><td class="px-4 py-3 text-right font-extrabold">{{ number_format($net,0,',',' ') }} F</td><td class="px-4 py-3 text-right font-extrabold text-emerald-300">{{ number_format($paye,0,',',' ') }} F</td><td class="px-4 py-3 text-right font-extrabold text-amber-300">{{ number_format($reste,0,',',' ') }} F</td><td class="px-4 py-3 text-center font-bold">{{ $taux }}%</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('paiements.create', ['eleve_id' => $eleve->id]) }}" class="px-4 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-bold">Enregistrer un paiement</a>
                <a href="{{ route('finances.point-postes.index', ['q' => $eleve->matricule_interne]) }}" class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-700 text-sm font-bold">Voir dans le point scolarité & inscription</a>
            </div>
        </div>

        <div x-show="activeTab === 'parents'" x-cloak class="p-6">
            @if($eleve->parents->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($eleve->parents as $p)
                        <div class="bg-gradient-to-br from-white to-violet-50/40 border border-violet-100/60 rounded-2xl p-5 shadow-card-violet"><p class="font-display text-base font-extrabold text-gray-900">{{ $p->nom_complet ?? trim(($p->prenom ?? '').' '.($p->nom ?? '')) ?: 'Parent' }}</p><p class="text-sm text-gray-600 mt-2">{{ $p->telephone ?? '—' }} @if($p->email) · {{ $p->email }} @endif</p></div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 text-gray-500 font-semibold">Aucun parent enregistré.</div>
            @endif
        </div>

        <div x-show="activeTab === 'moyennes'" x-cloak class="p-6">
            @if($eleve->moyennesGenerales->count() > 0)
                <div class="space-y-3">
                    @foreach($eleve->moyennesGenerales->sortBy('trimestre_id') as $moy)
                        <div class="flex items-center justify-between p-4 bg-violet-50/50 border border-violet-100/60 rounded-xl"><div><p class="font-bold text-gray-900">{{ $moy->trimestre->libelle ?? 'Trimestre' }}</p><p class="text-xs text-gray-500">Rang : {{ $moy->rang }}e</p></div><p class="font-display text-2xl font-extrabold {{ $moy->moyenne_generale >= 10 ? 'text-brand-600' : 'text-red-600' }}">{{ number_format($moy->moyenne_generale, 2) }}/20</p></div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 text-gray-500 font-semibold">Aucune moyenne enregistrée.</div>
            @endif
        </div>

        <div x-show="activeTab === 'paiements'" x-cloak class="p-6">
            @if($eleve->paiements->count() > 0)
                <div class="space-y-2">
                    @foreach($eleve->paiements->sortByDesc('date_paiement') as $p)
                        <div class="flex items-center justify-between gap-4 p-3 bg-gold-50/30 border border-gold-100/60 rounded-xl"><div><p class="text-sm font-bold text-gray-900">{{ $p->reference ?? 'Paiement' }}</p><p class="text-[11px] text-gray-500">{{ $p->date_paiement?->format('d/m/Y') }} · {{ str_replace('_', ' ', $p->mode) }} · {{ $p->statut }}</p></div><p class="font-display text-base font-extrabold text-brand-700">{{ number_format($p->montant, 0, ',', ' ') }} F</p></div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10"><p class="font-display text-base font-bold text-gray-700">Aucun paiement</p><a href="{{ route('paiements.create', ['eleve_id' => $eleve->id]) }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-gradient-to-r from-gold-300 to-gold-500 text-brand-900 text-[13px] font-bold rounded-xl shadow-gold-glow">Enregistrer le premier paiement</a></div>
            @endif
        </div>
    </div>
</div>
@endsection
