@extends('layouts.app')

@section('title', 'Diagnostic OCR')
@section('page-title', 'Diagnostic import par photo')
@section('page-subtitle', 'Vérification de la configuration OpenAI et de l\'environnement serveur')

@section('content')
<div class="max-w-3xl mx-auto">

    <div class="mb-4">
        <a href="{{ route('eleves.import.photo.form') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour à l'import photo
        </a>
    </div>

    {{-- Résumé global --}}
    @php
        $tousOk = collect($tests)->every(fn($t) => $t['ok']);
        $nbKo = collect($tests)->where('ok', false)->count();
    @endphp

    @if($tousOk)
    <div class="relative overflow-hidden bg-gradient-to-br from-brand-50 via-white to-brand-50/40 border border-brand-200 rounded-2xl p-5 mb-6 shadow-card-brand">
        <div class="absolute -top-6 -right-6 w-24 h-24 bg-brand-200/30 rounded-full blur-2xl"></div>
        <div class="relative flex items-start gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-brand-glow">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <p class="font-display text-base font-extrabold text-brand-800">Tous les tests sont OK</p>
                <p class="text-[12px] text-brand-700 mt-0.5">Vous pouvez utiliser l'import par photo. Si le problème persiste, vérifiez les logs Laravel (storage/logs/laravel.log).</p>
            </div>
        </div>
    </div>
    @else
    <div class="relative overflow-hidden bg-gradient-to-br from-red-50 via-white to-red-50/40 border border-red-200 rounded-2xl p-5 mb-6 shadow-[0_8px_24px_-8px_rgba(239,68,68,0.2)]">
        <div class="absolute -top-6 -right-6 w-24 h-24 bg-red-200/30 rounded-full blur-2xl"></div>
        <div class="relative flex items-start gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-red-400 to-red-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <p class="font-display text-base font-extrabold text-red-800">{{ $nbKo }} problème(s) détecté(s)</p>
                <p class="text-[12px] text-red-700 mt-0.5">Corrigez les éléments en rouge ci-dessous pour que l'import photo fonctionne.</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Liste des tests --}}
    <div class="relative overflow-hidden bg-white rounded-2xl border border-brand-100/60 shadow-card-brand">
        <div class="px-6 py-4 border-b border-brand-100/60 bg-gradient-to-r from-brand-50/60 via-white to-gold-50/30">
            <h3 class="font-display text-base font-extrabold text-gray-900">Résultats des tests</h3>
        </div>

        <div class="divide-y divide-gray-100">
            @foreach($tests as $test)
            <div class="px-6 py-4 flex items-start gap-4">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm
                    {{ $test['ok']
                        ? 'bg-gradient-to-br from-brand-400 to-brand-600'
                        : 'bg-gradient-to-br from-red-400 to-red-600' }}">
                    @if($test['ok'])
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    @else
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold {{ $test['ok'] ? 'text-gray-900' : 'text-red-800' }}">{{ $test['nom'] }}</p>
                    <p class="text-[12px] {{ $test['ok'] ? 'text-gray-500' : 'text-red-600' }} mt-0.5 font-mono">{{ $test['details'] }}</p>
                </div>
                <span class="inline-flex items-center text-[10px] font-extrabold px-2 py-1 rounded-full flex-shrink-0
                    {{ $test['ok']
                        ? 'text-brand-700 bg-brand-100 border border-brand-200/60'
                        : 'text-red-700 bg-red-100 border border-red-200/60' }}">
                    {{ $test['ok'] ? 'OK' : 'ÉCHEC' }}
                </span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Aide contextuelle --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-blue-50/50 to-white rounded-2xl border border-blue-100/60 shadow-card-blue p-5 mt-6">
        <div class="relative flex items-start gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm shadow-blue-500/30">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex-1">
                <p class="font-display text-sm font-extrabold text-blue-800 mb-2">Solutions courantes</p>
                <ul class="space-y-2 text-[12px] text-blue-700">
                    <li><strong>Clé API manquante :</strong> Ajoutez <code class="px-1.5 py-0.5 bg-white border border-blue-200 rounded font-mono text-[11px]">OPENAI_API_KEY=sk-...</code> dans votre fichier <code>.env</code>, puis lancez <code>php artisan config:clear</code></li>
                    <li><strong>Clé invalide (HTTP 401) :</strong> Régénérez une clé sur <a href="https://platform.openai.com/api-keys" target="_blank" class="underline font-bold">platform.openai.com</a></li>
                    <li><strong>DNS échoue :</strong> Hostinger bloque peut-être les connexions sortantes. Contactez leur support ou utilisez un autre hébergeur pour OCR</li>
                    <li><strong>Timeout pendant l'analyse :</strong> Vérifiez que <code>max_execution_time ≥ 60s</code> dans votre PHP. Le code fait <code>set_time_limit(180)</code> mais c'est ignoré sur certains Hostinger mutualisés</li>
                    <li><strong>Crédit épuisé :</strong> Vérifiez votre <a href="https://platform.openai.com/usage" target="_blank" class="underline font-bold">usage OpenAI</a>. Il faut au moins $5 de crédit</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="mt-6 flex items-center justify-between gap-3">
        <a href="{{ route('eleves.import.photo.diagnostic') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Relancer les tests
        </a>
        <a href="{{ route('eleves.import.photo.form') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-violet-500 to-purple-600 text-white text-[13px] font-bold rounded-xl shadow-lg shadow-violet-500/30 hover:-translate-y-0.5 transition-all">
            Retourner à l'import photo
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </a>
    </div>
</div>
@endsection