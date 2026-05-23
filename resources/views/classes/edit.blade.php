@extends('layouts.app')

@section('title', 'Modifier ' . $classe->nom)
@section('page-title', 'Modifier la classe')
@section('page-subtitle', $classe->nom . ' — ' . ($classe->anneeScolaire->libelle ?? '2025-2026'))

@section('content')
<div x-data="classeEditForm()" class="max-w-5xl mx-auto">

    <form method="POST" action="{{ route('classes.update', $classe) }}" class="space-y-6">
        @csrf
        @method('PUT')
        <input type="hidden" name="annee_scolaire_id" value="{{ $classe->annee_scolaire_id }}">

        {{-- Retour --}}
        <div class="mb-4">
            <a href="{{ route('classes.show', $classe) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour à la fiche
            </a>
        </div>

        @if($errors->any())
        <div class="bg-gradient-to-r from-red-50 to-red-100/50 border border-red-200 rounded-xl p-4 text-red-800 text-sm">
            <p class="font-bold mb-1">Merci de corriger les erreurs suivantes :</p>
            <ul class="list-disc list-inside text-[12px] space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">

                {{-- SECTION 1 : IDENTITÉ --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand p-6">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-200/20 rounded-full blur-3xl"></div>
                    <div class="relative flex items-center gap-3 mb-5 pb-4 border-b border-brand-100/60">
                        <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center shadow-brand-glow">
                            <span class="font-display text-white font-extrabold text-sm">1</span>
                        </div>
                        <div>
                            <h3 class="font-display text-base font-extrabold text-gray-900">Identité de la classe</h3>
                        </div>
                    </div>

                    <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Niveau <span class="text-red-500">*</span></label>
                            <select name="niveau_id" required
                                    class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                                @foreach($niveaux as $niveau)
                                    <option value="{{ $niveau->id }}" {{ $classe->niveau_id == $niveau->id ? 'selected' : '' }}>{{ $niveau->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Série</label>
                            <select name="serie_id"
                                    class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                                <option value="">Aucune série</option>
                                @foreach($series as $serie)
                                    <option value="{{ $serie->id }}" {{ $classe->serie_id == $serie->id ? 'selected' : '' }}>{{ $serie->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nom de la classe <span class="text-red-500">*</span></label>
                            <input type="text" name="nom" value="{{ old('nom', $classe->nom) }}" required maxlength="50"
                                   class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Description</label>
                            <textarea name="description" rows="2" maxlength="500"
                                      class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm resize-none">{{ old('description', $classe->description) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- SECTION 2 : CAPACITÉ --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-blue-50/30 rounded-2xl border border-blue-100/60 shadow-card-blue p-6">
                    <div class="absolute -top-10 -left-10 w-40 h-40 bg-blue-200/20 rounded-full blur-3xl"></div>
                    <div class="relative flex items-center gap-3 mb-5 pb-4 border-b border-blue-100/60">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-sm shadow-blue-500/30">
                            <span class="font-display text-white font-extrabold text-sm">2</span>
                        </div>
                        <div>
                            <h3 class="font-display text-base font-extrabold text-gray-900">Capacité & encadrement</h3>
                        </div>
                    </div>

                    <div class="relative grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Capacité maximale <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="number" name="capacite" value="{{ old('capacite', $classe->capacite) }}" required min="{{ $classe->effectif }}" max="200"
                                       class="w-full px-3 py-2.5 pr-16 bg-white border border-blue-100 rounded-xl text-sm font-bold focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none shadow-sm">
                                <span class="absolute right-3 top-2.5 text-xs font-bold text-blue-600">élèves</span>
                            </div>
                            @if($classe->effectif > 0)
                                <p class="text-[10px] text-gray-500 mt-1">Minimum {{ $classe->effectif }} (effectif actuel).</p>
                            @endif
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Professeur principal</label>
                            <select name="professeur_principal_id"
                                    class="w-full px-3 py-2.5 bg-white border border-blue-100 rounded-xl text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none shadow-sm cursor-pointer">
                                <option value="">Pas encore désigné</option>
                                @foreach($enseignants as $ens)
                                    <option value="{{ $ens->id }}" {{ $classe->professeur_principal_id == $ens->id ? 'selected' : '' }}>
                                        {{ $ens->prenom }} {{ $ens->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- SECTION 3 : TARIFICATION --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/40 rounded-2xl border border-gold-200/60 shadow-card-gold p-6">
                    <div class="absolute -top-10 -right-10 w-48 h-48 bg-gold-200/25 rounded-full blur-3xl"></div>
                    <div class="relative flex items-center gap-3 mb-5 pb-4 border-b border-gold-200/60">
                        <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-xl flex items-center justify-center shadow-gold-glow">
                            <span class="font-display text-white font-extrabold text-sm">3</span>
                        </div>
                        <div>
                            <h3 class="font-display text-base font-extrabold text-gray-900">Tarification</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Les modifications ne s'appliquent qu'aux nouvelles inscriptions</p>
                        </div>
                    </div>

                    <div class="relative grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Scolarité annuelle <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="number" name="scolarite_annuelle" value="{{ old('scolarite_annuelle', $classe->scolarite_annuelle ?? 0) }}" required min="0" step="1000"
                                       class="w-full px-3 py-2.5 pr-12 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none shadow-sm">
                                <span class="absolute right-3 top-2.5 text-[11px] font-bold text-gold-600">FCFA</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Frais inscription</label>
                            <div class="relative">
                                <input type="number" name="frais_inscription" value="{{ old('frais_inscription', $classe->frais_inscription ?? 0) }}" min="0" step="1000"
                                       class="w-full px-3 py-2.5 pr-12 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none shadow-sm">
                                <span class="absolute right-3 top-2.5 text-[11px] font-bold text-gold-600">FCFA</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Frais réinscription</label>
                            <div class="relative">
                                <input type="number" name="frais_reinscription" value="{{ old('frais_reinscription', $classe->frais_reinscription ?? 0) }}" min="0" step="1000"
                                       class="w-full px-3 py-2.5 pr-12 bg-white border border-gold-200 rounded-xl text-sm font-bold focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none shadow-sm">
                                <span class="absolute right-3 top-2.5 text-[11px] font-bold text-gold-600">FCFA</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SIDEBAR : INFOS --}}
            <div class="lg:col-span-1">
                <div class="sticky top-20 space-y-4">
                    <div class="relative overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-brand-800 rounded-2xl shadow-brand-glow p-5 text-white">
                        <div class="absolute -top-10 -right-10 w-32 h-32 bg-gold-400/20 rounded-full blur-3xl"></div>
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-gold-300 to-gold-500"></div>

                        <div class="relative">
                            <p class="text-[10px] text-brand-100 font-bold uppercase tracking-[0.15em] mb-3">État actuel</p>
                            <h3 class="font-display text-2xl font-extrabold leading-tight">{{ $classe->nom }}</h3>

                            <div class="mt-4 pt-4 border-t border-white/10 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] text-brand-100 font-medium">Effectif</span>
                                    <span class="font-display text-base font-extrabold">{{ $classe->effectif }} / {{ $classe->capacite }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] text-brand-100 font-medium">Créée le</span>
                                    <span class="text-sm font-bold">{{ $classe->created_at->format('d/m/Y') }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] text-brand-100 font-medium">Dernière modif.</span>
                                    <span class="text-sm font-bold">{{ $classe->updated_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($classe->effectif > 0)
                    <div class="bg-gradient-to-br from-gold-50 to-white border border-gold-200/60 rounded-xl p-4">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-gold-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <div>
                                <p class="text-[11px] font-bold text-gold-700">Attention</p>
                                <p class="text-[11px] text-gray-600 mt-1 leading-relaxed">{{ $classe->effectif }} élève(s) dans cette classe. Les changements de tarification n'affectent pas les inscriptions existantes.</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between gap-3 pt-2">
            <a href="{{ route('classes.show', $classe) }}"
               class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">
                Annuler
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Enregistrer les modifications
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function classeEditForm() {
    return {};
}
</script>
@endpush