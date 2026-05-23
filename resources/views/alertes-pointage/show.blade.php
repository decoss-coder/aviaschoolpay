@extends('layouts.app')

@section('title', 'Détail alerte')
@section('page-title', 'Détail de l’alerte')
@section('page-subtitle', ($alerte->enseignant->nom_complet ?? 'Enseignant') . ' — ' . ($alerte->date?->format('d/m/Y') ?? ''))

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('alertes-pointage.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour à la liste
        </a>
    </div>

    @php
        $graviteClass = match($alerte->gravite) {
            'info' => 'text-sky-700 bg-sky-50 border-sky-200/60',
            'warning' => 'text-orange-700 bg-orange-50 border-orange-200/60',
            'critique' => 'text-red-700 bg-red-50 border-red-200/60',
            default => 'text-gray-700 bg-gray-50 border-gray-200/60',
        };
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="space-y-6">
            <div class="relative overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-brand-800 rounded-2xl shadow-brand-glow p-5 text-white">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-gold-300 to-gold-500"></div>

                <div class="relative">
                    <div class="flex items-center gap-4">
                        @if(!empty($alerte->enseignant->photo_path))
                            <img src="{{ route('enseignants.photo', $alerte->enseignant) }}"
                                 alt="{{ $alerte->enseignant->nom_complet }}"
                                 class="w-20 h-20 rounded-2xl object-cover ring-2 ring-white/30 shadow-lg">
                        @else
                            <div class="w-20 h-20 rounded-2xl bg-white/10 flex items-center justify-center text-2xl font-extrabold">
                                {{ strtoupper(substr($alerte->enseignant->prenom ?? 'X', 0, 1)) }}{{ strtoupper(substr($alerte->enseignant->nom ?? 'X', 0, 1)) }}
                            </div>
                        @endif

                        <div>
                            <p class="font-display text-xl font-extrabold">{{ $alerte->enseignant->nom_complet ?? '—' }}</p>
                            <p class="text-sm text-brand-100 mt-1">{{ $alerte->enseignant->telephone ?? 'Téléphone non renseigné' }}</p>
                            <p class="text-xs text-brand-100 mt-1">{{ $alerte->enseignant->matricule_mena ?: 'Sans matricule MENA' }}</p>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-white/10 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] text-brand-100 font-medium">Date</span>
                            <span class="font-bold">{{ $alerte->date?->format('d/m/Y') ?: '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] text-brand-100 font-medium">Type</span>
                            <span class="font-bold">{{ $alerte->type_alerte_libelle }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] text-brand-100 font-medium">Gravité</span>
                            <span class="font-bold">{{ $alerte->gravite_libelle }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-brand-100/60 rounded-2xl p-5 shadow-card-brand">
                <h3 class="font-display text-base font-extrabold text-gray-900 mb-4">État</h3>

                <div class="space-y-3">
                    <span class="inline-flex items-center text-[12px] font-bold border px-3 py-1.5 rounded-full {{ $graviteClass }}">
                        {{ $alerte->gravite_libelle }}
                    </span>

                    <div class="grid grid-cols-1 gap-2">
                        <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50/70 px-3 py-2">
                            <span class="text-[11px] text-gray-500">Lecture</span>
                            <span class="text-[11px] font-bold {{ $alerte->lue ? 'text-blue-700' : 'text-red-700' }}">
                                {{ $alerte->lue ? 'Lue' : 'Non lue' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50/70 px-3 py-2">
                            <span class="text-[11px] text-gray-500">Traitement</span>
                            <span class="text-[11px] font-bold {{ $alerte->traitee ? 'text-emerald-700' : 'text-amber-700' }}">
                                {{ $alerte->traitee ? 'Traitée' : 'À traiter' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-brand-100/60 rounded-2xl p-5 shadow-card-brand">
                <h3 class="font-display text-base font-extrabold text-gray-900 mb-4">Message d’alerte</h3>
                <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4 text-sm text-gray-700">
                    {{ $alerte->message }}
                </div>
            </div>

            <div class="bg-white border border-brand-100/60 rounded-2xl p-5 shadow-card-brand">
                <h3 class="font-display text-base font-extrabold text-gray-900 mb-4">Pointage lié</h3>

                @if($alerte->pointage)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="rounded-xl border border-brand-100 bg-brand-50/50 p-4">
                            <p class="text-[10px] uppercase tracking-wider font-bold text-brand-600">Type scan</p>
                            <p class="mt-1 font-bold text-gray-900">{{ $alerte->pointage->type_scan_libelle }}</p>
                        </div>

                        <div class="rounded-xl border border-brand-100 bg-brand-50/50 p-4">
                            <p class="text-[10px] uppercase tracking-wider font-bold text-brand-600">Heure scan</p>
                            <p class="mt-1 font-bold text-gray-900">{{ $alerte->pointage->heure_scan ?: '—' }}</p>
                        </div>

                        <div class="rounded-xl border border-brand-100 bg-brand-50/50 p-4">
                            <p class="text-[10px] uppercase tracking-wider font-bold text-brand-600">Méthode</p>
                            <p class="mt-1 font-bold text-gray-900">{{ $alerte->pointage->methode_libelle }}</p>
                        </div>

                        <div class="rounded-xl border border-brand-100 bg-brand-50/50 p-4">
                            <p class="text-[10px] uppercase tracking-wider font-bold text-brand-600">Statut</p>
                            <p class="mt-1 font-bold text-gray-900">{{ $alerte->pointage->statut_libelle }}</p>
                        </div>

                        <div class="rounded-xl border border-brand-100 bg-brand-50/50 p-4">
                            <p class="text-[10px] uppercase tracking-wider font-bold text-brand-600">Salle</p>
                            <p class="mt-1 font-bold text-gray-900">{{ $alerte->pointage->salle->nom ?? '—' }}</p>
                        </div>

                        <div class="rounded-xl border border-brand-100 bg-brand-50/50 p-4">
                            <p class="text-[10px] uppercase tracking-wider font-bold text-brand-600">Distance école</p>
                            <p class="mt-1 font-bold text-gray-900">{{ number_format((float) ($alerte->pointage->distance_ecole_metres ?? 0), 1, ',', ' ') }} m</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('pointages.show', $alerte->pointage) }}"
                           class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-brand-100 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-brand-50 hover:border-brand-200 hover:text-brand-700 transition-all">
                            Voir le pointage lié
                        </a>
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-400">
                        Aucun pointage lié à cette alerte
                    </div>
                @endif
            </div>

            <div class="bg-white border border-brand-100/60 rounded-2xl p-5 shadow-card-brand">
                <h3 class="font-display text-base font-extrabold text-gray-900 mb-4">Traitement</h3>

                @if($alerte->traitee)
                    <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4">
                        <p class="text-sm font-extrabold text-emerald-700">Alerte déjà traitée</p>
                        <p class="text-[12px] text-gray-600 mt-1">
                            Par :
                            <strong>
                                {{ $alerte->traiteePar->name
                                    ?? trim(($alerte->traiteePar->prenom ?? '') . ' ' . ($alerte->traiteePar->nom ?? ''))
                                    ?: ($alerte->traiteePar->email ?? ('Utilisateur #' . $alerte->traitee_par)) }}
                            </strong>
                        </p>

                        @if($alerte->commentaire_traitement)
                            <div class="mt-3 rounded-xl border border-emerald-100 bg-white/70 p-3 text-[12px] text-gray-700">
                                <p class="font-bold text-emerald-700 mb-1">Commentaire</p>
                                <p>{{ $alerte->commentaire_traitement }}</p>
                            </div>
                        @endif
                    </div>
                @else
                    <form method="POST" action="{{ route('alertes-pointage.traiter', $alerte) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <textarea name="commentaire_traitement"
                                  rows="4"
                                  placeholder="Ajouter un commentaire de traitement..."
                                  class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-300 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm resize-none">{{ old('commentaire_traitement') }}</textarea>

                        <div class="flex justify-end gap-3">
                            @if(!$alerte->lue)
                                <form method="POST" action="{{ route('alertes-pointage.lire', $alerte) }}">
                                    @csrf
                                    @method('PATCH')
                                </form>
                            @endif

                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow hover:shadow-card-hover transition-all">
                                Marquer comme traitée
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection