@extends('layouts.app')

@section('title', 'Notes & Bulletins')
@section('page-title', 'Notes & Bulletins')
@section('page-subtitle', 'Hub direction — saisie, moyennes, bulletins')

@section('content')
<div class="space-y-6">

    @if(session('info'))
        <div class="px-4 py-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-sm">{{ session('info') }}</div>
    @endif

    {{-- KPI globaux --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="stat-card border-l-4 border-brand-500">
            <p class="text-xs text-gray-500 uppercase font-bold mb-1">Classes</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total_classes'] }}</p>
        </div>
        <div class="stat-card border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase font-bold mb-1">Élèves</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total_eleves'] }}</p>
        </div>
        <div class="stat-card border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase font-bold mb-1">Moy. générale</p>
            <p class="text-2xl font-bold text-green-700">{{ $stats['moyenne_generale'] ? number_format($stats['moyenne_generale'], 2) : '—' }}<span class="text-sm text-gray-400">/20</span></p>
        </div>
        <div class="stat-card border-l-4 border-red-500">
            <p class="text-xs text-gray-500 uppercase font-bold mb-1">En difficulté</p>
            <p class="text-2xl font-bold text-red-600">{{ $stats['eleves_en_difficulte'] }}<span class="text-sm text-gray-400"> &lt; 10</span></p>
        </div>
    </div>

    {{-- Cards d'accès aux outils --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('admin.rh.bulletins.index') }}" class="bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-md hover:border-brand-300 transition group">
            <div class="w-12 h-12 bg-brand-100 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <h3 class="font-bold text-gray-900">Bulletins</h3>
            <p class="text-xs text-gray-500 mt-1">Calculer, consulter et exporter PDF (élève, classe, masse).</p>
        </a>

        <a href="{{ route('admin.rh.moyennes-grille.index') }}" class="bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-md hover:border-brand-300 transition group">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <h3 class="font-bold text-gray-900">Grille des moyennes</h3>
            <p class="text-xs text-gray-500 mt-1">Vue lecture seule par classe, matière, trimestre.</p>
        </a>

        <a href="{{ route('admin.rh.evaluation-system.index') }}" class="bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-md hover:border-brand-300 transition group">
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
            </div>
            <h3 class="font-bold text-gray-900">Système d'évaluation</h3>
            <p class="text-xs text-gray-500 mt-1">Trimestre/semestre/quadrimestre + coefficients.</p>
        </a>

        <a href="{{ route('admin.rh.sous-disciplines.index') }}" class="bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-md hover:border-brand-300 transition group">
            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            </div>
            <h3 class="font-bold text-gray-900">Sous-disciplines</h3>
            <p class="text-xs text-gray-500 mt-1">CF / OG / EO pour Français — preset ivoirien.</p>
        </a>
    </div>

    {{-- Accès rapide saisie par classe --}}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b">
            <h2 class="font-bold text-gray-900">Saisie & consultation par classe</h2>
            <p class="text-xs text-gray-500 mt-0.5">
                Trimestre actif : <span class="font-semibold">{{ $trimestreActif?->libelle ?? '—' }}</span>
            </p>
        </div>
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Classe</th>
                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Niveau</th>
                    <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classes as $c)
                    <tr class="border-t border-gray-50 hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm font-semibold">{{ $c->nom }}</td>
                        <td class="px-4 py-2 text-sm text-gray-600">{{ $c->niveau?->libelle ?? '—' }}</td>
                        <td class="px-4 py-2 text-center text-xs">
                            <div class="flex justify-center gap-3">
                                <a href="{{ route('mon-espace.grille-notes.index', ['classe' => $c]) }}" class="text-brand-600 font-semibold hover:underline">Grille notes</a>
                                <a href="{{ route('mon-espace.moyennes', ['classe' => $c]) }}" class="text-green-600 font-semibold hover:underline">Saisie moyennes</a>
                                <a href="{{ route('classes.show', ['classe' => $c]) }}" class="text-gray-600 font-semibold hover:underline">Fiche classe</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-sm text-gray-400">Aucune classe pour cette année.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
