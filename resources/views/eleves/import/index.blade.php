@extends('layouts.app')

@section('title', 'Importer des élèves')
@section('page-title', 'Importer des élèves')
@section('page-subtitle', 'Ajoutez des élèves en masse — 4 méthodes disponibles')

@section('content')
<div>

    {{-- BREADCRUMB --}}
    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('eleves.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour aux élèves
        </a>
        <a href="{{ route('eleves.import.template') }}" class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-gold-200 text-gold-700 text-[12px] font-bold rounded-lg hover:bg-gold-50 transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Télécharger le modèle Excel
        </a>
    </div>

    @if(!$annee)
        <div class="relative overflow-hidden bg-gradient-to-br from-red-50 via-white to-red-50/30 border border-red-200 rounded-2xl p-5 mb-6">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-red-400 to-red-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-red-800">Aucune année scolaire en cours</p>
                    <p class="text-[12px] text-red-600 mt-1">Définissez une année scolaire avant d'importer des élèves.</p>
                </div>
            </div>
        </div>
    @endif

    @if($classes->isEmpty())
        <div class="relative overflow-hidden bg-gradient-to-br from-gold-50 via-white to-gold-50/30 border border-gold-200 rounded-2xl p-5 mb-6">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-xl flex items-center justify-center flex-shrink-0 shadow-gold-glow">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-gold-700">Aucune classe créée pour le moment</p>
                    <p class="text-[12px] text-gray-600 mt-1">Vous pouvez importer les élèves, mais il est recommandé de <a href="{{ route('classes.create') }}" class="font-bold text-brand-600 underline">créer d'abord vos classes</a>.</p>
                </div>
            </div>
        </div>
    @endif

    @if($statsGlobales['total_imports'] > 0)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
        <div class="relative overflow-hidden bg-gradient-to-br from-white to-brand-50/50 border border-brand-100/60 rounded-xl p-4 shadow-card-brand">
            <div class="absolute -top-4 -right-4 w-16 h-16 bg-brand-200/30 rounded-full blur-xl"></div>
            <div class="relative flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-lg flex items-center justify-center shadow-brand-glow">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <div>
                    <p class="font-display text-2xl font-extrabold text-gray-900 leading-none">{{ $statsGlobales['eleves_importes_total'] }}</p>
                    <p class="text-[11px] text-gray-500 font-medium mt-1">Élèves importés au total</p>
                </div>
            </div>
        </div>
        <div class="relative overflow-hidden bg-gradient-to-br from-white to-blue-50/50 border border-blue-100/60 rounded-xl p-4 shadow-card-blue">
            <div class="absolute -top-4 -right-4 w-16 h-16 bg-blue-200/30 rounded-full blur-xl"></div>
            <div class="relative flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center shadow-sm shadow-blue-500/30">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <div>
                    <p class="font-display text-2xl font-extrabold text-gray-900 leading-none">{{ $statsGlobales['total_imports'] }}</p>
                    <p class="text-[11px] text-gray-500 font-medium mt-1">Imports effectués</p>
                </div>
            </div>
        </div>
        @if($statsGlobales['imports_en_cours'] > 0)
        <div class="relative overflow-hidden bg-gradient-to-br from-white to-gold-50/50 border border-gold-200/60 rounded-xl p-4 shadow-card-gold">
            <div class="absolute -top-4 -right-4 w-16 h-16 bg-gold-200/30 rounded-full blur-xl"></div>
            <div class="relative flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-gold-300 to-gold-500 rounded-lg flex items-center justify-center shadow-gold-glow">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="font-display text-2xl font-extrabold text-gold-600 leading-none">{{ $statsGlobales['imports_en_cours'] }}</p>
                    <p class="text-[11px] text-gold-600 font-medium mt-1">Import(s) en attente</p>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- 4 MÉTHODES TOUTES ACTIVES --}}
    <h3 class="font-display text-base font-extrabold text-gray-900 mb-3 flex items-center gap-2">
        <span class="w-1 h-5 bg-gradient-to-b from-brand-400 to-brand-600 rounded-full"></span>
        Choisissez une méthode d'import
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">

        {{-- Option 1 : Excel --}}
        <a href="{{ route('eleves.import.excel.form') }}"
           class="group relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300 p-6 block">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-200/30 rounded-full blur-3xl group-hover:scale-110 transition-transform duration-500"></div>
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-brand-400 via-brand-500 to-brand-600"></div>
            <div class="relative">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-brand-400 via-brand-500 to-brand-700 rounded-xl flex items-center justify-center shadow-brand-glow ring-1 ring-brand-300/40">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-brand-700 bg-brand-100 border border-brand-200/60 px-2 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 bg-brand-500 rounded-full animate-pulse"></span>
                        DISPONIBLE
                    </span>
                </div>
                <h3 class="font-display text-lg font-extrabold text-gray-900 mb-1 group-hover:text-brand-700 transition-colors">Fichier Excel / CSV</h3>
                <p class="text-sm text-gray-600 mb-3">Importez 100, 500 ou 2000 élèves en un seul upload. Idéal si vous avez déjà vos listes en Excel.</p>
                <div class="flex items-center gap-2 text-[11px] text-gray-500">
                    <span class="inline-flex items-center gap-1">
                        <svg class="w-3 h-3 text-brand-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Template fourni
                    </span>
                    <span>•</span>
                    <span>.xlsx, .xls, .csv</span>
                </div>
                <div class="mt-4 pt-4 border-t border-brand-100/40">
                    <span class="inline-flex items-center gap-1 text-[12px] font-bold text-brand-700 group-hover:gap-2 transition-all">
                        Commencer l'import
                        <svg class="w-3 h-3 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </span>
                </div>
            </div>
        </a>

        {{-- Option 2 : Saisie rapide --}}
        <a href="{{ route('eleves.import.saisie.form') }}"
           class="group relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/40 rounded-2xl border border-gold-200/60 shadow-card-gold hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300 p-6 block">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-gold-200/30 rounded-full blur-3xl group-hover:scale-110 transition-transform duration-500"></div>
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-gold-300 via-gold-400 to-gold-500"></div>
            <div class="relative">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-gold-300 via-gold-400 to-gold-500 rounded-xl flex items-center justify-center shadow-gold-glow ring-1 ring-gold-200/60">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-gold-700 bg-gold-100 border border-gold-200/60 px-2 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 bg-gold-500 rounded-full animate-pulse"></span>
                        DISPONIBLE
                    </span>
                </div>
                <h3 class="font-display text-lg font-extrabold text-gray-900 mb-1 group-hover:text-gold-700 transition-colors">Saisie rapide</h3>
                <p class="text-sm text-gray-600 mb-3">Tableau éditable directement dans le navigateur. Utilisez <kbd class="px-1.5 py-0.5 bg-gray-100 border border-gray-200 rounded text-[10px] font-mono">Tab</kbd> pour passer d'une cellule à l'autre.</p>
                <div class="flex items-center gap-2 text-[11px] text-gray-500">
                    <span class="inline-flex items-center gap-1">
                        <svg class="w-3 h-3 text-gold-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        30 élèves en 10 min
                    </span>
                    <span>•</span>
                    <span>Aucun fichier requis</span>
                </div>
                <div class="mt-4 pt-4 border-t border-gold-200/40">
                    <span class="inline-flex items-center gap-1 text-[12px] font-bold text-gold-700 group-hover:gap-2 transition-all">
                        Commencer la saisie
                        <svg class="w-3 h-3 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </span>
                </div>
            </div>
        </a>

        {{-- Option 3 : PDF --}}
        <a href="{{ route('eleves.import.pdf.form') }}"
           class="group relative overflow-hidden bg-gradient-to-br from-white via-white to-blue-50/30 rounded-2xl border border-blue-100/60 shadow-card-blue hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300 p-6 block">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-blue-200/30 rounded-full blur-3xl group-hover:scale-110 transition-transform duration-500"></div>
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-400 via-blue-500 to-blue-600"></div>
            <div class="relative">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-400 via-blue-500 to-blue-700 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30 ring-1 ring-blue-300/40">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-blue-700 bg-blue-100 border border-blue-200/60 px-2 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></span>
                        DISPONIBLE
                    </span>
                </div>
                <h3 class="font-display text-lg font-extrabold text-gray-900 mb-1 group-hover:text-blue-700 transition-colors">Fichier PDF</h3>
                <p class="text-sm text-gray-600 mb-3">Uploadez une liste DRENA, DESPS ou une feuille de notes. L'extraction du tableau est automatique.</p>
                <div class="flex items-center gap-2 text-[11px] text-gray-500">
                    <span class="inline-flex items-center gap-1">
                        <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        PDF texte
                    </span>
                    <span>•</span>
                    <span>.pdf</span>
                </div>
                <div class="mt-4 pt-4 border-t border-blue-100/40">
                    <span class="inline-flex items-center gap-1 text-[12px] font-bold text-blue-700 group-hover:gap-2 transition-all">
                        Uploader un PDF
                        <svg class="w-3 h-3 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </span>
                </div>
            </div>
        </a>

        {{-- Option 4 : Photo OCR (ACTIF MAINTENANT - Lot 3.5) --}}
        <a href="{{ route('eleves.import.photo.form') }}"
           class="group relative overflow-hidden bg-gradient-to-br from-white via-white to-violet-50/30 rounded-2xl border border-violet-100/60 shadow-card-violet hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300 p-6 block">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-violet-200/30 rounded-full blur-3xl group-hover:scale-110 transition-transform duration-500"></div>
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-violet-400 via-purple-500 to-purple-600"></div>
            <div class="relative">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-violet-400 via-purple-500 to-purple-700 rounded-xl flex items-center justify-center shadow-lg shadow-violet-500/30 ring-1 ring-violet-300/40">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-violet-700 bg-violet-100 border border-violet-200/60 px-2 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 bg-violet-500 rounded-full animate-pulse"></span>
                        IA · NOUVEAU
                    </span>
                </div>
                <h3 class="font-display text-lg font-extrabold text-gray-900 mb-1 group-hover:text-violet-700 transition-colors">Prise de photo (IA)</h3>
                <p class="text-sm text-gray-600 mb-3">Photographiez une liste papier avec votre téléphone. GPT-4o Vision extrait les données en quelques secondes.</p>
                <div class="flex items-center gap-2 text-[11px] text-gray-500">
                    <span class="inline-flex items-center gap-1">
                        <svg class="w-3 h-3 text-violet-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Manuscrit ou imprimé
                    </span>
                    <span>•</span>
                    <span>.jpg, .png, .heic</span>
                </div>
                <div class="mt-4 pt-4 border-t border-violet-100/40">
                    <span class="inline-flex items-center gap-1 text-[12px] font-bold text-violet-700 group-hover:gap-2 transition-all">
                        Prendre une photo
                        <svg class="w-3 h-3 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </span>
                </div>
            </div>
        </a>
    </div>

    {{-- HISTORIQUE --}}
    @if($jobsRecents->isNotEmpty())
    <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/20 rounded-2xl border border-brand-100/60 shadow-card-brand">
        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-100/60 bg-gradient-to-r from-brand-50/60 via-white to-gold-50/30">
            <h3 class="font-display text-base font-extrabold text-gray-900">Historique récent</h3>
            <span class="inline-flex items-center text-[11px] font-bold text-brand-700 bg-brand-100 border border-brand-200/60 px-2.5 py-1 rounded-full">{{ $jobsRecents->count() }}</span>
        </div>
        <div class="divide-y divide-brand-50/60">
            @foreach($jobsRecents as $job)
            @php
                $sourceIcons = [
                    'excel' => ['M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'brand'],
                    'csv' => ['M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'brand'],
                    'pdf' => ['M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'blue'],
                    'photo_ocr' => ['M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z', 'violet'],
                    'saisie_rapide' => ['M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', 'gold'],
                ];
                $iconPath = $sourceIcons[$job->source][0] ?? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
                $iconColor = $sourceIcons[$job->source][1] ?? 'gray';
            @endphp
            <div class="flex items-center gap-4 px-6 py-4 hover:bg-brand-50/30 transition-colors">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm
                    @if($iconColor === 'brand') bg-gradient-to-br from-brand-400 to-brand-600
                    @elseif($iconColor === 'blue') bg-gradient-to-br from-blue-400 to-blue-600
                    @elseif($iconColor === 'violet') bg-gradient-to-br from-violet-400 to-purple-600
                    @elseif($iconColor === 'gold') bg-gradient-to-br from-gold-300 to-gold-500
                    @else bg-gradient-to-br from-gray-400 to-gray-600 @endif">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-sm font-bold text-gray-900">{{ $job->source_libelle }}</p>
                        @if($job->fichier_original)
                            <span class="text-[11px] text-gray-400 font-medium">· {{ $job->fichier_original }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 text-[11px] text-gray-500 mt-0.5 flex-wrap">
                        <span>{{ $job->user->prenom ?? '' }} {{ $job->user->nom ?? '' }}</span>
                        <span>·</span>
                        <span>{{ $job->created_at->diffForHumans() }}</span>
                        @if($job->classeCible)
                            <span>·</span>
                            <span class="font-semibold text-brand-600">{{ $job->classeCible->nom }}</span>
                        @endif
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    @if($job->statut === 'completed')
                        <p class="font-display text-base font-extrabold text-brand-600">{{ $job->lignes_importees }}<span class="text-xs text-gray-400 font-medium"> élève(s)</span></p>
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-brand-700 bg-brand-100 border border-brand-200/60 px-1.5 py-0.5 rounded-full mt-0.5">✓ Terminé</span>
                    @elseif($job->statut === 'preview')
                        <p class="text-[12px] font-bold text-gold-700">{{ $job->lignes_valides }} ligne(s) prêtes</p>
                        <a href="{{ route('eleves.import.preview', $job) }}" class="inline-flex items-center gap-1 mt-1 text-[10px] font-bold text-white bg-gradient-to-r from-gold-400 to-gold-600 px-2 py-1 rounded-full shadow-gold-glow hover:shadow-lg transition-all">Reprendre →</a>
                    @elseif($job->statut === 'failed')
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-red-700 bg-red-100 border border-red-200/60 px-1.5 py-0.5 rounded-full">✗ Échec</span>
                    @elseif($job->statut === 'cancelled')
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-gray-600 bg-gray-100 border border-gray-200/60 px-1.5 py-0.5 rounded-full">Annulé</span>
                    @else
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-700 bg-blue-100 border border-blue-200/60 px-1.5 py-0.5 rounded-full">{{ $job->statut_libelle }}</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/20 rounded-2xl border border-brand-100/60 shadow-card-brand p-10 text-center">
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-200/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-gold-200/20 rounded-full blur-2xl"></div>
        <div class="relative">
            <div class="w-14 h-14 bg-gradient-to-br from-brand-100 to-brand-50 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-card-brand">
                <svg class="w-7 h-7 text-brand-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="font-display text-base font-bold text-gray-700">Aucun import effectué pour le moment</p>
            <p class="text-sm text-gray-400 mt-1">Votre historique apparaîtra ici une fois que vous aurez importé des élèves.</p>
        </div>
    </div>
    @endif
</div>
@endsection