@extends('layouts.app')
@section('title', 'Plateforme Avia')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8 space-y-6">
    <h1 class="text-2xl font-extrabold text-gray-900">Plateforme Avia Technologie</h1>

    @if(session('success'))<div class="bg-emerald-50 text-emerald-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>@endif
    @if(session('cle_livree'))
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
            <p class="font-bold text-sm">Clé pour demande {{ session('demande_ref') ?? '' }}</p>
            <p class="font-mono text-lg mt-2 select-all">{{ session('cle_livree') }}</p>
        </div>
    @endif

    <div class="bg-white rounded-2xl border shadow-card p-6">
        <h2 class="font-bold mb-4">Wave — Restauration archive (500 FCFA)</h2>
        <form method="POST" action="{{ route('admin.platform.wave') }}" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Lien marchand Wave (sans montant)</label>
                <input name="wave_lien_base" value="{{ old('wave_lien_base', $waveRestauration) }}" class="w-full rounded-xl border-gray-200 font-mono text-sm mt-1" placeholder="https://pay.wave.com/m/M_ci_.../c/ci/">
            </div>
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Libellé</label>
                <input name="wave_libelle" value="{{ old('wave_libelle', $waveLibelle) }}" class="w-full rounded-xl border-gray-200 mt-1">
            </div>
            <button class="px-5 py-2.5 rounded-xl bg-brand-600 text-white font-bold text-sm">Enregistrer</button>
        </form>
        @if($waveRestauration)
            <p class="text-xs text-gray-500 mt-3">Lien test 500 F : {{ \App\Services\Scolarite\AnneeScolaireArchiveService::lienWaveRestauration() }}</p>
        @endif
    </div>

    <div class="bg-white rounded-2xl border shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b font-bold">Demandes de restauration</div>
        @forelse($demandes as $d)
            <div class="px-5 py-3 border-t flex flex-wrap justify-between gap-2 text-sm">
                <span>{{ $d->etablissement?->nom }} — {{ $d->anneeScolaire?->libelle }} — {{ $d->statut }}</span>
                @if($d->statut === 'en_attente_paiement')
                <form method="POST" action="{{ route('admin.platform.livrer-cle', $d) }}">
                    @csrf
                    <input type="hidden" name="confirme_paiement" value="1">
                    <button class="text-xs font-bold text-emerald-600">Paiement reçu → livrer clé</button>
                </form>
                @endif
            </div>
        @empty
            <p class="p-5 text-gray-400 text-sm">Aucune demande.</p>
        @endforelse
        <div class="px-4 py-3">{{ $demandes->links() }}</div>
    </div>

    <div class="bg-white rounded-2xl border shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b font-bold">Archives — clés</div>
        @foreach($archives as $a)
            <div class="px-5 py-3 border-t flex justify-between text-sm">
                <span>{{ $a->etablissement?->nom }} — {{ $a->libelle }} ({{ $a->archived_at?->format('d/m/Y') }})</span>
                <a href="{{ route('admin.platform.archive-cle', $a) }}" class="text-brand-600 font-bold text-xs">Voir clé</a>
            </div>
        @endforeach
    </div>
</div>
@endsection
