@extends('layouts.app')
@section('title', 'Saisie des moyennes · ' . $classe->nom)

@php
    $hasSd = $sousDisciplines->isNotEmpty();
    $sdConfig = $hasSd
        ? $sousDisciplines->map(fn($sd) => [
            'id'    => $sd->id,
            'code'  => $sd->code,
            'nom'   => $sd->nom,
            'poids' => (float) ($sd->poids_dans_parent ?? 1),
          ])->values()->all()
        : [];
@endphp

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4"
     x-data="gradesGrid({{ Js::from(['hasSd' => $hasSd, 'sdConfig' => $sdConfig]) }})">

    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('mon-espace.classes') }}" class="hover:text-brand-600">Mes classes</a>
        <span>/</span>
        <a href="{{ route('mon-espace.evaluations', $classe) }}" class="hover:text-brand-600">{{ $classe->nom }}</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">Moyennes</span>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif

    <div class="bg-gradient-to-r {{ $hasSd ? 'from-purple-600 to-purple-700' : 'from-indigo-600 to-indigo-700' }} rounded-2xl shadow-lg text-white px-6 py-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-xl font-extrabold">Saisie directe des moyennes</h1>
            <p class="text-xs {{ $hasSd ? 'text-purple-100' : 'text-indigo-100' }} mt-0.5">
                {{ $classe->nom }} · {{ $eleves->count() }} élèves
                @if($hasSd)
                    · <b>{{ $matiere?->nom }}</b> ({{ $sousDisciplines->count() }} sous-disciplines)
                @endif
            </p>
        </div>

        <form method="GET" class="flex items-center gap-2">
            <select name="matiere_id" onchange="this.form.submit()"
                    class="rounded-lg px-3 py-2 text-sm font-semibold text-gray-800 bg-white/95">
                @foreach($matieres as $m)
                    @continue(!$m)
                    <option value="{{ $m->id }}" {{ (int) $matiereId === (int) $m->id ? 'selected' : '' }}>
                        {{ $m->nom }} ({{ $m->code }})
                    </option>
                @endforeach
            </select>
            <select name="trimestre_id" onchange="this.form.submit()"
                    class="rounded-lg px-3 py-2 text-sm font-semibold text-gray-800 bg-white/95">
                @foreach($trimestres as $t)
                    <option value="{{ $t->id }}" {{ (int) $trimestreId === (int) $t->id ? 'selected' : '' }}>
                        {{ $t->libelle }} @if($t->en_cours) ★ @endif
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    @if($hasSd)
    <div class="bg-purple-50 border border-purple-200 rounded-2xl px-5 py-3 flex items-start gap-3">
        <svg class="w-5 h-5 text-purple-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="text-xs text-purple-800">
            <b>Matière à sous-disciplines détectée.</b>
            Saisissez les moyennes par sous-discipline pour chaque élève.
            La moyenne <b>{{ $matiere?->code }}</b> est calculée automatiquement selon les poids :
            <span class="inline-flex gap-2 mt-1 flex-wrap">
                @foreach($sousDisciplines as $sd)
                    <span class="bg-purple-100 text-purple-700 font-bold px-2 py-0.5 rounded-full">
                        {{ $sd->code }} × {{ $sd->poids_dans_parent ?? 1 }}
                    </span>
                @endforeach
            </span>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border {{ $hasSd ? 'border-purple-100' : 'border-brand-100' }} p-3 lg:col-span-1">
            <p class="text-[10px] font-bold {{ $hasSd ? 'text-purple-600' : 'text-brand-600' }} uppercase">Moyenne classe</p>
            <p class="text-2xl font-extrabold {{ $hasSd ? 'text-purple-700' : 'text-brand-700' }} mt-1" x-text="moyenneClasse()"></p>
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
            <p class="font-bold mb-1 uppercase text-[10px]">⌨️ Raccourcis clavier</p>
            <p><kbd class="bg-white px-1 rounded">↑↓</kbd> ligne · <kbd class="bg-white px-1 rounded">Tab</kbd> cellule suivante · <kbd class="bg-white px-1 rounded">Entrée</kbd> ligne suivante</p>
        </div>
    </div>

    <form method="POST" action="{{ route('mon-espace.moyennes.store', $classe) }}" x-ref="gradesForm">
        @csrf
        <input type="hidden" name="matiere_id" value="{{ $matiereId }}">
        <input type="hidden" name="trimestre_id" value="{{ $trimestreId }}">

        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
                <div>
                    <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">
                        {{ $matiere?->nom ?? '—' }} · {{ $trimestres->firstWhere('id', $trimestreId)?->libelle }}
                    </h2>
                    <p class="text-[10px] text-gray-400 mt-0.5">Coef. matière : {{ $matiere?->coefficient_defaut ?? 1 }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-500" x-show="dirty">
                        <span class="inline-block w-2 h-2 bg-amber-500 rounded-full animate-pulse mr-1"></span>
                        Modifications non enregistrées
                    </span>
                    <button type="submit"
                            class="{{ $hasSd ? 'bg-purple-600 hover:bg-purple-700' : 'bg-indigo-600 hover:bg-indigo-700' }} text-white text-sm font-bold px-5 py-2 rounded-xl flex items-center gap-2 shadow-lg">
                        💾 Enregistrer & Publier
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="grades-table">
                    <thead>
                        <tr class="bg-gray-50 border-b-2 border-gray-200">
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase w-10">N°</th>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Matricule</th>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Nom et prénom</th>
                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase w-8">S</th>

                            @if($hasSd)
                                @foreach($sousDisciplines as $sd)
                                <th class="px-2 py-2 text-center text-xs font-bold text-purple-700 bg-purple-50 border-l border-purple-100 whitespace-nowrap">
                                    <span class="block text-[10px] text-purple-400">× {{ $sd->poids_dans_parent ?? 1 }}</span>
                                    {{ $sd->code }} /20
                                </th>
                                @endforeach
                                <th class="px-3 py-2 text-center text-xs font-bold text-purple-900 bg-purple-100 border-l-2 border-purple-300 whitespace-nowrap">
                                    Moy. {{ $matiere?->code }}<br>
                                    <span class="text-[9px] font-normal text-purple-500">(calculée)</span>
                                </th>
                            @else
                                <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase w-24">Moyenne /20</th>
                            @endif

                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Appréciation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($eleves as $i => $eleve)
                        <tr class="group hover:bg-indigo-50/30 transition">
                            <td class="px-3 py-2 text-xs font-mono text-gray-400">{{ $i + 1 }}</td>
                            <td class="px-3 py-2 text-xs font-mono font-bold text-gray-700">{{ $eleve->matricule_desps ?: $eleve->matricule_interne }}</td>
                            <td class="px-3 py-2">
                                <p class="font-semibold text-gray-800 truncate"><span class="uppercase">{{ $eleve->nom }}</span> {{ $eleve->prenom }}</p>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="text-xs font-bold {{ $eleve->sexe === 'M' ? 'text-blue-600' : 'text-pink-600' }}">{{ $eleve->sexe }}</span>
                            </td>

                            @if($hasSd)
                                @foreach($sousDisciplines as $si => $sd)
                                @php $sdMoy = optional($moyennesSd->get($sd->id))->get($eleve->id); @endphp
                                <td class="px-2 py-1.5 text-center border-l border-purple-100 {{ $si === 0 ? 'border-l-2 border-purple-200' : '' }}">
                                    <input type="text"
                                           name="sd_moyennes[{{ $sd->id }}][{{ $eleve->id }}]"
                                           value="{{ $sdMoy?->moyenne }}"
                                           placeholder="—"
                                           maxlength="5"
                                           data-grid-cell="sd-grade"
                                           data-sd-id="{{ $sd->id }}"
                                           data-eleve-id="{{ $eleve->id }}"
                                           data-row="{{ $i }}"
                                           data-col="{{ $si }}"
                                           inputmode="decimal"
                                           @input="onSdInput($event, {{ $eleve->id }})"
                                           @keydown="onKey($event)"
                                           class="w-20 text-center font-extrabold text-lg rounded-lg border-2 border-gray-200 px-2 py-1.5 focus:ring-4 focus:ring-purple-200 focus:border-purple-500 outline-none transition">
                                </td>
                                @endforeach

                                <td class="px-3 py-1.5 text-center bg-purple-50 border-l-2 border-purple-200">
                                    <span class="text-lg font-extrabold"
                                          data-moy-parent="{{ $eleve->id }}"
                                          x-text="computeParentMoy({{ $eleve->id }})"
                                          :class="parentMoyClass({{ $eleve->id }})">—</span>
                                </td>
                            @else
                                @php $moy = $moyennes->get($eleve->id); @endphp
                                <td class="px-2 py-1.5 text-center">
                                    <input type="text"
                                           name="moyennes[{{ $eleve->id }}]"
                                           value="{{ $moy?->moyenne }}"
                                           placeholder="—"
                                           maxlength="5"
                                           data-grid-cell="grade"
                                           data-row="{{ $i }}"
                                           inputmode="decimal"
                                           @input="onGradeInput($event)"
                                           @keydown="onKey($event)"
                                           class="w-20 text-center font-extrabold text-lg rounded-lg border-2 border-gray-200 px-2 py-1.5 focus:ring-4 focus:ring-indigo-200 focus:border-indigo-500 outline-none transition">
                                </td>
                            @endif

                            <td class="px-3 py-1.5">
                                <input type="text"
                                       name="appreciations[{{ $eleve->id }}]"
                                       value="{{ ($hasSd ? $moyennesParent : $moyennes)->get($eleve->id)?->appreciation }}"
                                       maxlength="200"
                                       data-grid-cell="appreciation"
                                       data-row="{{ $i }}"
                                       @keydown="onKey($event)"
                                       placeholder="Bien · À encourager · Travail régulier..."
                                       class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs focus:ring-2 focus:ring-indigo-200 outline-none">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50 text-xs text-gray-500 flex items-center justify-between">
                <p>💡 Astuce : utilise <kbd class="bg-white border border-gray-300 px-1.5 py-0.5 rounded text-[10px]">,</kbd> ou <kbd class="bg-white border border-gray-300 px-1.5 py-0.5 rounded text-[10px]">.</kbd> pour les décimales. Vide = pas de note.</p>
                @if($hasSd)
                    <span class="font-mono text-gray-400" x-text="notesValides() + '/{{ $eleves->count() }} élèves complets'"></span>
                @else
                    <span class="font-mono text-gray-400" x-text="notesValides() + '/{{ $eleves->count() }} saisies'"></span>
                @endif
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function gradesGrid(cfg) {
    return {
        dirty: false,
        hasSd: cfg.hasSd ?? false,
        sdConfig: cfg.sdConfig ?? [],

        init() {
            this.$nextTick(() => {
                const first = this.$root.querySelector('[data-grid-cell="sd-grade"], [data-grid-cell="grade"]');
                if (first) first.focus();
            });
            window.addEventListener('beforeunload', (e) => {
                if (this.dirty) { e.preventDefault(); e.returnValue = ''; }
            });
        },

        onGradeInput(e) {
            this.dirty = true;
            const v = e.target.value.replace(',', '.');
            if (v !== e.target.value) e.target.value = v;
            e.target.className = this.gradeClass(v);
        },

        onSdInput(e, eleveId) {
            this.dirty = true;
            const v = e.target.value.replace(',', '.');
            if (v !== e.target.value) e.target.value = v;
            e.target.className = this.gradeClass(v);
            this.$nextTick(() => {
                const span = document.querySelector(`[data-moy-parent="${eleveId}"]`);
                if (span) span.textContent = this.computeParentMoy(eleveId);
            });
        },

        computeParentMoy(eleveId) {
            if (!this.hasSd || this.sdConfig.length === 0) return '—';
            let sumPoids = 0, sumMoy = 0;
            this.sdConfig.forEach(sd => {
                const input = document.querySelector(`[data-sd-id="${sd.id}"][data-eleve-id="${eleveId}"]`);
                if (!input) return;
                const v = parseFloat(input.value.replace(',', '.'));
                if (isNaN(v) || v < 0 || v > 20) return;
                sumMoy += v * sd.poids;
                sumPoids += sd.poids;
            });
            if (sumPoids <= 0) return '—';
            return (sumMoy / sumPoids).toFixed(2);
        },

        parentMoyClass(eleveId) {
            const v = parseFloat(this.computeParentMoy(eleveId));
            if (isNaN(v)) return 'text-gray-400';
            if (v >= 14) return 'text-green-700';
            if (v >= 10) return 'text-amber-700';
            return 'text-red-700';
        },

        gradeClass(value) {
            const base = 'w-20 text-center font-extrabold text-lg rounded-lg border-2 px-2 py-1.5 focus:ring-4 outline-none transition';
            const ring = this.hasSd ? 'focus:ring-purple-200 focus:border-purple-500' : 'focus:ring-indigo-200 focus:border-indigo-500';
            const v = parseFloat((value || '').replace(',', '.'));
            if (isNaN(v)) return base + ' ' + ring + ' border-gray-200 bg-white text-gray-400';
            if (v >= 14) return base + ' ' + ring + ' border-green-300 bg-green-50 text-green-700';
            if (v >= 10) return base + ' ' + ring + ' border-amber-300 bg-amber-50 text-amber-700';
            return base + ' ' + ring + ' border-red-300 bg-red-50 text-red-700';
        },

        notesValides() {
            if (this.hasSd) {
                const rows = new Set();
                document.querySelectorAll('[data-grid-cell="sd-grade"]').forEach(input => {
                    const v = parseFloat(input.value.replace(',', '.'));
                    if (!isNaN(v) && v >= 0 && v <= 20) rows.add(input.dataset.eleveId + ':' + input.dataset.sdId);
                });
                return rows.size;
            }
            let n = 0;
            document.querySelectorAll('[data-grid-cell="grade"]').forEach(input => {
                const v = parseFloat(input.value.replace(',', '.'));
                if (!isNaN(v) && v >= 0 && v <= 20) n++;
            });
            return n;
        },

        moyenneClasse() {
            const vals = [];
            const selector = this.hasSd ? '[data-moy-parent]' : '[data-grid-cell="grade"]';
            document.querySelectorAll(selector).forEach(el => {
                const raw = this.hasSd ? el.textContent : el.value;
                const v = parseFloat((raw || '').replace(',', '.'));
                if (!isNaN(v)) vals.push(v);
            });
            if (vals.length === 0) return '—';
            return (vals.reduce((a,b)=>a+b,0) / vals.length).toFixed(2);
        },

        maxNote() {
            const vals = this.collectVals();
            return vals.length ? Math.max(...vals).toFixed(2) : '—';
        },

        minNote() {
            const vals = this.collectVals();
            return vals.length ? Math.min(...vals).toFixed(2) : '—';
        },

        collectVals() {
            const vals = [];
            const selector = this.hasSd ? '[data-moy-parent]' : '[data-grid-cell="grade"]';
            document.querySelectorAll(selector).forEach(el => {
                const raw = this.hasSd ? el.textContent : el.value;
                const v = parseFloat((raw || '').replace(',', '.'));
                if (!isNaN(v)) vals.push(v);
            });
            return vals;
        },

        onKey(e) {
            const inputs = Array.from(document.querySelectorAll('[data-grid-cell]'));
            const idx = inputs.indexOf(e.target);
            if (idx < 0) return;
            if (e.key === 'Enter') {
                e.preventDefault();
                const next = inputs[idx + 1];
                if (next) next.focus();
            }
        }
    }
}
</script>
@endpush
