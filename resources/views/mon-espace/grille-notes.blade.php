@extends('layouts.app')
@section('title', 'Grille de notes · ' . $classe->nom)

@php
    $alpineConfig = [
        'csrf'             => csrf_token(),
        'classeId'         => $classe->id,
        'matiereId'        => $matiereId,
        'activeMatiereId'  => $activeMatiereId,  // SD active ou matière parent
        'sousDisciplineId' => $sousDisciplineId,
        'trimestreId'      => $trimestreId,
        'typesEval'        => $typesEval->map(fn ($t) => ['id' => $t->id, 'nom' => $t->nom])->values()->all(),
        'isPublished'      => (bool) ($moyennePubliee?->publie),
        'routes'           => [
            'addCol'    => route('mon-espace.grille-notes.add-col', $classe),
            'updateCol' => url('/mon-espace/grille-notes/evaluations'),
            'deleteCol' => url('/mon-espace/grille-notes/evaluations'),
            'saveNote'  => url('/mon-espace/grille-notes/evaluations'),
            'publish'   => route('mon-espace.grille-notes.publish', $classe),
            'unpublish' => route('mon-espace.grille-notes.unpublish', $classe),
        ],
    ];
@endphp

@section('content')
<div class="max-w-[1400px] mx-auto px-4 py-6 space-y-4"
     x-data="grilleNotes({{ Js::from($alpineConfig) }})">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('mon-espace.classes') }}" class="hover:text-brand-600">Mes classes</a>
        <span>/</span>
        <a href="{{ route('mon-espace.evaluations', $classe) }}" class="hover:text-brand-600">{{ $classe->nom }}</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">Grille de notes</span>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif

    {{-- Header + sélecteurs --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-display text-xl font-extrabold text-gray-900">Grille de notes — {{ $classe->nom }}</h1>
            <p class="text-xs text-gray-500 mt-0.5">
                {{ $matiere?->nom }}
                @if($sousDisciplineId)
                    → <span class="text-purple-700 font-bold">{{ $sousDisciplines->firstWhere('id', $sousDisciplineId)?->nom }}</span>
                @endif
                · {{ $trimestre?->libelle }} · {{ $eleves->count() }} élèves · {{ $evaluations->count() }} évaluations
            </p>
        </div>

        <form method="GET" class="flex items-center gap-2 flex-wrap">
            <select name="matiere_id" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold">
                @foreach($matieres as $m)
                    <option value="{{ $m->id }}" {{ $matiereId == $m->id ? 'selected' : '' }}>
                        {{ $m->nom }} ({{ $m->code }})
                    </option>
                @endforeach
            </select>
            <select name="trimestre_id" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold">
                @foreach($trimestres as $t)
                    <option value="{{ $t->id }}" {{ $trimestreId == $t->id ? 'selected' : '' }}>
                        {{ $t->libelle }} @if($t->en_cours) ★ @endif
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- ── Onglets sous-disciplines (si la matière en a) ── --}}
    @if($sousDisciplines->isNotEmpty())
    <div class="bg-purple-50 border border-purple-200 rounded-2xl px-4 py-3">
        <div class="flex items-center gap-2 flex-wrap">
            <div class="flex items-center gap-1.5 mr-2">
                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span class="text-xs font-extrabold text-purple-700 uppercase tracking-wide">Sous-disciplines</span>
            </div>
            @foreach($sousDisciplines as $sd)
            @php $estPublie = isset($sdPubliees[$sd->id]); @endphp
            <a href="{{ request()->fullUrlWithQuery(['sous_discipline_id' => $sd->id]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all
                      {{ $sousDisciplineId == $sd->id
                          ? 'bg-purple-600 text-white shadow-md'
                          : 'bg-white text-purple-700 border border-purple-200 hover:bg-purple-100' }}">
                {{ $sd->code }}
                @if($estPublie)
                    <span class="inline-block w-1.5 h-1.5 rounded-full {{ $sousDisciplineId == $sd->id ? 'bg-green-300' : 'bg-green-500' }}"
                          title="Moyennes publiées"></span>
                @endif
            </a>
            @endforeach

            {{-- Résumé publication --}}
            <span class="ml-auto text-[10px] text-purple-500 font-bold">
                {{ $sdPubliees->count() }}/{{ $sousDisciplines->count() }} publiée(s)
                @if($sdPubliees->count() === $sousDisciplines->count())
                    · <span class="text-green-600">Moy. {{ $matiere->code }} auto-calculée ✓</span>
                @endif
            </span>
        </div>

        <p class="text-[10px] text-purple-400 mt-2">
            Coef. de <b>{{ $matiere->nom }}</b> : {{ $matiere->coefficient_defaut ?? 1 }}
            @if($sousDisciplines->firstWhere('id', $sousDisciplineId))
                · Poids <b>{{ $sousDisciplines->firstWhere('id', $sousDisciplineId)->code }}</b> :
                {{ $sousDisciplines->firstWhere('id', $sousDisciplineId)->poids_dans_parent ?? 1 }}
            @endif
            · La moyenne parent est recalculée automatiquement quand toutes les SD sont publiées.
        </p>
    </div>
    @endif

    {{-- Bandeau état publication --}}
    @if($moyennePubliee?->publie)
        <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-2 text-sm text-green-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <b>Moyennes publiées</b>
                @if($sousDisciplineId)
                    ({{ $sousDisciplines->firstWhere('id', $sousDisciplineId)?->code }})
                @endif
                le {{ $moyennePubliee->date_publication?->format('d/m/Y H:i') }} — visibles par la direction.
            </div>
            <form method="POST" action="{{ route('mon-espace.grille-notes.unpublish', $classe) }}"
                  onsubmit="return confirm('Dépublier ces moyennes ?')">
                @csrf
                <input type="hidden" name="matiere_id" value="{{ $matiereId }}">
                <input type="hidden" name="trimestre_id" value="{{ $trimestreId }}">
                @if($sousDisciplineId)
                    <input type="hidden" name="sous_discipline_id" value="{{ $sousDisciplineId }}">
                @endif
                <button class="text-xs font-bold bg-amber-100 hover:bg-amber-200 text-amber-700 px-3 py-1.5 rounded-lg">
                    🔓 Dépublier
                </button>
            </form>
        </div>
    @else
        <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3">
            <p class="text-sm text-blue-800">
                💡 Saisissez vos notes puis cliquez sur <b>Publier les moyennes</b> quand vous êtes prêt.
                @if($sousDisciplineId)
                    La moyenne <b>{{ $matiere->code }}</b> sera recalculée automatiquement quand toutes les sous-disciplines seront publiées.
                @endif
            </p>
        </div>
    @endif

    {{-- Toolbar --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-2">
            <button @click="showAddCol = !showAddCol" type="button"
                    class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold px-4 py-2 rounded-xl flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Ajouter une colonne
            </button>
            <span class="text-xs text-gray-500 italic">
                💾 Auto-save · <kbd class="bg-gray-100 px-1 rounded text-[10px]">↑↓</kbd> nav · <kbd class="bg-gray-100 px-1 rounded text-[10px]">Tab</kbd> col. suivante · <kbd class="bg-gray-100 px-1 rounded text-[10px]">A</kbd>/<kbd class="bg-gray-100 px-1 rounded text-[10px]">D</kbd> abs/disp
            </span>
        </div>

        <form method="POST" action="{{ route('mon-espace.grille-notes.publish', $classe) }}"
              onsubmit="return confirm('Publier les moyennes{{ $sousDisciplineId ? ' (' . ($sousDisciplines->firstWhere('id', $sousDisciplineId)?->code ?? '') . ')' : '' }} ?\n\nLes notes seront verrouillées.')">
            @csrf
            <input type="hidden" name="matiere_id" value="{{ $matiereId }}">
            <input type="hidden" name="trimestre_id" value="{{ $trimestreId }}">
            @if($sousDisciplineId)
                <input type="hidden" name="sous_discipline_id" value="{{ $sousDisciplineId }}">
            @endif
            <button type="submit"
                    {{ $evaluations->isEmpty() ? 'disabled' : '' }}
                    class="bg-green-600 hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-bold px-5 py-2 rounded-xl shadow-lg flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Publier
                @if($sousDisciplineId)
                    ({{ $sousDisciplines->firstWhere('id', $sousDisciplineId)?->code }})
                @else
                    les moyennes
                @endif
            </button>
        </form>
    </div>

    {{-- Formulaire Ajout Colonne --}}
    <div x-show="showAddCol" x-cloak x-transition
         class="bg-white rounded-2xl shadow-card border-2 border-brand-200 p-4">
        <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide mb-3">
            + Nouvelle évaluation
            @if($sousDisciplineId)
                <span class="text-purple-600">({{ $sousDisciplines->firstWhere('id', $sousDisciplineId)?->code }})</span>
            @endif
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-6 gap-3">
            <div class="sm:col-span-2">
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Titre *</label>
                <input type="text" x-model="newCol.titre" placeholder="Ex: Interro 1"
                       class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-sm">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Type *</label>
                <select x-model="newCol.type_evaluation_id"
                        class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-sm">
                    <template x-for="t in typesEval" :key="t.id">
                        <option :value="t.id" x-text="t.nom"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Date *</label>
                <input type="date" x-model="newCol.date_evaluation"
                       class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-sm">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">/ Sur *</label>
                <input type="number" x-model.number="newCol.note_sur" min="1" max="100" step="0.5"
                       class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-sm">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Coef. *</label>
                <input type="number" x-model.number="newCol.coefficient" min="0.5" max="10" step="0.5"
                       class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-sm">
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-3">
            <button type="button" @click="showAddCol = false"
                    class="text-xs font-semibold text-gray-500 px-3 py-1.5">Annuler</button>
            <button type="button" @click="addColumn()"
                    class="bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold px-4 py-1.5 rounded-lg">
                ✓ Ajouter la colonne
            </button>
        </div>
    </div>

    {{-- LA GRILLE --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        @if($evaluations->isEmpty())
            <div class="px-5 py-12 text-center">
                <svg class="w-16 h-16 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2"/></svg>
                <p class="text-gray-400 mb-3">
                    Aucune évaluation pour
                    @if($sousDisciplineId)
                        <b>{{ $sousDisciplines->firstWhere('id', $sousDisciplineId)?->code }}</b>
                    @else
                        cette matière
                    @endif
                    sur cette période.
                </p>
                <p class="text-sm text-gray-500">Cliquez <b>+ Ajouter une colonne</b> pour créer votre première évaluation.</p>
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="grades-table">
                <thead class="sticky top-0 bg-gray-50 z-10">
                    <tr class="border-b border-gray-200">
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-gray-500 uppercase w-10">N°</th>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-gray-500 uppercase w-24">Matricule</th>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-gray-500 uppercase">Élève</th>
                        @foreach($evaluations as $col => $eval)
                        <th class="px-1 py-2 text-center bg-blue-50 border-l border-blue-100 min-w-[100px]"
                            data-eval-id="{{ $eval->id }}">
                            <div class="flex items-center justify-between gap-1 px-1">
                                <span class="text-[11px] font-bold text-blue-900 truncate" title="{{ $eval->titre }}">{{ $eval->titre }}</span>
                                <button type="button" @click="editColumn({{ $eval->id }}, '{{ addslashes($eval->titre) }}', {{ $eval->type_evaluation_id ?? 'null' }}, '{{ $eval->date_evaluation?->format('Y-m-d') }}', {{ $eval->note_sur }}, {{ $eval->coefficient }})"
                                        class="text-blue-400 hover:text-blue-700 text-[10px]" title="Modifier">⚙️</button>
                            </div>
                            <div class="text-[9px] text-blue-600 font-semibold mt-0.5">
                                {{ $eval->typeEvaluation?->code ?? '—' }} · /{{ rtrim(rtrim((string)$eval->note_sur, '0'), '.') }} · ×{{ rtrim(rtrim((string)$eval->coefficient, '0'), '.') }}
                            </div>
                            <div class="text-[8px] text-blue-400 mt-0.5">{{ $eval->date_evaluation?->format('d/m') }}</div>
                        </th>
                        @endforeach
                        <th class="px-2 py-2 text-center bg-brand-100 border-l-2 border-brand-300 min-w-[80px]">
                            <span class="text-[11px] font-extrabold text-brand-800">
                                MOY /20
                                @if($sousDisciplineId)
                                    <span class="block text-[9px] text-purple-600 font-bold">{{ $sousDisciplines->firstWhere('id', $sousDisciplineId)?->code }}</span>
                                @endif
                            </span>
                            <div class="text-[9px] text-brand-600 mt-0.5">Auto-calculée</div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($eleves as $i => $eleve)
                    <tr class="hover:bg-blue-50/30">
                        <td class="px-2 py-1.5 text-[11px] font-mono text-gray-400 text-center">{{ $i + 1 }}</td>
                        <td class="px-2 py-1.5 text-[11px] font-mono font-bold text-gray-700">{{ $eleve->matricule_desps ?: $eleve->matricule_interne }}</td>
                        <td class="px-2 py-1.5 truncate max-w-[200px]">
                            <p class="font-semibold text-xs text-gray-800 truncate">
                                <span class="uppercase">{{ $eleve->nom }}</span> {{ $eleve->prenom }}
                            </p>
                        </td>
                        @foreach($evaluations as $col => $eval)
                            @php
                                $n = $notes->get($eval->id)?->get($eleve->id);
                                $val = $n?->absent ? 'ABS' : ($n?->dispense ? 'DISP' : ($n?->note !== null ? rtrim(rtrim(number_format($n->note, 2, '.', ''), '0'), '.') : ''));
                            @endphp
                            <td class="px-1 py-1 text-center border-l border-gray-100">
                                <input type="text"
                                       value="{{ $val }}"
                                       data-eval-id="{{ $eval->id }}"
                                       data-eleve-id="{{ $eleve->id }}"
                                       data-bareme="{{ $eval->note_sur }}"
                                       data-coef="{{ $eval->coefficient }}"
                                       data-row="{{ $i }}"
                                       data-col="{{ $col }}"
                                       data-grid-cell="note"
                                       maxlength="6"
                                       inputmode="decimal"
                                       @blur="saveCell($event)"
                                       @keydown="onKey($event)"
                                       @input="onInput($event)"
                                       :class="cellClass($el?.value, {{ $eval->note_sur }})"
                                       class="w-16 text-center text-sm font-bold rounded-md border border-gray-200 px-1 py-1 outline-none transition focus:ring-2 focus:ring-blue-300 focus:border-blue-500">
                            </td>
                        @endforeach
                        <td class="px-1 py-1 text-center bg-brand-50/40 border-l-2 border-brand-200">
                            <span class="text-base font-extrabold"
                                  data-moy-eleve="{{ $eleve->id }}"
                                  x-text="computeMoyenne({{ $eleve->id }})"
                                  :class="moyClass({{ $eleve->id }})">—</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                        <td colspan="3" class="px-2 py-2 text-right text-[10px] font-bold text-gray-500 uppercase">Moyenne classe</td>
                        @foreach($evaluations as $col => $eval)
                            <td class="px-1 py-2 text-center text-xs font-bold text-blue-700 border-l border-gray-200"
                                data-moy-col="{{ $eval->id }}"
                                x-text="moyenneColonne({{ $eval->id }}, {{ $eval->note_sur }})">—</td>
                        @endforeach
                        <td class="px-1 py-2 text-center bg-brand-100 border-l-2 border-brand-300 text-sm font-extrabold text-brand-800"
                            x-text="moyenneClasse()">—</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

    {{-- Modal édition colonne --}}
    <div x-show="editingEval" x-cloak @click.self="editingEval = null"
         class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-5">
            <h3 class="font-bold text-gray-800 text-lg mb-4">Modifier la colonne</h3>
            <div class="space-y-3" x-show="editingEval">
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Titre *</label>
                    <input type="text" x-model="editCol.titre" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Type</label>
                        <select x-model="editCol.type_evaluation_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                            <template x-for="t in typesEval" :key="t.id">
                                <option :value="t.id" x-text="t.nom"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Date</label>
                        <input type="date" x-model="editCol.date_evaluation" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Sur</label>
                        <input type="number" x-model.number="editCol.note_sur" min="1" max="100" step="0.5" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Coef</label>
                        <input type="number" x-model.number="editCol.coefficient" min="0.5" max="10" step="0.5" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    </div>
                </div>
            </div>
            <div class="flex justify-between items-center gap-2 mt-5 pt-4 border-t border-gray-100">
                <button type="button" @click="deleteColumnConfirm()"
                        class="text-xs font-bold text-red-600 hover:text-red-800">🗑️ Supprimer</button>
                <div class="flex gap-2">
                    <button type="button" @click="editingEval = null"
                            class="text-xs font-semibold text-gray-500 px-3 py-2">Annuler</button>
                    <button type="button" @click="saveColumn()"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 rounded-lg">
                        Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function grilleNotes(config) {
    return {
        ...config,
        showAddCol: false,
        editingEval: null,
        editCol: {},
        newCol: {
            titre: '',
            type_evaluation_id: null,
            date_evaluation: new Date().toISOString().slice(0, 10),
            note_sur: 20,
            coefficient: 1,
        },

        init() {
            if (this.typesEval.length > 0 && !this.newCol.type_evaluation_id) {
                this.newCol.type_evaluation_id = this.typesEval[0].id;
            }
        },

        // ── Helpers ────────────────────────────────────────────────────────

        parseValue(v) {
            const s = String(v || '').trim().toUpperCase();
            if (s === '')  return { kind: 'empty' };
            if (['ABS','A','ABSENT'].includes(s))             return { kind: 'abs' };
            if (['DISP','D','DISPENSE','DISPENSÉ'].includes(s)) return { kind: 'disp' };
            const n = parseFloat(s.replace(',', '.'));
            if (isNaN(n)) return { kind: 'invalid' };
            return { kind: 'num', value: n };
        },

        cellClass(value, bareme) {
            const base = 'w-16 text-center text-sm font-bold rounded-md border px-1 py-1 outline-none transition focus:ring-2 focus:ring-blue-300';
            const p = this.parseValue(value);
            if (p.kind === 'empty')   return base + ' border-gray-200 bg-white text-gray-400';
            if (p.kind === 'abs')     return base + ' border-red-200 bg-red-50 text-red-700';
            if (p.kind === 'disp')    return base + ' border-blue-200 bg-blue-50 text-blue-700';
            if (p.kind === 'invalid') return base + ' border-red-400 bg-red-100 text-red-800';
            const max = parseFloat(bareme || 20);
            if (p.value > max || p.value < 0) return base + ' border-red-500 bg-red-200 text-red-900 ring-2 ring-red-400';
            const n20 = (p.value / max) * 20;
            if (n20 >= 14) return base + ' border-green-200 bg-green-50 text-green-700';
            if (n20 >= 10) return base + ' border-amber-200 bg-amber-50 text-amber-700';
            return             base + ' border-red-200   bg-red-50   text-red-700';
        },

        moyClass(eleveId) {
            const v = parseFloat(this.computeMoyenne(eleveId));
            if (isNaN(v)) return 'text-gray-400';
            if (v >= 14)  return 'text-green-700';
            if (v >= 10)  return 'text-amber-700';
            return 'text-red-700';
        },

        // ── Calculs ────────────────────────────────────────────────────────

        computeMoyenne(eleveId) {
            const cells = document.querySelectorAll(`[data-grid-cell="note"][data-eleve-id="${eleveId}"]`);
            let sumP = 0, sumC = 0;
            cells.forEach(c => {
                const p = this.parseValue(c.value);
                if (p.kind !== 'num') return;
                const bareme = parseFloat(c.dataset.bareme || 20);
                if (p.value > bareme || p.value < 0) return;
                const coef = parseFloat(c.dataset.coef || 1);
                const n20  = (p.value / bareme) * 20;
                sumP += n20 * coef;
                sumC += coef;
            });
            return sumC > 0 ? (sumP / sumC).toFixed(2) : '—';
        },

        moyenneColonne(evalId, bareme) {
            const cells = document.querySelectorAll(`[data-grid-cell="note"][data-eval-id="${evalId}"]`);
            const notes = [];
            cells.forEach(c => {
                const p = this.parseValue(c.value);
                if (p.kind === 'num') notes.push((p.value / bareme) * 20);
            });
            return notes.length ? (notes.reduce((a, b) => a + b, 0) / notes.length).toFixed(2) : '—';
        },

        moyenneClasse() {
            const spans = document.querySelectorAll('[data-moy-eleve]');
            const vals  = Array.from(spans).map(s => parseFloat(s.textContent)).filter(v => !isNaN(v));
            return vals.length ? (vals.reduce((a, b) => a + b, 0) / vals.length).toFixed(2) : '—';
        },

        // ── Auto-save cellule ──────────────────────────────────────────────

        onInput(e) {
            const v = e.target.value.replace(',', '.');
            if (v !== e.target.value) e.target.value = v;
            e.target.className = this.cellClass(v, parseFloat(e.target.dataset.bareme || 20));
        },

        async saveCell(e) {
            const cell   = e.target;
            const evalId = cell.dataset.evalId;
            const bareme = parseFloat(cell.dataset.bareme || 20);
            const p      = this.parseValue(cell.value);

            if (p.kind === 'num' && (p.value > bareme || p.value < 0)) {
                alert(`Note invalide : doit être comprise entre 0 et ${bareme}.`);
                cell.focus(); cell.select(); return;
            }
            if (p.kind === 'invalid') {
                alert('Valeur non valide. Utilisez un nombre, ABS ou DISP.');
                cell.focus(); cell.select(); return;
            }

            try {
                const res = await fetch(`${this.routes.saveNote}/${evalId}/notes`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    credentials: 'same-origin',
                    body: JSON.stringify({ eleve_id: parseInt(cell.dataset.eleveId), note: cell.value }),
                });
                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    cell.style.background = '#fee2e2';
                    if (data.message) alert(data.message);
                    setTimeout(() => cell.style.background = '', 1500);
                } else {
                    cell.style.background = '#d1fae5';
                    setTimeout(() => cell.style.background = '', 600);
                    this.$nextTick(() => {
                        document.querySelectorAll('[data-moy-eleve]').forEach(s => {
                            s.textContent = this.computeMoyenne(s.dataset.moyEleve);
                        });
                    });
                }
            } catch (err) { console.error(err); }
        },

        // ── Navigation clavier ────────────────────────────────────────────

        onKey(e) {
            const cell = e.target;
            const row  = parseInt(cell.dataset.row);
            const col  = parseInt(cell.dataset.col);

            if (e.key === 'Enter' || e.key === 'ArrowDown')  { e.preventDefault(); this.focusCell(row + 1, col); return; }
            if (e.key === 'ArrowUp')                          { e.preventDefault(); this.focusCell(row - 1, col); return; }
            if (e.key === 'ArrowRight' && cell.selectionStart === cell.value.length) { e.preventDefault(); this.focusCell(row, col + 1); return; }
            if (e.key === 'ArrowLeft'  && cell.selectionStart === 0)                 { e.preventDefault(); this.focusCell(row, col - 1); return; }
            if (cell.value === '' && e.key.toLowerCase() === 'a') { e.preventDefault(); cell.value = 'ABS';  this.saveCell({ target: cell }); this.focusCell(row + 1, col); return; }
            if (cell.value === '' && e.key.toLowerCase() === 'd') { e.preventDefault(); cell.value = 'DISP'; this.saveCell({ target: cell }); this.focusCell(row + 1, col); return; }
            if (e.key === 'Escape') cell.blur();
        },

        focusCell(row, col) {
            const el = document.querySelector(`[data-grid-cell="note"][data-row="${row}"][data-col="${col}"]`);
            if (el) { el.focus(); el.select(); }
        },

        // ── Colonnes ──────────────────────────────────────────────────────

        async addColumn() {
            if (!this.newCol.titre || !this.newCol.type_evaluation_id) {
                alert('Renseignez au moins le titre et le type.'); return;
            }
            const res = await fetch(this.routes.addCol, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                credentials: 'same-origin',
                body: JSON.stringify({
                    matiere_id:         this.activeMatiereId,  // SD active ou parent
                    trimestre_id:       this.trimestreId,
                    titre:              this.newCol.titre,
                    type_evaluation_id: this.newCol.type_evaluation_id,
                    date_evaluation:    this.newCol.date_evaluation,
                    note_sur:           this.newCol.note_sur,
                    coefficient:        this.newCol.coefficient,
                }),
            });
            if (res.ok) { location.reload(); }
            else { const data = await res.json(); alert('Erreur : ' + (data.message || 'inconnue')); }
        },

        editColumn(evalId, titre, typeId, date, noteSur, coef) {
            this.editingEval = evalId;
            this.editCol = {
                titre,
                type_evaluation_id: typeId,
                date_evaluation:    date,
                note_sur:           noteSur,
                coefficient:        coef,
            };
        },

        async saveColumn() {
            if (!this.editingEval) return;
            const res = await fetch(`${this.routes.updateCol}/${this.editingEval}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                credentials: 'same-origin',
                body: JSON.stringify(this.editCol),
            });
            if (res.ok) { location.reload(); }
            else { alert('Erreur : modification impossible'); }
        },

        async deleteColumnConfirm() {
            if (!confirm('Supprimer cette colonne ? Toutes les notes seront perdues.')) return;
            const res = await fetch(`${this.routes.deleteCol}/${this.editingEval}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            const data = await res.json();
            if (data.success) { location.reload(); }
            else { alert(data.message || 'Suppression impossible'); }
        },
    };
}
</script>
@endpush
@endsection
