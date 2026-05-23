@extends('layouts.app')
@section('title', 'Saisie des notes · ' . $evaluation->titre)

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4" x-data="notesGrid()">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('mon-espace.classes') }}" class="hover:text-brand-600">Mes classes</a>
        <span>/</span>
        <a href="{{ route('mon-espace.evaluations', $evaluation->classe) }}" class="hover:text-brand-600">{{ $evaluation->classe->nom }}</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold truncate">{{ $evaluation->titre }}</span>
    </div>

    {{-- Header riche --}}
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl shadow-lg text-white px-6 py-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="font-display text-xl font-extrabold">{{ $evaluation->titre }}</h1>
                <p class="text-xs text-blue-100 mt-1">{{ $evaluation->classe->nom }} · {{ $eleves->count() }} élèves</p>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-xs">
                <div class="bg-white/15 backdrop-blur rounded-lg px-3 py-2">
                    <p class="text-blue-100 text-[9px] uppercase font-bold">Matière</p>
                    <p class="font-bold mt-0.5">{{ $evaluation->matiere?->code ?? '—' }}</p>
                </div>
                <div class="bg-white/15 backdrop-blur rounded-lg px-3 py-2">
                    <p class="text-blue-100 text-[9px] uppercase font-bold">Type</p>
                    <p class="font-bold mt-0.5">{{ $evaluation->typeEvaluation?->nom ?? '—' }}</p>
                </div>
                <div class="bg-white/15 backdrop-blur rounded-lg px-3 py-2">
                    <p class="text-blue-100 text-[9px] uppercase font-bold">Barème</p>
                    <p class="font-bold mt-0.5">/ {{ $evaluation->note_sur }}</p>
                </div>
                <div class="bg-white/15 backdrop-blur rounded-lg px-3 py-2">
                    <p class="text-blue-100 text-[9px] uppercase font-bold">Coef.</p>
                    <p class="font-bold mt-0.5">{{ $evaluation->coefficient }}</p>
                </div>
                <div class="bg-white/15 backdrop-blur rounded-lg px-3 py-2">
                    <p class="text-blue-100 text-[9px] uppercase font-bold">Date</p>
                    <p class="font-bold mt-0.5">{{ \Carbon\Carbon::parse($evaluation->date_evaluation)->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats + raccourcis --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border border-blue-100 p-3">
            <p class="text-[10px] font-bold text-blue-600 uppercase">Moyenne classe</p>
            <p class="text-2xl font-extrabold text-blue-700 mt-1" x-text="moyenne()"></p>
        </div>
        <div class="bg-white rounded-xl border border-green-100 p-3">
            <p class="text-[10px] font-bold text-green-600 uppercase">Max</p>
            <p class="text-2xl font-extrabold text-green-700 mt-1" x-text="maxNote()"></p>
        </div>
        <div class="bg-white rounded-xl border border-red-100 p-3">
            <p class="text-[10px] font-bold text-red-600 uppercase">Min</p>
            <p class="text-2xl font-extrabold text-red-700 mt-1" x-text="minNote()"></p>
        </div>
        <div class="bg-amber-50 rounded-xl border border-amber-200 p-3 text-xs text-amber-800">
            <p class="font-bold mb-1 uppercase text-[10px]">⌨️ Navigation</p>
            <p><kbd class="bg-white px-1 rounded">↑↓</kbd> · <kbd class="bg-white px-1 rounded">Entrée</kbd> ligne suivante · <kbd class="bg-white px-1 rounded">A</kbd> absent · <kbd class="bg-white px-1 rounded">D</kbd> dispensé</p>
        </div>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('mon-espace.notes.store', $evaluation) }}">
        @csrf

        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
                <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Saisie des notes</h2>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-500" x-show="dirty">
                        <span class="inline-block w-2 h-2 bg-amber-500 rounded-full animate-pulse mr-1"></span>Modifications non enregistrées
                    </span>
                    <a href="{{ route('mon-espace.evaluations', $evaluation->classe) }}"
                       class="text-xs font-semibold text-gray-500 px-3 py-1.5">Annuler</a>
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-5 py-2 rounded-xl flex items-center gap-2 shadow-lg">
                        💾 Enregistrer
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b-2 border-gray-200">
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase w-10">N°</th>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Matricule</th>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Nom et prénom</th>
                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase w-24">Note / {{ $evaluation->note_sur }}</th>
                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase w-16">/20</th>
                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase w-16">Abs</th>
                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase w-16">Disp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($eleves as $i => $eleve)
                        @php
                            $note   = $notes[$eleve->id] ?? null;
                            $absent = $absents->has($eleve->id);
                            $dispense = $dispenses->has($eleve->id);
                        @endphp
                        <tr class="hover:bg-blue-50/30 transition"
                            x-data="{
                                absent: {{ $absent ? 'true' : 'false' }},
                                dispense: {{ $dispense ? 'true' : 'false' }},
                                note: '{{ $note ?? '' }}'
                            }">
                            <td class="px-3 py-2 text-xs font-mono text-gray-400">{{ $i + 1 }}</td>
                            <td class="px-3 py-2 text-xs font-mono font-bold">{{ $eleve->matricule_desps ?: $eleve->matricule_interne }}</td>
                            <td class="px-3 py-2">
                                <p class="font-semibold text-gray-800"><span class="uppercase">{{ $eleve->nom }}</span> {{ $eleve->prenom }}</p>
                            </td>
                            <td class="px-2 py-1.5 text-center">
                                <input type="text"
                                       name="notes[{{ $eleve->id }}]"
                                       x-model="note"
                                       :disabled="absent || dispense"
                                       maxlength="6"
                                       data-grid-cell="note"
                                       data-row="{{ $i }}"
                                       data-bareme="{{ $evaluation->note_sur }}"
                                       inputmode="decimal"
                                       @input="onInput($event)"
                                       @keydown="onKey($event, { absentRef: $refs.abs{{ $eleve->id }}, dispenseRef: $refs.disp{{ $eleve->id }} })"
                                       :class="noteClass(note, {{ $evaluation->note_sur }}, absent || dispense)"
                                       class="w-20 text-center font-extrabold text-lg rounded-lg border-2 px-2 py-1.5 outline-none transition disabled:bg-gray-100 disabled:text-gray-300 disabled:cursor-not-allowed">
                            </td>
                            <td class="px-2 py-1.5 text-center">
                                <span class="text-xs font-bold text-gray-500"
                                      x-text="noteSur20(note, {{ $evaluation->note_sur }})"></span>
                            </td>
                            <td class="px-2 py-1.5 text-center">
                                <input type="checkbox" name="absents[]" value="{{ $eleve->id }}"
                                       x-ref="abs{{ $eleve->id }}"
                                       x-model="absent"
                                       @change="if(absent) { dispense = false; note = ''; } dirty = true"
                                       class="w-4 h-4 text-red-500 rounded border-gray-300 focus:ring-red-300">
                            </td>
                            <td class="px-2 py-1.5 text-center">
                                <input type="checkbox" name="dispenses[]" value="{{ $eleve->id }}"
                                       x-ref="disp{{ $eleve->id }}"
                                       x-model="dispense"
                                       @change="if(dispense) { absent = false; note = ''; } dirty = true"
                                       class="w-4 h-4 text-amber-500 rounded border-gray-300 focus:ring-amber-300">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50 text-xs text-gray-500 flex items-center justify-between">
                <p>💡 <kbd class="bg-white border px-1.5 py-0.5 rounded text-[10px]">A</kbd> absent · <kbd class="bg-white border px-1.5 py-0.5 rounded text-[10px]">D</kbd> dispensé · <kbd class="bg-white border px-1.5 py-0.5 rounded text-[10px]">,</kbd>/<kbd class="bg-white border px-1.5 py-0.5 rounded text-[10px]">.</kbd> décimales</p>
                <span class="font-mono text-gray-400" x-text="`${notesValides()}/${{{ $eleves->count() }}} saisies`"></span>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function notesGrid() {
    return {
        dirty: false,

        init() {
            this.$nextTick(() => document.querySelector('[data-grid-cell="note"]:not([disabled])')?.focus());
            window.addEventListener('beforeunload', (e) => {
                if (this.dirty) { e.preventDefault(); e.returnValue = ''; }
            });
        },

        onInput(e) {
            this.dirty = true;
            const v = e.target.value.replace(',', '.');
            if (v !== e.target.value) e.target.value = v;
        },

        noteClass(value, bareme, disabled) {
            const base = 'w-20 text-center font-extrabold text-lg rounded-lg border-2 px-2 py-1.5 outline-none transition';
            if (disabled) return base + ' bg-gray-100 text-gray-300 border-gray-200';
            const v = parseFloat(String(value || '').replace(',','.'));
            if (isNaN(v)) return base + ' border-gray-200 bg-white text-gray-400 focus:ring-4 focus:ring-blue-200 focus:border-blue-500';
            const n20 = (v / parseFloat(bareme || 20)) * 20;
            if (n20 >= 14) return base + ' border-green-300 bg-green-50 text-green-700 focus:ring-4 focus:ring-green-200';
            if (n20 >= 10) return base + ' border-amber-300 bg-amber-50 text-amber-700 focus:ring-4 focus:ring-amber-200';
            return            base + ' border-red-300   bg-red-50   text-red-700   focus:ring-4 focus:ring-red-200';
        },

        noteSur20(value, bareme) {
            const v = parseFloat(String(value || '').replace(',','.'));
            if (isNaN(v)) return '—';
            return ((v / parseFloat(bareme || 20)) * 20).toFixed(2);
        },

        onKey(e, { absentRef, dispenseRef }) {
            const cell = e.target;
            const row = parseInt(cell.dataset.row);
            const max = document.querySelectorAll('[data-grid-cell="note"]').length;

            if (cell.value === '') {
                if (e.key.toLowerCase() === 'a') { e.preventDefault(); absentRef?.click(); return; }
                if (e.key.toLowerCase() === 'd') { e.preventDefault(); dispenseRef?.click(); return; }
            }

            if (e.key === 'Enter' || e.key === 'ArrowDown') {
                e.preventDefault(); this.focusRow(row + 1, max); return;
            }
            if (e.key === 'ArrowUp') {
                e.preventDefault(); this.focusRow(row - 1, max); return;
            }
            if (e.key === 'Escape') { cell.blur(); }
        },

        focusRow(row, max) {
            if (row < 0 || row >= max) return;
            const el = document.querySelector(`[data-grid-cell="note"][data-row="${row}"]`);
            if (el && !el.disabled) { el.focus(); el.select?.(); }
            else if (el) this.focusRow(row + 1, max); // skip disabled
        },

        allNotes() {
            return Array.from(document.querySelectorAll('[data-grid-cell="note"]'))
                .filter(i => !i.disabled)
                .map(i => parseFloat((i.value || '').replace(',', '.')))
                .filter(v => !isNaN(v));
        },
        notesValides() { return this.allNotes().length; },
        moyenne() {
            const ns = this.allNotes();
            return ns.length ? (ns.reduce((a,b)=>a+b,0)/ns.length).toFixed(2) : '—';
        },
        maxNote() { const ns = this.allNotes(); return ns.length ? Math.max(...ns).toFixed(2) : '—'; },
        minNote() { const ns = this.allNotes(); return ns.length ? Math.min(...ns).toFixed(2) : '—'; },
    };
}
</script>
@endpush
@endsection
