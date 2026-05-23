@extends('layouts.app')
@section('title', 'Sous-disciplines des matières')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-5" x-data="{ openParent: null, editingId: null }">

    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('admin.rh.dashboard') }}" class="hover:text-brand-600">RH</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">Sous-disciplines</span>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div>
        <h1 class="font-display text-2xl font-extrabold text-gray-900">Sous-disciplines des matières</h1>
        <p class="text-sm text-gray-500 mt-1">
            Exemple : <b>Français</b> au premier cycle (6e→3e) = <b>CF</b> (Composition française) + <b>OG</b> (Orthographe & Grammaire) + <b>EO</b> (Expression orale).
            Chaque sous-discipline a son propre coefficient dans la matière parente.
        </p>
    </div>

    {{-- Preset : Français standard ivoirien --}}
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 flex items-center justify-between flex-wrap gap-2">
        <div class="text-sm text-amber-800">
            <b>Raccourci :</b> initialiser Français premier cycle avec le standard ivoirien
            (CF×3, OG×1, EO×1).
        </div>
        <form method="POST" action="{{ route('admin.rh.sous-disciplines.preset-francais') }}">
            @csrf
            <button class="text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white px-3 py-1.5 rounded-lg">
                ⚡ Auto-créer CF / OG / EO
            </button>
        </form>
    </div>

    {{-- Liste des matières (racine) avec leurs sous-disciplines --}}
    <div class="space-y-3">
        @forelse($matieres as $matiere)
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
            {{-- Header matière --}}
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-3 cursor-pointer hover:bg-gray-50"
                 @click="openParent = openParent === {{ $matiere->id }} ? null : {{ $matiere->id }}">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-brand-100 flex items-center justify-center font-extrabold text-brand-700 text-xs">
                        {{ $matiere->code }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-bold text-gray-900 truncate">{{ $matiere->nom }}</p>
                        <p class="text-xs text-gray-500">
                            Coef matière : <b>{{ rtrim(rtrim(number_format($matiere->coefficient_defaut, 2, '.', ''), '0'), '.') }}</b>
                            @if($matiere->sousDisciplines->isNotEmpty())
                                · <span class="text-purple-700 font-bold">{{ $matiere->sousDisciplines->count() }} sous-discipline(s)</span>
                            @endif
                        </p>
                    </div>
                </div>
                <button @click.stop="openParent = openParent === {{ $matiere->id }} ? null : {{ $matiere->id }}"
                        class="text-xs font-bold text-brand-600 hover:text-brand-800">
                    <span x-show="openParent !== {{ $matiere->id }}">+ Ajouter / Gérer</span>
                    <span x-show="openParent === {{ $matiere->id }}">▼ Fermer</span>
                </button>
            </div>

            {{-- Liste des sous-disciplines + form --}}
            <div x-show="openParent === {{ $matiere->id }}" x-cloak x-transition class="bg-gray-50/40">
                @if($matiere->sousDisciplines->isNotEmpty())
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-5 py-2 text-left text-xs font-bold text-gray-500 uppercase">Code</th>
                            <th class="px-5 py-2 text-left text-xs font-bold text-gray-500 uppercase">Nom</th>
                            <th class="px-5 py-2 text-center text-xs font-bold text-gray-500 uppercase">Poids</th>
                            <th class="px-5 py-2 text-center text-xs font-bold text-gray-500 uppercase">Ordre</th>
                            <th class="px-5 py-2 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($matiere->sousDisciplines as $sub)
                        <tr class="hover:bg-white">
                            <td class="px-5 py-2 font-mono font-bold text-purple-700">{{ $sub->code }}</td>
                            <td class="px-5 py-2">{{ $sub->nom }}</td>
                            <td class="px-5 py-2 text-center font-bold">×{{ rtrim(rtrim(number_format($sub->poids_dans_parent, 2, '.', ''), '0'), '.') }}</td>
                            <td class="px-5 py-2 text-center text-xs text-gray-500">{{ $sub->ordre }}</td>
                            <td class="px-5 py-2 text-right">
                                <button @click="editingId = editingId === {{ $sub->id }} ? null : {{ $sub->id }}"
                                        class="text-xs font-bold text-blue-600 hover:text-blue-800 mr-2">✏ Éditer</button>
                                <form method="POST" action="{{ route('admin.rh.sous-disciplines.destroy', $sub) }}"
                                      onsubmit="return confirm('Désactiver cette sous-discipline ?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs font-bold text-red-600 hover:text-red-800">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        {{-- Form édition --}}
                        <tr x-show="editingId === {{ $sub->id }}" x-cloak class="bg-blue-50/40">
                            <td colspan="5" class="px-5 py-3">
                                <form method="POST" action="{{ route('admin.rh.sous-disciplines.update', $sub) }}"
                                      class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
                                    @csrf @method('PATCH')
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Code</label>
                                        <input type="text" name="code" value="{{ $sub->code }}" required
                                               class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-sm font-mono">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nom</label>
                                        <input type="text" name="nom" value="{{ $sub->nom }}" required
                                               class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Poids</label>
                                        <input type="number" name="poids_dans_parent" value="{{ $sub->poids_dans_parent }}" step="0.5" min="0.5" max="10" required
                                               class="w-full text-center rounded-lg border border-gray-200 px-2 py-1.5 text-sm font-bold">
                                    </div>
                                    <div class="flex gap-2 items-end">
                                        <input type="number" name="ordre" value="{{ $sub->ordre }}" min="0"
                                               placeholder="Ordre"
                                               class="w-16 text-center rounded-lg border border-gray-200 px-2 py-1.5 text-sm">
                                        <button class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg">OK</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif

                {{-- Formulaire d'ajout --}}
                <form method="POST" action="{{ route('admin.rh.sous-disciplines.store') }}"
                      class="bg-white border-t border-gray-200 p-4 grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
                    @csrf
                    <input type="hidden" name="parent_matiere_id" value="{{ $matiere->id }}">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Code *</label>
                        <input type="text" name="code" placeholder="Ex: CF" required maxlength="20"
                               class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-sm font-mono uppercase">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nom *</label>
                        <input type="text" name="nom" placeholder="Ex: Composition française" required
                               class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Poids *</label>
                        <input type="number" name="poids_dans_parent" value="1" step="0.5" min="0.5" max="10" required
                               class="w-full text-center rounded-lg border border-gray-200 px-2 py-1.5 text-sm font-bold">
                    </div>
                    <div class="flex gap-2">
                        <input type="number" name="ordre" placeholder="Ordre" min="0"
                               class="w-16 text-center rounded-lg border border-gray-200 px-2 py-1.5 text-sm">
                        <button class="bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg">
                            + Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-12 text-center text-gray-400">
            Aucune matière trouvée dans cet établissement.
        </div>
        @endforelse
    </div>
</div>
@endsection
