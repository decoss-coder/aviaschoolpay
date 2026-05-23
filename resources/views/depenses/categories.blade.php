@extends('layouts.app')

@section('title', 'Catégories de dépenses')
@section('page-title', 'Catégories de dépenses')
@section('page-subtitle', 'Organiser les dépenses par type et compte comptable')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    {{-- Retour --}}
    <div class="flex items-center justify-between mb-2">
        <a href="{{ route('depenses.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour aux dépenses
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            ✓ {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ═══════════════ LISTE DES CATÉGORIES (carte brand) ═══════════════ --}}
        <div class="lg:col-span-2">
            <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand p-6">
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-200/20 rounded-full blur-3xl"></div>

                <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-brand-100/60">
                    <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center shadow-brand-glow">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-display text-base font-extrabold text-gray-900">Catégories existantes</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $categories->count() }} catégorie{{ $categories->count() > 1 ? 's' : '' }} configurée{{ $categories->count() > 1 ? 's' : '' }}</p>
                    </div>
                </div>

                @if($categories->isEmpty())
                    <div class="relative px-5 py-16 text-center">
                        <div class="w-16 h-16 mx-auto rounded-2xl bg-brand-50 border border-brand-100 flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-brand-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <p class="font-display font-extrabold text-gray-900">Aucune catégorie</p>
                        <p class="text-sm text-gray-500 mt-1">Créez votre première catégorie pour classer vos dépenses (formulaire à droite).</p>
                    </div>
                @else
                    <div class="relative overflow-x-auto -mx-2">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-[11px] uppercase tracking-wider text-gray-500">
                                    <th class="px-3 py-2 text-left font-bold">Catégorie</th>
                                    <th class="px-3 py-2 text-left font-bold">Code</th>
                                    <th class="px-3 py-2 text-left font-bold">Type</th>
                                    <th class="px-3 py-2 text-left font-bold">Compte</th>
                                    <th class="px-3 py-2 text-right font-bold">Dépenses</th>
                                    <th class="px-3 py-2 text-center font-bold">Statut</th>
                                    <th class="px-3 py-2 text-right font-bold"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brand-100/40">
                                @foreach($categories as $cat)
                                    @php
                                        $typeStyles = [
                                            'fixe'           => ['🔁', 'bg-blue-100 text-blue-700'],
                                            'variable'      => ['📊', 'bg-amber-100 text-amber-700'],
                                            'exceptionnelle' => ['⚡', 'bg-violet-100 text-violet-700'],
                                        ];
                                        [$typeIcon, $typeCls] = $typeStyles[$cat->type] ?? ['•', 'bg-gray-100 text-gray-700'];
                                    @endphp
                                    <tr class="hover:bg-brand-50/40 transition-colors">
                                        <td class="px-3 py-3">
                                            <div class="flex items-center gap-2">
                                                <span class="w-3 h-3 rounded-full ring-2 ring-white shadow-sm" style="background:{{ $cat->couleur ?: '#94a3b8' }}"></span>
                                                <span class="font-semibold text-gray-900">{{ $cat->nom }}</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 font-mono text-xs text-gray-600">{{ $cat->code }}</td>
                                        <td class="px-3 py-3">
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-bold {{ $typeCls }}">
                                                <span>{{ $typeIcon }}</span> {{ ucfirst($cat->type) }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 font-mono text-xs text-gray-600">{{ $cat->compte_comptable_numero ?: '—' }}</td>
                                        <td class="px-3 py-3 text-right font-bold text-gray-800">{{ $cat->depenses_count }}</td>
                                        <td class="px-3 py-3 text-center">
                                            @if($cat->active)
                                                <span class="inline-flex px-2 py-1 rounded-lg text-[11px] font-bold bg-emerald-100 text-emerald-700">✓ Active</span>
                                            @else
                                                <span class="inline-flex px-2 py-1 rounded-lg text-[11px] font-bold bg-gray-100 text-gray-500">Désactivée</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            @if($cat->active)
                                                <form method="POST" action="{{ route('depenses.categories.destroy', $cat->id) }}" onsubmit="return confirm('Désactiver cette catégorie ?')" class="inline">
                                                    @csrf @method('DELETE')
                                                    <button class="text-rose-600 hover:text-rose-800 text-[11px] font-bold uppercase tracking-wider">Désactiver</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- ═══════════════ FORMULAIRE CRÉATION (carte or) ═══════════════ --}}
        <div class="lg:col-span-1">
            <div class="sticky top-20">
                <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/40 rounded-2xl border border-gold-200/60 shadow-card-gold p-6">
                    <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-gold-200/25 rounded-full blur-3xl"></div>

                    <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-gold-200/60">
                        <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-xl flex items-center justify-center shadow-gold-glow">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <div>
                            <h3 class="font-display text-base font-extrabold text-gray-900">Nouvelle catégorie</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Classification des dépenses</p>
                        </div>
                    </div>

                    @if($errors->any())
                        <div class="relative rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 mb-4">
                            @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('depenses.categories.store') }}" class="relative space-y-4">
                        @csrf

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nom <span class="text-red-500">*</span></label>
                            <input type="text" name="nom" value="{{ old('nom') }}" required maxlength="100"
                                   placeholder="Ex : Fournitures de bureau"
                                   class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm placeholder:text-gray-400 focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Code court <span class="text-red-500">*</span></label>
                            <input type="text" name="code" value="{{ old('code') }}" required maxlength="20"
                                   placeholder="Ex : FOUR"
                                   class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-mono uppercase placeholder:text-gray-400 focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Type <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach([
                                    'variable'       => ['Variable', '📊'],
                                    'fixe'           => ['Fixe', '🔁'],
                                    'exceptionnelle' => ['Except.', '⚡'],
                                ] as $val => [$lbl, $emoji])
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="type" value="{{ $val }}"
                                               @checked(old('type', 'variable') === $val) required class="sr-only peer">
                                        <div class="p-2.5 bg-white border border-gold-200 rounded-xl text-center hover:border-gold-300 transition-all peer-checked:bg-gradient-to-br peer-checked:from-gold-50 peer-checked:to-gold-100/50 peer-checked:border-gold-400 peer-checked:shadow-sm">
                                            <p class="text-base">{{ $emoji }}</p>
                                            <p class="text-[11px] font-bold text-gray-800 mt-0.5">{{ $lbl }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">N° compte comptable</label>
                            <input type="text" name="compte_comptable_numero" value="{{ old('compte_comptable_numero') }}" maxlength="20"
                                   placeholder="Ex : 604000"
                                   class="w-full px-3 py-2.5 bg-white border border-gold-200 rounded-xl text-sm font-mono placeholder:text-gray-400 focus:border-gold-400 focus:ring-2 focus:ring-gold-100 outline-none transition-all shadow-sm">
                            <p class="text-[11px] text-gray-400 mt-1">Plan comptable SYSCOA / SYSCOHADA</p>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Couleur d'identification</label>
                            <div class="flex items-center gap-2">
                                <input type="color" name="couleur" value="{{ old('couleur', '#0A7B3F') }}"
                                       class="w-12 h-10 rounded-xl border border-gold-200 cursor-pointer p-0.5 shadow-sm no-default-style">
                                <span class="text-[11px] text-gray-500">Couleur affichée dans la liste et les graphiques</span>
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full mt-3 inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:shadow-card-hover hover:-translate-y-0.5 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Créer la catégorie
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Encart info --}}
    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-xs text-blue-800 flex items-start gap-3">
        <span class="text-xl">💡</span>
        <div>
            <p class="font-bold mb-0.5">Types de catégories</p>
            <ul class="list-disc list-inside space-y-0.5">
                <li><b>🔁 Fixe</b> — dépenses récurrentes prévisibles (loyer, salaires, abonnements...)</li>
                <li><b>📊 Variable</b> — dépenses régulières dont le montant fluctue (électricité, fournitures...)</li>
                <li><b>⚡ Exceptionnelle</b> — dépenses ponctuelles non récurrentes (travaux, événements...)</li>
            </ul>
        </div>
    </div>
</div>
@endsection
