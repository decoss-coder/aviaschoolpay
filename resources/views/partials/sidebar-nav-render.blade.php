@if(request()->routeIs('eleves.import.preview'))
<style>
    div[x-data="previewApp()"] .overflow-x-auto > table { min-width: 1280px; table-layout: fixed; }
    div[x-data="previewApp()"] .overflow-x-auto > table th:nth-child(1), div[x-data="previewApp()"] .overflow-x-auto > table td:nth-child(1) { width: 44px; min-width: 44px; }
    div[x-data="previewApp()"] .overflow-x-auto > table th:nth-child(2), div[x-data="previewApp()"] .overflow-x-auto > table td:nth-child(2) { width: 58px; min-width: 58px; }
    div[x-data="previewApp()"] .overflow-x-auto > table th:nth-child(3), div[x-data="previewApp()"] .overflow-x-auto > table td:nth-child(3) { width: 145px; min-width: 145px; }
    div[x-data="previewApp()"] .overflow-x-auto > table th:nth-child(4), div[x-data="previewApp()"] .overflow-x-auto > table td:nth-child(4) { width: 205px; min-width: 205px; }
    div[x-data="previewApp()"] .overflow-x-auto > table th:nth-child(5), div[x-data="previewApp()"] .overflow-x-auto > table td:nth-child(5) { width: 260px; min-width: 260px; }
    div[x-data="previewApp()"] .overflow-x-auto > table th:nth-child(6), div[x-data="previewApp()"] .overflow-x-auto > table td:nth-child(6) { width: 82px; min-width: 82px; }
    div[x-data="previewApp()"] .overflow-x-auto > table th:nth-child(7), div[x-data="previewApp()"] .overflow-x-auto > table td:nth-child(7) { width: 145px; min-width: 145px; }
    div[x-data="previewApp()"] .overflow-x-auto > table th:nth-child(8), div[x-data="previewApp()"] .overflow-x-auto > table td:nth-child(8) { width: 135px; min-width: 135px; }
    div[x-data="previewApp()"] .overflow-x-auto > table th:nth-child(9), div[x-data="previewApp()"] .overflow-x-auto > table td:nth-child(9) { width: 210px; min-width: 210px; }
    div[x-data="previewApp()"] .overflow-x-auto > table th:nth-child(10), div[x-data="previewApp()"] .overflow-x-auto > table td:nth-child(10) { width: 50px; min-width: 50px; }
    div[x-data="previewApp()"] .overflow-x-auto > table input[type="text"] { min-width: 100%; overflow: visible; text-overflow: clip; }
</style>
@endif

@php
    $currentUser = auth()->user();
    $role = $currentUser?->role;

    $canSeeAccessControl = $currentUser && ($currentUser->isFondateur() || $currentUser->isSuperAdmin());
    $canSeeUnpaidStudents = in_array($role, ['fondateur', 'directeur', 'directeur_adjoint', 'gestionnaire', 'comptable', 'secretaire', 'censeur', 'super_admin'], true);
    $canSeePointPostes = $canSeeUnpaidStudents;
    $canSeeSchoolSettings = in_array($role, ['fondateur', 'super_admin', 'directeur', 'directeur_adjoint', 'gestionnaire', 'secretaire', 'comptable', 'censeur'], true);

    $extraItems = [];
    $schoolSettingItems = [];

    if ($canSeeSchoolSettings) {
        $schoolSettingItems[] = [
            'r' => 'admin.rh.niveaux.index',
            'match' => 'admin.rh.niveaux.*',
            'label' => 'Niveaux',
            'd' => 'M4 6h16M4 10h16M4 14h16M4 18h16',
        ];

        $schoolSettingItems[] = [
            'r' => 'admin.rh.disciplines.index',
            'match' => 'admin.rh.disciplines.*',
            'label' => 'Disciplines',
            'd' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        ];
    }

    if (! empty($schoolSettingItems)) {
        $nav[] = ['section' => 'Paramétrage scolaire', 'items' => $schoolSettingItems];
    }

    if ($canSeeAccessControl) {
        $extraItems[] = [
            'r' => 'access-control.index',
            'match' => 'access-control.*',
            'label' => 'Contrôle des accès',
            'd' => 'M12 11c0-1.105.895-2 2-2s2 .895 2 2m-2 4h.01M5 11V9a7 7 0 1114 0v2m-2 0H7a2 2 0 00-2 2v7a2 2 0 002 2h10a2 2 0 002-2v-7a2 2 0 00-2-2z',
        ];
    }

    if ($canSeePointPostes) {
        $extraItems[] = [
            'r' => 'finances.index',
            'rp' => ['point' => 'inscription'],
            'match' => 'finances.index',
            'label' => 'Point inscription',
            'd' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        ];

        $extraItems[] = [
            'r' => 'finances.index',
            'rp' => ['point' => 'scolarite'],
            'match' => 'finances.index',
            'label' => 'Point scolarité',
            'd' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
        ];
    }

    if ($canSeeUnpaidStudents) {
        $extraItems[] = [
            'r' => 'finances.impayes.index',
            'match' => 'finances.impayes.*',
            'label' => 'Élèves non soldés',
            'd' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-1M9 11h6M9 15h4m4-1l2 2 4-4',
        ];
    }

    if (! empty($extraItems)) {
        $nav[] = ['section' => 'Accès & recouvrement', 'items' => $extraItems];
    }
@endphp

@foreach($nav as $gIdx => $group)
<div class="mb-1 {{ $gIdx > 0 ? 'mt-4' : 'mt-2' }}">
    <p class="px-3 pb-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-[0.14em] flex items-center gap-1.5" x-show="sidebarOpen" x-transition>
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
                $subActive = $subActive || (is_array($subItem['match']) ? request()->routeIs(...$subItem['match']) : request()->routeIs($subItem['match']));
            }
        @endphp

        <details class="nav-sub group/sub mb-0.5" {{ $subActive ? 'open' : '' }} x-show="sidebarOpen">
            <summary class="flex items-center gap-3 px-3 py-2.5 my-0.5 rounded-xl text-[13px] font-semibold text-gray-600 cursor-pointer select-none hover:bg-brand-50/80 hover:text-brand-700 transition-all list-none {{ $subActive ? 'text-brand-700 bg-brand-50/60' : '' }}">
                <svg class="w-[18px] h-[18px] flex-shrink-0 text-brand-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sub['d'] }}"/></svg>
                <span class="flex-1 truncate">{{ $sub['label'] }}</span>
                <svg class="w-4 h-4 flex-shrink-0 text-gray-400 transition-transform group-open/sub:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </summary>
            <div class="ml-4 pl-2 border-l-2 border-brand-100/80 mb-1">
                @foreach($sub['items'] as $item)
                    @include('partials.sidebar-nav-link', ['item' => $item, 'compact' => true])
                @endforeach
            </div>
        </details>

        <div x-show="!sidebarOpen" x-cloak class="space-y-0.5">
            @foreach($sub['items'] as $item)
                @include('partials.sidebar-nav-link', ['item' => $item])
            @endforeach
        </div>
    @endforeach
</div>
@endforeach
