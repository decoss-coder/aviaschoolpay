@extends('layouts.app')
@section('title', 'Centre SMS')
@section('page-title', 'Centre SMS')
@section('page-subtitle', 'Envoi, recharge et relances SMS via Infobip')

@section('content')
@php $money = fn($v) => number_format((float) $v, 0, ',', ' '); @endphp

<div class="space-y-6" x-data="{ modalRecharge: false, modalRelance: false, modalManuel: false, apercu: null, loadingApercu: false }">

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-sky-500 to-indigo-700 rounded-xl flex items-center justify-center shadow-card-blue">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Communication SMS</p>
                <h2 class="font-display text-2xl font-extrabold text-gray-900">Centre SMS</h2>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <button @click="modalRecharge = true" class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow-card-gold hover:shadow-lg transition flex items-center gap-2">
                💳 Recharger SMS
            </button>
            <button @click="modalRelance = true" class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-rose-500 to-pink-700 text-white shadow-card-violet hover:shadow-lg transition flex items-center gap-2">
                📣 Relance impayés
            </button>
            <button @click="modalManuel = true" class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-sky-600 text-white shadow-card-blue hover:shadow-lg transition flex items-center gap-2">
                ✉ SMS manuel
            </button>
        </div>
    </div>

    {{-- Solde principal --}}
    <div class="bg-gradient-to-br from-sky-500 via-indigo-600 to-purple-700 rounded-2xl p-6 lg:p-8 shadow-card-violet text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <div class="relative grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div>
                <p class="text-xs font-bold uppercase text-sky-100 tracking-wider">📲 Solde SMS</p>
                <p class="text-5xl font-extrabold mt-2">{{ number_format($credit->solde, 0, ',', ' ') }}</p>
                <p class="text-xs text-sky-100 mt-1">SMS disponibles · {{ $prixUnitaire }} F / SMS</p>
            </div>
            <div class="lg:border-l lg:border-white/20 lg:pl-5">
                <p class="text-xs font-bold uppercase text-sky-100 tracking-wider">Statistiques</p>
                <div class="mt-3 space-y-1.5 text-sm">
                    <p><span class="opacity-80">Envoyés ce mois :</span> <b>{{ number_format($stats['envoyes_mois'], 0, ',', ' ') }}</b></p>
                    <p><span class="opacity-80">Échecs ce mois :</span> <b>{{ $stats['echecs_mois'] }}</b></p>
                    <p><span class="opacity-80">Cumul recharge :</span> <b>{{ number_format($credit->cumul_recharge, 0, ',', ' ') }}</b></p>
                    <p><span class="opacity-80">Cumul envoyé :</span> <b>{{ number_format($credit->cumul_envoye, 0, ',', ' ') }}</b></p>
                </div>
            </div>
            <div class="lg:border-l lg:border-white/20 lg:pl-5">
                <p class="text-xs font-bold uppercase text-sky-100 tracking-wider">Investi à Avia</p>
                <p class="text-2xl font-extrabold mt-2">{{ $money($credit->cumul_paye_fcfa) }} <span class="text-sm">F</span></p>
                <p class="text-xs text-sky-100 mt-1">Cumul recharges payées</p>
                @if(! $waveConfigure)
                    <div class="mt-3 p-2 bg-amber-400/30 rounded-lg text-xs">
                        ⚠ Lien Wave Avia non configuré côté plateforme
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Historique recharges --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-extrabold text-gray-900">💳 Historique des recharges</h3>
            <span class="text-xs font-semibold text-gray-500">{{ $recharges->count() }}</span>
        </div>
        @if($recharges->isEmpty())
            <div class="px-5 py-12 text-center text-sm text-gray-500">Aucune recharge effectuée. Cliquez sur « Recharger SMS » pour commencer.</div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-bold">Référence</th>
                        <th class="px-5 py-3 text-center font-bold">Nb SMS</th>
                        <th class="px-5 py-3 text-right font-bold">Montant</th>
                        <th class="px-5 py-3 text-center font-bold">Statut</th>
                        <th class="px-5 py-3 text-left font-bold">Date</th>
                        <th class="px-5 py-3 text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($recharges as $r)
                        @php
                            $sb = [
                                'en_attente_paiement' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => '⏳ À payer'],
                                'paye'                => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => '💵 Payé · attente Avia'],
                                'credite'             => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'label' => '✓ Crédité'],
                                'annule'              => ['bg' => 'bg-gray-100', 'text' => 'text-gray-500', 'label' => 'Annulé'],
                                'expire'              => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Expiré'],
                            ][$r->statut];
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-mono text-xs">{{ $r->reference }}</td>
                            <td class="px-5 py-3 text-center font-bold">{{ number_format($r->nb_sms, 0, ',', ' ') }}</td>
                            <td class="px-5 py-3 text-right font-extrabold text-amber-700">{{ $money($r->montant_fcfa) }} F</td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold {{ $sb['bg'] }} {{ $sb['text'] }}">{{ $sb['label'] }}</span>
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-500">{{ $r->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-right">
                                @if($r->statut === 'en_attente_paiement')
                                    @if($r->wave_checkout_url)
                                        <a href="{{ $r->wave_checkout_url }}" target="_blank" class="text-xs font-bold text-blue-600 hover:text-blue-800">🌊 Payer Wave</a>
                                        <span class="text-gray-300 mx-1">·</span>
                                    @endif
                                    <form method="POST" action="{{ route('sms.recharges.annuler', $r->id) }}" class="inline" onsubmit="return confirm('Annuler cette recharge ?')">
                                        @csrf
                                        <button class="text-xs font-bold text-red-600 hover:text-red-800">Annuler</button>
                                    </form>
                                @elseif($r->statut === 'paye')
                                    <span class="text-xs text-blue-700">Avia traite votre paiement</span>
                                @elseif($r->statut === 'credite')
                                    <span class="text-xs text-emerald-700">Crédité {{ $r->credite_at?->diffForHumans() }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Derniers envois --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-extrabold text-gray-900">📤 Derniers envois</h3>
            <span class="text-xs font-semibold text-gray-500">{{ $envois->count() }} récents</span>
        </div>
        @if($envois->isEmpty())
            <div class="px-5 py-12 text-center text-sm text-gray-500">Aucun envoi SMS pour le moment.</div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-bold">Date</th>
                        <th class="px-5 py-3 text-left font-bold">Destinataire</th>
                        <th class="px-5 py-3 text-left font-bold">Contenu</th>
                        <th class="px-5 py-3 text-center font-bold">Type</th>
                        <th class="px-5 py-3 text-center font-bold">Statut</th>
                        <th class="px-5 py-3 text-center font-bold">Parties</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($envois as $e)
                        @php
                            $sb = [
                                'en_attente' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => '⏳'],
                                'envoye'     => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'label' => '✓'],
                                'echec'      => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => '✕'],
                                'recu'       => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => '✓✓'],
                            ][$e->statut] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => $e->statut];
                            $tc = [
                                'relance_impaye' => 'bg-rose-100 text-rose-700',
                                'annonce'        => 'bg-blue-100 text-blue-700',
                                'note'           => 'bg-violet-100 text-violet-700',
                                'manuel'         => 'bg-gray-100 text-gray-700',
                            ][$e->type] ?? 'bg-gray-100 text-gray-700';
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-xs text-gray-500">{{ $e->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3">
                                <p class="font-mono text-xs">{{ $e->destinataire }}</p>
                                @if($e->destinataire_nom)<p class="text-xs text-gray-500">{{ $e->destinataire_nom }}</p>@endif
                            </td>
                            <td class="px-5 py-3 max-w-xs">
                                <p class="text-xs text-gray-700 line-clamp-2">{{ $e->contenu }}</p>
                                @if($e->statut === 'echec' && $e->erreur)
                                    <p class="text-xs text-red-600 mt-1">⚠ {{ $e->erreur }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded-lg text-xs font-bold {{ $tc }}">{{ str_replace('_', ' ', $e->type) }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold {{ $sb['bg'] }} {{ $sb['text'] }}">{{ $sb['label'] }}</span>
                            </td>
                            <td class="px-5 py-3 text-center text-xs font-bold text-gray-600">{{ $e->nb_parties }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ═══════════ MODAL RECHARGE ═══════════ --}}
    <div x-show="modalRecharge" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="modalRecharge = false">
        <form method="POST" action="{{ route('sms.recharger') }}" class="bg-white rounded-2xl shadow-xl w-full max-w-md" x-data="{ nb: 100, prix: {{ $prixUnitaire }} }">
            @csrf
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-extrabold text-gray-900">💳 Recharger le compte SMS</h3>
                <p class="text-xs text-gray-500 mt-1">Paiement via Wave vers Avia Technologie</p>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Nombre de SMS *</label>
                    <input type="number" name="nb_sms" x-model.number="nb" min="50" max="100000" step="50" required class="w-full rounded-xl border-gray-200 text-lg font-bold focus:border-amber-400" />
                    <div class="flex gap-1 mt-2">
                        <button type="button" @click="nb = 100" class="px-2 py-1 text-xs bg-gray-100 rounded">100</button>
                        <button type="button" @click="nb = 500" class="px-2 py-1 text-xs bg-gray-100 rounded">500</button>
                        <button type="button" @click="nb = 1000" class="px-2 py-1 text-xs bg-gray-100 rounded">1 000</button>
                        <button type="button" @click="nb = 5000" class="px-2 py-1 text-xs bg-gray-100 rounded">5 000</button>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-4 border-2 border-amber-200">
                    <div class="flex items-baseline justify-between">
                        <span class="text-xs font-bold uppercase text-amber-800">Montant à payer</span>
                        <span class="text-3xl font-extrabold text-amber-700"><span x-text="(nb * prix).toLocaleString('fr-FR')"></span> F</span>
                    </div>
                    <p class="text-xs text-amber-700 mt-2"><span x-text="nb"></span> SMS × {{ $prixUnitaire }} F / SMS</p>
                </div>
                <div class="bg-blue-50 rounded-xl p-3 text-xs text-blue-900">
                    ℹ Après création, vous obtiendrez un lien Wave. Une fois le paiement effectué, <b>Avia créditera vos SMS sous 24h</b>.
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-2 justify-end">
                <button type="button" @click="modalRecharge = false" class="px-4 py-2 text-sm font-bold text-gray-600">Annuler</button>
                <button type="submit" class="px-6 py-2 bg-amber-600 text-white text-sm font-bold rounded-xl hover:bg-amber-700">Créer la recharge</button>
            </div>
        </form>
    </div>

    {{-- ═══════════ MODAL RELANCE IMPAYÉS ═══════════ --}}
    <div x-show="modalRelance" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="modalRelance = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-gray-900">📣 Relance des impayés</h3>
                    <p class="text-xs text-gray-500 mt-1">Envoie un SMS aux parents des élèves non soldés</p>
                </div>
                <button @click="modalRelance = false" class="text-gray-400 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <form method="POST" action="{{ route('sms.relance.envoyer') }}">
                @csrf
                <div class="p-6 space-y-4">
                    <button type="button" @click="loadingApercu = true; fetch('{{ route('sms.relance.apercu') }}').then(r=>r.json()).then(d=>{ apercu=d; loadingApercu=false; })"
                            class="w-full px-4 py-2.5 bg-blue-100 text-blue-800 text-sm font-bold rounded-xl hover:bg-blue-200">
                        <span x-show="!loadingApercu">🔍 Calculer l'aperçu (gratuit)</span>
                        <span x-show="loadingApercu">⏳ Chargement…</span>
                    </button>

                    <div x-show="apercu" class="space-y-3">
                        <div class="grid grid-cols-3 gap-2">
                            <div class="bg-blue-50 rounded-xl p-3 text-center">
                                <p class="text-xs text-blue-700 font-bold">Destinataires</p>
                                <p class="text-2xl font-extrabold text-blue-900" x-text="apercu?.nb_destinataires"></p>
                            </div>
                            <div class="bg-amber-50 rounded-xl p-3 text-center">
                                <p class="text-xs text-amber-700 font-bold">Coût</p>
                                <p class="text-2xl font-extrabold text-amber-900"><span x-text="apercu?.cout_estime?.toLocaleString('fr-FR')"></span> F</p>
                            </div>
                            <div class="bg-emerald-50 rounded-xl p-3 text-center">
                                <p class="text-xs text-emerald-700 font-bold">Solde actuel</p>
                                <p class="text-2xl font-extrabold text-emerald-900" x-text="apercu?.solde_actuel?.toLocaleString('fr-FR')"></p>
                            </div>
                        </div>

                        <div x-show="apercu && apercu.nb_destinataires > apercu.solde_actuel" class="rounded-xl bg-red-50 border border-red-200 p-3 text-xs text-red-800">
                            ⚠ Solde SMS insuffisant — rechargez avant d'envoyer.
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Modèle du message</label>
                        <textarea name="modele" rows="3" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400" placeholder="Bonjour, l'eleve {ELEVE} ({CLASSE}) a un reste de {RESTE} F. Merci de regulariser.">Bonjour, l'eleve {ELEVE} ({CLASSE}) a un reste de {RESTE} F CFA a regler. Merci de regulariser au plus tot. {ETABLISSEMENT}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Variables : <code>{ELEVE}</code> <code>{CLASSE}</code> <code>{RESTE}</code> <code>{ETABLISSEMENT}</code></p>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex gap-2 justify-end">
                    <button type="button" @click="modalRelance = false" class="px-4 py-2 text-sm font-bold text-gray-600">Annuler</button>
                    <button type="submit" onclick="return confirm('Envoyer les SMS de relance maintenant ?')" class="px-6 py-2 bg-rose-600 text-white text-sm font-bold rounded-xl hover:bg-rose-700">
                        🚀 Envoyer la campagne
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════ MODAL SMS MANUEL ═══════════ --}}
    <div x-show="modalManuel" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="modalManuel = false">
        <form method="POST" action="{{ route('sms.envoyer') }}" class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
            @csrf
            <div class="px-6 py-4 border-b border-gray-100"><h3 class="font-extrabold text-gray-900">✉ Envoyer un SMS</h3></div>
            <div class="p-6 space-y-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Destinataire (téléphone) *</label>
                    <input name="destinataire" required placeholder="07 12 34 56 78" class="w-full rounded-xl border-gray-200 text-sm focus:border-sky-400" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Nom (facultatif)</label>
                    <input name="nom" maxlength="200" class="w-full rounded-xl border-gray-200 text-sm focus:border-sky-400" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Message *</label>
                    <textarea name="contenu" rows="4" required maxlength="1500" class="w-full rounded-xl border-gray-200 text-sm focus:border-sky-400"></textarea>
                    <p class="text-xs text-gray-500 mt-1">160 caractères = 1 SMS · {{ $prixUnitaire }} F par SMS</p>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-2 justify-end">
                <button type="button" @click="modalManuel = false" class="px-4 py-2 text-sm font-bold text-gray-600">Annuler</button>
                <button type="submit" class="px-6 py-2 bg-sky-600 text-white text-sm font-bold rounded-xl">Envoyer</button>
            </div>
        </form>
    </div>
</div>

@push('styles')<style>[x-cloak]{display:none!important}.line-clamp-2{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}</style>@endpush
@endsection
