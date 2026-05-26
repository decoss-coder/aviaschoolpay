@extends('layouts.app')

@section('title', 'Gestion des niveaux')
@section('page-title', 'Gestion des niveaux')
@section('page-subtitle', 'Niveaux, cycles, ordre et statut')

@section('content')
@include('partials.rh-admin-nav')

<div class="max-w-7xl mx-auto space-y-6">
    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-bold mb-1">Veuillez corriger :</p>
            <ul class="list-disc list-inside text-xs space-y-0.5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-blue-100 bg-blue-50 px-5 py-4 text-sm text-blue-900">
        <b>Important :</b> cette page sert uniquement à gérer les niveaux scolaires. Les montants de scolarité, inscription et réinscription se gèrent dans
        <a href="{{ route('finances.tarifs') }}" class="font-extrabold underline">Grilles tarifaires</a>.
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-white border border-brand-100 p-4 shadow-card-brand"><p class="text-xs text-gray-500 font-bold uppercase">Niveaux</p><p class="text-2xl font-extrabold text-gray-900">{{ $stats['total'] }}</p></div>
        <div class="rounded-2xl bg-white border border-emerald-100 p-4 shadow-card-brand"><p class="text-xs text-gray-500 font-bold uppercase">Actifs</p><p class="text-2xl font-extrabold text-emerald-700">{{ $stats['actifs'] }}</p></div>
        <div class="rounded-2xl bg-white border border-blue-100 p-4 shadow-card-blue"><p class="text-xs text-gray-500 font-bold uppercase">Collège</p><p class="text-2xl font-extrabold text-blue-700">{{ $stats['college'] }}</p></div>
        <div class="rounded-2xl bg-white border border-gold-200 p-4 shadow-card-gold"><p class="text-xs text-gray-500 font-bold uppercase">Classes liées</p><p class="text-2xl font-extrabold text-gold-700">{{ $stats['classes'] }}</p></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <form method="POST" action="{{ route('admin.rh.niveaux.store') }}" class="bg-white rounded-2xl border border-brand-100 p-5 shadow-card-brand space-y-4">
                @csrf
                <div>
                    <h3 class="font-display text-lg font-extrabold text-gray-900">Ajouter un niveau</h3>
                    <p class="text-xs text-gray-500 mt-1">Exemple : 6e, 5e, 3e, 2nde, 1re, Tle.</p>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Code *</label>
                    <input name="code" value="{{ old('code') }}" required maxlength="30" placeholder="6E, 3E, TLE" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Libellé *</label>
                    <input name="libelle" value="{{ old('libelle') }}" required maxlength="100" placeholder="Sixième, Troisième, Terminale" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Cycle *</label>
                    <select name="cycle" required class="w-full px-3 py-2.5 border border-brand-100 rounded-xl text-sm">
                        <option value="premier_cycle">Premier cycle / Collège</option>
                        <option value="second_cycle">Second cycle / Lycée</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Ordre</label>
                    <input type="number" name="ordre" value="{{ old('ordre', 0) }}" min="0" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl text-sm">
                </div>

                <button class="w-full px-4 py-2.5 bg-brand-600 text-white rounded-xl font-bold">Ajouter le niveau</button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl border border-brand-100 p-5 shadow-card-brand">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-display text-lg font-extrabold text-gray-900">Niveaux existants</h3>
                    <p class="text-xs text-gray-500">Modifiez ici uniquement le libellé, le cycle, l’ordre et le statut.</p>
                </div>
            </div>

            <div class="space-y-4">
                @forelse($niveaux as $niveau)
                    <form method="POST" action="{{ route('admin.rh.niveaux.update', $niveau) }}" class="rounded-2xl border border-gray-100 bg-gray-50/50 p-4">
                        @csrf
                        @method('PATCH')
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Code</label>
                                <input name="code" value="{{ $niveau->code }}" required class="w-full px-2 py-2 border rounded-lg text-sm font-bold">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Libellé</label>
                                <input name="libelle" value="{{ $niveau->libelle }}" required class="w-full px-2 py-2 border rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Cycle</label>
                                <select name="cycle" class="w-full px-2 py-2 border rounded-lg text-sm">
                                    <option value="premier_cycle" @selected($niveau->cycle === 'premier_cycle')>Collège</option>
                                    <option value="second_cycle" @selected($niveau->cycle === 'second_cycle')>Lycée</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Ordre</label>
                                <input type="number" name="ordre" value="{{ $niveau->ordre }}" min="0" class="w-full px-2 py-2 border rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Statut</label>
                                <select name="actif" class="w-full px-2 py-2 border rounded-lg text-sm">
                                    <option value="1" @selected($niveau->actif)>Actif</option>
                                    <option value="0" @selected(!$niveau->actif)>Inactif</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-200">
                            <span class="text-xs text-gray-500">{{ $niveau->classes_count }} classe(s)</span>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('finances.tarifs') }}" class="px-3 py-2 bg-gold-50 border border-gold-200 text-gold-800 rounded-lg text-xs font-bold">Gérer les tarifs</a>
                                <button class="px-3 py-2 bg-brand-600 text-white rounded-lg text-xs font-bold">Enregistrer</button>
                            </div>
                        </div>
                    </form>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-400">Aucun niveau enregistré.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
