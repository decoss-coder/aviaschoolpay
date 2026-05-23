@extends('layouts.app')

@section('title', 'Paramètres pointage')
@section('page-title', 'Paramètres pointage')
@section('page-subtitle', 'Géolocalisation et périmètre de validation')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <a href="{{ route('pointage.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Retour supervision
    </a>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-6">
        <h2 class="font-display text-lg font-bold text-gray-900 mb-1">Coordonnées GPS de l'école</h2>
        <p class="text-sm text-gray-500 mb-6">
            Utilisées pour valider que les enseignants pointent sur le site. Sans coordonnées, les pointages sont acceptés mais une alerte est envoyée à la direction.
        </p>

        <form method="POST" action="{{ route('pointage.parametres.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Latitude</label>
                    <input type="text" name="gps_latitude" value="{{ old('gps_latitude', $etab->gps_latitude) }}"
                           placeholder="5.345317" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Longitude</label>
                    <input type="text" name="gps_longitude" value="{{ old('gps_longitude', $etab->gps_longitude) }}"
                           placeholder="-4.024429" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Rayon autorisé (mètres)</label>
                <input type="number" name="gps_rayon_metres" min="50" max="500"
                       value="{{ old('gps_rayon_metres', $etab->gps_rayon_metres ?? 100) }}"
                       class="w-full sm:w-48 rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none">
                <p class="text-xs text-gray-400 mt-1">Recommandé : 100 m (cahier des charges : 50 à 200 m)</p>
            </div>

            @if($etab->gps_latitude && $etab->gps_longitude)
                <a href="https://www.google.com/maps?q={{ $etab->gps_latitude }},{{ $etab->gps_longitude }}"
                   target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 text-sm font-bold text-brand-600 hover:text-brand-700">
                    Voir sur Google Maps
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            @endif

            <div class="pt-2 flex gap-3">
                <button type="submit" class="px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold rounded-xl shadow-brand-glow transition-colors">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>

    <div class="bg-brand-50 border border-brand-100 rounded-2xl p-5 text-sm text-brand-900">
        <p class="font-bold mb-2">Règles actives (enseignant)</p>
        <ul class="list-disc list-inside space-y-1 text-brand-800/90">
            <li>Pointage autorisé entre <strong>7h00</strong> et <strong>18h30</strong> (départ jusqu'à 19h00)</li>
            <li>Scan aligné sur le créneau EDT de la salle (marge présence +5 min, retard +15 min)</li>
            <li>Cahier de texte : photo IA ou report avant <strong>18h30</strong></li>
            <li>Un pointage par créneau et par type (arrivée / départ)</li>
        </ul>
    </div>
</div>
@endsection
