@php
    $isDashboard = request()->routeIs('admin.rh.dashboard');
    $isEnseignants = request()->routeIs('enseignants.*') || request()->routeIs('admin.rh.enseignants.*');
    $isAffectations = request()->routeIs('admin.rh.affectations.*');
    $isPointages = request()->routeIs('admin.rh.pointages.*') || request()->routeIs('pointage.*');
    $isAlertes = request()->routeIs('admin.rh.alertes.*');
@endphp

<div class="mb-6">
    <div class="relative overflow-hidden rounded-2xl border border-brand-100/70 bg-gradient-to-br from-white via-white to-brand-50/30 shadow-card-brand">
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-500 via-brand-600 to-gold-500"></div>

        <div class="p-4 lg:p-5">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.16em] font-bold text-brand-600">Administration RH</p>
                    <h2 class="font-display text-lg font-extrabold text-gray-900 mt-1">Pilotage des enseignants</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Enseignants, affectations, pointages et alertes.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.rh.dashboard') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-[13px] font-bold border transition-all {{ $isDashboard ? 'bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white border-brand-600 shadow-brand-glow' : 'bg-white text-gray-700 border-brand-100 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-200 shadow-sm' }}">
                        Dashboard RH
                    </a>

                    <a href="{{ route('enseignants.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-[13px] font-bold border transition-all {{ $isEnseignants ? 'bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white border-brand-600 shadow-brand-glow' : 'bg-white text-gray-700 border-brand-100 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-200 shadow-sm' }}">
                        Enseignants
                    </a>

                    <a href="{{ route('admin.rh.affectations.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-[13px] font-bold border transition-all {{ $isAffectations ? 'bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white border-brand-600 shadow-brand-glow' : 'bg-white text-gray-700 border-brand-100 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-200 shadow-sm' }}">
                        Affectations
                    </a>

                    <a href="{{ route('admin.rh.pointages.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-[13px] font-bold border transition-all {{ $isPointages ? 'bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white border-brand-600 shadow-brand-glow' : 'bg-white text-gray-700 border-brand-100 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-200 shadow-sm' }}">
                        Pointages
                    </a>

                    <a href="{{ route('admin.rh.alertes.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-[13px] font-bold border transition-all {{ $isAlertes ? 'bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white border-brand-600 shadow-brand-glow' : 'bg-white text-gray-700 border-brand-100 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-200 shadow-sm' }}">
                        Alertes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>