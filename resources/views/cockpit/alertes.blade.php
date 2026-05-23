@extends('layouts.app')
@section('title', 'Alertes financières')
@section('page-title', 'Alertes financières')

@section('content')
@php $money = fn($v) => number_format((float) $v, 0, ',', ' '); @endphp

<div class="space-y-6">
    @include('cockpit._nav')

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('cockpit.alertes', ['gravite' => 'critique']) }}" class="bg-white rounded-2xl border border-red-100 p-5 shadow-card hover:shadow-card-hover transition">
            <p class="text-xs font-bold uppercase text-red-600 tracking-wider">Critiques</p>
            <p class="text-3xl font-extrabold text-red-700 mt-2">{{ $stats['critiques'] }}</p>
        </a>
        <a href="{{ route('cockpit.alertes', ['gravite' => 'warning']) }}" class="bg-white rounded-2xl border border-amber-100 p-5 shadow-card hover:shadow-card-hover transition">
            <p class="text-xs font-bold uppercase text-amber-600 tracking-wider">Warnings</p>
            <p class="text-3xl font-extrabold text-amber-700 mt-2">{{ $stats['warnings'] }}</p>
        </a>
        <a href="{{ route('cockpit.alertes', ['gravite' => 'info']) }}" class="bg-white rounded-2xl border border-blue-100 p-5 shadow-card hover:shadow-card-hover transition">
            <p class="text-xs font-bold uppercase text-blue-600 tracking-wider">Infos</p>
            <p class="text-3xl font-extrabold text-blue-700 mt-2">{{ $stats['infos'] }}</p>
        </a>
        <a href="{{ route('cockpit.alertes', ['statut' => 'traitees']) }}" class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card hover:shadow-card-hover transition">
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Traitées</p>
            <p class="text-3xl font-extrabold text-gray-700 mt-2">{{ $stats['traitees'] }}</p>
        </a>
    </div>

    {{-- Filtres --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Statut</label>
                <select name="statut" class="rounded-xl border-gray-200 text-sm focus:border-violet-400 focus:ring-violet-100">
                    <option value="">Tous</option>
                    <option value="non_traitees" @selected(request('statut') === 'non_traitees')>Non traitées</option>
                    <option value="traitees" @selected(request('statut') === 'traitees')>Traitées</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Gravité</label>
                <select name="gravite" class="rounded-xl border-gray-200 text-sm focus:border-violet-400 focus:ring-violet-100">
                    <option value="">Toutes</option>
                    <option value="critique" @selected(request('gravite') === 'critique')>Critique</option>
                    <option value="warning" @selected(request('gravite') === 'warning')>Warning</option>
                    <option value="info" @selected(request('gravite') === 'info')>Info</option>
                </select>
            </div>
            <button class="px-4 py-2 bg-violet-600 text-white text-sm font-bold rounded-xl hover:bg-violet-700">Filtrer</button>
            <a href="{{ route('cockpit.alertes') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-bold rounded-xl">⟲</a>
        </form>
    </div>

    {{-- Liste --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-extrabold text-gray-900">Toutes les alertes</h3>
            <span class="text-xs font-semibold text-gray-500">{{ $alertes->total() }} résultat(s)</span>
        </div>

        @if($alertes->isEmpty())
            <div class="px-5 py-16 text-center">
                <p class="text-4xl mb-3">🎉</p>
                <p class="font-bold text-gray-800">Aucune alerte</p>
                <p class="text-sm text-gray-500 mt-1">Votre situation financière est saine.</p>
            </div>
        @else
            <div class="divide-y divide-gray-100" x-data="{ openId: null }">
                @foreach($alertes as $a)
                    @php $g = ['critique' => 'red', 'warning' => 'amber', 'info' => 'blue'][$a->gravite] ?? 'gray'; @endphp
                    <div class="px-5 py-4 hover:bg-gray-50">
                        <div class="flex items-start gap-4">
                            <span class="flex-shrink-0 w-10 h-10 rounded-full bg-{{ $g }}-100 flex items-center justify-center text-lg">
                                @if($a->gravite === 'critique') ⛔ @elseif($a->gravite === 'warning') ⚠ @else ℹ @endif
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-bold text-gray-900">{{ $a->titre }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $a->type }} · {{ $a->created_at?->diffForHumans() }}</p>
                                    </div>
                                    @if($a->traitee)
                                        <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-700">✓ Traitée</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold bg-{{ $g }}-100 text-{{ $g }}-700">{{ ucfirst($a->gravite) }}</span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-700 mt-2">{{ $a->message }}</p>
                                @if($a->recommandation_ia)
                                    <div class="mt-2 p-2 bg-violet-50 border border-violet-100 rounded-lg text-xs text-violet-900">
                                        <b>💡 Recommandation IA :</b> {{ $a->recommandation_ia }}
                                    </div>
                                @endif
                                @if($a->montant_concerne)
                                    <p class="text-xs font-bold text-{{ $g }}-700 mt-2">Montant concerné : {{ $money($a->montant_concerne) }} F</p>
                                @endif

                                @if(! $a->traitee)
                                    <div class="mt-3">
                                        <button @click="openId = openId === {{ $a->id }} ? null : {{ $a->id }}" class="text-xs font-bold text-{{ $g }}-700 hover:underline">Marquer comme traitée →</button>
                                        <form x-show="openId === {{ $a->id }}" x-cloak method="POST" action="{{ route('cockpit.alertes.traiter', $a->id) }}" class="mt-2 flex gap-2">
                                            @csrf
                                            <input name="action_prise" placeholder="Action prise (facultatif)" class="flex-1 rounded-xl border-gray-200 text-xs focus:border-{{ $g }}-400" />
                                            <button class="px-3 py-1.5 bg-{{ $g }}-600 text-white text-xs font-bold rounded-xl">Confirmer</button>
                                        </form>
                                    </div>
                                @elseif($a->action_prise)
                                    <p class="text-xs text-emerald-700 mt-2"><b>Action prise :</b> {{ $a->action_prise }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="px-5 py-4 border-t border-gray-100">{{ $alertes->links() }}</div>
        @endif
    </div>
</div>

@push('styles')<style>[x-cloak]{display:none!important}</style>@endpush
@endsection
