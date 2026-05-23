@extends('layouts.app')
@section('title', 'Cahier d\'appel · ' . $classe->nom)

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-4">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('mon-espace.classes') }}" class="hover:text-brand-600">Mes classes</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">{{ $classe->nom }}</span>
        <span>/</span>
        <span>Cahier d'appel</span>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="font-display text-2xl font-extrabold text-gray-900">Cahier d'appel — {{ $classe->nom }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                Semaine du <b>{{ $semaine->format('d/m/Y') }}</b>
                · {{ $eleves->count() }} élèves
                · {{ count($seances) }} séances
            </p>
        </div>

        <form method="GET" class="flex items-center gap-2">
            <a href="?semaine={{ $semaine->copy()->subWeek()->toDateString() }}"
               class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm">← Sem. préc.</a>
            <input type="date" name="semaine" value="{{ $semaine->toDateString() }}"
                   onchange="this.form.submit()"
                   class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm">
            <a href="?semaine={{ $semaine->copy()->addWeek()->toDateString() }}"
               class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm">Sem. suiv. →</a>
        </form>
    </div>

    {{-- Actions --}}
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('mon-espace.cahier-appel.appel-jour', $classe) }}"
           class="bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold px-4 py-2 rounded-xl flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Appel du jour (rapide)
        </a>
        <a href="{{ route('mon-espace.cahier-appel.pdf', ['classe' => $classe, 'semaine' => $semaine->toDateString()]) }}"
           target="_blank"
           class="bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-4 py-2 rounded-xl flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            PDF imprimable
        </a>
        <a href="{{ route('mon-espace.cahier-appel.excel', ['classe' => $classe, 'semaine' => $semaine->toDateString()]) }}"
           class="bg-green-600 hover:bg-green-700 text-white text-sm font-bold px-4 py-2 rounded-xl flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Excel template
        </a>
        <a href="{{ route('mon-espace.cahier-appel.import-ocr.form', $classe) }}"
           class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-bold px-4 py-2 rounded-xl flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3" stroke-width="2"/></svg>
            OCR photo
        </a>
    </div>

    @if(empty($seances))
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center">
            <p class="text-amber-800 font-bold mb-2">Aucune séance dans l'emploi du temps</p>
            <p class="text-sm text-amber-700">
                Vous n'avez pas de cours configuré pour cette classe sur la semaine du {{ $semaine->format('d/m/Y') }}.
                Vérifiez avec l'administration que l'EDT est bien validé.
            </p>
        </div>
    @else

    {{-- Saisie en grille --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between flex-wrap gap-3">
            <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Vue par séance</h2>
            <span class="text-xs text-gray-500 italic">P=Présent · A=Absent · R=Retard · E=Excusé · D=Dispensé</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th rowspan="2" class="px-2 py-2 text-left text-xs font-bold text-gray-500 uppercase">N°</th>
                        <th rowspan="2" class="px-2 py-2 text-left text-xs font-bold text-gray-500 uppercase">Matricule</th>
                        <th rowspan="2" class="px-2 py-2 text-left text-xs font-bold text-gray-500 uppercase">Nom Prénom</th>
                        @php
                            $parJour = [];
                            foreach ($seances as $s) { $parJour[$s['date']][] = $s; }
                        @endphp
                        @foreach($parJour as $date => $sJour)
                            @php $libJ = \Carbon\Carbon::parse($date)->locale('fr')->isoFormat('ddd D/MM'); @endphp
                            <th colspan="{{ count($sJour) }}"
                                class="px-2 py-2 text-center text-xs font-bold text-violet-700 bg-violet-50 border-l border-violet-100">
                                {{ $libJ }}
                            </th>
                        @endforeach
                    </tr>
                    <tr class="bg-violet-50/50 border-b border-violet-100">
                        @foreach($seances as $s)
                            <th class="px-1 py-1 text-center text-[10px] font-semibold text-violet-700">
                                {{ $s['libelle_creneau'] }}
                                @if($s['matiere'])<br><span class="text-violet-500 font-normal">{{ $s['matiere'] }}</span>@endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($eleves as $i => $eleve)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-2 py-1.5 text-gray-400 font-mono">{{ $i + 1 }}</td>
                        <td class="px-2 py-1.5 font-mono font-bold text-[10px]">{{ $eleve->matricule_desps ?: $eleve->matricule_interne }}</td>
                        <td class="px-2 py-1.5 font-semibold text-gray-800 whitespace-nowrap text-xs">
                            {{ strtoupper($eleve->nom) }} {{ $eleve->prenom }}
                        </td>
                        @foreach($seances as $s)
                            @php
                                $key = $eleve->id . '_' . $s['date'] . '_' . $s['creneau_id'];
                                $p = $presences->get($key);
                            @endphp
                            <td class="px-1 py-1 text-center"
                                data-eleve="{{ $eleve->id }}"
                                data-date="{{ $s['date'] }}"
                                data-creneau="{{ $s['creneau_id'] }}">
                                @php
                                    $statut = $p?->statut;
                                    $color = match($statut) {
                                        'present'  => 'bg-green-100  text-green-700',
                                        'absent'   => 'bg-red-100    text-red-700',
                                        'retard'   => 'bg-amber-100  text-amber-700',
                                        'excuse'   => 'bg-blue-100   text-blue-700',
                                        'dispense' => 'bg-purple-100 text-purple-700',
                                        default    => 'bg-gray-50    text-gray-400',
                                    };
                                @endphp
                                <select onchange="markPresence(this)"
                                        class="w-10 text-center font-bold text-xs rounded-md border-0 px-1 py-1 {{ $color }}">
                                    <option value="" {{ !$statut ? 'selected' : '' }}>—</option>
                                    <option value="present"  {{ $statut === 'present' ? 'selected' : '' }}>P</option>
                                    <option value="absent"   {{ $statut === 'absent' ? 'selected' : '' }}>A</option>
                                    <option value="retard"   {{ $statut === 'retard' ? 'selected' : '' }}>R</option>
                                    <option value="excuse"   {{ $statut === 'excuse' ? 'selected' : '' }}>E</option>
                                    <option value="dispense" {{ $statut === 'dispense' ? 'selected' : '' }}>D</option>
                                </select>
                            </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
async function markPresence(select) {
    const cell = select.parentElement;
    const statut = select.value;
    if (!statut) return;

    try {
        const res = await fetch('{{ route('mon-espace.cahier-appel.appel-jour.mark', $classe) }}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                eleve_id:   parseInt(cell.dataset.eleve),
                date:       cell.dataset.date,
                creneau_id: parseInt(cell.dataset.creneau),
                statut:     statut,
            }),
        });
        const data = await res.json();
        if (!data.success) {
            alert(data.message || 'Erreur enregistrement');
            return;
        }
        // Recolorer
        select.className = 'w-10 text-center font-bold text-xs rounded-md border-0 px-1 py-1 ' + ({
            present:  'bg-green-100  text-green-700',
            absent:   'bg-red-100    text-red-700',
            retard:   'bg-amber-100  text-amber-700',
            excuse:   'bg-blue-100   text-blue-700',
            dispense: 'bg-purple-100 text-purple-700',
        }[statut] || 'bg-gray-50 text-gray-400');
    } catch (e) {
        alert('Erreur réseau : ' + e);
    }
}
</script>
@endpush
@endsection
