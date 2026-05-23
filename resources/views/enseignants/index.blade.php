@extends('layouts.app')

@section('title', 'Enseignants')
@section('page-title', 'Gestion des enseignants')
@section('page-subtitle', ($stats['total'] ?? $enseignants->total()) . ' enseignants — ' . ($annee->libelle ?? 'Année scolaire en cours'))

@section('content')
@include('partials.rh-admin-nav')
<div x-data="{
    openDeleteModal: false,
    deleteUrl: '',
    deleteNom: '',
    openDeleteTeacherModal(url, nom) {
        this.deleteUrl = url;
        this.deleteNom = nom;
        this.openDeleteModal = true;
    },
    closeDeleteTeacherModal() {
        this.openDeleteModal = false;
        this.deleteUrl = '';
        this.deleteNom = '';
    }
}">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('enseignants.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nouvel enseignant
            </a>
        </div>

        <form method="GET" class="flex items-center gap-2 flex-wrap">
            <div class="relative min-w-[220px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Nom, prénom, matricule, téléphone..."
                       class="w-full lg:w-72 pl-10 pr-4 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-300 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm">
                <svg class="w-4 h-4 text-brand-400 absolute left-3 top-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <select name="statut" onchange="this.form.submit()"
                    class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm text-gray-700 focus:border-brand-300 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                <option value="">Tous statuts</option>
                @foreach($statutsDisponibles as $statut)
                    <option value="{{ $statut }}" {{ request('statut') === $statut ? 'selected' : '' }}>
                        {{ ucfirst($statut) }}
                    </option>
                @endforeach
            </select>

            <select name="specialite" onchange="this.form.submit()"
                    class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm text-gray-700 focus:border-brand-300 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                <option value="">Toutes spécialités</option>
                @foreach($specialitesDisponibles as $specialite)
                    <option value="{{ $specialite }}" {{ request('specialite') === $specialite ? 'selected' : '' }}>
                        {{ $specialite }}
                    </option>
                @endforeach
            </select>

            <select name="presence" onchange="this.form.submit()"
                    class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm text-gray-700 focus:border-brand-300 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                <option value="">Présence du jour</option>
                <option value="present" {{ request('presence') === 'present' ? 'selected' : '' }}>Présents</option>
                <option value="absent" {{ request('presence') === 'absent' ? 'selected' : '' }}>Absents</option>
                <option value="retard" {{ request('presence') === 'retard' ? 'selected' : '' }}>Retards</option>
                <option value="anormal" {{ request('presence') === 'anormal' ? 'selected' : '' }}>Anomalies</option>
            </select>
        </form>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
        <div class="relative overflow-hidden bg-gradient-to-br from-white to-brand-50/50 border border-brand-100/60 rounded-xl p-4 shadow-card-brand">
            <p class="text-[11px] text-gray-500 font-semibold uppercase tracking-[0.12em]">Total</p>
            <p class="font-display text-3xl font-extrabold text-gray-900 mt-2">{{ $stats['total'] ?? 0 }}</p>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-emerald-50/60 border border-emerald-100/60 rounded-xl p-4 shadow-sm">
            <p class="text-[11px] text-emerald-600 font-semibold uppercase tracking-[0.12em]">Présents</p>
            <p class="font-display text-3xl font-extrabold text-emerald-700 mt-2">{{ $stats['presents'] ?? 0 }}</p>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-amber-50/60 border border-amber-100/60 rounded-xl p-4 shadow-sm">
            <p class="text-[11px] text-amber-600 font-semibold uppercase tracking-[0.12em]">Retards</p>
            <p class="font-display text-3xl font-extrabold text-amber-700 mt-2">{{ $stats['retards'] ?? 0 }}</p>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-red-50/60 border border-red-100/60 rounded-xl p-4 shadow-sm">
            <p class="text-[11px] text-red-600 font-semibold uppercase tracking-[0.12em]">Absents</p>
            <p class="font-display text-3xl font-extrabold text-red-700 mt-2">{{ $stats['absents'] ?? 0 }}</p>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-gold-50/60 border border-gold-200/60 rounded-xl p-4 shadow-sm">
            <p class="text-[11px] text-gold-700 font-semibold uppercase tracking-[0.12em]">Alertes</p>
            <p class="font-display text-3xl font-extrabold text-gold-700 mt-2">{{ $stats['alertes'] ?? 0 }}</p>
        </div>
    </div>

    <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/20 rounded-2xl border border-brand-100/60 shadow-card-brand">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gradient-to-r from-brand-50/60 via-white to-gold-50/30 border-b border-brand-100/60">
                        <th class="px-6 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Enseignant</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Compte</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Spécialité</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Statut</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Classes</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Pointage du jour</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Ponctualité</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Alertes</th>
                        <th class="px-4 py-3.5 text-center text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-50/60">
                    @forelse($enseignants as $enseignant)
                        @php
                            $pointageJour = $enseignant->pointages->first();
                            $affectations = $enseignant->affectations->take(2);
                            $classesPrincipales = $enseignant->classesPrincipales->take(2);

                            $userLabel = $enseignant->user->name
                                ?? trim(($enseignant->user->prenom ?? '') . ' ' . ($enseignant->user->nom ?? ''))
                                ?: ($enseignant->user->email ?? ('Utilisateur #' . $enseignant->user_id));
                        @endphp

                        <tr class="hover:bg-brand-50/30 transition-colors cursor-pointer"
                            onclick="window.location='{{ route('enseignants.show', $enseignant) }}'">
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    @if(!empty($enseignant->photo_path))
                                        <img src="{{ route('enseignants.photo', $enseignant) }}"
                                             alt="{{ $enseignant->nom_complet }}"
                                             class="w-10 h-10 rounded-full object-cover ring-2 ring-white shadow-sm flex-shrink-0">
                                    @else
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-[11px] font-extrabold shadow-sm ring-2 ring-white flex-shrink-0 bg-gradient-to-br from-brand-400 to-brand-600 text-white">
                                            {{ strtoupper(substr($enseignant->prenom, 0, 1)) }}{{ strtoupper(substr($enseignant->nom, 0, 1)) }}
                                        </div>
                                    @endif

                                    <div class="min-w-0">
                                        <p class="text-[13px] font-bold text-gray-900 truncate">{{ $enseignant->nom_complet }}</p>
                                        <p class="text-[11px] text-gray-400">
                                            {{ $enseignant->telephone }} · {{ $enseignant->matricule_mena ?: 'Sans matricule MENA' }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3.5">
                                <div class="space-y-1">
                                    <p class="text-[12px] font-bold text-gray-800">{{ $userLabel }}</p>
                                    <p class="text-[10px] text-gray-400">{{ $enseignant->user->email ?? '—' }}</p>
                                </div>
                            </td>

                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center text-[11px] font-bold text-blue-700 bg-blue-50 border border-blue-200/60 px-2.5 py-1 rounded-full">
                                    {{ $enseignant->specialite ?: '—' }}
                                </span>
                            </td>

                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center text-[11px] font-bold text-brand-700 bg-brand-50 border border-brand-200/60 px-2.5 py-1 rounded-full">
                                    {{ ucfirst($enseignant->statut) }}
                                </span>
                            </td>

                            <td class="px-4 py-3.5">
                                <div class="space-y-1">
                                    @foreach($classesPrincipales as $classe)
                                        <div class="text-[11px] font-bold text-emerald-700">PP · {{ $classe->nom }}</div>
                                    @endforeach

                                    @foreach($affectations as $aff)
                                        <div class="text-[11px] text-gray-700">
                                            {{ $aff->classe->nom ?? '—' }}
                                            @if($aff->matiere)
                                                · {{ $aff->matiere->nom ?? $aff->matiere->libelle ?? 'Matière' }}
                                            @endif
                                        </div>
                                    @endforeach

                                    @if($classesPrincipales->isEmpty() && $affectations->isEmpty())
                                        <span class="text-[11px] text-gray-300">—</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-4 py-3.5">
                                @if($pointageJour)
                                    @php
                                        $badge = match ($pointageJour->statut) {
                                            'present' => 'text-emerald-700 bg-emerald-50 border-emerald-200/60',
                                            'retard' => 'text-amber-700 bg-amber-50 border-amber-200/60',
                                            'anomalie' => 'text-red-700 bg-red-50 border-red-200/60',
                                            default => 'text-gray-700 bg-gray-50 border-gray-200/60',
                                        };
                                    @endphp

                                    <div class="space-y-1">
                                        <span class="inline-flex items-center text-[11px] font-bold border px-2.5 py-1 rounded-full {{ $badge }}">
                                            {{ $pointageJour->statut_libelle ?? ucfirst($pointageJour->statut) }}
                                        </span>
                                        <p class="text-[10px] text-gray-400">{{ $pointageJour->heure_scan }}</p>
                                    </div>
                                @else
                                    <span class="inline-flex items-center text-[11px] font-bold text-red-700 bg-red-50 border border-red-200/60 px-2.5 py-1 rounded-full">
                                        Absent
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3.5">
                                @php $score = (float) ($enseignant->score_ponctualite ?? 0); @endphp
                                <p class="font-display text-sm font-extrabold {{ $score >= 80 ? 'text-emerald-700' : ($score >= 50 ? 'text-amber-700' : 'text-red-700') }}">
                                    {{ number_format($score, 2, ',', ' ') }}<span class="text-xs text-gray-400">%</span>
                                </p>
                            </td>

                            <td class="px-4 py-3.5">
                                @if(($enseignant->alertes_non_traitees_count ?? 0) > 0)
                                    <span class="inline-flex items-center text-[11px] font-bold text-red-700 bg-red-50 border border-red-200/60 px-2.5 py-1 rounded-full">
                                        {{ $enseignant->alertes_non_traitees_count }} en attente
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200/60 px-2.5 py-1 rounded-full">
                                        Aucune
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3.5">
                                <div class="flex items-center justify-center gap-1" onclick="event.stopPropagation()">
                                    <a href="{{ route('enseignants.show', $enseignant) }}"
                                       class="p-2 text-gray-500 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors"
                                       title="Voir">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    <a href="{{ route('enseignants.edit', $enseignant) }}"
                                       class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                       title="Modifier">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>

                                    <button type="button"
                                            @click="openDeleteTeacherModal('{{ route('enseignants.destroy', $enseignant) }}', '{{ e($enseignant->nom_complet) }}')"
                                            class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Archiver">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.1" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-7 0h8"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-20 h-20 bg-gradient-to-br from-brand-100 to-brand-50 rounded-full flex items-center justify-center mb-4 shadow-card-brand">
                                        <svg class="w-10 h-10 text-brand-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <p class="font-display text-base font-bold text-gray-700">Aucun enseignant trouvé</p>
                                    <p class="text-sm text-gray-400 mt-1">Essayez de modifier vos filtres ou ajoutez un nouvel enseignant.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($enseignants->hasPages())
            <div class="px-6 py-4 border-t border-brand-100/60 bg-gradient-to-r from-brand-50/40 to-transparent">
                {{ $enseignants->links() }}
            </div>
        @endif
    </div>

    <template x-teleport="body">
        <div x-show="openDeleteModal"
             x-cloak
             class="fixed inset-0 z-[9999] bg-black/50 backdrop-blur-[2px]"
             @click.self="closeDeleteTeacherModal()"
             @keydown.escape.window="closeDeleteTeacherModal()">
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-red-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-red-100 bg-gradient-to-r from-red-50 via-white to-red-50/40 flex items-center justify-between">
                        <div>
                            <h3 class="font-display text-lg font-extrabold text-gray-900">Confirmer l’archivage</h3>
                            <p class="text-[12px] text-gray-500 mt-0.5">Cette action rend l’enseignant inactif</p>
                        </div>

                        <button type="button"
                                @click="closeDeleteTeacherModal()"
                                class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6">
                        <p class="text-sm text-gray-700">Vous êtes sur le point d’archiver :</p>
                        <p class="mt-2 text-base font-extrabold text-gray-900" x-text="deleteNom"></p>

                        <form :action="deleteUrl" method="POST" class="mt-6 flex items-center justify-end gap-3">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="confirm_delete" value="1">

                            <button type="button"
                                    @click="closeDeleteTeacherModal()"
                                    class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">
                                Annuler
                            </button>

                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white text-[13px] font-extrabold rounded-xl shadow-sm hover:shadow-md transition-all">
                                Archiver l’enseignant
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection