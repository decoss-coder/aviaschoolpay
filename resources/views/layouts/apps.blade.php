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
            background-color: #F5F9F6;
            background-image:
                radial-gradient(at 15% 10%, rgba(10, 123, 63, 0.09) 0%, transparent 45%),
                radial-gradient(at 85% 85%, rgba(232, 168, 23, 0.08) 0%, transparent 45%),
                radial-gradient(at 50% 50%, rgba(10, 123, 63, 0.03) 0%, transparent 60%);
            background-attachment: fixed;
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
    </style>
    @stack('styles')
</head>
<body class="h-full text-gray-800 antialiased relative {{ ($lectureSeule ?? false) ? 'annee-lecture-seule' : '' }}" x-data="{ sidebarOpen: true, mobileMenu: false }">

    {{-- Decorative background blobs --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-0">
        <div class="absolute top-32 -right-32 w-[500px] h-[500px] bg-gradient-to-br from-brand-200/30 to-brand-100/10 rounded-full blur-3xl animate-float-slow"></div>
        <div class="absolute -bottom-20 -left-32 w-[500px] h-[500px] bg-gradient-to-br from-gold-200/25 to-gold-100/10 rounded-full blur-3xl animate-float-slow" style="animation-delay: -4s"></div>
    </div>

    {{-- Overlay mobile --}}
    <div x-show="mobileMenu" x-cloak @click="mobileMenu = false"
         class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-sm lg:hidden"
         x-transition.opacity></div>

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
        <div class="px-3 py-3 flex-shrink-0" x-show="sidebarOpen" x-transition>
            <div class="relative overflow-hidden bg-gradient-to-br from-brand-500 via-brand-600 to-brand-700 rounded-xl px-3 py-3 shadow-brand-glow">
                <div class="absolute -top-6 -right-6 w-16 h-16 bg-gold-400/20 rounded-full blur-xl"></div>
                <div class="absolute -bottom-4 -left-4 w-12 h-12 bg-brand-300/30 rounded-full blur-lg"></div>
                <div class="relative flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[11px] text-white font-bold truncate leading-tight">
                            {{ auth()->user()->etablissement->nom ?? 'Mon École' }}
                        </p>
                        <p class="text-[9px] text-brand-100 font-medium mt-0.5 flex items-center gap-1">
                            <span class="w-1 h-1 bg-gold-300 rounded-full"></span>
                            DESPS · {{ auth()->user()->etablissement->code_desps ?? '000000' }}
                        </p>
                    </div>
                    <span class="text-[9px] font-extrabold text-brand-900 bg-gradient-to-br from-gold-300 to-gold-500 px-1.5 py-1 rounded-md whitespace-nowrap shadow-sm">2025-26</span>
                </div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-2.5 py-1 overflow-y-auto overflow-x-hidden">
            @php
                $nav = [
                    ['section' => 'Principal', 'items' => [
                        ['r' => 'dashboard', 'match' => 'dashboard', 'label' => 'Tableau de bord', 'd' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                    ]],

                    ['section' => 'Gestion scolaire', 'items' => [
                        ['r' => 'eleves.index', 'match' => 'eleves.*', 'label' => 'Élèves', 'd' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                        ['r' => 'enseignants.index', 'match' => 'enseignants.*', 'label' => 'Enseignants', 'd' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z'],
                        ['r' => 'affectations.index', 'match' => 'affectations.*', 'label' => 'Affectations', 'd' => 'M7 7h10M7 12h10M7 17h10M5 7h.01M5 12h.01M5 17h.01'],
                        ['r' => 'classes.index', 'match' => 'classes.*', 'label' => 'Classes', 'd' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                        ['r' => 'pointage.index', 'match' => 'pointage.*', 'label' => 'Pointage QR', 'd' => 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z'],
                        ['r' => 'alertes-pointage.index', 'match' => 'alertes-pointage.*', 'label' => 'Alertes pointage', 'd' => 'M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                    ]],

                    ['section' => 'Pédagogie', 'items' => [
                        ['r' => 'notes.index', 'match' => 'notes.*', 'label' => 'Notes & Bulletins', 'd' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                        [
                            'r' => 'emploi-du-temps.index',
                            'match' => [
                                'emploi-du-temps.index',
                                'emploi-du-temps.create',
                                'emploi-du-temps.store',
                                'emploi-du-temps.edit',
                                'emploi-du-temps.update',
                                'emploi-du-temps.destroy',
                                'emploi-du-temps.toggle',
                                'emploi-du-temps.ia.*',
                            ],
                            'label' => 'Emploi du temps',
                            'd' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'
                        ],
                        ['r' => 'emploi-du-temps.conflits', 'match' => 'emploi-du-temps.conflits', 'label' => 'Conflits EDT', 'd' => 'M8 10h.01M12 10h.01M16 10h.01M9 16h6M7 21h10a2 2 0 002-2V7l-5-5H7a2 2 0 00-2 2v15a2 2 0 002 2z'],
                    ]],

                    ['section' => 'Finances', 'items' => [
                        ['r' => 'finances.index', 'match' => 'finances.index', 'label' => 'Scolarité & paiements', 'd' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                        ['r' => 'finances.wave', 'match' => 'finances.wave', 'label' => 'Paiements Wave', 'd' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
                        ['r' => 'finances.tarifs', 'match' => 'finances.tarifs*', 'label' => 'Grilles tarifaires', 'd' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['r' => 'comptabilite.index', 'match' => 'comptabilite.*', 'label' => 'Comptabilité', 'd' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                        ['r' => 'depenses.index', 'match' => 'depenses.*', 'label' => 'Dépenses', 'd' => 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z'],
                        ['r' => 'tresorerie.index', 'match' => 'tresorerie.*', 'label' => 'Trésorerie', 'd' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                        ['r' => 'budgets.index', 'match' => 'budgets.*', 'label' => 'Budget', 'd' => 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    ]],

                    ['section' => 'Pilotage', 'items' => [
                        ['r' => 'rentabilite.index', 'match' => 'rentabilite.*', 'label' => 'Rentabilité', 'd' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
                        ['r' => 'simulations.index', 'match' => 'simulations.*', 'label' => 'Simulations', 'd' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z'],
                        ['r' => 'cockpit.index', 'match' => 'cockpit.*', 'label' => 'Cockpit IA', 'd' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    ]],

                    ['section' => 'Documents & Rapports', 'items' => [
                        ['r' => 'documents.index', 'match' => 'documents.*', 'label' => 'Centre de documents', 'd' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['r' => 'rapports.index', 'match' => 'rapports.*', 'label' => 'Rapports financiers', 'd' => 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        ['r' => 'fiches-paie.index', 'match' => 'fiches-paie.*', 'label' => 'Fiches de paie', 'd' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    ]],
                    ['section' => 'Outils', 'items' => [
                        ['r' => 'sigfne.index', 'match' => 'sigfne.*', 'label' => 'SIGFNE / DESPS', 'd' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['r' => 'communication.index', 'match' => 'communication.*', 'label' => 'Communication', 'd' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
                        ['r' => 'ia.index', 'match' => 'ia.*', 'label' => 'IA & Analyses', 'd' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'],
                    ]],
                ];
            @endphp

            @foreach($nav as $gIdx => $group)
                <div class="mb-1 {{ $gIdx > 0 ? 'mt-4' : 'mt-2' }}">
                    <p class="px-3 pb-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-[0.14em] flex items-center gap-1.5"
                       x-show="sidebarOpen" x-transition>
                        <span class="w-1 h-1 bg-brand-400 rounded-full"></span>
                        {{ $group['section'] }}
                    </p>

                    @if(!$loop->first)
                        <div class="h-px bg-gradient-to-r from-transparent via-brand-100 to-transparent mx-3 mb-2" x-show="!sidebarOpen"></div>
                    @endif

                    @foreach($group['items'] as $item)
                        @if(\Illuminate\Support\Facades\Route::has($item['r']))
                            @php
                                $matches = is_array($item['match']) ? $item['match'] : [$item['match']];
                                $active = request()->routeIs(...$matches);
                            @endphp

                            <a href="{{ route($item['r']) }}"
                               class="relative flex items-center gap-3 px-3 py-2.5 my-0.5 rounded-xl text-[13px] font-medium transition-all duration-200 group
                                      {{ $active
                                         ? 'bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white shadow-brand-glow ring-1 ring-brand-300/50'
                                         : 'text-gray-700 hover:bg-gradient-to-r hover:from-brand-50 hover:to-brand-50/50 hover:text-brand-700' }}"
                               :title="!sidebarOpen ? '{{ $item['label'] }}' : ''">
                                @if($active)
                                    <span class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-7 bg-gradient-to-b from-gold-300 to-gold-500 rounded-r-full shadow-gold-glow"></span>
                                @endif

                                <svg class="w-[18px] h-[18px] flex-shrink-0 {{ $active ? 'drop-shadow' : 'group-hover:scale-110 transition-transform' }}"
                                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['d'] }}"/>
                                </svg>

                                <span x-show="sidebarOpen" x-transition class="truncate">{{ $item['label'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endforeach
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

    {{-- ════════════════════════════════════════════════════ --}}
    {{-- MAIN --}}
    {{-- ════════════════════════════════════════════════════ --}}
    <main class="min-h-screen transition-[margin] duration-300 relative z-10"
          :class="sidebarOpen ? 'lg:ml-[260px]' : 'lg:ml-[72px]'">

        {{-- Bandeau LECTURE SEULE (visible quand année cloturée/archivée) --}}
        @include('components.banniere-lecture-seule')

        {{-- Topbar --}}
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
                        <h2 class="font-display text-base lg:text-xl font-extrabold text-gray-900 leading-tight tracking-tight">
                            @yield('page-title', 'Tableau de bord')
                        </h2>
                        <p class="text-[11px] text-gray-500 leading-tight mt-0.5">
                            @yield('page-subtitle', now()->locale('fr')->isoFormat('dddd D MMMM YYYY'))
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 lg:gap-3">
                    <div class="hidden md:block relative">
                        <input type="text" placeholder="Rechercher..."
                               class="w-56 lg:w-72 pl-9 pr-4 py-2 bg-white/70 border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:bg-white focus:border-brand-300 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                        <svg class="w-4 h-4 text-brand-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    @include('components.notification-bell')

                    <div class="hidden sm:flex items-center gap-2 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 px-3 py-1.5 rounded-xl shadow-brand-glow">
                        <span class="w-1.5 h-1.5 bg-gold-300 rounded-full animate-pulse"></span>
                        <span class="text-[11px] font-extrabold text-white tracking-wide">2025–2026</span>
                    </div>
                </div>
            </div>
        </header>

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

            @if($errors->any())
                <div class="mb-4 p-4 bg-gradient-to-r from-red-50 to-red-100/50 border border-red-200 rounded-xl text-red-800 text-sm font-medium flex items-center gap-2 shadow-sm">
                    <svg class="w-5 h-5 flex-shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ $errors->first() }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    @include('components.lecture-seule-masquage')
    @stack('scripts')
</body>
</html>