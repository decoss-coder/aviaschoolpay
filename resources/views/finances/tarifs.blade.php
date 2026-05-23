@extends('layouts.app')

@section('title', 'Grilles tarifaires')
@section('page-title', 'Grilles tarifaires')
@section('page-subtitle', 'Configuration des frais — ' . ($annee->libelle ?? ''))

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Retour --}}
    <div class="flex items-center justify-between mb-2">
        <a href="{{ route('finances.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour aux finances
        </a>
    </div>

    {{-- Hero --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-600 via-brand-700 to-brand-800 shadow-brand-glow p-6 text-white">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-gold-300 to-gold-500"></div>
        <div class="absolute -top-6 -right-6 w-32 h-32 bg-gold-400/20 rounded-full blur-2xl"></div>

        <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="font-display text-2xl font-extrabold">Grilles tarifaires {{ $annee->libelle }}</h1>
                <p class="text-brand-100 text-sm mt-1 max-w-xl">
                    <strong class="text-gold-300">AFF</strong> = inscription seule · <strong class="text-gold-300">NAFF</strong> = inscription + scolarité annuelle. La 3ème et la Terminale sont paramétrables séparément.
                </p>
            </div>
            <form method="POST" action="{{ route('finances.synchroniser') }}">
                @csrf
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-white/15 backdrop-blur-sm border border-white/20 text-white text-sm font-bold hover:bg-white/25 transition">
                    🔁 Recalculer toutes les inscriptions
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
        </div>
    @endif

    @php
        $ref6e4e = $college6e4e->first();
        $scol6e4e = $ref6e4e?->frais_scolarite_defaut ?? 0;
        $ins6e4e = $ref6e4e?->frais_inscription_defaut ?? 0;
        $reins6e4e = $ref6e4e?->frais_reinscription_defaut ?? 0;

        $scol3e = $troisieme?->frais_scolarite_defaut ?? 0;
        $ins3e = $troisieme?->frais_inscription_defaut ?? 0;
        $reins3e = $troisieme?->frais_reinscription_defaut ?? 0;

        $refLycee = $lycee2nde1ere->first();
        $scolL = $refLycee?->frais_scolarite_defaut ?? 0;
        $insL = $refLycee?->frais_inscription_defaut ?? 0;
        $reinsL = $refLycee?->frais_reinscription_defaut ?? 0;

        $refTle = $terminales->first();
        $scolTle = $refTle?->frais_scolarite_defaut ?? 0;
        $insTle = $refTle?->frais_inscription_defaut ?? 0;
        $reinsTle = $refTle?->frais_reinscription_defaut ?? 0;
    @endphp

    {{-- ═══════════════ 1. COLLÈGE 6e → 4e (brand) ═══════════════ --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand p-6">
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-200/20 rounded-full blur-3xl"></div>

        <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-brand-100/60">
            <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center shadow-brand-glow">
                <span class="font-display text-white font-extrabold text-sm">1</span>
            </div>
            <div>
                <h3 class="font-display text-base font-extrabold text-gray-900">Collège — 6e à 4e</h3>
                <p class="text-xs text-gray-500 mt-0.5">Grille uniforme pour les 3 premiers niveaux du collège</p>
            </div>
        </div>

        @if($college6e4e->isEmpty())
            <p class="relative text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">⚠ Aucun niveau collège 6e-4e configuré.</p>
        @else
            <form method="POST" action="{{ route('finances.tarifs.college') }}" class="relative grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                <input type="hidden" name="groupe" value="6e_4e">
                @foreach($college6e4e as $n)
                    <input type="hidden" name="niveau_ids[]" value="{{ $n->id }}">
                @endforeach
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Scolarité annuelle (NAFF) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="scolarite_annuelle" min="0" value="{{ old('scolarite_annuelle', $scol6e4e) }}" required
                               class="w-full px-3 py-2.5 pr-14 bg-white border border-brand-100 rounded-xl text-sm font-bold text-brand-700 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-brand-600">FCFA</span>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Frais d'inscription <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="frais_inscription" min="0" value="{{ old('frais_inscription', $ins6e4e) }}" required
                               class="w-full px-3 py-2.5 pr-14 bg-white border border-brand-100 rounded-xl text-sm font-bold focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-brand-600">FCFA</span>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Frais de réinscription <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="frais_reinscription" min="0" value="{{ old('frais_reinscription', $reins6e4e) }}" required
                               class="w-full px-3 py-2.5 pr-14 bg-white border border-brand-100 rounded-xl text-sm font-bold focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-brand-600">FCFA</span>
                    </div>
                </div>
                <div class="md:col-span-3 flex items-center justify-between pt-2 border-t border-brand-100/60">
                    <p class="text-[11px] text-gray-500">Niveaux : <strong>{{ $college6e4e->pluck('libelle')->join(', ') }}</strong></p>
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:-translate-y-0.5 hover:shadow-lg transition-all">
                        Appliquer au collège 6e→4e
                    </button>
                </div>
            </form>
        @endif
    </div>

    {{-- ═══════════════ 2. CLASSE DE 3ème (or) ═══════════════ --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/40 rounded-2xl border border-gold-200/60 shadow-card-gold p-6">
        <div class="absolute -top-10 -left-10 w-40 h-40 bg-gold-200/25 rounded-full blur-3xl"></div>

        <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-gold-200/60">
            <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-xl flex items-center justify-center shadow-gold-glow">
                <span class="font-display text-white font-extrabold text-sm">2</span>
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <h3 class="font-display text-base font-extrabold text-gray-900">Collège — 3ème</h3>
                    <span class="text-[10px] font-bold uppercase bg-amber-100 text-amber-800 px-2 py-1 rounded-full">Examen BEPC</span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5">Tarification séparée (examen, dossier candidat...)</p>
            </div>
        </div>

        @if(! $troisieme)
            <p class="relative text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">⚠ Aucun niveau de 3ème détecté.</p>
        @else
            <form method="POST" action="{{ route('finances.tarifs.college') }}" class="relative grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                <input type="hidden" name="groupe" value="3e">
                <input type="hidden" name="niveau_ids[]" value="{{ $troisieme->id }}">
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Scolarité annuelle (NAFF) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="scolarite_annuelle" min="0" value="{{ old('scolarite_annuelle', $scol3e) }}" required
                               class="w-full px-3 py-2.5 pr-14 bg-white border border-gold-200 rounded-xl text-sm font-bold text-gold-700 focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-gold-600">FCFA</span>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Frais d'inscription <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="frais_inscription" min="0" value="{{ old('frais_inscription', $ins3e) }}" required
                               class="w-full px-3 py-2.5 pr-14 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-gold-600">FCFA</span>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Frais de réinscription <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="frais_reinscription" min="0" value="{{ old('frais_reinscription', $reins3e) }}" required
                               class="w-full px-3 py-2.5 pr-14 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-gold-600">FCFA</span>
                    </div>
                </div>
                <div class="md:col-span-3 flex items-center justify-between pt-2 border-t border-gold-200/60">
                    <p class="text-[11px] text-gray-500">Niveau : <strong>{{ $troisieme->libelle }}</strong> ({{ $troisieme->code }})</p>
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-gold-400 to-gold-600 text-white text-[13px] font-bold rounded-xl shadow-gold-glow ring-1 ring-gold-300/40 hover:-translate-y-0.5 hover:shadow-lg transition-all">
                        Appliquer à la 3ème
                    </button>
                </div>
            </form>
        @endif
    </div>

    {{-- ═══════════════ 3. LYCÉE 2nde + 1ère (bleu) ═══════════════ --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-blue-50/30 rounded-2xl border border-blue-100/60 shadow-card-blue p-6">
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-blue-200/20 rounded-full blur-3xl"></div>

        <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-blue-100/60">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-sm shadow-blue-500/30">
                <span class="font-display text-white font-extrabold text-sm">3</span>
            </div>
            <div>
                <h3 class="font-display text-base font-extrabold text-gray-900">Lycée — 2nde et 1ère</h3>
                <p class="text-xs text-gray-500 mt-0.5">Grille uniforme pour 2nde et 1ère (toutes séries)</p>
            </div>
        </div>

        @if($lycee2nde1ere->isEmpty())
            <p class="relative text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">⚠ Aucun niveau 2nde/1ère configuré.</p>
        @else
            <form method="POST" action="{{ route('finances.tarifs.lycee') }}" class="relative grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                <input type="hidden" name="groupe" value="2nde_1ere">
                @foreach($lycee2nde1ere as $n)
                    <input type="hidden" name="niveau_ids[]" value="{{ $n->id }}">
                @endforeach
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Scolarité annuelle (NAFF) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="scolarite_annuelle" min="0" value="{{ old('scolarite_annuelle', $scolL) }}" required
                               class="w-full px-3 py-2.5 pr-14 bg-white border border-blue-100 rounded-xl text-sm font-bold text-blue-700 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-blue-600">FCFA</span>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Frais d'inscription <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="frais_inscription" min="0" value="{{ old('frais_inscription', $insL) }}" required
                               class="w-full px-3 py-2.5 pr-14 bg-white border border-blue-100 rounded-xl text-sm font-bold focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-blue-600">FCFA</span>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Frais de réinscription <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="frais_reinscription" min="0" value="{{ old('frais_reinscription', $reinsL) }}" required
                               class="w-full px-3 py-2.5 pr-14 bg-white border border-blue-100 rounded-xl text-sm font-bold focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition-all shadow-sm">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-blue-600">FCFA</span>
                    </div>
                </div>
                <div class="md:col-span-3 flex items-center justify-between pt-2 border-t border-blue-100/60">
                    <p class="text-[11px] text-gray-500">Niveaux : <strong>{{ $lycee2nde1ere->pluck('libelle')->join(', ') }}</strong></p>
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-blue-500 to-blue-700 text-white text-[13px] font-bold rounded-xl shadow-card-blue ring-1 ring-blue-300/40 hover:-translate-y-0.5 hover:shadow-lg transition-all">
                        Appliquer au lycée 2nde + 1ère
                    </button>
                </div>
            </form>
        @endif
    </div>

    {{-- ═══════════════ 4. TERMINALE (violet) ═══════════════ --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-violet-50/30 rounded-2xl border border-violet-100/60 shadow-card-violet p-6">
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-violet-200/20 rounded-full blur-3xl"></div>

        <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-violet-100/60">
            <div class="w-10 h-10 bg-gradient-to-br from-violet-400 to-purple-600 rounded-xl flex items-center justify-center shadow-sm shadow-violet-500/30">
                <span class="font-display text-white font-extrabold text-sm">4</span>
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <h3 class="font-display text-base font-extrabold text-gray-900">Lycée — Terminale</h3>
                    <span class="text-[10px] font-bold uppercase bg-amber-100 text-amber-800 px-2 py-1 rounded-full">Examen BAC</span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5">Tarification distincte (examen, dossier candidat...)</p>
            </div>
        </div>

        @if($terminales->isEmpty())
            <p class="relative text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">⚠ Aucun niveau de Terminale détecté.</p>
        @else
            <form method="POST" action="{{ route('finances.tarifs.lycee') }}" class="relative grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                <input type="hidden" name="groupe" value="terminale">
                @foreach($terminales as $n)
                    <input type="hidden" name="niveau_ids[]" value="{{ $n->id }}">
                @endforeach
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Scolarité annuelle (NAFF) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="scolarite_annuelle" min="0" value="{{ old('scolarite_annuelle', $scolTle) }}" required
                               class="w-full px-3 py-2.5 pr-14 bg-white border border-violet-100 rounded-xl text-sm font-bold text-violet-700 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-violet-600">FCFA</span>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Frais d'inscription <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="frais_inscription" min="0" value="{{ old('frais_inscription', $insTle) }}" required
                               class="w-full px-3 py-2.5 pr-14 bg-white border border-violet-100 rounded-xl text-sm font-bold focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-violet-600">FCFA</span>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Frais de réinscription <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" name="frais_reinscription" min="0" value="{{ old('frais_reinscription', $reinsTle) }}" required
                               class="w-full px-3 py-2.5 pr-14 bg-white border border-violet-100 rounded-xl text-sm font-bold focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-violet-600">FCFA</span>
                    </div>
                </div>
                <div class="md:col-span-3 flex items-center justify-between pt-2 border-t border-violet-100/60">
                    <p class="text-[11px] text-gray-500">Niveaux : <strong>{{ $terminales->pluck('libelle')->join(', ') }}</strong></p>
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-violet-500 to-purple-700 text-white text-[13px] font-bold rounded-xl shadow-card-violet ring-1 ring-violet-300/40 hover:-translate-y-0.5 hover:shadow-lg transition-all">
                        Appliquer aux Terminales
                    </button>
                </div>
            </form>
        @endif
    </div>

    {{-- ═══════════════ AJUSTEMENT FIN PAR NIVEAU LYCÉE (collapsible) ═══════════════ --}}
    <details class="relative overflow-hidden bg-white rounded-2xl border border-gray-100 shadow-card-hover">
        <summary class="px-6 py-4 cursor-pointer hover:bg-gray-50 select-none flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-gray-600 to-gray-800 rounded-xl flex items-center justify-center shadow-sm">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="font-display text-base font-extrabold text-gray-900">Ajustement fin par niveau lycée</h3>
                <p class="text-xs text-gray-500 mt-0.5">Cliquez pour ouvrir — utile si une série a un tarif spécifique (TleC vs TleD, etc.)</p>
            </div>
        </summary>

        <div class="divide-y divide-gray-50">
            @forelse($lycee as $niveau)
                <form method="POST" action="{{ route('finances.tarifs.niveau', $niveau) }}" class="px-6 py-5 grid grid-cols-1 lg:grid-cols-5 gap-4 items-end hover:bg-gray-50/40">
                    @csrf
                    <div class="lg:col-span-1">
                        <p class="font-bold text-gray-900">{{ $niveau->libelle }}</p>
                        <p class="text-[11px] text-gray-400 font-mono">{{ $niveau->code }}</p>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Scolarité</label>
                        <input type="number" name="frais_scolarite_defaut" min="0" value="{{ $niveau->frais_scolarite_defaut ?? 0 }}" required
                               class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Inscription</label>
                        <input type="number" name="frais_inscription_defaut" min="0" value="{{ $niveau->frais_inscription_defaut ?? 0 }}" required
                               class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Réinscription</label>
                        <input type="number" name="frais_reinscription_defaut" min="0" value="{{ $niveau->frais_reinscription_defaut ?? 0 }}" required
                               class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="flex items-center gap-1.5 text-[11px] text-gray-600">
                            <input type="checkbox" name="appliquer_classes" value="1" checked class="rounded border-gray-300 text-brand-600 focus:ring-brand-200">
                            Maj classes
                        </label>
                        <button type="submit" class="ml-auto px-4 py-2 rounded-xl bg-gray-900 hover:bg-gray-800 text-white text-xs font-bold transition-all">Enregistrer</button>
                    </div>
                </form>
            @empty
                <p class="px-6 py-8 text-sm text-gray-400 text-center">Aucun niveau lycée configuré.</p>
            @endforelse
        </div>
    </details>

</div>
@endsection
