@extends('layouts.app')

@section('title', 'Alertes de pointage')
@section('page-title', 'Centre des alertes de pointage')
@section('page-subtitle', ($stats['total'] ?? 0) . ' alertes — ' . \Carbon\Carbon::parse($date)->format('d/m/Y'))

@section('content')
<div>
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3 flex-wrap">
            <div class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-brand-100 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm">
                <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Journée du {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
            </div>
        </div>

        <form method="GET" class="flex items-center gap-2 flex-wrap">
            <div class="relative min-w-[220px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Enseignant, message, commentaire..."
                       class="w-full lg:w-72 pl-10 pr-4 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-300 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm">
                <svg class="w-4 h-4 text-brand-400 absolute left-3 top-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <input type="date" name="date" value="{{ request('date', $date) }}"
                   class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm text-gray-700 focus:border-brand-300 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm">

            <select name="enseignant_id" onchange="this.form.submit()"
                    class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm text-gray-700 focus:border-brand-300 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                <option value="">Tous enseignants</option>
                @foreach($enseignants as $enseignant)
                    <option value="{{ $enseignant->id }}" {{ request('enseignant_id') == $enseignant->id ? 'selected' : '' }}>
                        {{ $enseignant->nom_complet }}
                    </option>
                @endforeach
            </select>

            <select name="type_alerte" onchange="this.form.submit()"
                    class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm text-gray-700 focus:border-brand-300 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                <option value="">Tous types</option>
                @foreach($typesAlerteDisponibles as $type)
                    <option value="{{ $type }}" {{ request('type_alerte') === $type ? 'selected' : '' }}>
                        {{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $type)) }}
                    </option>
                @endforeach
            </select>

            <select name="gravite" onchange="this.form.submit()"
                    class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm text-gray-700 focus:border-brand-300 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                <option value="">Toutes gravités</option>
                <option value="info" {{ request('gravite') === 'info' ? 'selected' : '' }}>Info</option>
                <option value="warning" {{ request('gravite') === 'warning' ? 'selected' : '' }}>Warning</option>
                <option value="critique" {{ request('gravite') === 'critique' ? 'selected' : '' }}>Critique</option>
            </select>

            <select name="etat" onchange="this.form.submit()"
                    class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm text-gray-700 focus:border-brand-300 focus:ring-2 focus:ring-brand-100 outline-none shadow-sm cursor-pointer">
                <option value="">Tous états</option>
                <option value="non_lue" {{ request('etat') === 'non_lue' ? 'selected' : '' }}>Non lues</option>
                <option value="lue" {{ request('etat') === 'lue' ? 'selected' : '' }}>Lues</option>
                <option value="non_traitee" {{ request('etat') === 'non_traitee' ? 'selected' : '' }}>Non traitées</option>
                <option value="traitee" {{ request('etat') === 'traitee' ? 'selected' : '' }}>Traitées</option>
            </select>
        </form>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-7 gap-3 mb-6">
        <div class="relative overflow-hidden bg-gradient-to-br from-white to-brand-50/50 border border-brand-100/60 rounded-xl p-4 shadow-card-brand">
            <p class="text-[11px] text-gray-500 font-semibold uppercase tracking-[0.12em]">Total</p>
            <p class="font-display text-3xl font-extrabold text-gray-900 mt-2">{{ $stats['total'] ?? 0 }}</p>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-red-50/60 border border-red-100/60 rounded-xl p-4 shadow-sm">
            <p class="text-[11px] text-red-600 font-semibold uppercase tracking-[0.12em]">Non lues</p>
            <p class="font-display text-3xl font-extrabold text-red-700 mt-2">{{ $stats['non_lues'] ?? 0 }}</p>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-amber-50/60 border border-amber-100/60 rounded-xl p-4 shadow-sm">
            <p class="text-[11px] text-amber-600 font-semibold uppercase tracking-[0.12em]">À traiter</p>
            <p class="font-display text-3xl font-extrabold text-amber-700 mt-2">{{ $stats['non_traitees'] ?? 0 }}</p>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-emerald-50/60 border border-emerald-100/60 rounded-xl p-4 shadow-sm">
            <p class="text-[11px] text-emerald-600 font-semibold uppercase tracking-[0.12em]">Traitées</p>
            <p class="font-display text-3xl font-extrabold text-emerald-700 mt-2">{{ $stats['traitees'] ?? 0 }}</p>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-sky-50/60 border border-sky-100/60 rounded-xl p-4 shadow-sm">
            <p class="text-[11px] text-sky-600 font-semibold uppercase tracking-[0.12em]">Info</p>
            <p class="font-display text-3xl font-extrabold text-sky-700 mt-2">{{ $stats['info'] ?? 0 }}</p>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-orange-50/60 border border-orange-100/60 rounded-xl p-4 shadow-sm">
            <p class="text-[11px] text-orange-600 font-semibold uppercase tracking-[0.12em]">Warning</p>
            <p class="font-display text-3xl font-extrabold text-orange-700 mt-2">{{ $stats['warning'] ?? 0 }}</p>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-white to-red-50/60 border border-red-100/60 rounded-xl p-4 shadow-sm">
            <p class="text-[11px] text-red-700 font-semibold uppercase tracking-[0.12em]">Critiques</p>
            <p class="font-display text-3xl font-extrabold text-red-800 mt-2">{{ $stats['critiques'] ?? 0 }}</p>
        </div>
    </div>

    <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/20 rounded-2xl border border-brand-100/60 shadow-card-brand">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gradient-to-r from-brand-50/60 via-white to-gold-50/30 border-b border-brand-100/60">
                        <th class="px-6 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Enseignant</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Date</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Type</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Gravité</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Message</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Pointage lié</th>
                        <th class="px-4 py-3.5 text-left text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">État</th>
                        <th class="px-4 py-3.5 text-center text-[10px] font-extrabold text-brand-700 uppercase tracking-[0.1em]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-50/60">
                    @forelse($alertes as $alerte)
                        @php
                            $graviteClass = match($alerte->gravite) {
                                'info' => 'text-sky-700 bg-sky-50 border-sky-200/60',
                                'warning' => 'text-orange-700 bg-orange-50 border-orange-200/60',
                                'critique' => 'text-red-700 bg-red-50 border-red-200/60',
                                default => 'text-gray-700 bg-gray-50 border-gray-200/60',
                            };
                        @endphp

                        <tr class="hover:bg-brand-50/30 transition-colors cursor-pointer"
                            onclick="window.location='{{ route('alertes-pointage.show', $alerte) }}'">
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    @if(!empty($alerte->enseignant->photo_path))
                                        <img src="{{ route('enseignants.photo', $alerte->enseignant) }}"
                                             alt="{{ $alerte->enseignant->nom_complet }}"
                                             class="w-10 h-10 rounded-full object-cover ring-2 ring-white shadow-sm flex-shrink-0">
                                    @else
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-[11px] font-extrabold shadow-sm ring-2 ring-white flex-shrink-0 bg-gradient-to-br from-brand-400 to-brand-600 text-white">
                                            {{ strtoupper(substr($alerte->enseignant->prenom ?? 'X', 0, 1)) }}{{ strtoupper(substr($alerte->enseignant->nom ?? 'X', 0, 1)) }}
                                        </div>
                                    @endif

                                    <div class="min-w-0">
                                        <p class="text-[13px] font-bold text-gray-900 truncate">{{ $alerte->enseignant->nom_complet ?? '—' }}</p>
                                        <p class="text-[11px] text-gray-400">{{ $alerte->enseignant->telephone ?? 'Téléphone non renseigné' }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3.5">
                                <p class="text-[12px] font-bold text-gray-800">{{ $alerte->date?->format('d/m/Y') ?: '—' }}</p>
                                <p class="text-[10px] text-gray-400">#{{ $alerte->id }}</p>
                            </td>

                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center text-[11px] font-bold text-blue-700 bg-blue-50 border border-blue-200/60 px-2.5 py-1 rounded-full">
                                    {{ $alerte->type_alerte_libelle }}
                                </span>
                            </td>

                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center text-[11px] font-bold border px-2.5 py-1 rounded-full {{ $graviteClass }}">
                                    {{ $alerte->gravite_libelle }}
                                </span>
                            </td>

                            <td class="px-4 py-3.5">
                                <div class="max-w-[320px]">
                                    <p class="text-[12px] text-gray-700 line-clamp-2">{{ $alerte->message }}</p>
                                </div>
                            </td>

                            <td class="px-4 py-3.5">
                                @if($alerte->pointage)
                                    <div class="space-y-1">
                                        <p class="text-[11px] font-bold text-gray-700">{{ $alerte->pointage->type_scan_libelle }}</p>
                                        <p class="text-[10px] text-gray-400">{{ $alerte->pointage->heure_scan }}</p>
                                    </div>
                                @else
                                    <span class="text-[11px] text-gray-300">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3.5">
                                <div class="space-y-1">
                                    @if(!$alerte->lue)
                                        <span class="inline-flex items-center text-[11px] font-bold text-red-700 bg-red-50 border border-red-200/60 px-2.5 py-1 rounded-full">
                                            Non lue
                                        </span>
                                    @else
                                        <span class="inline-flex items-center text-[11px] font-bold text-blue-700 bg-blue-50 border border-blue-200/60 px-2.5 py-1 rounded-full">
                                            Lue
                                        </span>
                                    @endif

                                    @if($alerte->traitee)
                                        <span class="inline-flex items-center text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200/60 px-2.5 py-1 rounded-full">
                                            Traitée
                                        </span>
                                    @else
                                        <span class="inline-flex items-center text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200/60 px-2.5 py-1 rounded-full">
                                            À traiter
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-4 py-3.5">
                                <div class="flex items-center justify-center gap-1" onclick="event.stopPropagation()">
                                    <a href="{{ route('alertes-pointage.show', $alerte) }}"
                                       class="p-2 text-gray-500 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors"
                                       title="Voir">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    @if(!$alerte->lue)
                                        <form method="POST" action="{{ route('alertes-pointage.lire', $alerte) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                    title="Marquer comme lue">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 4.945a2 2 0 002.22 0L21 8m-18 8h18a2 2 0 002-2V8a2 2 0 00-2-2H3a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    @if(!$alerte->traitee)
                                        <form method="POST" action="{{ route('alertes-pointage.traiter', $alerte) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="p-2 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                                    title="Marquer comme traitée">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-20 h-20 bg-gradient-to-br from-brand-100 to-brand-50 rounded-full flex items-center justify-center mb-4 shadow-card-brand">
                                        <svg class="w-10 h-10 text-brand-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-7.938 4h15.876c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L2.33 17c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                    </div>
                                    <p class="font-display text-base font-bold text-gray-700">Aucune alerte trouvée</p>
                                    <p class="text-sm text-gray-400 mt-1">Ajustez les filtres ou changez la date sélectionnée.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($alertes->hasPages())
            <div class="px-6 py-4 border-t border-brand-100/60 bg-gradient-to-r from-brand-50/40 to-transparent">
                {{ $alertes->links() }}
            </div>
        @endif
    </div>
</div>
@endsection