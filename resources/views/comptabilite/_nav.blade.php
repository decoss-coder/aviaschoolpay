@php
    $tabs = [
        ['label' => 'Vue d\'ensemble', 'route' => 'comptabilite.index', 'match' => 'comptabilite.index'],
        ['label' => 'Journal', 'route' => 'comptabilite.journal', 'match' => 'comptabilite.journal'],
        ['label' => 'Grand livre', 'route' => 'comptabilite.grand-livre', 'match' => 'comptabilite.grand-livre'],
        ['label' => 'Bilan', 'route' => 'comptabilite.bilan', 'match' => 'comptabilite.bilan'],
        ['label' => 'Résultat', 'route' => 'comptabilite.resultat', 'match' => 'comptabilite.resultat'],
    ];
@endphp

<div class="flex flex-col gap-4 mb-2">
    <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-blue-700 rounded-xl flex items-center justify-center shadow-card-blue">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
        </div>
        <div>
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Module 12 — SYSCOHADA</p>
            <h2 class="font-display text-2xl font-extrabold text-gray-900">Comptabilité scolaire</h2>
        </div>
    </div>

    <nav class="flex flex-wrap gap-2">
        @foreach($tabs as $tab)
            <a href="{{ route($tab['route']) }}"
               class="px-4 py-2 rounded-xl text-sm font-bold border transition
                   {{ request()->routeIs($tab['match'])
                        ? 'bg-gradient-to-r from-brand-500 to-brand-700 text-white border-brand-600 shadow-brand-glow'
                        : 'bg-white text-gray-600 border-gray-200 hover:border-brand-300 hover:text-brand-700' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>
