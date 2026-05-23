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

    @foreach($group['items'] ?? [] as $item)
        @include('partials.sidebar-nav-link', ['item' => $item])
    @endforeach

    @foreach($group['subs'] ?? [] as $sub)
        @php
            $subActive = false;
            foreach ($sub['items'] as $subItem) {
                $subActive = $subActive || (is_array($subItem['match'])
                    ? request()->routeIs(...$subItem['match'])
                    : request()->routeIs($subItem['match']));
            }
        @endphp

        {{-- Sidebar ouverte : sous-menu repliable --}}
        <details class="nav-sub group/sub mb-0.5" {{ $subActive ? 'open' : '' }} x-show="sidebarOpen">
            <summary class="flex items-center gap-3 px-3 py-2.5 my-0.5 rounded-xl text-[13px] font-semibold text-gray-600 cursor-pointer select-none
                             hover:bg-brand-50/80 hover:text-brand-700 transition-all list-none
                             {{ $subActive ? 'text-brand-700 bg-brand-50/60' : '' }}">
                <svg class="w-[18px] h-[18px] flex-shrink-0 text-brand-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $sub['d'] }}"/>
                </svg>
                <span class="flex-1 truncate">{{ $sub['label'] }}</span>
                <svg class="w-4 h-4 flex-shrink-0 text-gray-400 transition-transform group-open/sub:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="ml-4 pl-2 border-l-2 border-brand-100/80 mb-1">
                @foreach($sub['items'] as $item)
                    @include('partials.sidebar-nav-link', ['item' => $item, 'compact' => true])
                @endforeach
            </div>
        </details>

        {{-- Sidebar réduite : liens directs (icônes) --}}
        <div x-show="!sidebarOpen" x-cloak class="space-y-0.5">
            @foreach($sub['items'] as $item)
                @include('partials.sidebar-nav-link', ['item' => $item])
            @endforeach
        </div>
    @endforeach
</div>
@endforeach
