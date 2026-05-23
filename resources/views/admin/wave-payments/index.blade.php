@extends('layouts.app')
@section('title', 'Configuration Wave — Plateforme')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900">Liens de paiement Wave</h1>
            <p class="text-sm text-gray-500 mt-1 max-w-2xl">
                Réservé au super administrateur. Collez le lien marchand Wave de chaque école
                (<span class="font-mono text-xs">https://pay.wave.com/m/…/c/ci/</span>).
                Les montants sont ajoutés automatiquement par AviaSchoolPay selon les tarifs AFF/NAFF.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    @if(session('wave_test_url'))
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm">
            <p class="font-bold text-blue-900 mb-2">Lien de test généré</p>
            <a href="{{ session('wave_test_url') }}" target="_blank" rel="noopener" class="text-blue-700 break-all underline">{{ session('wave_test_url') }}</a>
        </div>
    @endif

    <div class="space-y-6">
        @foreach($etablissements as $etab)
        <div class="bg-white rounded-2xl border shadow-card overflow-hidden">
            <div class="px-6 py-4 border-b bg-gray-50/80 flex flex-wrap justify-between gap-2">
                <div>
                    <h2 class="font-bold text-gray-900">{{ $etab->nom }}</h2>
                    <p class="text-xs text-gray-500">DESPS {{ $etab->code_desps }} · {{ ucfirst($etab->type) }}</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $etab->wave_actif ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ $etab->wave_actif ? 'Wave actif' : 'Wave inactif' }}
                </span>
            </div>

            <form method="POST" action="{{ route('admin.wave.update', $etab) }}" class="px-6 py-5 space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Libellé Wave (nom affiché)</label>
                        <input type="text" name="wave_libelle" value="{{ old('wave_libelle', $etab->wave_libelle ?: $etab->nom) }}"
                               class="w-full rounded-xl border-gray-200" placeholder="Ex: Collège Saint-Joseph">
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                            <input type="checkbox" name="wave_actif" value="1" {{ $etab->wave_actif ? 'checked' : '' }} class="rounded border-gray-300">
                            Activer Wave pour cette école
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Lien marchand Wave (sans montant)</label>
                    <input type="url" name="wave_lien_base"
                           value="{{ old('wave_lien_base', $etab->wave_lien_base) }}"
                           placeholder="https://pay.wave.com/m/M_ci_xxxxx/c/ci/"
                           class="w-full rounded-xl border-gray-200 font-mono text-sm">
                    @if($etab->wave_lien_masque)
                        <p class="text-xs text-gray-400 mt-1">Enregistré : {{ $etab->wave_lien_masque }}</p>
                    @endif
                    <p class="text-xs text-amber-700 mt-2">
                        Exemple Avia Technologie : collez uniquement la partie avant le montant, par ex.
                        <code class="bg-amber-50 px-1 rounded">https://pay.wave.com/m/M_ci_1Onagr26EsBs/c/ci/</code>
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-bold hover:bg-brand-700">Enregistrer</button>
                </div>
            </form>

            @if($etab->wave_lien_base)
            <form method="POST" action="{{ route('admin.wave.test', $etab) }}" class="px-6 pb-5 flex flex-wrap items-end gap-3 border-t border-gray-50 pt-4">
                @csrf
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Tester avec montant (FCFA)</label>
                    <input type="number" name="montant" value="500" min="100" class="w-32 rounded-lg border-gray-200 text-sm">
                </div>
                <button type="submit" class="px-4 py-2 rounded-lg border border-blue-200 text-blue-700 text-sm font-bold hover:bg-blue-50">Générer lien test</button>
            </form>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endsection
