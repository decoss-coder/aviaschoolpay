@extends('layouts.app')
@section('title', 'Dépenses')
@section('page-title', 'Dépenses')
@section('page-subtitle', 'Suivi, validation et comptabilisation des dépenses')

@section('content')
@php
    $money = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $statutBadge = [
        'brouillon' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => 'Brouillon'],
        'soumise'   => ['bg' => 'bg-amber-100', 'text' => 'text-amber-800', 'label' => 'En attente'],
        'approuvee' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'label' => 'Approuvée'],
        'rejetee'   => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Rejetée'],
        'payee'     => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'Payée'],
        'annulee'   => ['bg' => 'bg-gray-100', 'text' => 'text-gray-500', 'label' => 'Annulée'],
    ];
@endphp

<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ session('error') }}</div>
    @endif

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-rose-500 to-pink-600 rounded-xl flex items-center justify-center shadow-card-violet">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m3 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H10a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Module 13</p>
                <h2 class="font-display text-2xl font-extrabold text-gray-900">Gestion des dépenses</h2>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('depenses.categories') }}" class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-white border border-gray-200 text-gray-700 hover:border-brand-300 hover:text-brand-700 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                Catégories
            </a>
            <a href="{{ route('depenses.create') }}" class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-brand-500 to-brand-700 text-white shadow-brand-glow hover:shadow-lg transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Nouvelle dépense
            </a>
        </div>
    </div>

    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card hover:shadow-card-hover transition">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Ce mois</p>
                <span class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </span>
            </div>
            <p class="text-2xl font-extrabold text-gray-900">{{ $money($totalMois) }} <span class="text-sm text-gray-400 font-bold">F</span></p>
            <p class="text-xs text-gray-500 mt-1">Dépenses approuvées</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card hover:shadow-card-hover transition">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Cette année</p>
                <span class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </span>
            </div>
            <p class="text-2xl font-extrabold text-gray-900">{{ $money($totalAnnee) }} <span class="text-sm text-gray-400 font-bold">F</span></p>
            <p class="text-xs text-gray-500 mt-1">{{ now()->year }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-amber-100 p-5 shadow-card-gold hover:shadow-card-hover transition">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-bold uppercase text-amber-600 tracking-wider">À valider</p>
                <span class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <p class="text-2xl font-extrabold text-amber-700">{{ $enAttente }}</p>
            <p class="text-xs text-amber-600 mt-1">{{ $money($montantAttente) }} F en attente</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card hover:shadow-card-hover transition">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Statuts</p>
                <span class="w-8 h-8 bg-violet-50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="flex items-baseline gap-3">
                <p class="text-xl font-extrabold text-emerald-700">{{ $approuvees }}</p>
                <span class="text-xs text-gray-400">/</span>
                <p class="text-xl font-extrabold text-red-600">{{ $rejetees }}</p>
            </div>
            <p class="text-xs text-gray-500 mt-1">Approuvées / Rejetées</p>
        </div>
    </section>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <div class="lg:col-span-2">
                <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Recherche</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Réf, libellé, bénéficiaire…"
                       class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100" />
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Statut</label>
                <select name="statut" class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100">
                    <option value="">Tous</option>
                    @foreach($statutBadge as $key => $b)
                        <option value="{{ $key }}" @selected(request('statut') === $key)>{{ $b['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Catégorie</label>
                <select name="categorie" class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100">
                    <option value="">Toutes</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(request('categorie') == $cat->id)>{{ $cat->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <input type="month" name="mois" value="{{ request('mois') }}" class="flex-1 rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100" />
                <button type="submit" class="px-4 py-2 bg-brand-600 text-white text-sm font-bold rounded-xl hover:bg-brand-700">Filtrer</button>
            </div>
        </form>
    </section>

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-5">
        <div class="xl:col-span-3 bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-extrabold text-gray-900">Liste des dépenses</h3>
                <span class="text-xs font-semibold text-gray-500">{{ $depenses->total() }} résultat(s)</span>
            </div>

            @if($depenses->isEmpty())
                <div class="px-5 py-16 text-center">
                    <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <p class="font-bold text-gray-800">Aucune dépense trouvée</p>
                    <p class="text-sm text-gray-500 mt-1 mb-4">Commencez par enregistrer votre première dépense.</p>
                    <a href="{{ route('depenses.create') }}" class="inline-flex items-center px-4 py-2 bg-brand-600 text-white text-sm font-bold rounded-xl">Nouvelle dépense</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3 text-left font-bold">Référence</th>
                                <th class="px-5 py-3 text-left font-bold">Libellé</th>
                                <th class="px-5 py-3 text-left font-bold">Catégorie</th>
                                <th class="px-5 py-3 text-right font-bold">Montant</th>
                                <th class="px-5 py-3 text-left font-bold">Date</th>
                                <th class="px-5 py-3 text-center font-bold">Statut</th>
                                <th class="px-5 py-3 text-right font-bold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($depenses as $d)
                                @php $b = $statutBadge[$d->statut] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => $d->statut]; @endphp
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-5 py-3 font-mono text-xs text-gray-600">{{ $d->reference }}</td>
                                    <td class="px-5 py-3">
                                        <div class="font-semibold text-gray-900">{{ $d->libelle }}</div>
                                        @if($d->beneficiaire)
                                            <div class="text-xs text-gray-500">{{ $d->beneficiaire }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-700">
                                            <span class="w-2 h-2 rounded-full" style="background:{{ $d->categorie?->couleur ?: '#94a3b8' }}"></span>
                                            {{ $d->categorie?->nom ?: '—' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right font-extrabold text-gray-900">{{ $money($d->montant) }} <span class="text-xs text-gray-400 font-bold">F</span></td>
                                    <td class="px-5 py-3 text-gray-600 text-xs">{{ $d->date_depense?->format('d/m/Y') }}</td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-bold {{ $b['bg'] }} {{ $b['text'] }}">{{ $b['label'] }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <a href="{{ route('depenses.show', $d->id) }}" class="text-brand-600 hover:text-brand-800 text-sm font-bold">Voir →</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-4 border-t border-gray-100">{{ $depenses->links() }}</div>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
            <h3 class="font-extrabold text-gray-900 mb-1">Top catégories</h3>
            <p class="text-xs text-gray-500 mb-4">{{ now()->locale('fr')->isoFormat('MMMM YYYY') }}</p>

            @if($topCategories->isEmpty())
                <p class="text-sm text-gray-400 italic">Aucune dépense ce mois.</p>
            @else
                <div class="space-y-3">
                    @php $maxTop = $topCategories->max('total') ?: 1; @endphp
                    @foreach($topCategories as $tc)
                        @php $pct = round(($tc->total / $maxTop) * 100); @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="font-semibold text-gray-700 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full" style="background:{{ $tc->categorie?->couleur ?: '#94a3b8' }}"></span>
                                    {{ $tc->categorie?->nom ?: '—' }}
                                </span>
                                <span class="text-xs font-bold text-gray-900">{{ $money($tc->total) }} F</span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full" style="width:{{ $pct }}%;background:{{ $tc->categorie?->couleur ?: '#94a3b8' }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
