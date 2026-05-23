@extends('layouts.app')
@section('title', 'Communication')
@section('page-title', 'Communication')
@section('page-subtitle', 'Annonces, messages et notifications')

@section('content')
@php
    $typeBadge = [
        'annonce'    => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'icon' => '📢'],
        'circulaire' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-700', 'icon' => '📄'],
        'convocation' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'icon' => '📨'],
        'evenement'  => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'icon' => '🎉'],
        'urgent'     => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => '🚨'],
    ];
    $tab = $tab ?? 'annonces';
@endphp

<div class="space-y-6" x-data="{ modalAnnonce: false }">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-rose-700 rounded-xl flex items-center justify-center shadow-card-violet">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Module 8</p>
                <h2 class="font-display text-2xl font-extrabold text-gray-900">Centre de communication</h2>
            </div>
        </div>
        <button @click="modalAnnonce = true" class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-pink-500 to-rose-700 text-white shadow-card-violet hover:shadow-lg transition flex items-center gap-2 self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Nouvelle annonce
        </button>
    </div>

    {{-- KPIs --}}
    <section class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-2xl border border-emerald-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-emerald-600 tracking-wider">Annonces publiées</p>
            <p class="text-2xl font-extrabold text-emerald-700 mt-2">{{ $stats['annonces_publiees'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-amber-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-amber-600 tracking-wider">Brouillons</p>
            <p class="text-2xl font-extrabold text-amber-700 mt-2">{{ $stats['annonces_brouillon'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-blue-100 p-5 shadow-card-blue">
            <p class="text-xs font-bold uppercase text-blue-600 tracking-wider">Messages</p>
            <p class="text-2xl font-extrabold text-blue-700 mt-2">{{ $stats['messages_total'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-violet-100 p-5 shadow-card-violet">
            <p class="text-xs font-bold uppercase text-violet-600 tracking-wider">Notifications</p>
            <p class="text-2xl font-extrabold text-violet-700 mt-2">{{ $stats['notif_total'] }}</p>
            <p class="text-xs text-violet-500 mt-1">{{ $stats['notif_envoyees'] }} envoyée(s)</p>
        </div>
        <div class="bg-white rounded-2xl border border-rose-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-rose-600 tracking-wider">Notifs non lues</p>
            <p class="text-2xl font-extrabold text-rose-700 mt-2">{{ $stats['notif_non_lues'] }}</p>
        </div>
    </section>

    {{-- Tabs --}}
    <nav class="flex flex-wrap gap-2 border-b border-gray-200">
        @php
            $tabs = [
                'annonces' => ['label' => '📢 Annonces', 'count' => $stats['annonces_publiees'] + $stats['annonces_brouillon']],
                'messages' => ['label' => '💬 Messages', 'count' => $stats['messages_total']],
                'notifications' => ['label' => '🔔 Notifications', 'count' => $stats['notif_total']],
            ];
        @endphp
        @foreach($tabs as $key => $t)
            <a href="{{ route('communication.index', ['tab' => $key]) }}"
               class="px-4 py-2 text-sm font-bold border-b-2 transition
                   {{ $tab === $key
                        ? 'border-pink-600 text-pink-700'
                        : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                {{ $t['label'] }} <span class="text-xs opacity-60">({{ $t['count'] }})</span>
            </a>
        @endforeach
    </nav>

    {{-- Annonces tab --}}
    @if($tab === 'annonces')
        <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
            @if($annonces->isEmpty())
                <div class="px-5 py-16 text-center">
                    <p class="text-4xl mb-3">📢</p>
                    <p class="font-bold text-gray-800">Aucune annonce</p>
                    <p class="text-sm text-gray-500 mt-1 mb-4">Créez votre première annonce pour la communauté scolaire.</p>
                    <button @click="modalAnnonce = true" class="inline-flex items-center px-4 py-2 bg-pink-600 text-white text-sm font-bold rounded-xl">Nouvelle annonce</button>
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($annonces as $a)
                        @php $tb = $typeBadge[$a->type] ?? $typeBadge['annonce']; @endphp
                        <div class="px-5 py-4 hover:bg-gray-50">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <span class="text-lg">{{ $tb['icon'] }}</span>
                                        <h4 class="font-bold text-gray-900">{{ $a->titre }}</h4>
                                        <span class="inline-flex px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase {{ $tb['bg'] }} {{ $tb['text'] }}">{{ $a->type }}</span>
                                        <span class="inline-flex px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase bg-gray-100 text-gray-600">→ {{ $a->audience }}</span>
                                        @if($a->publiee)
                                            <span class="inline-flex px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-100 text-emerald-700">✓ Publiée</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-100 text-amber-700">Brouillon</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-700 line-clamp-2">{{ $a->contenu }}</p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        par <b>{{ $a->auteur?->name ?? '—' }}</b> ·
                                        Du {{ $a->date_debut_affichage?->format('d/m/Y') }}
                                        @if($a->date_fin_affichage) au {{ $a->date_fin_affichage->format('d/m/Y') }}@endif
                                        @if($a->envoyer_sms) · 📱 SMS @endif
                                        @if($a->envoyer_notification) · 🔔 Notif @endif
                                    </p>
                                </div>
                                <div class="flex flex-col gap-1 flex-shrink-0">
                                    @if(! $a->publiee)
                                        <form method="POST" action="{{ route('communication.annonces.publier', $a->id) }}">
                                            @csrf
                                            <button class="text-xs font-bold text-emerald-700 hover:text-emerald-900">✓ Publier</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('communication.annonces.destroy', $a->id) }}" onsubmit="return confirm('Supprimer cette annonce ?')">
                                        @csrf @method('DELETE')
                                        <button class="text-xs font-bold text-red-600 hover:text-red-800">🗑</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="px-5 py-4 border-t border-gray-100">{{ $annonces->links() }}</div>
            @endif
        </div>
    @endif

    {{-- Messages tab --}}
    @if($tab === 'messages')
        <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
            @if($messages->isEmpty())
                <div class="px-5 py-16 text-center">
                    <p class="text-4xl mb-3">💬</p>
                    <p class="font-bold text-gray-800">Aucun message</p>
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($messages as $m)
                        <div class="px-5 py-4 hover:bg-gray-50">
                            <div class="flex items-start gap-3">
                                <span class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm flex-shrink-0">{{ mb_substr($m->expediteur?->name ?? '?', 0, 1) }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="font-bold text-gray-900">{{ $m->sujet }}</p>
                                        @if($m->important)<span class="inline-flex px-2 py-0.5 rounded-lg text-[10px] font-bold bg-red-100 text-red-700">⭐ Important</span>@endif
                                        @if(! $m->lu)<span class="w-2 h-2 rounded-full bg-blue-600"></span>@endif
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        {{ $m->expediteur?->name ?? 'Inconnu' }} → {{ $m->destinataire?->name ?? ucfirst(str_replace('_', ' ', $m->type_destinataire)) }}
                                        · {{ $m->created_at?->diffForHumans() }}
                                    </p>
                                    <p class="text-sm text-gray-700 mt-1 line-clamp-2">{{ $m->contenu }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="px-5 py-4 border-t border-gray-100">{{ $messages->links() }}</div>
            @endif
        </div>
    @endif

    {{-- Notifications tab --}}
    @if($tab === 'notifications')
        <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
            @if($notifications->isEmpty())
                <div class="px-5 py-16 text-center">
                    <p class="text-4xl mb-3">🔔</p>
                    <p class="font-bold text-gray-800">Aucune notification</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3 text-left font-bold">Titre</th>
                                <th class="px-5 py-3 text-left font-bold">Destinataire</th>
                                <th class="px-5 py-3 text-left font-bold">Canal</th>
                                <th class="px-5 py-3 text-left font-bold">Type</th>
                                <th class="px-5 py-3 text-center font-bold">Envoyée</th>
                                <th class="px-5 py-3 text-center font-bold">Lue</th>
                                <th class="px-5 py-3 text-left font-bold">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($notifications as $n)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3">
                                        <div class="font-semibold text-gray-900">{{ $n->titre }}</div>
                                        <div class="text-xs text-gray-500 line-clamp-1">{{ $n->message }}</div>
                                    </td>
                                    <td class="px-5 py-3 text-xs text-gray-700">{{ $n->user?->name ?? '—' }}</td>
                                    <td class="px-5 py-3">
                                        @php $cc = ['app' => 'bg-blue-100 text-blue-700', 'sms' => 'bg-emerald-100 text-emerald-700', 'email' => 'bg-violet-100 text-violet-700', 'whatsapp' => 'bg-green-100 text-green-700'][$n->canal] ?? 'bg-gray-100 text-gray-700'; @endphp
                                        <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold {{ $cc }}">{{ ucfirst($n->canal) }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-xs text-gray-600">{{ $n->type }}</td>
                                    <td class="px-5 py-3 text-center">{!! $n->envoyee ? '<span class="text-emerald-600 font-bold">✓</span>' : '<span class="text-gray-300">○</span>' !!}</td>
                                    <td class="px-5 py-3 text-center">{!! $n->lue ? '<span class="text-emerald-600 font-bold">✓</span>' : '<span class="text-gray-300">○</span>' !!}</td>
                                    <td class="px-5 py-3 text-xs text-gray-500">{{ $n->created_at?->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-4 border-t border-gray-100">{{ $notifications->links() }}</div>
            @endif
        </div>
    @endif

    {{-- Modal nouvelle annonce --}}
    <div x-show="modalAnnonce" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="modalAnnonce = false">
        <form method="POST" action="{{ route('communication.annonces.store') }}" class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            @csrf
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-extrabold text-gray-900">Nouvelle annonce</h3>
                <button type="button" @click="modalAnnonce = false" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Titre *</label>
                    <input name="titre" required maxlength="200" class="w-full rounded-xl border-gray-200 text-sm focus:border-pink-400 focus:ring-pink-100" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Type *</label>
                        <select name="type" required class="w-full rounded-xl border-gray-200 text-sm focus:border-pink-400 focus:ring-pink-100">
                            <option value="annonce">📢 Annonce</option>
                            <option value="circulaire">📄 Circulaire</option>
                            <option value="convocation">📨 Convocation</option>
                            <option value="evenement">🎉 Événement</option>
                            <option value="urgent">🚨 Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Audience *</label>
                        <select name="audience" required class="w-full rounded-xl border-gray-200 text-sm focus:border-pink-400 focus:ring-pink-100">
                            <option value="tous">Tous</option>
                            <option value="parents">Parents</option>
                            <option value="enseignants">Enseignants</option>
                            <option value="eleves">Élèves</option>
                            <option value="personnel">Personnel</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Date début *</label>
                        <input name="date_debut_affichage" type="date" value="{{ now()->format('Y-m-d') }}" required class="w-full rounded-xl border-gray-200 text-sm focus:border-pink-400" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Date fin (facultative)</label>
                        <input name="date_fin_affichage" type="date" class="w-full rounded-xl border-gray-200 text-sm focus:border-pink-400" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Contenu *</label>
                    <textarea name="contenu" rows="6" required class="w-full rounded-xl border-gray-200 text-sm focus:border-pink-400 focus:ring-pink-100"></textarea>
                </div>
                <div class="space-y-2 p-3 bg-gray-50 rounded-xl">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="envoyer_notification" value="1" checked class="rounded" />
                        <span class="font-semibold text-gray-700">🔔 Envoyer une notification in-app</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="envoyer_sms" value="1" class="rounded" />
                        <span class="font-semibold text-gray-700">📱 Envoyer aussi par SMS</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm pt-2 border-t border-gray-200">
                        <input type="checkbox" name="publier_maintenant" value="1" checked class="rounded text-emerald-600" />
                        <span class="font-bold text-emerald-700">✓ Publier maintenant</span>
                        <span class="text-xs text-gray-500">(sinon : brouillon)</span>
                    </label>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-2 justify-end">
                <button type="button" @click="modalAnnonce = false" class="px-4 py-2 text-sm font-bold text-gray-600">Annuler</button>
                <button type="submit" class="px-6 py-2 bg-pink-600 text-white text-sm font-bold rounded-xl hover:bg-pink-700">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

@push('styles')<style>[x-cloak]{display:none!important}.line-clamp-2{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}.line-clamp-1{display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden;}</style>@endpush
@endsection
