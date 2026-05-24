@extends('layouts.app')

@section('title', 'Contrôle des accès')
@section('page-title', 'Contrôle des accès')
@section('page-subtitle', 'Bloquer les menus et les routes par rôle')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="bg-white rounded-2xl border shadow-card p-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="font-display text-xl font-extrabold text-gray-900">Blocage des accès par rôle</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Le fondateur peut bloquer un menu pour un rôle. Le blocage masque le menu et bloque aussi les routes associées.
                </p>
            </div>
            <span class="px-3 py-1 rounded-full bg-violet-100 text-violet-700 text-xs font-bold">Fondateur</span>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            ✓ {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('access-control.update') }}" class="space-y-6">
        @csrf

        @foreach($roles as $role => $roleLabel)
            <div class="bg-white rounded-2xl border shadow-card overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-gray-900">{{ $roleLabel }}</h2>
                        <p class="text-xs text-gray-500">Coche les modules à bloquer pour ce rôle.</p>
                    </div>
                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ $role }}</span>
                </div>

                <div class="p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($catalogue as $menuKey => $module)
                        @php $checked = in_array($menuKey, $blocked[$role] ?? [], true); @endphp
                        <label class="cursor-pointer rounded-xl border p-3 transition-all {{ $checked ? 'border-red-200 bg-red-50' : 'border-gray-100 bg-gray-50/50 hover:border-violet-200 hover:bg-violet-50/40' }}">
                            <div class="flex items-start gap-3">
                                <input type="checkbox"
                                       name="blocks[{{ $role }}][]"
                                       value="{{ $menuKey }}"
                                       @checked($checked)
                                       class="mt-1 rounded border-gray-300 text-red-600 focus:ring-red-200">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold {{ $checked ? 'text-red-800' : 'text-gray-800' }}">{{ $module['label'] }}</p>
                                    <p class="text-[11px] text-gray-500 mt-0.5">{{ $module['section'] ?? 'Module' }}</p>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="sticky bottom-4 flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-violet-500 to-purple-700 text-white text-sm font-bold rounded-xl shadow-card-violet hover:-translate-y-0.5 transition-all">
                Enregistrer les blocages
            </button>
        </div>
    </form>
</div>
@endsection
