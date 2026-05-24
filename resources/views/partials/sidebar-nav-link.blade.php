@php
    $blocked = false;
    if (auth()->check()) {
        try {
            $patterns = collect((array) ($item['match'] ?? $item['r'] ?? []));
            $blockedKeys = \App\Services\Access\SchoolRoleAccessService::blockedKeysFor(auth()->user());
            $catalogue = \App\Services\Access\SchoolRoleAccessService::catalogue();

            foreach ($blockedKeys as $blockedKey) {
                $entry = $catalogue[$blockedKey] ?? null;
                if (! $entry) continue;
                foreach (($entry['routes'] ?? []) as $pattern) {
                    if ($patterns->contains(fn ($itemPattern) => \Illuminate\Support\Str::is($pattern, $itemPattern) || \Illuminate\Support\Str::is($itemPattern, $pattern))) {
                        $blocked = true;
                        break 2;
                    }
                }
            }
        } catch (\Throwable $e) {
            $blocked = false;
        }
    }

    $active = is_array($item['match'])
        ? request()->routeIs(...$item['match'])
        : request()->routeIs($item['match']);
    try {
        $navUrl = route($item['r'], $item['rp'] ?? []);
    } catch (\Exception $e) {
        $navUrl = '#';
    }
    $compact = $compact ?? false;
@endphp

@if(! $blocked)
<a href="{{ $navUrl }}"
   @click="if (window.matchMedia('(max-width: 1023px)').matches) mobileMenu = false"
   class="relative flex items-center gap-3 rounded-xl font-medium transition-all duration-200 group
          {{ $compact ? 'px-3 py-2 my-0.5 text-[12px]' : 'px-3 py-2.5 my-0.5 text-[13px]' }}
          {{ $active
             ? 'bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white shadow-brand-glow ring-1 ring-brand-300/50'
             : 'text-gray-700 hover:bg-gradient-to-r hover:from-brand-50 hover:to-brand-50/50 hover:text-brand-700' }}"
   :title="!sidebarOpen ? '{{ $item['label'] }}' : ''">
    @if($active)
        <span class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-gradient-to-b from-gold-300 to-gold-500 rounded-r-full shadow-gold-glow"></span>
    @endif
    <svg class="{{ $compact ? 'w-4 h-4' : 'w-[18px] h-[18px]' }} flex-shrink-0 {{ $active ? 'drop-shadow' : 'group-hover:scale-110 transition-transform' }}"
         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['d'] }}"/>
    </svg>
    <span x-show="sidebarOpen" x-transition class="truncate">{{ $item['label'] }}</span>
</a>
@endif
