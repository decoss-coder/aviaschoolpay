@extends('layouts.app')

@section('title', $enseignant->nom_complet)
@section('page-title', 'Fiche enseignant')
@section('page-subtitle', $enseignant->nom_complet)

@section('content')
@include('partials.rh-admin-nav')

@php
    $userLabel = $enseignant->user->name
        ?? trim(($enseignant->user->prenom ?? '') . ' ' . ($enseignant->user->nom ?? ''))
        ?: ($enseignant->user->email ?? ('Utilisateur #' . $enseignant->user_id));

    $disciplinesProfil = collect(explode(',', (string) $enseignant->specialite))
        ->map(fn ($item) => trim($item))
        ->filter()
        ->unique()
        ->values();

    $disciplinesAffectations = $enseignant->affectations
        ->pluck('matiere')
        ->filter()
        ->map(fn ($matiere) => $matiere->nom ?? $matiere->libelle ?? null)
        ->filter()
        ->unique()
        ->values();

    $disciplines = $disciplinesProfil
        ->merge($disciplinesAffectations)
        ->filter()
        ->unique()
        ->values();
@endphp

<div x-data="{ openDeleteModal: false }" class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div>
            <a href="{{ route('enseignants.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors mb-3">
                ← Retour à la liste
            </a>

            <h1 class="font-display text-2xl font-extrabold text-gray-900">{{ $enseignant->nom_complet }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ ucfirst($enseignant->statut) }} · {{ $enseignant->matricule_mena ?: 'Sans matricule MENA' }}
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('enseignants.edit', $enseignant) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-brand-100 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-blue-50 hover:border-blue-200 hover:text-blue-700 transition-all">
                Modifier
            </a>

            <a href="{{ route('emploi-du-temps.horaires-externes.index', $enseignant) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-indigo-200 text-indigo-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-indigo-50 transition-all">
                EDT autres écoles
                @php $nbExternes = $enseignant->horairesExternesActifs()->count(); @endphp
                @if($nbExternes > 0)
                    <span class="ml-1 bg-indigo-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ $nbExternes }}</span>
                @endif
            </a>

            <button type="button" @click="openDeleteModal = true" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white text-[13px] font-extrabold rounded-xl shadow-sm hover:shadow-md transition-all">
                Archiver
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="space-y-6">
            <div class="relative overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-brand-800 rounded-2xl shadow-brand-glow p-5 text-white">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-gold-300 to-gold-500"></div>
                <div class="relative flex items-center gap-4">
                    @if(!empty($enseignant->photo_path))
                        <img src="{{ route('enseignants.photo', $enseignant) }}" alt="{{ $enseignant->nom_complet }}" class="w-20 h-20 rounded-2xl object-cover ring-2 ring-white/30 shadow-lg">
                    @else
                        <div class="w-20 h-20 rounded-2xl bg-white/10 flex items-center justify-center text-2xl font-extrabold">
                            {{ strtoupper(substr($enseignant->prenom, 0, 1)) }}{{ strtoupper(substr($enseignant->nom, 0, 1)) }}
                        </div>
                    @endif

                    <div>
                        <p class="font-display text-xl font-extrabold">{{ $enseignant->nom_complet }}</p>
                        <p class="text-sm text-brand-100 mt-1">{{ $enseignant->matricule_mena ?: 'Sans matricule MENA' }}</p>
                        <p class="text-xs text-brand-100 mt-1">Score ponctualité : {{ number_format((float) ($enseignant->score_ponctualite ?? 0), 2, ',', ' ') }}%</p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gold-200/70 rounded-2xl p-5 shadow-card-gold">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="font-display text-base font-extrabold text-gray-900">Disciplines / matières enseignées</h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">Matières enregistrées sur la fiche enseignant</p>
                    </div>
                    <span class="text-xs font-bold text-gold-700 bg-gold-50 border border-gold-200 px-2.5 py-1 rounded-full">{{ $disciplines->count() }}</span>
                </div>

                @if($disciplines->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach($disciplines as $discipline)
                            <span class="inline-flex items-center rounded-full bg-gold-50 border border-gold-200 text-gold-800 px-3 py-1.5 text-[12px] font-extrabold">
                                {{ $discipline }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-gray-200 p-4 text-sm text-gray-400 text-center">
                        Aucune discipline renseignée. Cliquez sur “Modifier” pour ajouter les matières enseignées.
                    </div>
                @endif
            </div>

            <div class="bg-white border border-brand-100/60 rounded-2xl p-5 shadow-card-brand">
                <h3 class="font-display text-base font-extrabold text-gray-900 mb-4">Informations générales</h3>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><span class="text-gray-500">Compte utilisateur</span><span class="font-bold text-gray-800 text-right">{{ $userLabel }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-gray-500">Téléphone</span><span class="font-bold text-gray-800">{{ $enseignant->telephone }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-gray-500">Téléphone 2</span><span class="font-bold text-gray-800">{{ $enseignant->telephone_2 ?: '—' }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-gray-500">Email</span><span class="font-bold text-gray-800 text-right">{{ $enseignant->email ?: '—' }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-gray-500">Sexe</span><span class="font-bold text-gray-800">{{ $enseignant->sexe }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-gray-500">Date de naissance</span><span class="font-bold text-gray-800">{{ $enseignant->date_naissance?->format('d/m/Y') ?: '—' }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-gray-500">Diplôme</span><span class="font-bold text-gray-800 text-right">{{ $enseignant->diplome_plus_eleve ?: '—' }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-gray-500">Date prise de fonction</span><span class="font-bold text-gray-800">{{ $enseignant->date_prise_fonction?->format('d/m/Y') ?: '—' }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-gray-500">Salaire de base</span><span class="font-bold text-gray-800">{{ number_format((float) ($enseignant->salaire_base ?? 0), 0, ',', ' ') }} F</span></div>
                    <div class="flex justify-between gap-3"><span class="text-gray-500">Banque</span><span class="font-bold text-gray-800">{{ $enseignant->banque ?: '—' }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-gray-500">Numéro de compte</span><span class="font-bold text-gray-800 text-right">{{ $enseignant->numero_compte ?: '—' }}</span></div>
                </div>

                @if($enseignant->adresse)
                    <div class="mt-4 pt-4 border-t border-brand-100/60">
                        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Adresse</p>
                        <p class="text-sm text-gray-700">{{ $enseignant->adresse }}</p>
                    </div>
                @endif
            </div>

            <div class="bg-white border border-brand-100/60 rounded-2xl p-5 shadow-card-brand">
                <h3 class="font-display text-base font-extrabold text-gray-900 mb-4">Résumé 30 jours</h3>
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-3 text-center"><p class="text-[10px] uppercase tracking-wider font-bold text-emerald-600">Présents</p><p class="mt-1 text-xl font-extrabold text-emerald-700">{{ $stats30j['presents'] ?? 0 }}</p></div>
                    <div class="rounded-xl bg-amber-50 border border-amber-100 p-3 text-center"><p class="text-[10px] uppercase tracking-wider font-bold text-amber-600">Retards</p><p class="mt-1 text-xl font-extrabold text-amber-700">{{ $stats30j['retards'] ?? 0 }}</p></div>
                    <div class="rounded-xl bg-red-50 border border-red-100 p-3 text-center"><p class="text-[10px] uppercase tracking-wider font-bold text-red-600">Anomalies</p><p class="mt-1 text-xl font-extrabold text-red-700">{{ $stats30j['anomalies'] ?? 0 }}</p></div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-brand-100/60 rounded-2xl p-5 shadow-card-brand">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-display text-base font-extrabold text-gray-900">Classes & affectations actuelles</h3>
                    <span class="text-xs text-gray-400">{{ $enseignant->affectations->count() + $enseignant->classesPrincipales->count() }} élément(s)</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @forelse($enseignant->classesPrincipales as $classe)
                        <div class="rounded-xl border border-emerald-100 bg-emerald-50/70 p-4">
                            <p class="text-[10px] uppercase tracking-wider font-bold text-emerald-600">Professeur principal</p>
                            <p class="mt-1 text-sm font-extrabold text-gray-900">{{ $classe->nom }}</p>
                            <p class="text-[11px] text-gray-500">{{ $classe->niveau->libelle ?? $classe->niveau->code ?? '—' }}</p>
                        </div>
                    @empty
                    @endforelse

                    @foreach($enseignant->affectations as $aff)
                        <div class="rounded-xl border border-brand-100 bg-brand-50/60 p-4">
                            <p class="text-sm font-extrabold text-gray-900">{{ $aff->classe->nom ?? 'Classe non définie' }}</p>
                            <p class="text-[11px] text-gray-500">
                                {{ $aff->classe->niveau->libelle ?? $aff->classe->niveau->code ?? '—' }}
                                @if($aff->matiere)
                                    · {{ $aff->matiere->nom ?? $aff->matiere->libelle ?? 'Matière' }}
                                @endif
                            </p>
                        </div>
                    @endforeach

                    @if($enseignant->classesPrincipales->isEmpty() && $enseignant->affectations->isEmpty())
                        <div class="md:col-span-2 rounded-xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-400">Aucune affectation active</div>
                    @endif
                </div>
            </div>

            <div class="bg-white border border-brand-100/60 rounded-2xl p-5 shadow-card-brand">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-display text-base font-extrabold text-gray-900">Pointages récents</h3>
                    <span class="text-xs text-gray-400">{{ $enseignant->pointages->count() }} affiché(s)</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-brand-100/60">
                                <th class="py-2 text-left text-[10px] uppercase tracking-wider font-extrabold text-brand-700">Date</th>
                                <th class="py-2 text-left text-[10px] uppercase tracking-wider font-extrabold text-brand-700">Heure</th>
                                <th class="py-2 text-left text-[10px] uppercase tracking-wider font-extrabold text-brand-700">Type</th>
                                <th class="py-2 text-left text-[10px] uppercase tracking-wider font-extrabold text-brand-700">Statut</th>
                                <th class="py-2 text-left text-[10px] uppercase tracking-wider font-extrabold text-brand-700">GPS</th>
                                <th class="py-2 text-left text-[10px] uppercase tracking-wider font-extrabold text-brand-700">EDT</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-50/60">
                            @forelse($enseignant->pointages as $pointage)
                                <tr>
                                    <td class="py-3 text-sm text-gray-700">{{ $pointage->date?->format('d/m/Y') ?: '—' }}</td>
                                    <td class="py-3 text-sm font-bold text-gray-900">{{ $pointage->heure_scan ?: '—' }}</td>
                                    <td class="py-3 text-sm text-gray-700">{{ $pointage->type_scan_libelle ?? ucfirst($pointage->type_scan) }}</td>
                                    <td class="py-3"><span class="inline-flex items-center text-[11px] font-bold px-2.5 py-1 rounded-full border {{ $pointage->statut === 'present' ? 'text-emerald-700 bg-emerald-50 border-emerald-200/60' : ($pointage->statut === 'retard' ? 'text-amber-700 bg-amber-50 border-amber-200/60' : 'text-red-700 bg-red-50 border-red-200/60') }}">{{ $pointage->statut_libelle ?? ucfirst($pointage->statut) }}</span></td>
                                    <td class="py-3 text-sm {{ $pointage->gps_valide ? 'text-emerald-700' : 'text-red-700' }}">{{ $pointage->gps_valide ? 'Valide' : 'Invalide' }}</td>
                                    <td class="py-3 text-sm {{ $pointage->conforme_emploi_temps ? 'text-emerald-700' : 'text-red-700' }}">{{ $pointage->conforme_emploi_temps ? 'Conforme' : 'Non conforme' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-8 text-center text-sm text-gray-400">Aucun pointage récent</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white border border-brand-100/60 rounded-2xl p-5 shadow-card-brand">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-display text-base font-extrabold text-gray-900">Alertes récentes</h3>
                    <span class="text-xs text-gray-400">{{ $enseignant->alertes_non_traitees_count ?? 0 }} non traitée(s)</span>
                </div>
                <div class="space-y-3">
                    @forelse($enseignant->alertesPointage as $alerte)
                        <div class="rounded-xl border p-4 {{ $alerte->traitee ? 'border-gray-200 bg-gray-50/50' : 'border-red-200 bg-red-50/60' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div><p class="text-sm font-extrabold text-gray-900">{{ $alerte->message }}</p><p class="text-[11px] text-gray-500 mt-1">{{ $alerte->date?->format('d/m/Y') ?: '—' }} · {{ $alerte->gravite_libelle ?? ucfirst($alerte->gravite) }}</p></div>
                                <span class="inline-flex items-center text-[11px] font-bold px-2.5 py-1 rounded-full border {{ $alerte->traitee ? 'text-emerald-700 bg-emerald-50 border-emerald-200/60' : 'text-red-700 bg-red-50 border-red-200/60' }}">{{ $alerte->traitee ? 'Traitée' : 'À traiter' }}</span>
                            </div>
                            @if($alerte->commentaire_traitement)<p class="mt-2 text-[12px] text-gray-600">{{ $alerte->commentaire_traitement }}</p>@endif
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-400">Aucune alerte récente</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <template x-teleport="body">
        <div x-show="openDeleteModal" x-cloak class="fixed inset-0 z-[9999] bg-black/50 backdrop-blur-[2px]" @click.self="openDeleteModal = false" @keydown.escape.window="openDeleteModal = false">
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-red-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-red-100 bg-gradient-to-r from-red-50 via-white to-red-50/40">
                        <h3 class="font-display text-lg font-extrabold text-gray-900">Confirmer l’archivage</h3>
                        <p class="text-[12px] text-gray-500 mt-0.5">L’enseignant sera rendu inactif</p>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-700">Confirmez l’archivage de <strong>{{ $enseignant->nom_complet }}</strong>.</p>
                        <form action="{{ route('enseignants.destroy', $enseignant) }}" method="POST" class="mt-6 flex items-center justify-end gap-3">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="confirm_delete" value="1">
                            <button type="button" @click="openDeleteModal = false" class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">Annuler</button>
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white text-[13px] font-extrabold rounded-xl shadow-sm hover:shadow-md transition-all">Archiver l’enseignant</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
