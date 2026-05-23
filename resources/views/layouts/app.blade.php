<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AviaSchoolPay')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50:'#E8F5EE', 100:'#C3E6D1', 200:'#8FD4A8', 300:'#5BBF7F',
                            400:'#2DAA5B', 500:'#0A7B3F', 600:'#086832', 700:'#065526',
                            800:'#04421A', 900:'#022F0E'
                        },
                        gold: {
                            50:'#FFF8E6', 100:'#FFE9B3', 200:'#FFD97F', 300:'#FFC94C',
                            400:'#E8A817', 500:'#C48E0F', 600:'#A37708'
                        },
                    },
                    fontFamily: {
                        sans: ['DM Sans', 'system-ui', 'sans-serif'],
                        display: ['Bricolage Grotesque', 'DM Sans', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        'brand-glow':  '0 8px 24px -8px rgba(10, 123, 63, 0.4), 0 2px 4px -1px rgba(10, 123, 63, 0.1)',
                        'gold-glow':   '0 8px 24px -8px rgba(232, 168, 23, 0.4), 0 2px 4px -1px rgba(232, 168, 23, 0.1)',
                        'card':        '0 1px 3px 0 rgba(10, 30, 20, 0.05), 0 1px 2px 0 rgba(10, 30, 20, 0.04)',
                        'card-hover':  '0 20px 40px -12px rgba(10, 30, 20, 0.12), 0 4px 8px -2px rgba(10, 30, 20, 0.05)',
                        'card-brand':  '0 8px 24px -8px rgba(10, 123, 63, 0.18), 0 2px 4px -1px rgba(10, 123, 63, 0.06)',
                        'card-gold':   '0 8px 24px -8px rgba(232, 168, 23, 0.18), 0 2px 4px -1px rgba(232, 168, 23, 0.06)',
                        'card-blue':   '0 8px 24px -8px rgba(59, 130, 246, 0.18), 0 2px 4px -1px rgba(59, 130, 246, 0.06)',
                        'card-violet': '0 8px 24px -8px rgba(139, 92, 246, 0.18), 0 2px 4px -1px rgba(139, 92, 246, 0.06)',
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Bricolage+Grotesque:wght@500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'DM Sans', system-ui, sans-serif;
            background-color: #F8FAF8;
        }
        .font-display { font-family: 'Bricolage Grotesque', 'DM Sans', system-ui, sans-serif; }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1dbd4; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #a8b8ab; }

        @keyframes float-slow {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-12px); }
        }
        .animate-float-slow { animation: float-slow 8s ease-in-out infinite; }

        .nav-sub > summary::-webkit-details-marker { display: none; }
        .nav-sub > summary::marker { content: ''; }
    </style>
    @stack('styles')
</head>
<body class="h-full text-gray-800 antialiased relative {{ ($lectureSeule ?? false) ? 'annee-lecture-seule' : '' }}"
      x-data="{ sidebarOpen: true, mobileMenu: false }"
      :class="mobileMenu ? 'max-lg:overflow-hidden' : ''"
      @keydown.escape.window="mobileMenu = false">

    {{-- ════════════════════════════════════════════════════ --}}
    {{-- SIDEBAR --}}
    {{-- ════════════════════════════════════════════════════ --}}
    <aside class="fixed top-0 left-0 h-screen z-50 flex flex-col transition-all duration-300
                  bg-gradient-to-b from-white via-white to-brand-50/40
                  border-r border-brand-100/40
                  shadow-[8px_0_32px_-12px_rgba(10,123,63,0.08)]"
           :class="{
               'w-[260px]': sidebarOpen && !mobileMenu,
               'w-[72px]': !sidebarOpen && !mobileMenu,
               'w-[260px] !left-0': mobileMenu,
               '-left-72 lg:left-0': !mobileMenu
           }">

        {{-- Gold accent bar (right edge) --}}
        <div class="absolute top-0 right-0 bottom-0 w-px bg-gradient-to-b from-transparent via-gold-200/60 to-transparent"></div>

        {{-- Logo header --}}
        <div class="h-16 px-4 border-b border-brand-100/40 flex items-center gap-3 flex-shrink-0 relative">
            <div class="w-10 h-10 bg-gradient-to-br from-brand-400 via-brand-500 to-brand-700 rounded-xl flex items-center justify-center shadow-brand-glow flex-shrink-0 ring-1 ring-brand-300/30">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                </svg>
            </div>
            <div x-show="sidebarOpen" x-transition class="min-w-0 flex-1">
                <h1 class="font-display text-[16px] font-extrabold text-brand-700 leading-none tracking-tight truncate">AviaSchoolPay</h1>
                <p class="text-[9px] text-gold-600 font-bold tracking-[0.15em] uppercase mt-1">ERP Scolaire Premium</p>
            </div>
        </div>

        {{-- Établissement badge - GRADIENT BRAND --}}
        @php
            $activeEtabId  = auth()->user()?->ecoleActiveId();
            $activeEtab    = $activeEtabId ? \App\Models\Etablissement::find($activeEtabId) : auth()->user()?->etablissement;
            $isEnseignant  = auth()->user()?->role === 'enseignant';
            $nbEcolesEns   = $isEnseignant ? auth()->user()->enseignants()->where('actif', true)->count() : 0;
            $multiEcole    = $nbEcolesEns > 1;
        @endphp
        <div class="px-3 py-3 flex-shrink-0" x-show="sidebarOpen" x-transition>
            <div class="relative overflow-hidden bg-gradient-to-br from-brand-500 via-brand-600 to-brand-700 rounded-xl px-3 py-3 shadow-brand-glow">
                <div class="absolute -top-6 -right-6 w-16 h-16 bg-gold-400/20 rounded-full blur-xl"></div>
                <div class="absolute -bottom-4 -left-4 w-12 h-12 bg-brand-300/30 rounded-full blur-lg"></div>
                <div class="relative flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[11px] text-white font-bold truncate leading-tight">
                            @if(auth()->user()?->isSuperAdmin() && !session('super_admin_impersonate_etab_id'))
                                Plateforme Avia
                            @else
                                {{ $activeEtab->nom ?? 'Mon École' }}
                            @endif
                        </p>
                        <p class="text-[9px] text-brand-100 font-medium mt-0.5 flex items-center gap-1">
                            <span class="w-1 h-1 bg-gold-300 rounded-full"></span>
                            @if(auth()->user()?->isSuperAdmin() && !session('super_admin_impersonate_etab_id'))
                                Super administrateur
                            @else
                                DESPS · {{ $activeEtab->code_desps ?? '000000' }}
                            @endif
                        </p>
                    </div>
                    @php $anneeCtx = \App\Services\Scolarite\AnneeScolaireContext::courante(); @endphp
                    <span class="text-[9px] font-extrabold text-brand-900 bg-gradient-to-br from-gold-300 to-gold-500 px-1.5 py-1 rounded-md whitespace-nowrap shadow-sm" title="Année scolaire en cours">
                        {{ $anneeCtx?->libelle ?? '—' }}
                    </span>
                </div>
                @if($multiEcole)
                <a href="{{ route('ecole.switcher.index') }}"
                   class="mt-2 flex items-center gap-1.5 text-[10px] font-bold text-white bg-white/15 hover:bg-white/25 transition px-2 py-1 rounded-md">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    Changer d'école ({{ $nbEcolesEns }})
                </a>
                @endif
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-2.5 py-1 overflow-y-auto overflow-x-hidden">

            @php
            $isEnseignant = auth()->user()?->role === 'enseignant';
            $isEleve      = auth()->user()?->role === 'eleve';
            $isParent     = auth()->user()?->role === 'parent';
            $isSuperAdmin = auth()->user()?->isSuperAdmin();
            $ensId = auth()->user()?->enseignantActif()?->id;

            if ($isParent) {
                $nav = [
                    ['section' => 'Mon espace', 'items' => [
                        ['r' => 'mon-espace-parent.dashboard', 'match' => 'mon-espace-parent.dashboard', 'label' => 'Tableau de bord', 'd' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                    ]],
                ];
            } elseif ($isEleve) {
                $nav = [
                    ['section' => 'Mon espace', 'items' => [
                        ['r' => 'mon-espace-eleve.dashboard', 'match' => 'mon-espace-eleve.dashboard', 'label' => 'Tableau de bord', 'd' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                        ['r' => 'mon-espace-eleve.notes', 'match' => 'mon-espace-eleve.notes', 'label' => 'Mes notes', 'd' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                        ['r' => 'mon-espace-eleve.devoirs', 'match' => 'mon-espace-eleve.devoirs', 'label' => 'Mes devoirs', 'd' => 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z'],
                        ['r' => 'mon-espace-eleve.evaluations', 'match' => 'mon-espace-eleve.evaluations', 'label' => 'Évaluations', 'd' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                    ]],
                ];
            } elseif ($isSuperAdmin && !session('super_admin_impersonate_etab_id')) {
                $nav = [
                    ['section' => 'Plateforme Avia', 'items' => [
                        ['r' => 'admin.platform.dashboard', 'match' => 'admin.platform.dashboard', 'label' => 'Tableau de bord', 'd' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z'],
                        ['r' => 'admin.etablissements.index', 'match' => 'admin.etablissements.*', 'label' => 'Établissements', 'd' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                    ]],
                    ['section' => 'Configuration', 'items' => [
                        ['r' => 'admin.wave.index', 'match' => 'admin.wave.*', 'label' => 'Wave par école', 'd' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
                        ['r' => 'admin.platform.parametres', 'match' => ['admin.platform.parametres', 'admin.platform.wave', 'admin.platform.livrer-cle', 'admin.platform.archive-cle'], 'label' => 'Restaurations & Wave 500F', 'd' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622'],
                    ]],
                ];
            } elseif ($isEnseignant) {
                $nav = [
                    ['section' => 'Mon espace', 'items' => [
                        ['r' => 'mon-espace.dashboard', 'match' => 'mon-espace.dashboard', 'label' => 'Tableau de bord', 'd' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                        ['r' => 'mon-espace.classes', 'match' => 'mon-espace.*', 'label' => 'Mes classes', 'd' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                        ['r' => 'mon-espace.pointage', 'match' => 'mon-espace.pointage*', 'label' => 'Mon pointage', 'd' => 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z'],
                        ['r' => 'mon-espace.notes-hub', 'match' => 'mon-espace.notes-hub', 'label' => 'Notes & Devoirs', 'd' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                    ]],
                    ['section' => 'Emploi du temps', 'items' => [
                        ['r' => 'emploi-du-temps.professeur', 'rp' => ['enseignant' => $ensId], 'match' => 'emploi-du-temps.professeur', 'label' => 'Mon EDT', 'd' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    ]],
                ];
            } else {
            $nav = [
                ['section' => 'Principal', 'items' => [
                    ['r' => 'dashboard', 'match' => 'dashboard', 'label' => 'Tableau de bord', 'd' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                ]],
                ['section' => 'École', 'subs' => [
                    ['label' => 'Effectifs', 'd' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'items' => [
                        ['r' => 'eleves.index', 'match' => 'eleves.*', 'label' => 'Élèves', 'd' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                        ['r' => 'enseignants.index', 'match' => 'enseignants.*', 'label' => 'Enseignants', 'd' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z'],
                        ['r' => 'classes.index', 'match' => 'classes.*', 'label' => 'Classes', 'd' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                        ['r' => 'admin.rh.affectations.index', 'match' => 'admin.rh.affectations.*', 'label' => 'Affectations', 'd' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
                    ]],
                    ['label' => 'Pointage enseignants', 'd' => 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z', 'items' => [
                        ['r' => 'admin.rh.dashboard', 'match' => 'admin.rh.dashboard', 'label' => 'Dashboard RH', 'd' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z'],
                        ['r' => 'pointage.index', 'match' => ['pointage.index', 'pointages.show', 'pointages.selfie', 'pointages.cahier-texte', 'pointages.alertes.traiter'], 'label' => 'Supervision QR', 'd' => 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z'],
                        ['r' => 'admin.rh.pointages.index', 'match' => ['admin.rh.pointages.*', 'pointages.*'], 'label' => 'Historique pointages', 'd' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                        ['r' => 'pointage.rapport', 'match' => 'pointage.rapport', 'label' => 'Rapport ponctualité', 'd' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                        ['r' => 'alertes-pointage.index', 'match' => 'alertes-pointage.*', 'label' => 'Alertes pointage', 'd' => 'M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                        ['r' => 'admin.rh.alertes.index', 'match' => 'admin.rh.alertes.*', 'label' => 'Alertes RH', 'd' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
                        ['r' => 'admin.rh.qr-codes.index', 'match' => 'admin.rh.qr-codes.*', 'label' => 'QR Codes salles', 'd' => 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z'],
                        ['r' => 'pointage.parametres.edit', 'match' => 'pointage.parametres.*', 'label' => 'GPS établissement', 'd' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                    ]],
                    ['label' => 'Présences élèves', 'd' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'items' => [
                        ['r' => 'admin.rh.presences.dashboard', 'match' => ['admin.rh.presences.dashboard', 'admin.rh.presences.index', 'admin.rh.presences.eleve', 'admin.rh.presences.justifier', 'admin.rh.presences.traiter'], 'label' => 'Suivi quotidien', 'd' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['r' => 'admin.rh.presences.bilan', 'match' => 'admin.rh.presences.bilan*', 'label' => 'Bilans trimestriels', 'd' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    ]],
                ]],
                ['section' => 'Pédagogie', 'subs' => [
                    ['label' => 'Notes & évaluations', 'd' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'items' => [
                        ['r' => 'notes.index', 'match' => 'notes.*', 'label' => 'Saisie des notes', 'd' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                        ['r' => 'admin.rh.evaluation-system.index', 'match' => 'admin.rh.evaluation-system.*', 'label' => 'Système éval. (Trim/Sem)', 'd' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                        ['r' => 'admin.rh.sous-disciplines.index', 'match' => 'admin.rh.sous-disciplines.*', 'label' => 'Sous-disciplines', 'd' => 'M4 6h16M4 12h8m-8 6h16'],
                        ['r' => 'admin.rh.moyennes-grille.index', 'match' => 'admin.rh.moyennes-grille.*', 'label' => 'Grille moyennes', 'd' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
                        ['r' => 'admin.rh.bulletins.index', 'match' => 'admin.rh.bulletins.*', 'label' => 'Bulletins officiels', 'd' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                    ]],
                    ['label' => 'Emploi du temps', 'd' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'items' => [
                        ['r' => 'emploi-du-temps.index', 'match' => 'emploi-du-temps.*', 'label' => 'Planning général', 'd' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    ]],
                ]],
                ['section' => 'Finances', 'subs' => [
                    ['label' => 'Recouvrement', 'd' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'items' => [
                        ['r' => 'finances.index', 'match' => 'finances.index', 'label' => 'Scolarité & paiements', 'd' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                        ['r' => 'finances.wave', 'match' => 'finances.wave', 'label' => 'Paiements Wave', 'd' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
                        ['r' => 'finances.tarifs', 'match' => 'finances.tarifs*', 'label' => 'Grilles tarifaires', 'd' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['r' => 'notifications.index', 'match' => 'notifications.*', 'label' => 'Notifications', 'd' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
                    ]],
                    ['label' => 'Comptabilité', 'd' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z', 'items' => [
                        ['r' => 'comptabilite.index', 'match' => 'comptabilite.*', 'label' => 'Comptabilité', 'd' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                        ['r' => 'depenses.index', 'match' => 'depenses.*', 'label' => 'Dépenses', 'd' => 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z'],
                        ['r' => 'tresorerie.index', 'match' => 'tresorerie.*', 'label' => 'Trésorerie', 'd' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                        ['r' => 'budgets.index', 'match' => 'budgets.*', 'label' => 'Budget', 'd' => 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    ]],
                ]],
                ['section' => 'Pilotage', 'items' => [
                    ['r' => 'rentabilite.index', 'match' => 'rentabilite.*', 'label' => 'Rentabilité', 'd' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
                    ['r' => 'simulations.index', 'match' => 'simulations.*', 'label' => 'Simulations', 'd' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z'],
                    ['r' => 'cockpit.index', 'match' => 'cockpit.*', 'label' => 'Cockpit IA', 'd' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                ]],
                ['section' => 'Documents & vie scolaire', 'subs' => [
                    ['label' => 'Documents', 'd' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'items' => [
                        ['r' => 'documents.index', 'match' => 'documents.*', 'label' => 'Centre de documents', 'd' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['r' => 'rapports.index', 'match' => 'rapports.*', 'label' => 'Rapports financiers', 'd' => 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        ['r' => 'fiches-paie.index', 'match' => 'fiches-paie.*', 'label' => 'Fiches de paie', 'd' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['r' => 'fournitures.index', 'match' => 'fournitures.*', 'label' => 'Fournitures', 'd' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                    ]],
                    ['label' => 'Vie scolaire', 'd' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'items' => [
                        ['r' => 'evenements.index', 'match' => 'evenements.*', 'label' => 'Calendrier scolaire', 'd' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        ['r' => 'conseils-classe.index', 'match' => 'conseils-classe.*', 'label' => 'Conseils de classe', 'd' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                    ]],
                ]],
                ['section' => 'Communication & outils', 'subs' => [
                    ['label' => 'Communication', 'd' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z', 'items' => [
                        ['r' => 'communication.index', 'match' => 'communication.*', 'label' => 'Messages & annonces', 'd' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
                        ['r' => 'sms.index', 'match' => 'sms.*', 'label' => 'Centre SMS', 'd' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
                    ]],
                ], 'items' => [
                    ['r' => 'sigfne.index', 'match' => 'sigfne.*', 'label' => 'SIGFNE / DESPS', 'd' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    ['r' => 'ia.index', 'match' => 'ia.*', 'label' => 'IA & Analyses', 'd' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'],
                ]],
            ];
            if (in_array(auth()->user()?->role, ['super_admin', 'directeur', 'directeur_adjoint', 'gestionnaire'], true)) {
                $nav[] = ['section' => 'Administration', 'items' => [
                    ['r' => 'admin.annees.index', 'match' => 'admin.annees.*', 'label' => 'Années scolaires', 'd' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ]];
            }
            } // end else (non-enseignant)
            @endphp

            @include('partials.sidebar-nav-render')
        </nav>

        {{-- User footer --}}
        <div class="px-3 py-3 border-t border-brand-100/40 flex-shrink-0 bg-gradient-to-br from-brand-50/60 via-white to-gold-50/30 backdrop-blur-sm">
            <div class="flex items-center gap-3">
                <div class="relative flex-shrink-0">
                    <div class="w-9 h-9 bg-gradient-to-br from-brand-400 via-brand-500 to-brand-700 rounded-full flex items-center justify-center text-white font-bold text-xs ring-2 ring-white shadow-brand-glow">
                        {{ strtoupper(substr(auth()->user()->prenom ?? 'U', 0, 1)) }}{{ strtoupper(substr(auth()->user()->nom ?? '', 0, 1)) }}
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-brand-500 border-2 border-white rounded-full"></span>
                </div>
                <div x-show="sidebarOpen" x-transition class="min-w-0 flex-1">
                    <p class="text-[12px] font-bold text-gray-900 truncate leading-tight">
                        {{ auth()->user()->prenom ?? '' }} {{ auth()->user()->nom ?? '' }}
                    </p>
                    <p class="text-[10px] text-gold-600 font-semibold capitalize mt-0.5">{{ str_replace('_', ' ', auth()->user()->role ?? 'directeur') }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" x-show="sidebarOpen" x-transition>
                    @csrf
                    <button type="submit"
                            class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                            title="Déconnexion">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Overlay mobile : clic hors sidebar pour fermer --}}
    <div x-show="mobileMenu"
         x-cloak
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileMenu = false"
         class="fixed inset-0 z-40 bg-gray-900/45 backdrop-blur-[1px] lg:hidden"
         aria-hidden="true"></div>

    {{-- ════════════════════════════════════════════════════ --}}
    {{-- MAIN --}}
    {{-- ════════════════════════════════════════════════════ --}}
    <main class="min-h-screen transition-[margin] duration-300 relative z-10"
          :class="sidebarOpen ? 'lg:ml-[260px]' : 'lg:ml-[72px]'">

        {{-- Topbar --}}
        @php $anneeCtxHeader = \App\Services\Scolarite\AnneeScolaireContext::courante(); @endphp
        <header class="sticky top-0 z-20 bg-white/70 backdrop-blur-xl border-b border-brand-100/50 shadow-sm">
            <div class="absolute bottom-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-gold-300/40 to-transparent"></div>
            <div class="flex items-center justify-between px-4 lg:px-6 h-16 relative">
                <div class="flex items-center gap-3">
                    <button @click="mobileMenu = !mobileMenu"
                            class="lg:hidden p-2 text-gray-500 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <button @click="sidebarOpen = !sidebarOpen"
                            class="hidden lg:flex p-2 text-gray-400 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div>
                        <h2 class="font-display text-base lg:text-xl font-extrabold text-gray-900 leading-tight tracking-tight">@yield('page-title', 'Tableau de bord')</h2>
                        <p class="text-[11px] text-gray-500 leading-tight mt-0.5">@yield('page-subtitle', now()->locale('fr')->isoFormat('dddd D MMMM YYYY'))</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 lg:gap-3">
                    @include('components.global-search')
                    @include('components.sms-balance')
                    @include('components.notification-bell')
                    <div class="hidden sm:flex items-center gap-2 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 px-3 py-1.5 rounded-xl shadow-brand-glow">
                        <span class="w-1.5 h-1.5 bg-gold-300 rounded-full animate-pulse"></span>
                        <span class="text-[11px] font-extrabold text-white tracking-wide">{{ $anneeCtxHeader?->libelle ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </header>

        @if(auth()->user()?->isSuperAdmin() && session('super_admin_impersonate_etab_id') && $activeEtab)
            <div class="mx-4 lg:mx-6 mt-4 p-3 rounded-xl bg-gradient-to-r from-gold-50 to-amber-50 border border-gold-200 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-amber-900">
                    <span class="font-bold">Mode consultation</span> — espace <strong>{{ $activeEtab->nom }}</strong>
                </p>
                <form method="POST" action="{{ route('admin.quitter-espace') }}">@csrf
                    <button type="submit" class="px-4 py-2 rounded-lg bg-white border border-gold-300 text-amber-900 text-xs font-bold hover:bg-gold-50">Quitter l'espace école</button>
                </form>
            </div>
        @endif

        {{-- Content --}}
        <div class="p-4 lg:p-6 relative">
            @if(session('success'))
                <div class="mb-4 p-4 bg-gradient-to-r from-brand-50 to-brand-100/50 border border-brand-200 rounded-xl text-brand-800 text-sm font-medium flex items-center gap-2 shadow-card-brand"
                     x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                    <svg class="w-5 h-5 flex-shrink-0 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-gradient-to-r from-red-50 to-red-100/50 border border-red-200 rounded-xl text-red-800 text-sm font-medium flex items-center gap-2 shadow-sm">
                    <svg class="w-5 h-5 flex-shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif
            @yield('content')
        </div>
    </main>

    @include('components.lecture-seule-masquage')
    @stack('scripts')
</body>
</html>