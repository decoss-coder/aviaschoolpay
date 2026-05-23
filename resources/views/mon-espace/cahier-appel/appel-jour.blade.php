@extends('layouts.app')
@section('title', 'Appel du jour · ' . $classe->nom)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 space-y-4"
     x-data="appelJour({{ Js::from([
        'date'     => $date->toDateString(),
        'stats'    => $stats,
        'seances'  => $seances,
        'eleves'   => $eleves->map(fn ($e) => [
            'id' => $e->id,
            'matricule' => $e->matricule_desps ?: $e->matricule_interne,
            'nom' => strtoupper($e->nom),
            'prenom' => $e->prenom,
            'sexe' => $e->sexe,
        ])->values(),
        'presences' => $presences->map(fn ($p) => [
            'eleve_id' => $p->eleve_id,
            'creneau_id' => $p->creneau_id,
            'statut' => $p->statut,
        ])->values(),
        'csrf' => csrf_token(),
        'routeMark' => route('mon-espace.cahier-appel.appel-jour.mark', $classe),
     ]) }})">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('mon-espace.classes') }}" class="hover:text-brand-600">Mes classes</a>
        <span>/</span>
        <a href="{{ route('mon-espace.cahier-appel.index', $classe) }}" class="hover:text-brand-600">{{ $classe->nom }}</a>
        <span>/</span>
        <span>Appel du jour</span>
    </div>

    {{-- Header + sélecteurs --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 px-5 py-4 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-display text-xl font-extrabold text-gray-900">Appel — {{ $classe->nom }}</h1>
                <p class="text-xs text-gray-500 mt-0.5" x-text="dateLabel"></p>
            </div>
            <form method="GET" class="flex items-center gap-2 text-sm">
                <label class="text-xs font-bold text-gray-500 uppercase">Date :</label>
                <input type="date" name="date" value="{{ $date->toDateString() }}" onchange="this.form.submit()"
                       class="rounded-lg border border-gray-200 px-3 py-1.5">
            </form>
        </div>

        {{-- Sélecteur de créneau --}}
        @if(count($seances) > 0)
        <div class="border-t border-gray-100 pt-3">
            <label class="block text-xs font-bold text-violet-600 uppercase mb-1.5">
                🕐 Séance à pointer ({{ count($seances) }} disponibles aujourd'hui)
            </label>
            <select x-model.number="selectedCreneauId"
                    class="w-full rounded-xl border-2 border-violet-200 px-4 py-2.5 text-sm font-bold text-violet-900 bg-violet-50 focus:ring-2 focus:ring-violet-300 focus:border-violet-400 outline-none">
                <template x-for="s in seances" :key="s.creneau_id">
                    <option :value="s.creneau_id"
                            x-text="`${s.libelle_creneau}${s.matiere ? ' · ' + s.matiere : ''} (${seanceStats(s.creneau_id).saisis}/${eleves.length} saisis)`"></option>
                </template>
            </select>
        </div>
        @endif
    </div>

    {{-- Toast --}}
    <template x-if="toast">
        <div :class="toast.error ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'"
             class="border rounded-xl px-3 py-2 text-sm font-semibold flex items-center justify-between">
            <span x-text="toast.msg"></span>
            <button @click="toast = null" class="opacity-60 hover:opacity-100">✕</button>
        </div>
    </template>

    @if(empty($seances))
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center">
            <p class="text-amber-800 font-bold mb-2">Aucune séance aujourd'hui</p>
            <p class="text-sm text-amber-700">
                Vous n'avez pas de cours configuré pour cette classe le {{ $date->locale('fr')->isoFormat('dddd D MMMM') }}.
            </p>
        </div>
    @else

    {{-- Stats DE LA SÉANCE SÉLECTIONNÉE --}}
    <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
        <div class="bg-white rounded-xl border border-green-100 p-3 text-center">
            <p class="text-[10px] font-bold text-green-600 uppercase">Présents</p>
            <p class="text-2xl font-extrabold text-green-700 mt-0.5" x-text="currentSeanceStats.present"></p>
        </div>
        <div class="bg-white rounded-xl border border-red-100 p-3 text-center">
            <p class="text-[10px] font-bold text-red-600 uppercase">Absents</p>
            <p class="text-2xl font-extrabold text-red-700 mt-0.5" x-text="currentSeanceStats.absent"></p>
        </div>
        <div class="bg-white rounded-xl border border-amber-100 p-3 text-center">
            <p class="text-[10px] font-bold text-amber-600 uppercase">Retards</p>
            <p class="text-2xl font-extrabold text-amber-700 mt-0.5" x-text="currentSeanceStats.retard"></p>
        </div>
        <div class="bg-white rounded-xl border border-blue-100 p-3 text-center">
            <p class="text-[10px] font-bold text-blue-600 uppercase">Excusés</p>
            <p class="text-2xl font-extrabold text-blue-700 mt-0.5" x-text="currentSeanceStats.excuse"></p>
        </div>
        <div class="bg-white rounded-xl border border-purple-100 p-3 text-center">
            <p class="text-[10px] font-bold text-purple-600 uppercase">Dispensés</p>
            <p class="text-2xl font-extrabold text-purple-700 mt-0.5" x-text="currentSeanceStats.dispense"></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-3 text-center">
            <p class="text-[10px] font-bold text-gray-500 uppercase">Non saisis</p>
            <p class="text-2xl font-extrabold text-gray-500 mt-0.5" x-text="currentSeanceStats.non_saisi"></p>
        </div>
    </div>

    {{-- Actions en masse pour la séance courante --}}
    <div class="bg-amber-50 border border-amber-100 rounded-xl px-4 py-2.5 flex items-center justify-between text-sm">
        <span class="text-amber-800 font-semibold">⚡ Pour cette séance, marquer les non-saisis comme :</span>
        <div class="flex gap-2">
            <button @click="bulkMark('present')"
                    class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg">Présents</button>
            <button @click="bulkMark('absent')"
                    class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg">Absents</button>
        </div>
    </div>

    {{-- Liste élèves pour la séance courante --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <template x-for="(e, idx) in eleves" :key="e.id">
            <div class="border-b border-gray-50 last:border-0 flex items-center gap-3 px-4 py-2.5"
                 :class="{
                    'bg-green-50/50':  presenceOf(e) === 'present',
                    'bg-red-50/50':    presenceOf(e) === 'absent',
                    'bg-amber-50/50':  presenceOf(e) === 'retard',
                    'bg-blue-50/50':   presenceOf(e) === 'excuse',
                    'bg-purple-50/50': presenceOf(e) === 'dispense',
                 }">
                <div class="w-7 text-xs font-mono text-gray-400 text-center" x-text="idx + 1"></div>
                <div class="w-24 text-xs font-mono font-bold text-gray-700" x-text="e.matricule"></div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-sm text-gray-800 truncate">
                        <span x-text="e.nom"></span>
                        <span x-text="e.prenom" class="font-normal text-gray-600"></span>
                    </p>
                </div>
                <span class="text-xs font-bold"
                      :class="{ 'text-blue-500': e.sexe === 'M', 'text-pink-500': e.sexe === 'F' }"
                      x-text="e.sexe || ''"></span>

                <div class="flex gap-1">
                    <button @click="mark(e, 'present')"
                            :class="presenceOf(e) === 'present' ? 'bg-green-600 text-white' : 'bg-gray-100 hover:bg-green-100 text-gray-600 hover:text-green-700'"
                            class="w-9 h-9 rounded-lg font-extrabold text-sm transition" title="Présent">P</button>
                    <button @click="mark(e, 'absent')"
                            :class="presenceOf(e) === 'absent' ? 'bg-red-600 text-white' : 'bg-gray-100 hover:bg-red-100 text-gray-600 hover:text-red-700'"
                            class="w-9 h-9 rounded-lg font-extrabold text-sm transition" title="Absent">A</button>
                    <button @click="mark(e, 'retard')"
                            :class="presenceOf(e) === 'retard' ? 'bg-amber-600 text-white' : 'bg-gray-100 hover:bg-amber-100 text-gray-600 hover:text-amber-700'"
                            class="w-9 h-9 rounded-lg font-extrabold text-sm transition" title="Retard">R</button>
                    <button @click="mark(e, 'excuse')"
                            :class="presenceOf(e) === 'excuse' ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-blue-100 text-gray-600 hover:text-blue-700'"
                            class="w-9 h-9 rounded-lg font-extrabold text-sm transition" title="Excusé">E</button>
                    <button @click="mark(e, 'dispense')"
                            :class="presenceOf(e) === 'dispense' ? 'bg-purple-600 text-white' : 'bg-gray-100 hover:bg-purple-100 text-gray-600 hover:text-purple-700'"
                            class="w-9 h-9 rounded-lg font-extrabold text-sm transition" title="Dispensé">D</button>
                </div>
            </div>
        </template>
    </div>

    <p class="text-xs text-gray-400 italic text-center">💾 Les modifications sont enregistrées automatiquement à chaque clic.</p>
    @endif
</div>

@push('scripts')
<script>
function appelJour(props) {
    return {
        ...props,
        toast: null,
        presenceMap: {},   // key = `${eleve_id}_${creneau_id}` → statut
        selectedCreneauId: null,

        init() {
            for (const p of this.presences) {
                this.presenceMap[`${p.eleve_id}_${p.creneau_id}`] = p.statut;
            }
            if (this.seances.length > 0) {
                this.selectedCreneauId = this.seances[0].creneau_id;
            }
        },

        get currentSeance() {
            return this.seances.find(s => s.creneau_id === this.selectedCreneauId) ?? null;
        },

        get currentSeanceStats() {
            return this.seanceStats(this.selectedCreneauId);
        },

        seanceStats(creneauId) {
            const counts = { present: 0, absent: 0, retard: 0, excuse: 0, dispense: 0 };
            for (const e of this.eleves) {
                const st = this.presenceMap[`${e.id}_${creneauId}`];
                if (st && counts[st] !== undefined) counts[st]++;
            }
            const saisis = Object.values(counts).reduce((a, b) => a + b, 0);
            return {
                ...counts,
                saisis,
                non_saisi: Math.max(0, this.eleves.length - saisis),
                total: this.eleves.length,
            };
        },

        presenceOf(eleve) {
            return this.presenceMap[`${eleve.id}_${this.selectedCreneauId}`] || null;
        },

        get dateLabel() {
            const d = new Date(this.date);
            const opts = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
            const lib = d.toLocaleDateString('fr-FR', opts);
            return lib.charAt(0).toUpperCase() + lib.slice(1);
        },

        // Fire-and-forget : UI mise à jour immédiatement, requête en background.
        // Si le serveur refuse, on rollback et on affiche l'erreur.
        mark(eleve, statut) {
            if (!this.selectedCreneauId) return;
            const key = `${eleve.id}_${this.selectedCreneauId}`;
            const prev = this.presenceMap[key];

            // 1. UI instantanée
            this.presenceMap[key] = statut;
            this.flashFast(eleve.nom, statut);

            // 2. Sauvegarde en arrière-plan (non bloquante)
            fetch(this.routeMark, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                },
                credentials: 'same-origin',
                keepalive: true,
                body: JSON.stringify({
                    eleve_id:   eleve.id,
                    date:       this.date,
                    creneau_id: this.selectedCreneauId,
                    statut:     statut,
                }),
            })
            .then(r => r.ok ? r.json() : Promise.reject(r))
            .then(data => {
                if (!data.success) {
                    this.presenceMap[key] = prev;
                    this.flash('Erreur : ' + (data.message || 'rejeté'), true);
                }
            })
            .catch(err => {
                this.presenceMap[key] = prev;
                this.flash(`Erreur sur ${eleve.nom} (réessaie)`, true);
            });
        },

        bulkMark(statut) {
            if (!this.selectedCreneauId) return;
            const cibles = this.eleves.filter(e => !this.presenceMap[`${e.id}_${this.selectedCreneauId}`]);
            if (cibles.length === 0) {
                this.flash('Aucun élève non-saisi sur cette séance', true);
                return;
            }
            // Fire en parallèle → terminé en 1 roundtrip réseau au lieu de N
            cibles.forEach(e => this.mark(e, statut));
            this.flash(`✓ ${cibles.length} élèves marqués ${this.label(statut)}`);
        },

        label(s) {
            return { present: 'Présent', absent: 'Absent', retard: 'Retard', excuse: 'Excusé', dispense: 'Dispensé' }[s] ?? s;
        },

        flash(msg, error = false) {
            this.toast = { msg, error };
            setTimeout(() => { if (this.toast?.msg === msg) this.toast = null; }, 2000);
        },

        // Toast léger sans rerender lourd quand on clique vite (auto-dismiss court)
        flashFast(nom, statut) {
            const msg = `${nom} → ${this.label(statut)}`;
            this.toast = { msg, error: false };
            clearTimeout(this._fastTimer);
            this._fastTimer = setTimeout(() => { this.toast = null; }, 1200);
        },
    };
}
</script>
@endpush
@endsection
