@extends('layouts.app')

@section('title', 'Paiements Wave')
@section('page-title', 'Paiements Wave')
@section('page-subtitle', $etab->nom)

@section('content')
<div class="max-w-5xl mx-auto space-y-5">

    <nav class="flex items-center gap-2 text-sm">
        <a href="{{ route('finances.index') }}" class="text-brand-600 font-semibold hover:underline">Finances</a>
        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-900 font-bold">Wave</span>
    </nav>

    @if(session('success'))
        <div class="px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            @foreach($errors->all() as $e) <p>• {{ $e }}</p> @endforeach
        </div>
    @endif

    {{-- Hero Wave --}}
    <div class="relative overflow-hidden rounded-3xl {{ $waveActif ? 'bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800' : 'bg-gradient-to-br from-gray-600 via-gray-700 to-gray-900' }} text-white p-6 shadow-xl">
        <div class="absolute -top-12 -right-12 w-72 h-72 bg-gradient-to-br from-white/10 to-transparent rounded-full blur-3xl"></div>

        <div class="relative flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center text-2xl font-extrabold border border-white/20">W</div>
                <div>
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/15 backdrop-blur-sm text-[10px] font-bold uppercase tracking-wider">
                        <span class="w-1.5 h-1.5 rounded-full {{ $waveActif ? 'bg-emerald-300 animate-pulse' : 'bg-gray-300' }}"></span>
                        {{ $waveActif ? 'Activé' : 'Désactivé' }}
                    </div>
                    <h1 class="text-2xl font-extrabold mt-1">Paiement Wave — {{ $libelle }}</h1>
                    <p class="text-white/80 text-sm mt-1">
                        {{ $waveActif
                            ? 'Vos parents peuvent payer la scolarité en un clic.'
                            : 'Activez Wave pour offrir le paiement mobile aux parents.' }}
                    </p>
                </div>
            </div>

            @if(filled($etab->wave_lien_base))
                <form method="POST" action="{{ route('finances.wave.toggle') }}">
                    @csrf
                    <button class="px-5 py-2.5 rounded-xl text-sm font-bold transition shadow-md
                                   {{ $waveActif ? 'bg-amber-500 hover:bg-amber-600 text-white' : 'bg-emerald-500 hover:bg-emerald-600 text-white' }}">
                        {{ $waveActif ? '⏸ Désactiver Wave' : '✓ Activer Wave' }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- ─── Toggle Paiements Manuels (Espèces / Chèque / Virement) ─── --}}
    @php $manuelsActifs = (bool) ($etab->paiements_manuels_actifs ?? true); @endphp
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="w-12 h-12 rounded-xl {{ $manuelsActifs ? 'bg-emerald-100' : 'bg-gray-100' }} flex items-center justify-center">
                    <svg class="w-6 h-6 {{ $manuelsActifs ? 'text-emerald-700' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <div>
                    <h2 class="font-bold text-gray-900">Paiements manuels</h2>
                    <p class="text-xs text-gray-500 mt-1 max-w-md">
                        Active la saisie directe à l'école (<strong>espèces, chèque, virement</strong>) par la direction.
                        Si désactivés, <strong>seul Wave (paiement en ligne)</strong> est disponible pour encaisser.
                    </p>
                    <p class="text-xs mt-2 font-bold {{ $manuelsActifs ? 'text-emerald-700' : 'text-amber-700' }}">
                        @if($manuelsActifs)
                            ✓ Activé — espèces / chèque / virement autorisés
                        @else
                            ⏸ Désactivé — paiement uniquement en ligne (Wave)
                        @endif
                    </p>
                </div>
            </div>
            <form method="POST" action="{{ route('finances.paiements-manuels.toggle') }}">
                @csrf
                <button class="px-5 py-2.5 rounded-xl text-sm font-bold transition shadow-md
                               {{ $manuelsActifs ? 'bg-amber-500 hover:bg-amber-600 text-white' : 'bg-emerald-500 hover:bg-emerald-600 text-white' }}">
                    {{ $manuelsActifs ? '⏸ Désactiver' : '✓ Activer' }}
                </button>
            </form>
        </div>
    </div>

    {{-- Stats Wave --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-[11px] uppercase font-bold text-gray-500">Libellé parents</p>
            <p class="font-bold text-gray-900 mt-1 truncate" title="{{ $libelle }}">{{ $libelle }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-[11px] uppercase font-bold text-gray-500">Compte marchand</p>
            @if($lienMasque)
                <p class="font-mono text-xs text-gray-700 mt-1 truncate">{{ $lienMasque }}</p>
            @else
                <p class="text-amber-600 text-sm font-bold mt-1">Non configuré</p>
            @endif
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-[11px] uppercase font-bold text-gray-500">En attente</p>
            <p class="text-2xl font-extrabold text-amber-600 mt-1">{{ $statsWave['en_attente'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-[11px] uppercase font-bold text-gray-500">Confirmés</p>
            <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $statsWave['confirmes'] }}</p>
        </div>
    </div>

    {{-- Configuration --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h2 class="font-bold text-gray-900">Configuration du compte marchand</h2>
                <p class="text-xs text-gray-500 mt-1">
                    Format : <code class="bg-gray-100 px-1.5 py-0.5 rounded text-[11px]">https://pay.wave.com/m/IDENTIFIANT/c/ci/</code>
                </p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
            </div>
        </div>

        <form method="POST" action="{{ route('finances.wave.config') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1.5">Libellé affiché aux parents</label>
                    <input type="text" name="wave_libelle" maxlength="100"
                           value="{{ old('wave_libelle', $etab->wave_libelle ?? $etab->nom) }}"
                           class="w-full rounded-xl border-gray-200 focus:border-brand-500 focus:ring-brand-500 text-sm"
                           placeholder="Ex : Lycée Avia">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1.5">Lien marchand Wave</label>
                    <input type="text" name="wave_lien_base" required
                           value="{{ old('wave_lien_base', $etab->wave_lien_base) }}"
                           class="w-full rounded-xl border-gray-200 focus:border-brand-500 focus:ring-brand-500 font-mono text-xs"
                           placeholder="https://pay.wave.com/m/M_xxxxxxxxxx/c/ci/">
                </div>
            </div>
            <button class="px-5 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-bold hover:bg-brand-700 transition">
                Enregistrer la configuration
            </button>
        </form>
    </div>

    {{-- Paiements Wave en attente --}}
    @if($paiementsWaveEnAttente->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-gray-900 flex items-center gap-2">
                        Liens Wave en attente de confirmation
                        <span class="text-xs font-medium bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">{{ $paiementsWaveEnAttente->count() }}</span>
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">Validez après réception du paiement sur votre compte Wave.</p>
                </div>
            </div>
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50/50 text-[11px] uppercase tracking-wider text-gray-500">
                        <th class="px-4 py-2 text-left font-bold">Élève</th>
                        <th class="px-4 py-2 text-right font-bold">Montant</th>
                        <th class="px-4 py-2 text-left font-bold">Généré</th>
                        <th class="px-4 py-2 text-center font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($paiementsWaveEnAttente as $p)
                        <tr class="hover:bg-gray-50/60 transition">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-900 text-sm">{{ $p->eleve?->prenom }} {{ $p->eleve?->nom }}</p>
                                <p class="text-[11px] text-gray-400 font-mono">{{ $p->reference }}</p>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="font-extrabold text-gray-900">{{ number_format($p->montant, 0, ',', ' ') }}</span>
                                <span class="text-[10px] text-gray-400 ml-0.5">F</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $p->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('paiements.show', $p) }}" class="px-2.5 py-1 rounded-lg text-xs font-bold text-brand-600 hover:bg-brand-50 transition">Voir</a>
                                    <form method="POST" action="{{ route('paiements.confirmer', $p) }}" onsubmit="return confirm('Confirmer ce paiement Wave ?');">
                                        @csrf
                                        <button class="px-2.5 py-1 rounded-lg text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition">Confirmer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Note finale --}}
    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 text-sm text-blue-900 flex gap-3">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
            <p class="font-bold mb-1">Comment ça marche ?</p>
            <ul class="list-disc list-inside space-y-1 text-blue-800 text-xs">
                <li>L'argent arrive directement sur le compte Wave de l'établissement.</li>
                <li>La direction génère un lien depuis la fiche élève ou via l'app mobile parent.</li>
                <li>Après réception, confirmez le paiement pour générer le reçu PDF.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
