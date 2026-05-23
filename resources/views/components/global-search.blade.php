@php
    $colorMap = [
        'blue'    => 'bg-blue-100 text-blue-700',
        'teal'    => 'bg-teal-100 text-teal-700',
        'violet'  => 'bg-violet-100 text-violet-700',
        'emerald' => 'bg-emerald-100 text-emerald-700',
        'rose'    => 'bg-rose-100 text-rose-700',
        'amber'   => 'bg-amber-100 text-amber-700',
    ];
@endphp

<div class="hidden md:block relative" x-data="globalSearch()" @keydown.escape.window="open = false" @click.away="open = false">
    {{-- Input --}}
    <div class="relative">
        <input type="text"
               x-model="q"
               @input.debounce.300ms="rechercher()"
               @focus="if (q.length >= 2) open = true"
               placeholder="Rechercher élève, enseignant, classe, paiement…"
               class="w-56 lg:w-80 pl-9 pr-9 py-2 bg-white/80 border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:bg-white focus:border-brand-300 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm" />
        {{-- Icône recherche --}}
        <svg class="w-4 h-4 text-brand-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        {{-- Spinner ou clear --}}
        <button x-show="q.length > 0" type="button" @click="reset()" x-cloak
                class="absolute right-2 top-2 p-0.5 text-gray-400 hover:text-gray-700 rounded">
            <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            <svg x-show="loading" class="w-4 h-4 animate-spin text-brand-500" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"></circle><path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"></path></svg>
        </button>
    </div>

    {{-- Dropdown résultats --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute top-full right-0 mt-2 w-[420px] bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-50 max-h-[70vh] overflow-y-auto">

        {{-- État : trop court --}}
        <template x-if="q.length < 2 && !loading">
            <div class="p-6 text-center text-sm text-gray-400">
                <p class="text-3xl mb-2">🔍</p>
                <p>Tapez au moins 2 caractères pour rechercher</p>
                <p class="text-xs mt-2 text-gray-500">Élèves, enseignants, classes, paiements, dépenses, utilisateurs</p>
            </div>
        </template>

        {{-- État : pas de résultat --}}
        <template x-if="q.length >= 2 && !loading && results.length === 0">
            <div class="p-6 text-center text-sm text-gray-500">
                <p class="text-3xl mb-2">😕</p>
                <p>Aucun résultat pour « <b x-text="q"></b> »</p>
                <p class="text-xs mt-2 text-gray-400">Vérifiez l'orthographe ou essayez un autre mot-clé</p>
            </div>
        </template>

        {{-- Header résultats --}}
        <template x-if="results.length > 0">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <span x-text="results.length"></span> résultat<span x-show="results.length > 1">s</span> pour « <b class="text-brand-700" x-text="q"></b> »
            </div>
        </template>

        {{-- Liste résultats --}}
        <div class="divide-y divide-gray-100">
            <template x-for="(r, i) in results" :key="i">
                <a :href="r.url"
                   :class="i === selected ? 'bg-brand-50' : 'hover:bg-gray-50'"
                   @mouseenter="selected = i"
                   class="flex items-start gap-3 px-4 py-3 cursor-pointer transition">
                    <span class="text-2xl flex-shrink-0" x-text="r.icon"></span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-bold text-gray-900 text-sm truncate" x-text="r.titre"></p>
                            <span class="inline-flex px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase"
                                  :class="{
                                    'bg-blue-100 text-blue-700': r.couleur === 'blue',
                                    'bg-teal-100 text-teal-700': r.couleur === 'teal',
                                    'bg-violet-100 text-violet-700': r.couleur === 'violet',
                                    'bg-emerald-100 text-emerald-700': r.couleur === 'emerald',
                                    'bg-rose-100 text-rose-700': r.couleur === 'rose',
                                    'bg-amber-100 text-amber-700': r.couleur === 'amber',
                                  }"
                                  x-text="r.type_label"></span>
                        </div>
                        <p class="text-xs text-gray-500 truncate mt-0.5" x-text="r.sous"></p>
                    </div>
                    <svg class="w-4 h-4 text-gray-300 flex-shrink-0 mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            </template>
        </div>

        {{-- Footer raccourcis --}}
        <div class="px-4 py-2 bg-gray-50 border-t border-gray-100 flex items-center justify-between text-[10px] text-gray-400">
            <span><kbd class="px-1.5 py-0.5 bg-white border border-gray-200 rounded font-mono">↑</kbd> <kbd class="px-1.5 py-0.5 bg-white border border-gray-200 rounded font-mono">↓</kbd> Naviguer</span>
            <span><kbd class="px-1.5 py-0.5 bg-white border border-gray-200 rounded font-mono">Esc</kbd> Fermer</span>
        </div>
    </div>
</div>

<script>
function globalSearch() {
    return {
        q: '',
        results: [],
        loading: false,
        open: false,
        selected: 0,
        ctrl: null,
        async rechercher() {
            if (this.q.length < 2) {
                this.results = [];
                this.open = false;
                return;
            }
            // Annuler la requête précédente
            if (this.ctrl) this.ctrl.abort();
            this.ctrl = new AbortController();
            this.loading = true;
            this.open = true;
            try {
                const url = "{{ route('search') }}?q=" + encodeURIComponent(this.q);
                const r = await fetch(url, { signal: this.ctrl.signal, headers: { 'Accept': 'application/json' } });
                const d = await r.json();
                this.results = d.results || [];
                this.selected = 0;
            } catch (e) {
                if (e.name !== 'AbortError') console.error(e);
            } finally {
                this.loading = false;
            }
        },
        reset() {
            this.q = '';
            this.results = [];
            this.open = false;
        },
    };
}

// Navigation clavier
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd+K = focus barre
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        document.querySelector('[x-data="globalSearch()"] input')?.focus();
    }
});
</script>
