@auth
<div class="relative" x-data="notificationBell()" x-init="init()" @keydown.escape.window="open = false">
    <button type="button"
            @click="open = !open; if(open) fetchFeed()"
            class="relative p-2 text-gray-500 hover:text-brand-600 bg-white/70 hover:bg-brand-50 border border-brand-100 rounded-xl transition-colors shadow-sm"
            title="Notifications">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span x-show="unread > 0" x-cloak
              class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center bg-red-500 text-white text-[10px] font-bold rounded-full ring-2 ring-white"
              x-text="unread > 9 ? '9+' : unread"></span>
        <span x-show="unread > 0 && pulse" class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white animate-ping"></span>
    </button>

    <div x-show="open" x-cloak @click.outside="open = false"
         x-transition
         class="absolute right-0 mt-2 w-[min(100vw-2rem,22rem)] bg-white rounded-2xl shadow-card-hover border border-brand-100/80 z-50 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-brand-50 to-white">
            <div>
                <p class="text-sm font-extrabold text-gray-900">Notifications</p>
                <p class="text-[11px] text-gray-500" x-show="pendingPayments > 0">
                    <span x-text="pendingPayments"></span> paiement(s) en attente
                </p>
            </div>
            <a href="{{ route('notifications.index') }}" class="text-[11px] font-bold text-brand-600 hover:underline">Tout voir</a>
        </div>
        <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
            <template x-if="loading">
                <p class="p-4 text-sm text-gray-500 text-center">Chargement…</p>
            </template>
            <template x-if="!loading && items.length === 0">
                <p class="p-6 text-sm text-gray-500 text-center">Aucune notification</p>
            </template>
            <template x-for="item in items" :key="item.id">
                <a :href="item.lien_action || '#'"
                   @click.prevent="openItem(item)"
                   class="block px-4 py-3 hover:bg-brand-50/60 transition-colors"
                   :class="item.lue ? 'opacity-75' : 'bg-brand-50/30 border-l-2 border-amber-400'">
                    <p class="text-sm font-bold text-gray-900 leading-tight" x-text="item.titre"></p>
                    <p class="text-xs text-gray-600 mt-1 line-clamp-2" x-text="item.message"></p>
                    <p class="text-[10px] text-gray-400 mt-1" x-text="item.created_human"></p>
                </a>
            </template>
        </div>
        <div class="px-3 py-2 border-t border-gray-100 flex gap-2">
            <button type="button" @click="markAllRead()"
                    class="flex-1 text-center text-xs font-bold text-brand-700 py-2 rounded-lg hover:bg-brand-50">
                Tout marquer lu
            </button>
            @if(Route::has('paiements.index'))
            <a href="{{ route('paiements.index', ['statut' => 'en_attente']) }}"
               class="flex-1 text-center text-xs font-bold text-amber-800 py-2 rounded-lg bg-amber-50 hover:bg-amber-100">
                Paiements
            </a>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function notificationBell() {
    return {
        open: false,
        loading: false,
        items: [],
        unread: 0,
        pendingPayments: 0,
        pulse: false,
        lastCount: 0,
        pollTimer: null,
        init() {
            this.fetchFeed();
            this.pollTimer = setInterval(() => this.fetchFeed(true), 20000);
        },
        playSound() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const o = ctx.createOscillator();
                const g = ctx.createGain();
                o.type = 'sine';
                o.frequency.value = 880;
                o.connect(g);
                g.connect(ctx.destination);
                g.gain.setValueAtTime(0.12, ctx.currentTime);
                g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
                o.start(ctx.currentTime);
                o.stop(ctx.currentTime + 0.35);
            } catch (e) {}
        },
        async fetchFeed(silent = false) {
            if (!silent) this.loading = true;
            try {
                const res = await fetch('{{ route('notifications.feed') }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                const badge = data.badge_count ?? data.unread_count ?? 0;
                if (silent && badge > this.lastCount) {
                    this.pulse = true;
                    this.playSound();
                    setTimeout(() => { this.pulse = false; }, 2000);
                }
                this.lastCount = badge;
                this.unread = badge;
                this.pendingPayments = data.paiements_en_attente ?? 0;
                this.items = data.notifications ?? [];
            } catch (e) {
                console.warn('Notifications feed', e);
            } finally {
                this.loading = false;
            }
        },
        openItem(item) {
            if (!item.synthetic && item.id) {
                this.markRead(item.id);
            }
            if (item.lien_action) {
                window.location.href = item.lien_action;
            }
        },
        async markRead(id) {
            if (String(id).startsWith('paiement-')) {
                return;
            }
            await fetch(`{{ url('/notifications') }}/${id}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            });
            this.fetchFeed(true);
        },
        async markAllRead() {
            await fetch('{{ route('notifications.read-all') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            });
            this.unread = 0;
            this.items = this.items.map(i => ({ ...i, lue: true }));
        },
    };
}
</script>
@endpush
@endauth
