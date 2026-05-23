@props([
    'anneeLibelle',
    'nbClassesProvisionnees' => 0,
    'meta' => null, // array d'archive_meta (optionnel)
])

<div class="bg-white rounded-2xl border-2 border-emerald-300 shadow-card-brand overflow-hidden">
    {{-- Header gradient --}}
    <div class="bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 p-6 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-48 h-48 bg-gold-300/15 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"></div>
        <div class="relative flex items-start gap-4">
            <div class="w-14 h-14 bg-white/15 backdrop-blur rounded-2xl flex items-center justify-center text-3xl flex-shrink-0">🎓</div>
            <div class="flex-1">
                <p class="text-xs font-bold uppercase tracking-wider text-brand-100">Bascule réussie</p>
                <h2 class="font-display text-2xl font-extrabold mt-1">Bienvenue dans l'année {{ $anneeLibelle }}</h2>
                <p class="text-sm text-brand-100 mt-2">
                    Toute l'application affiche désormais cette année.
                    @if($nbClassesProvisionnees > 0)
                        <b class="text-white">{{ $nbClassesProvisionnees }} classe(s)</b> ont été recréées automatiquement à partir de l'ancienne structure.
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- Récap archivage --}}
    @if($meta && ! empty($meta['counts']))
    <div class="px-6 py-4 bg-amber-50/60 border-b border-amber-100 grid grid-cols-2 lg:grid-cols-4 gap-3 text-center">
        @foreach($meta['counts'] as $type => $nb)
            @if($nb > 0)
                <div class="bg-white rounded-xl px-3 py-2 border border-amber-200">
                    <p class="text-[10px] font-bold uppercase text-amber-700 tracking-wider">{{ ucfirst($type) }}</p>
                    <p class="text-lg font-extrabold text-amber-900">{{ number_format($nb, 0, ',', ' ') }}</p>
                </div>
            @endif
        @endforeach
        <div class="col-span-2 lg:col-span-4 text-xs text-amber-800 italic">
            🔒 Données précédentes archivées de manière chiffrée et sécurisée
        </div>
    </div>
    @endif

    {{-- 3 étapes clés --}}
    <div class="p-6">
        <div class="flex items-center gap-2 mb-4">
            <span class="text-xl">📋</span>
            <h3 class="font-extrabold text-gray-900">Étapes recommandées</h3>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {{-- Étape 1 : Vérifier les classes --}}
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-2xl p-5 hover:shadow-card-blue transition">
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-extrabold">1</span>
                    <span class="text-2xl">🏫</span>
                </div>
                <p class="font-bold text-blue-900">Vérifier les classes</p>
                <p class="text-xs text-blue-700 mt-1 mb-3">
                    {{ $nbClassesProvisionnees > 0 ? 'Adaptez capacité, professeur principal, frais…' : 'Créez les classes de l\'année' }}
                </p>
                <a href="{{ route('classes.index') }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white text-xs font-bold rounded-lg hover:bg-blue-700">
                    Voir les classes →
                </a>
            </div>

            {{-- Étape 2 : Importer les élèves (avec matricules existants) --}}
            <div class="bg-gradient-to-br from-emerald-50 to-brand-50 border-2 border-emerald-300 rounded-2xl p-5 hover:shadow-card-brand transition relative">
                <span class="absolute top-2 right-2 inline-flex px-2 py-0.5 rounded-full bg-emerald-600 text-white text-[10px] font-extrabold">RECOMMANDÉ</span>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center text-sm font-extrabold">2</span>
                    <span class="text-2xl">🎓</span>
                </div>
                <p class="font-bold text-emerald-900">Réinscrire les élèves</p>
                <p class="text-xs text-emerald-700 mt-1 mb-3">
                    Les <b>fiches élèves sont conservées</b>. Réinscrivez-les avec leurs matricules existants.
                </p>
                <div class="flex flex-wrap gap-1.5">
                    @if(\Illuminate\Support\Facades\Route::has('eleves.import.form'))
                        <a href="{{ route('eleves.import.form') }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 text-white text-xs font-bold rounded-lg hover:bg-emerald-700">
                            📥 Importer Excel
                        </a>
                    @endif
                    <a href="{{ route('eleves.index') }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-emerald-300 text-emerald-700 text-xs font-bold rounded-lg hover:bg-emerald-50">
                        Voir élèves
                    </a>
                </div>
            </div>

            {{-- Étape 3 : Préparer la rentrée --}}
            <div class="bg-gradient-to-br from-amber-50 to-orange-50 border-2 border-amber-200 rounded-2xl p-5 hover:shadow-card-gold transition">
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-8 h-8 rounded-full bg-amber-600 text-white flex items-center justify-center text-sm font-extrabold">3</span>
                    <span class="text-2xl">📅</span>
                </div>
                <p class="font-bold text-amber-900">Préparer la rentrée</p>
                <p class="text-xs text-amber-700 mt-1 mb-3">
                    Calendrier, événements, listes de fournitures, budget annuel.
                </p>
                <div class="flex flex-wrap gap-1.5">
                    <a href="{{ route('evenements.index') }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-600 text-white text-xs font-bold rounded-lg hover:bg-amber-700">
                        📅 Calendrier
                    </a>
                    <a href="{{ route('budgets.index') }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-amber-300 text-amber-700 text-xs font-bold rounded-lg hover:bg-amber-50">
                        Budget
                    </a>
                </div>
            </div>
        </div>

        {{-- Footer info --}}
        <div class="mt-5 pt-4 border-t border-gray-100 grid grid-cols-1 lg:grid-cols-2 gap-3 text-xs text-gray-600">
            <div class="flex items-start gap-2">
                <span class="text-base">💡</span>
                <p><b class="text-gray-900">Astuce :</b> les enseignants, affectations, comptes parents et structures pédagogiques sont automatiquement conservés.</p>
            </div>
            <div class="flex items-start gap-2">
                <span class="text-base">🔐</span>
                <p><b class="text-gray-900">Sécurité :</b> les données de l'année précédente sont chiffrées et restaurables à tout moment.</p>
            </div>
        </div>
    </div>
</div>
