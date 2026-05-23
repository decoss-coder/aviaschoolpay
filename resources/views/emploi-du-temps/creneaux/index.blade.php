@extends('layouts.app')

@section('title', 'Créneaux horaires')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8" x-data="creneauxManager()">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-display text-2xl font-extrabold text-gray-900">Créneaux horaires</h1>
            <p class="text-sm text-gray-500 mt-1">Paramétrez les plages horaires de votre école. L'IA de génération les utilise automatiquement.</p>
        </div>
        <a href="{{ route('emploi-du-temps.parametres.edit') }}"
           class="text-sm text-brand-600 hover:text-brand-700 font-medium flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Paramètres EDT
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm font-medium">
            {{ session('error') }}
        </div>
    @endif

    {{-- Liste actuelle --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 mb-6 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Créneaux configurés</h2>
            <span class="text-xs text-gray-400">Glisser-déposer pour réordonner</span>
        </div>

        <ul id="sortable-list" class="divide-y divide-gray-50">
            @forelse($creneaux as $c)
            <li class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50/60 transition group" data-id="{{ $c->id }}">
                {{-- Drag handle --}}
                <div class="cursor-grab text-gray-300 hover:text-gray-500 flex-shrink-0">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
                </div>

                {{-- Type badge --}}
                @php
                    $badgeColor = match($c->type) {
                        'recreation'    => 'bg-orange-100 text-orange-700',
                        'pause_dejeuner'=> 'bg-amber-100 text-amber-700',
                        default         => 'bg-brand-100 text-brand-700',
                    };
                    $typeLabel = match($c->type) {
                        'recreation'    => 'Récréation',
                        'pause_dejeuner'=> 'Pause déjeuner',
                        default         => 'Cours',
                    };
                @endphp
                <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $badgeColor }} flex-shrink-0">{{ $typeLabel }}</span>

                {{-- Infos --}}
                <div class="flex-1 min-w-0">
                    <span class="font-semibold text-gray-800 text-sm">{{ $c->libelle }}</span>
                </div>
                <span class="text-sm text-gray-500 font-mono tabular-nums flex-shrink-0">
                    {{ \Carbon\Carbon::parse($c->heure_debut)->format('H:i') }}
                    <span class="text-gray-300 mx-1">→</span>
                    {{ \Carbon\Carbon::parse($c->heure_fin)->format('H:i') }}
                </span>

                {{-- Actions --}}
                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition flex-shrink-0">
                    <button @click="openEdit({{ $c }})"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <form method="POST" action="{{ route('emploi-du-temps.creneaux.destroy', $c) }}"
                          onsubmit="return confirm('Supprimer ce créneau ?')">
                        @csrf @method('DELETE')
                        <button class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
            </li>
            @empty
            <li class="px-5 py-8 text-center text-sm text-gray-400">
                Aucun créneau configuré. Ajoutez-en ci-dessous.
            </li>
            @endforelse
        </ul>
    </div>

    {{-- Formulaire d'ajout --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-5">
        <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide mb-4">Ajouter un créneau</h2>
        <form method="POST" action="{{ route('emploi-du-temps.creneaux.store') }}" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @csrf
            <div class="sm:col-span-1">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Libellé</label>
                <input type="text" name="libelle" placeholder="C1, Récré…" required
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-300 focus:border-brand-400 outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Début</label>
                <input type="time" name="heure_debut" required
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-300 focus:border-brand-400 outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Fin</label>
                <input type="time" name="heure_fin" required
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-300 focus:border-brand-400 outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Type</label>
                <select name="type" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-300 focus:border-brand-400 outline-none">
                    <option value="cours">Cours</option>
                    <option value="recreation">Récréation</option>
                    <option value="pause_dejeuner">Pause déjeuner</option>
                </select>
            </div>
            <div class="sm:col-span-4 flex justify-end">
                <button type="submit"
                        class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold px-5 py-2 rounded-xl shadow-brand-glow transition">
                    + Ajouter
                </button>
            </div>
        </form>
    </div>

    {{-- Modal édition --}}
    <div x-show="editOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm"
         @click.self="editOpen = false">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md mx-4">
            <h3 class="font-bold text-gray-900 mb-4">Modifier le créneau</h3>
            <form method="POST" :action="editUrl" class="space-y-3">
                @csrf @method('PUT')
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Libellé</label>
                    <input type="text" name="libelle" x-model="editForm.libelle" required
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-300 outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Début</label>
                        <input type="time" name="heure_debut" x-model="editForm.heure_debut" required
                               class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-300 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Fin</label>
                        <input type="time" name="heure_fin" x-model="editForm.heure_fin" required
                               class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-300 outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Type</label>
                    <select name="type" x-model="editForm.type"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-300 outline-none">
                        <option value="cours">Cours</option>
                        <option value="recreation">Récréation</option>
                        <option value="pause_dejeuner">Pause déjeuner</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-2 justify-end">
                    <button type="button" @click="editOpen = false"
                            class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">
                        Annuler
                    </button>
                    <button type="submit"
                            class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold px-5 py-2 rounded-xl shadow-brand-glow transition">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
function creneauxManager() {
    return {
        editOpen: false,
        editUrl: '',
        editForm: { libelle: '', heure_debut: '', heure_fin: '', type: 'cours' },

        init() {
            const el = document.getElementById('sortable-list');
            if (!el) return;
            Sortable.create(el, {
                handle: '[data-id]',
                animation: 150,
                onEnd: () => {
                    const ids = [...el.querySelectorAll('[data-id]')].map(li => li.dataset.id);
                    fetch('{{ route('emploi-du-temps.creneaux.reorder') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ ids }),
                    });
                }
            });
        },

        openEdit(c) {
            this.editForm = {
                libelle:    c.libelle,
                heure_debut: c.heure_debut ? c.heure_debut.substring(0,5) : '',
                heure_fin:   c.heure_fin   ? c.heure_fin.substring(0,5)   : '',
                type:        c.type,
            };
            this.editUrl = `/emploi-du-temps/creneaux/${c.id}`;
            this.editOpen = true;
        }
    }
}
</script>
@endpush
