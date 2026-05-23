@php
    $tabs = [
        ['label' => 'Vue 360°', 'route' => 'cockpit.index'],
        ['label' => 'Score de santé', 'route' => 'cockpit.score'],
        ['label' => 'Alertes', 'route' => 'cockpit.alertes'],
    ];
@endphp

<div class="flex flex-col gap-4 mb-2">
    <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-gradient-to-br from-violet-500 to-purple-700 rounded-xl flex items-center justify-center shadow-card-violet">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </div>
        <div>
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Module 17 — Cockpit Dirigeant</p>
            <h2 class="font-display text-2xl font-extrabold text-gray-900">Vision 360° santé financière</h2>
        </div>
    </div>
    <nav class="flex flex-wrap gap-2">
        @foreach($tabs as $tab)
            <a href="{{ route($tab['route']) }}"
               class="px-4 py-2 rounded-xl text-sm font-bold border transition
                   {{ request()->routeIs($tab['route'])
                        ? 'bg-gradient-to-r from-violet-500 to-purple-700 text-white border-violet-500 shadow-card-violet'
                        : 'bg-white text-gray-600 border-gray-200 hover:border-violet-300 hover:text-violet-700' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>
