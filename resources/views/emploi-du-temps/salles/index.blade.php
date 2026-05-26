@extends('layouts.app')

@section('title', 'Gestion des salles')
@section('page-title', 'Gestion des salles')
@section('page-subtitle', 'Salles, laboratoires et espaces utilisés dans les emplois du temps')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl font-extrabold text-gray-900">Gestion des salles</h1>
            <p class="text-sm text-gray-500 mt-1">Ajoutez les salles disponibles pour l’emploi du temps, le pointage et les QR codes.</p>
        </div>
        <a href="{{ route('emploi-du-temps.index') }}" class="text-sm font-bold text-brand-700 hover:text-brand-800">← Emploi du temps</a>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-bold mb-1">Veuillez corriger :</p>
            <ul class="list-disc list-inside text-xs space-y-0.5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-white border border-brand-100 p-4 shadow-card-brand"><p class="text-xs text-gray-500 font-bold uppercase">Salles</p><p class="text-2xl font-extrabold text-gray-900">{{ $stats['total'] }}</p></div>
        <div class="rounded-2xl bg-white border border-emerald-100 p-4 shadow-card-brand"><p class="text-xs text-gray-500 font-bold uppercase">Actives</p><p class="text-2xl font-extrabold text-emerald-700">{{ $stats['actives'] }}</p></div>
        <div class="rounded-2xl bg-white border border-blue-100 p-4 shadow-card-blue"><p class="text-xs text-gray-500 font-bold uppercase">Capacité totale</p><p class="text-2xl font-extrabold text-blue-700">{{ $stats['capacite'] }}</p></div>
        <div class="rounded-2xl bg-white border border-gold-200 p-4 shadow-card-gold"><p class="text-xs text-gray-500 font-bold uppercase">Utilisées EDT</p><p class="text-2xl font-extrabold text-gold-700">{{ $stats['utilisees_edt'] }}</p></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <form method="POST" action="{{ route('emploi-du-temps.salles.store') }}" class="bg-white rounded-2xl border border-brand-100 p-5 shadow-card-brand space-y-4">
                @csrf
                <div>
                    <h3 class="font-display text-lg font-extrabold text-gray-900">Ajouter une salle</h3>
                    <p class="text-xs text-gray-500 mt-1">Exemples : Salle 1, Salle 7, Laboratoire, CDI.</p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Nom de la salle *</label>
                    <input name="nom" value="{{ old('nom') }}" required maxlength="100" placeholder="Salle 1, Laboratoire..." class="w-full px-3 py-2.5 border border-brand-100 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Bâtiment / zone</label>
                    <input name="batiment" value="{{ old('batiment') }}" maxlength="100" placeholder="Bloc A, Bâtiment principal..." class="w-full px-3 py-2.5 border border-brand-100 rounded-xl text-sm">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Capacité</label>
                        <input type="number" name="capacite" value="{{ old('capacite', 0) }}" min="0" max="500" class="w-full px-3 py-2.5 border border-brand-100 rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Type *</label>
                        <select name="type" required class="w-full px-3 py-2.5 border border-brand-100 rounded-xl text-sm">
                            <option value="classe">Salle de classe</option>
                            <option value="laboratoire">Laboratoire</option>
                            <option value="informatique">Informatique</option>
                            <option value="bibliotheque">Bibliothèque / CDI</option>
                            <option value="bureau">Bureau</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                </div>
                <button class="w-full px-4 py-2.5 bg-brand-600 text-white rounded-xl font-bold">Ajouter la salle</button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl border border-brand-100 p-5 shadow-card-brand">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-display text-lg font-extrabold text-gray-900">Salles existantes</h3>
                    <p class="text-xs text-gray-500">Ces salles seront utilisées dans l’emploi du temps, les QR codes et le pointage.</p>
                </div>
            </div>

            <div class="space-y-4">
                @forelse($salles as $salle)
                    <form method="POST" action="{{ route('emploi-du-temps.salles.update', $salle) }}" class="rounded-2xl border border-gray-100 bg-gray-50/50 p-4">
                        @csrf
                        @method('PATCH')
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nom</label>
                                <input name="nom" value="{{ $salle->nom }}" required class="w-full px-2 py-2 border rounded-lg text-sm font-bold">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Bâtiment</label>
                                <input name="batiment" value="{{ $salle->batiment }}" class="w-full px-2 py-2 border rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Capacité</label>
                                <input type="number" name="capacite" value="{{ $salle->capacite ?? 0 }}" min="0" max="500" class="w-full px-2 py-2 border rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Type</label>
                                <select name="type" class="w-full px-2 py-2 border rounded-lg text-sm">
                                    <option value="classe" @selected($salle->type === 'classe')>Classe</option>
                                    <option value="laboratoire" @selected($salle->type === 'laboratoire')>Laboratoire</option>
                                    <option value="informatique" @selected($salle->type === 'informatique')>Informatique</option>
                                    <option value="bibliotheque" @selected($salle->type === 'bibliotheque')>Bibliothèque</option>
                                    <option value="bureau" @selected($salle->type === 'bureau')>Bureau</option>
                                    <option value="autre" @selected($salle->type === 'autre')>Autre</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Statut</label>
                                <select name="active" class="w-full px-2 py-2 border rounded-lg text-sm">
                                    <option value="1" @selected($salle->active)>Active</option>
                                    <option value="0" @selected(!$salle->active)>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-200">
                            <span class="text-xs text-gray-500">{{ $salle->emploi_du_temps_count }} cours EDT · {{ $salle->pointages_count }} pointage(s)</span>
                            <div class="flex items-center gap-2">
                                <button class="px-3 py-2 bg-brand-600 text-white rounded-lg text-xs font-bold">Enregistrer</button>
                            </div>
                        </div>
                    </form>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-400">Aucune salle enregistrée.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
