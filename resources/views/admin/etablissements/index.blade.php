@extends('layouts.app')
@section('title', 'Établissements')
@section('page-title', 'Établissements')
@section('page-subtitle', 'Créer, modifier, bloquer l\'accès')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-extrabold text-gray-900">Établissements</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $etablissements->count() }} école(s) affichée(s)</p>
        </div>
        <a href="{{ route('admin.etablissements.create') }}" class="px-4 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-bold hover:bg-brand-700 shadow-brand-glow text-center">+ Ajouter une école</a>
    </div>

    <form method="GET" class="bg-white rounded-2xl border shadow-card p-4 flex flex-col md:flex-row gap-3">
        <input type="search" name="q" value="{{ $q }}" placeholder="Nom, DESPS, ville…" class="flex-1 rounded-xl border-gray-200 text-sm">
        <select name="filtre" class="rounded-xl border-gray-200 text-sm md:w-40">
            <option value="tous" @selected($filtre === 'tous')>Tous</option>
            <option value="actifs" @selected($filtre === 'actifs')>Actifs</option>
            <option value="bloques" @selected($filtre === 'bloques')>Bloqués</option>
        </select>
        <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 text-white text-sm font-bold">Filtrer</button>
    </form>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-800">
            @foreach($errors->all() as $err)<p>{{ $err }}</p>@endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($etablissements as $row)
            @php $e = $row['etablissement']; $r = $row['recouvrement']; @endphp
            <article class="bg-white rounded-2xl border shadow-card overflow-hidden flex flex-col {{ !$e->actif ? 'ring-2 ring-red-100' : '' }}">
                <div class="p-5 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h2 class="font-bold text-gray-900 truncate">{{ $e->nom }}</h2>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $e->sigle }} · DESPS {{ $e->code_desps }}</p>
                            <p class="text-xs text-gray-400">{{ $e->ville }}</p>
                        </div>
                        @if($e->actif)
                            <span class="shrink-0 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">Actif</span>
                        @else
                            <span class="shrink-0 px-2 py-0.5 rounded-full bg-red-100 text-red-800 text-[10px] font-bold">Bloqué</span>
                        @endif
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-2 text-xs">
                        <div><dt class="text-gray-400">Élèves</dt><dd class="font-bold">{{ $row['eleves'] }}</dd></div>
                        <div><dt class="text-gray-400">Comptes</dt><dd class="font-bold">{{ $row['utilisateurs_actifs'] }}</dd></div>
                        <div class="col-span-2"><dt class="text-gray-400">Recouvrement</dt><dd class="font-bold text-brand-600">{{ $r['taux'] }}%</dd></div>
                    </dl>
                </div>
                <div class="px-5 py-3 bg-gray-50 border-t flex flex-wrap gap-2 items-center">
                    <a href="{{ route('admin.etablissements.show', $e) }}" class="text-xs font-bold text-brand-600">Fiche</a>
                    <a href="{{ route('admin.etablissements.edit', $e) }}" class="text-xs font-bold text-gray-600">Modifier</a>
                    @if($e->actif)
                        <form method="POST" action="{{ route('admin.etablissements.ouvrir', $e) }}" class="inline">@csrf
                            <button type="submit" class="text-xs font-bold text-gold-600">Ouvrir</button>
                        </form>
                        <form method="POST" action="{{ route('admin.etablissements.toggle-access', $e) }}" class="inline ml-auto" onsubmit="return confirm('Suspendre l\'accès pour toute l\'école ?')">@csrf
                            <input type="hidden" name="bloquer" value="1">
                            <button type="submit" class="text-xs font-bold text-red-600">Bloquer</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.etablissements.toggle-access', $e) }}" class="inline ml-auto">@csrf
                            <button type="submit" class="text-xs font-bold text-emerald-600">Réactiver</button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <p class="col-span-full text-center text-gray-400 py-12">Aucun établissement trouvé.</p>
        @endforelse
    </div>
</div>
@endsection
