@extends('layouts.app')
@section('title', 'Disciplines')

@php
    // Groupes prédéfinis (cohérents avec la table BILAN du bulletin officiel ivoirien)
    $groupesDisponibles = [
        'Littéraire'   => '📖 Littéraire (Français, Philo, Langues, Histoire-Géo…)',
        'Scientifique' => '🧪 Scientifique (Maths, Phys-Chimie, SVT…)',
        'Artistique'   => '🎨 Artistique (Arts, Musique…)',
        'Sportive'     => '🏃 Sportive (EPS…)',
        'Autres'       => '📋 Autres (ECM, Conduite…)',
    ];

    $formatHeuresHebdo = static function ($value): string {
        if ($value === null || $value === '') {
            return '—';
        }

        $formatted = rtrim(rtrim(number_format((float) $value, 2, ',', ' '), '0'), ',');

        return $formatted.' h';
    };
@endphp

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-5" x-data="{ editingId: null, showCreate: false }">

    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('admin.rh.dashboard') }}" class="hover:text-brand-600">RH</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">Disciplines</span>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-start justify-between gap-3 flex-wrap">
        <div>
            <h1 class="font-display text-2xl font-extrabold text-gray-900">Disciplines (matières)</h1>
            <p class="text-sm text-gray-500 mt-1">
                Définissez les disciplines enseignées (FR, MATH, ANG, HG, SVT, PC…). Chaque discipline a un <b>coefficient par défaut</b>, des <b>heures hebdomadaires par cycle</b> et peut avoir des <b>sous-disciplines</b> (ex : Français → CF/OG/EO).
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.rh.sous-disciplines.index') }}"
               class="bg-purple-100 hover:bg-purple-200 text-purple-700 text-sm font-bold px-4 py-2 rounded-xl flex items-center gap-1">
                🔀 Sous-disciplines
            </a>
            <button @click="showCreate = !showCreate"
                    class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold px-5 py-2 rounded-xl flex items-center gap-2">
                <span x-show="!showCreate">+ Nouvelle discipline</span>
                <span x-show="showCreate">▲ Fermer</span>
            </button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-4">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Total disciplines</p>
            <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-green-100 p-4">
            <p class="text-xs font-bold text-green-600 uppercase tracking-wide">Actives</p>
            <p class="text-2xl font-extrabold text-green-700 mt-1">{{ $stats['actives'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-purple-100 p-4">
            <p class="text-xs font-bold text-purple-600 uppercase tracking-wide">Avec sous-disciplines</p>
            <p class="text-2xl font-extrabold text-purple-700 mt-1">{{ $stats['avec_sous'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-blue-100 p-4">
            <p class="text-xs font-bold text-blue-600 uppercase tracking-wide">Affectées à des classes</p>
            <p class="text-2xl font-extrabold text-blue-700 mt-1">{{ $stats['affectees'] }}</p>
        </div>
    </div>

    {{-- Formulaire création --}}
    <div x-show="showCreate" x-cloak x-transition
         class="bg-white rounded-2xl shadow-card border-2 border-brand-200 p-5">
        <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide mb-3">+ Nouvelle discipline</h2>
        <form method="POST" action="{{ route('admin.rh.disciplines.store') }}"
              class="grid grid-cols-1 md:grid-cols-8 gap-3 items-end">
            @csrf
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Code *</label>
                <input type="text" name="code" placeholder="Ex: FR" required maxlength="20"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm font-mono uppercase">
            </div>
            <div class="md:col-span-2">
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nom *</label>
                <input type="text" name="nom" placeholder="Ex: Français" required maxlength="100"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Coef *</label>
                <input type="number" name="coefficient_defaut" value="1" step="0.5" min="0.5" max="20" required
                       class="w-full text-center rounded-lg border border-gray-200 px-3 py-2 text-sm font-bold">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">H 1er cycle</label>
                <input type="number" name="heures_hebdo_premier_cycle" step="0.5" min="0" max="60" placeholder="Ex : 5"
                       class="w-full text-center rounded-lg border border-gray-200 px-3 py-2 text-sm font-bold">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">H 2nd cycle</label>
                <input type="number" name="heures_hebdo_second_cycle" step="0.5" min="0" max="60" placeholder="Ex : 4"
                       class="w-full text-center rounded-lg border border-gray-200 px-3 py-2 text-sm font-bold">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Groupe</label>
                <select name="groupe"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm bg-white">
                    <option value="">— Non classé —</option>
                    @foreach($groupesDisponibles as $val => $lbl)
                        <option value="{{ $val }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Ordre</label>
                <input type="number" name="ordre" value="0" min="0"
                       class="w-full text-center rounded-lg border border-gray-200 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-8 flex justify-end">
                <button class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold px-6 py-2 rounded-xl">
                    💾 Enregistrer
                </button>
            </div>
        </form>
        <div class="mt-3 px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-700">
            💡 Le <b>groupe</b> détermine le <b>BILAN</b> sur le bulletin officiel. Les <b>heures 1er cycle</b> et <b>2nd cycle</b> servent de volumes horaires de référence pour les affectations et l'emploi du temps.
        </div>
    </div>

    {{-- Liste --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Disciplines de l'établissement</h2>
            <span class="text-xs text-gray-500">{{ $matieres->count() }} discipline(s)</span>
        </div>

        @if($matieres->isEmpty())
            <div class="px-5 py-12 text-center text-sm text-gray-400">
                Aucune discipline créée. Cliquez sur « + Nouvelle discipline » pour commencer.
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Code</th>
                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Nom</th>
                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Groupe</th>
                        <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase">Coef</th>
                        <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase">H 1er</th>
                        <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase">H 2nd</th>
                        <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase">Sous-disc.</th>
                        <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase">Affect.</th>
                        <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase">Statut</th>
                        <th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($matieres as $matiere)
                    <tr class="hover:bg-gray-50/60 {{ $matiere->active ? '' : 'opacity-60' }}">
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center justify-center min-w-10 px-2 py-1 rounded-lg bg-brand-100 text-brand-700 font-mono font-extrabold text-xs">
                                {{ $matiere->code }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ $matiere->nom }}</td>
                        <td class="px-4 py-3 text-xs">
                            @if($matiere->groupe)
                                @php
                                    $cls = match($matiere->groupe) {
                                        'Littéraire'   => 'bg-blue-100 text-blue-700',
                                        'Scientifique' => 'bg-green-100 text-green-700',
                                        'Artistique'   => 'bg-purple-100 text-purple-700',
                                        'Sportive'     => 'bg-orange-100 text-orange-700',
                                        default        => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <span class="inline-block px-2 py-0.5 rounded-full font-bold {{ $cls }}">{{ $matiere->groupe }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center font-bold">×{{ rtrim(rtrim(number_format($matiere->coefficient_defaut, 2, '.', ''), '0'), '.') }}</td>
                        <td class="px-4 py-3 text-center font-bold text-gray-700">{{ $formatHeuresHebdo($matiere->heures_hebdo_premier_cycle) }}</td>
                        <td class="px-4 py-3 text-center font-bold text-gray-700">{{ $formatHeuresHebdo($matiere->heures_hebdo_second_cycle) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($matiere->sous_count > 0)
                                <a href="{{ route('admin.rh.sous-disciplines.index') }}"
                                   class="inline-flex items-center gap-1 text-xs font-bold bg-purple-100 hover:bg-purple-200 text-purple-700 px-2 py-1 rounded-full">
                                    {{ $matiere->sous_count }} 🔀
                                </a>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($matiere->affectations_count > 0)
                                <span class="text-xs font-bold bg-blue-100 text-blue-700 px-2 py-1 rounded-full">{{ $matiere->affectations_count }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($matiere->active)
                                <span class="text-xs font-bold bg-green-100 text-green-700 px-2 py-1 rounded-full">✓ Active</span>
                            @else
                                <span class="text-xs font-bold bg-gray-200 text-gray-600 px-2 py-1 rounded-full">Désactivée</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button @click="editingId = editingId === {{ $matiere->id }} ? null : {{ $matiere->id }}"
                                    class="text-xs font-bold text-blue-600 hover:text-blue-800 px-2">✏ Éditer</button>
                            <form method="POST" action="{{ route('admin.rh.disciplines.toggle', $matiere) }}" class="inline">
                                @csrf
                                <button class="text-xs font-bold {{ $matiere->active ? 'text-amber-600 hover:text-amber-800' : 'text-green-600 hover:text-green-800' }} px-2">
                                    {{ $matiere->active ? '⏸' : '▶' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.rh.disciplines.destroy', $matiere) }}"
                                  onsubmit="return confirm('Supprimer/désactiver cette discipline ? Les données existantes seront préservées.');" class="inline">
                                @csrf @method('DELETE')
                                <button class="text-xs font-bold text-red-600 hover:text-red-800 px-2">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    {{-- Form édition --}}
                    <tr x-show="editingId === {{ $matiere->id }}" x-cloak class="bg-blue-50/40">
                        <td colspan="10" class="px-4 py-4">
                            <form method="POST" action="{{ route('admin.rh.disciplines.update', $matiere) }}"
                                  class="grid grid-cols-1 md:grid-cols-8 gap-3 items-end">
                                @csrf @method('PATCH')
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Code</label>
                                    <input type="text" name="code" value="{{ $matiere->code }}" required
                                           class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-sm font-mono">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nom</label>
                                    <input type="text" name="nom" value="{{ $matiere->nom }}" required
                                           class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Coef</label>
                                    <input type="number" name="coefficient_defaut" value="{{ $matiere->coefficient_defaut }}" step="0.5" min="0.5" max="20" required
                                           class="w-full text-center rounded-lg border border-gray-200 px-2 py-1.5 text-sm font-bold">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">H 1er</label>
                                    <input type="number" name="heures_hebdo_premier_cycle" value="{{ $matiere->heures_hebdo_premier_cycle }}" step="0.5" min="0" max="60" placeholder="Ex : 5"
                                           class="w-full text-center rounded-lg border border-gray-200 px-2 py-1.5 text-sm font-bold">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">H 2nd</label>
                                    <input type="number" name="heures_hebdo_second_cycle" value="{{ $matiere->heures_hebdo_second_cycle }}" step="0.5" min="0" max="60" placeholder="Ex : 4"
                                           class="w-full text-center rounded-lg border border-gray-200 px-2 py-1.5 text-sm font-bold">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Groupe</label>
                                    <select name="groupe"
                                            class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-sm bg-white">
                                        <option value="">— Non classé —</option>
                                        @foreach($groupesDisponibles as $val => $lbl)
                                            <option value="{{ $val }}" {{ $matiere->groupe === $val ? 'selected' : '' }}>{{ $val }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex gap-2 items-end">
                                    <input type="number" name="ordre" value="{{ $matiere->ordre }}" min="0"
                                           placeholder="Ordre"
                                           class="w-16 text-center rounded-lg border border-gray-200 px-2 py-1.5 text-sm">
                                    <label class="flex items-center gap-1 text-xs">
                                        <input type="checkbox" name="active" value="1" {{ $matiere->active ? 'checked' : '' }}
                                               class="w-4 h-4 rounded border-gray-300">
                                        <span>Active</span>
                                    </label>
                                    <button class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg">OK</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Aide --}}
    <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-xl px-4 py-3 text-sm">
        <p class="font-bold mb-1">💡 Comment ça marche</p>
        <ol class="list-decimal pl-5 space-y-0.5">
            <li>Créez les disciplines de votre établissement (Français, Math, Anglais...) avec un <b>coefficient par défaut</b>.</li>
            <li>Les heures du <b>1er cycle</b> et du <b>2nd cycle</b> indiquent les volumes horaires de référence. Les réglages plus fins par niveau restent possibles via le paramétrage niveau/matière.</li>
            <li>Pour les matières composites (Français au 1ᵉʳ cycle), ajoutez des <a href="{{ route('admin.rh.sous-disciplines.index') }}" class="underline font-bold">sous-disciplines</a> (CF, OG, EO).</li>
            <li>Affectez ensuite chaque discipline à un enseignant et à une classe dans <a href="{{ route('admin.rh.affectations.index') }}" class="underline font-bold">Affectations</a>.</li>
            <li>Les enseignants saisissent les notes/moyennes ; le système calcule la moyenne générale en pondérant par <b>coef matière × coef trimestre</b>.</li>
        </ol>
    </div>
</div>
@endsection
