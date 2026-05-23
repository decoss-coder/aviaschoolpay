@extends('layouts.app')

@section('title', 'Paiement ' . $paiement->reference)
@section('page-title', 'Détail paiement')

@section('content')
<div class="max-w-3xl mx-auto space-y-4">

    @if(session('success'))
        <div class="px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('wave_url'))
        <div class="px-4 py-3 rounded-xl bg-blue-50 border border-blue-200">
            <p class="text-sm font-bold text-blue-900 mb-2">Lien Wave généré</p>
            <a href="{{ session('wave_url') }}" target="_blank" class="font-mono text-xs text-blue-700 break-all underline">{{ session('wave_url') }}</a>
            @if(session('wave_message'))
                <p class="mt-2 text-xs text-blue-800 italic">{{ session('wave_message') }}</p>
            @endif
        </div>
    @endif

    <a href="{{ route('paiements.index') }}" class="text-sm text-brand-600 font-semibold hover:underline">← Tous les paiements</a>

    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b flex justify-between items-start">
            <div>
                <p class="text-xs text-gray-400 uppercase font-bold">Référence</p>
                <p class="text-xl font-mono font-bold text-gray-900">{{ $paiement->reference }}</p>
                @if($paiement->numero_recu)
                    <p class="text-xs text-green-600 font-semibold mt-1">Reçu N° {{ $paiement->numero_recu }}</p>
                @endif
            </div>
            <div>
                @if($paiement->statut === 'confirme')
                    <span class="badge badge-green">Confirmé</span>
                @elseif($paiement->statut === 'en_attente')
                    <span class="badge badge-yellow">En attente</span>
                @elseif($paiement->statut === 'annule')
                    <span class="badge bg-gray-200 text-gray-700">Annulé</span>
                @else
                    <span class="badge badge-red">{{ ucfirst($paiement->statut) }}</span>
                @endif
            </div>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-6 text-sm">
            <div>
                <dt class="text-xs text-gray-500 uppercase font-bold">Élève</dt>
                <dd class="font-semibold text-gray-900">{{ $paiement->eleve?->prenom }} {{ $paiement->eleve?->nom }}</dd>
                <dd class="text-xs text-gray-500 font-mono">{{ $paiement->eleve?->matricule_interne }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500 uppercase font-bold">Classe</dt>
                <dd class="font-semibold text-gray-900">{{ $paiement->inscription?->classe?->nom ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500 uppercase font-bold">Montant</dt>
                <dd class="text-xl font-extrabold text-brand-600">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</dd>
            </div>
            @if(($paiement->montant_inscription ?? 0) > 0 || ($paiement->montant_scolarite ?? 0) > 0)
            <div class="sm:col-span-2">
                <dt class="text-xs text-gray-500 uppercase font-bold">Répartition</dt>
                <dd class="text-sm font-semibold">
                    <span class="text-blue-700">Inscription {{ number_format($paiement->montant_inscription ?? 0, 0, ',', ' ') }} F</span>
                    <span class="text-gray-300 mx-1">·</span>
                    <span class="text-purple-700">Scolarité {{ number_format($paiement->montant_scolarite ?? 0, 0, ',', ' ') }} F</span>
                </dd>
            </div>
            @endif
            <div>
                <dt class="text-xs text-gray-500 uppercase font-bold">Mode</dt>
                <dd class="font-semibold text-gray-900">{{ str_replace('_', ' ', ucfirst($paiement->mode)) }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500 uppercase font-bold">Date paiement</dt>
                <dd class="font-semibold text-gray-900">{{ $paiement->date_paiement?->format('d/m/Y') }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500 uppercase font-bold">Encaissé par</dt>
                <dd class="font-semibold text-gray-900">{{ $paiement->encaissePar?->name ?? '—' }}</dd>
            </div>
            @if($paiement->wave_checkout_url)
                <div class="sm:col-span-2">
                    <dt class="text-xs text-gray-500 uppercase font-bold">Lien Wave</dt>
                    <dd>
                        <a href="{{ $paiement->wave_checkout_url }}" target="_blank" class="text-xs text-blue-600 underline font-mono break-all">{{ $paiement->wave_checkout_url }}</a>
                    </dd>
                </div>
            @endif
            @if($paiement->observations)
                <div class="sm:col-span-2">
                    <dt class="text-xs text-gray-500 uppercase font-bold">Observations</dt>
                    <dd class="text-gray-700">{{ $paiement->observations }}</dd>
                </div>
            @endif
        </dl>

        <div class="px-6 py-4 border-t bg-gray-50 flex flex-wrap items-center gap-2">
            @if($paiement->statut === 'confirme')
                <a href="{{ route('paiements.recu', $paiement) }}" class="btn-primary text-sm">Télécharger le reçu PDF</a>
            @endif

            @if($paiement->statut === 'en_attente')
                <form method="POST" action="{{ route('paiements.confirmer', $paiement) }}" class="inline" onsubmit="return confirm('Confirmer ce paiement ?');">
                    @csrf
                    <button class="btn-primary text-sm bg-emerald-600 hover:bg-emerald-700">Confirmer (encaissé)</button>
                </form>
                <form method="POST" action="{{ route('paiements.annuler', $paiement) }}" class="inline-flex flex-wrap items-end gap-2" onsubmit="return confirm('Annuler ce paiement ?');">
                    @csrf
                    <input type="text" name="motif_annulation" required maxlength="500" placeholder="Motif d'annulation (obligatoire)" class="rounded-lg border-gray-200 text-xs py-1.5 px-2 min-w-[200px]">
                    <button type="submit" class="btn-secondary text-sm text-red-700 border-red-200">Annuler</button>
                </form>
            @endif

            <a href="{{ route('finances.eleve', $paiement->eleve) }}" class="btn-secondary text-sm">Fiche financière élève</a>
        </div>
    </div>
</div>
@endsection
