@extends('layouts.app')
@section('title', 'Vérification OCR · ' . $classe->nom)

@section('content')
@php
    $matiere = $matieres->firstWhere('id', $matiereId);
    $typesEval = \App\Models\TypeEvaluation::where('etablissement_id', auth()->user()->etablissement_id)
        ->where('actif', true)->get();
    $colonnes = $extracted['colonnes'] ?? [];
    $rows     = $extracted['eleves'] ?? [];
    $conf     = $extracted['confidence'] ?? 0;
@endphp

<div class="max-w-7xl mx-auto px-4 py-8 space-y-5">

    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('mon-espace.classes') }}" class="hover:text-brand-600 font-medium">Mes classes</a>
        <span>/</span>
        <a href="{{ route('mon-espace.feuille-de-note.index', $classe) }}" class="hover:text-brand-600 font-medium">{{ $classe->nom }}</a>
        <span>/</span>
        <span>Vérification OCR</span>
    </div>

    <div class="flex items-center justify-between">
        <h1 class="font-display text-2xl font-extrabold text-gray-900">Vérifiez les notes extraites</h1>
        <span class="text-xs font-bold px-3 py-1 rounded-full
            {{ $conf >= 70 ? 'bg-green-100 text-green-700' : ($conf >= 40 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
            Confiance IA : {{ $conf }}%
        </span>
    </div>

    @if(!empty($extracted['notes_extraction']))
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 text-sm">
            <b>💡 Note de l'IA :</b> {{ $extracted['notes_extraction'] }}
        </div>
    @endif

    @if(empty($colonnes) || empty($rows))
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
            <b>Aucune donnée n'a pu être extraite.</b> Vérifiez la qualité de la photo et réessayez.
            <div class="mt-3">
                <a href="{{ route('mon-espace.feuille-de-note.import-ocr.form', $classe) }}"
                   class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold px-4 py-2 rounded-lg">Recommencer</a>
            </div>
        </div>
    @else

    <form method="POST" action="{{ route('mon-espace.feuille-de-note.import-ocr.confirm', $classe) }}"
          class="space-y-5"
          x-data='ocrPreview(@json([
              "colonnes" => $colonnes,
              "rows"     => $rows,
              "types"    => $typesEval->map(fn($t) => ["id" => $t->id, "nom" => $t->nom, "code" => $t->code])->values(),
              "trimestres" => $trimestres->map(fn($t) => ["id" => $t->id, "libelle" => "T" . $t->numero])->values(),
          ]))'>
        @csrf
        <input type="hidden" name="matiere_id" value="{{ $matiereId }}">
        <input type="hidden" name="image_path" value="{{ $imagePath }}">

        {{-- Métadonnées par colonne --}}
        <div class="bg-white rounded-2xl shadow-card border border-purple-100 p-5">
            <h2 class="font-bold text-purple-800 text-sm uppercase tracking-wide mb-3">Configurer les évaluations</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Col.</th>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Titre *</th>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Type *</th>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Trimestre *</th>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Date *</th>
                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase">Sur</th>
                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase">Coef</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="(c, i) in colonnes" :key="i">
                            <tr>
                                <td class="px-3 py-2 font-bold text-gray-400">N° <span x-text="i+1"></span></td>
                                <td class="px-3 py-2">
                                    <input type="text" :name="`columns[${i}][titre]`" x-model="c.titre" required
                                           class="w-40 rounded-lg border border-gray-200 px-2 py-1.5">
                                </td>
                                <td class="px-3 py-2">
                                    <select :name="`columns[${i}][type_evaluation_id]`" required
                                            class="rounded-lg border border-gray-200 px-2 py-1.5">
                                        <template x-for="t in types" :key="t.id">
                                            <option :value="t.id" :selected="matchType(c.type, t)" x-text="t.nom"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <select :name="`columns[${i}][trimestre_id]`" required
                                            class="rounded-lg border border-gray-200 px-2 py-1.5">
                                        <template x-for="t in trimestres" :key="t.id">
                                            <option :value="t.id" x-text="t.libelle"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="date" :name="`columns[${i}][date_evaluation]`"
                                           :value="new Date().toISOString().slice(0,10)" required
                                           class="rounded-lg border border-gray-200 px-2 py-1.5">
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <input type="number" :name="`columns[${i}][note_sur]`" :value="c.note_sur || 20"
                                           min="1" max="100" step="0.5" required
                                           class="w-16 text-center rounded-lg border border-gray-200 px-2 py-1.5">
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <input type="number" :name="`columns[${i}][coefficient]`" value="1"
                                           min="0.5" max="10" step="0.5" required
                                           class="w-16 text-center rounded-lg border border-gray-200 px-2 py-1.5">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Grille élèves × notes extraites --}}
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Notes extraites — vérifiez et corrigez</h2>
                <span class="text-xs font-semibold text-gray-500" x-text="`${rows.length} élève(s) détecté(s)`"></span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Matricule OCR</th>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Match en base</th>
                            <template x-for="(c, i) in colonnes" :key="i">
                                <th class="px-2 py-2 text-center text-xs font-bold text-gray-500 uppercase" x-text="c.titre"></th>
                            </template>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="(r, ri) in rows" :key="ri">
                            <tr :class="r.eleve_id ? '' : 'bg-red-50'">
                                <td class="px-3 py-2 font-mono font-bold" x-text="r.matricule_ocr"></td>
                                <td class="px-3 py-2">
                                    <span x-show="r.eleve_id" class="text-green-700 text-xs">
                                        ✓ <span x-text="r.nom_match"></span>
                                    </span>
                                    <span x-show="!r.eleve_id" class="text-red-600 text-xs font-bold">
                                        ⚠ Non trouvé
                                    </span>
                                </td>
                                <template x-for="(c, ci) in colonnes" :key="ci">
                                    <td class="px-2 py-2 text-center">
                                        <input type="text"
                                               :name="`columns[${ci}][notes][${r.matricule_ocr}]`"
                                               :value="r.notes[ci] ?? ''"
                                               :disabled="!r.eleve_id"
                                               class="w-16 text-center font-bold rounded-lg border border-gray-200 px-1 py-1
                                                      disabled:bg-gray-100 disabled:text-gray-300">
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('mon-espace.feuille-de-note.import-ocr.form', $classe) }}"
               class="px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">Recommencer</a>
            <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white text-sm font-bold px-6 py-2.5 rounded-xl transition">
                ✓ Confirmer et enregistrer
            </button>
        </div>
    </form>
    @endif
</div>

@push('scripts')
<script>
function ocrPreview(data) {
    return {
        colonnes: data.colonnes,
        rows: data.rows,
        types: data.types,
        trimestres: data.trimestres,
        matchType(ocrType, t) {
            if (!ocrType) return false;
            const u = String(ocrType).toUpperCase();
            const codeU = (t.code || '').toUpperCase();
            const nomU  = (t.nom  || '').toUpperCase();
            return codeU === u || nomU.includes(u) || u.includes(codeU);
        },
    };
}
</script>
@endpush
@endsection
