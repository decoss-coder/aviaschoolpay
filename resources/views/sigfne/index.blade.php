@extends('layouts.app')
@section('title', 'SIGFNE / DESPS')
@section('page-title', 'Conformité SIGFNE / DESPS')
@section('page-subtitle', 'Remontée des moyennes trimestrielles vers ' . strtoupper($plateforme))

@section('content')
<div class="space-y-6" x-data="sigfnePage()">

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif

    {{-- ═══════════ BANNIÈRE DESPS ═══════════ --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-orange-500 via-amber-600 to-orange-700 p-6 shadow-card-gold text-white">
        <div class="absolute top-0 right-0 w-48 h-48 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"></div>
        <div class="relative grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2">
                <p class="text-xs font-bold uppercase tracking-wider text-amber-100">Direction du Suivi et de la Performance Scolaire</p>
                <h1 class="font-display text-2xl font-extrabold mt-1">{{ strtoupper($plateforme) }} — SIGFNE</h1>
                <p class="text-sm text-amber-100 mt-2">
                    Système Intégré de Gestion du Fichier National des Élèves
                </p>
                <div class="flex flex-wrap items-center gap-3 mt-3">
                    <span class="px-3 py-1 bg-white/15 rounded-lg text-xs">
                        Code établissement : <b class="font-mono text-white">{{ $etab->code_desps ?? '—' }}</b>
                    </span>
                    @if($etab->sigfne_actif)
                        <span class="px-3 py-1 bg-emerald-400/30 rounded-lg text-xs font-bold">✓ Sync API activée</span>
                    @else
                        <span class="px-3 py-1 bg-white/15 rounded-lg text-xs">⚠ Sync API désactivée</span>
                    @endif
                </div>
            </div>
            <div class="bg-white/10 backdrop-blur rounded-xl p-4">
                <p class="text-xs font-bold uppercase text-amber-100">Plateforme officielle</p>
                <a href="{{ $urlPlateforme }}/vas/agcs-moyenne/" target="_blank" class="text-sm font-bold underline hover:text-white block mt-1 break-all">
                    {{ str_replace(['https://', 'http://'], '', $urlPlateforme) }}
                </a>
                @if($etab->sigfne_derniere_sync)
                    <p class="text-xs text-amber-100 mt-2">Dernière sync : {{ $etab->sigfne_derniere_sync->diffForHumans() }}</p>
                @else
                    <p class="text-xs text-amber-100 mt-2">Jamais synchronisé via API</p>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════ TRIMESTRES — WORKFLOW ═══════════ --}}
    <div>
        <h2 class="font-display text-lg font-extrabold text-gray-900 mb-3">📅 Trimestres — état des remontées</h2>
        @if($trimestres->isEmpty())
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                ⚠ Aucun trimestre configuré pour l'année en cours.
            </div>
        @else
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach($trimestres as $trim)
                @php
                    $derniereRemontee = $remontees->where('trimestre_id', $trim->id)->first();
                    $statutRemontee = $derniereRemontee?->statut;
                    $border = $statutRemontee === 'envoye' ? 'border-emerald-300' : ($statutRemontee === 'pret_envoi' ? 'border-blue-300' : ($trim->en_cours ?? false ? 'border-amber-400' : 'border-gray-200'));
                @endphp
                <div class="bg-white rounded-2xl border-2 {{ $border }} shadow-card overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="font-display text-lg font-extrabold text-gray-900">{{ $trim->libelle }}</p>
                            <p class="text-xs text-gray-500">{{ $trim->date_debut?->format('d/m') }} → {{ $trim->date_fin?->format('d/m/Y') }}</p>
                        </div>
                        @if(! empty($trim->en_cours))
                            <span class="px-2 py-1 rounded-lg bg-amber-100 text-amber-700 text-xs font-bold">En cours</span>
                        @elseif($statutRemontee === 'envoye')
                            <span class="px-2 py-1 rounded-lg bg-emerald-100 text-emerald-700 text-xs font-bold">✓ Transmis</span>
                        @elseif($statutRemontee === 'pret_envoi')
                            <span class="px-2 py-1 rounded-lg bg-blue-100 text-blue-700 text-xs font-bold">📤 À uploader</span>
                        @elseif($statutRemontee === 'erreur' || $statutRemontee === 'erreur_api')
                            <span class="px-2 py-1 rounded-lg bg-red-100 text-red-700 text-xs font-bold">⚠ Erreurs</span>
                        @endif
                    </div>
                    <div class="p-5 space-y-3">
                        <button @click="apercu({{ $trim->id }})"
                                class="w-full px-3 py-2.5 bg-blue-100 text-blue-800 text-sm font-bold rounded-xl hover:bg-blue-200">
                            🔍 Aperçu remontée
                        </button>
                        <form method="POST" action="{{ route('sigfne.executer') }}">
                            @csrf
                            <input type="hidden" name="trimestre_id" value="{{ $trim->id }}" />
                            @if($etab->sigfne_actif && $etab->sigfne_token)
                                <label class="flex items-center gap-2 mb-2 p-2 bg-emerald-50 rounded-lg text-xs">
                                    <input type="checkbox" name="push_api" value="1" checked class="rounded" />
                                    <span class="font-semibold text-emerald-900">📡 Envoyer automatiquement à SIGFNE</span>
                                </label>
                            @endif
                            <button type="submit" onclick="return confirm('Générer la remontée pour {{ $trim->libelle }} ?')"
                                    class="w-full px-3 py-2.5 bg-gradient-to-r from-orange-500 to-amber-600 text-white text-sm font-bold rounded-xl shadow-card-gold">
                                ⚡ Générer la remontée
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ═══════════ MODAL APERÇU ═══════════ --}}
    <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="modal=false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-extrabold text-gray-900">🔍 Aperçu de la remontée SIGFNE</h3>
                <button @click="modal=false" class="text-gray-400 text-2xl">&times;</button>
            </div>
            <div class="p-6 space-y-4" x-show="data">
                <div class="grid grid-cols-4 gap-3">
                    <div class="bg-blue-50 rounded-xl p-3 text-center">
                        <p class="text-xs text-blue-700 font-bold uppercase">Total</p>
                        <p class="text-2xl font-extrabold text-blue-900" x-text="data?.total"></p>
                    </div>
                    <div class="bg-emerald-50 rounded-xl p-3 text-center">
                        <p class="text-xs text-emerald-700 font-bold uppercase">Prêts</p>
                        <p class="text-2xl font-extrabold text-emerald-900" x-text="data?.prets"></p>
                    </div>
                    <div class="bg-red-50 rounded-xl p-3 text-center">
                        <p class="text-xs text-red-700 font-bold uppercase">Sans matricule</p>
                        <p class="text-2xl font-extrabold text-red-900" x-text="data?.sans_matricule"></p>
                    </div>
                    <div class="bg-amber-50 rounded-xl p-3 text-center">
                        <p class="text-xs text-amber-700 font-bold uppercase">Sans moyenne</p>
                        <p class="text-2xl font-extrabold text-amber-900" x-text="data?.sans_moyenne"></p>
                    </div>
                </div>

                <div class="rounded-xl p-3 text-center" :class="data?.taux >= 95 ? 'bg-emerald-100' : (data?.taux >= 70 ? 'bg-amber-100' : 'bg-red-100')">
                    <p class="text-xs font-bold uppercase">Taux de préparation</p>
                    <p class="text-3xl font-extrabold" x-text="(data?.taux ?? 0) + '%'"></p>
                </div>

                <div x-show="data?.erreurs?.length > 0">
                    <p class="font-bold text-red-700 text-sm mb-2">⚠ Élèves non transmissibles (<span x-text="data?.erreurs?.length"></span>)</p>
                    <div class="bg-red-50 rounded-xl max-h-48 overflow-y-auto">
                        <template x-for="e in (data?.erreurs ?? [])" :key="e.eleve_id">
                            <div class="p-2 border-b border-red-100 text-xs">
                                <b x-text="e.prenom + ' ' + e.nom"></b>
                                <span class="text-red-700"> · <span x-text="e.erreurs.join(', ')"></span></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 text-right">
                <button @click="modal=false" class="px-4 py-2 text-sm font-bold text-gray-600">Fermer</button>
            </div>
        </div>
    </div>

    {{-- ═══════════ HISTORIQUE ═══════════ --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-extrabold text-gray-900">📋 Historique des remontées</h3>
            <span class="text-xs font-semibold text-gray-500">{{ $remontees->count() }} remontée(s)</span>
        </div>
        @if($remontees->isEmpty())
            <div class="px-5 py-12 text-center text-sm text-gray-500">Aucune remontée effectuée pour le moment.</div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-bold">Trimestre</th>
                        <th class="px-5 py-3 text-center font-bold">Plateforme</th>
                        <th class="px-5 py-3 text-center font-bold">Transmis / Total</th>
                        <th class="px-5 py-3 text-center font-bold">Erreurs</th>
                        <th class="px-5 py-3 text-center font-bold">Statut</th>
                        <th class="px-5 py-3 text-left font-bold">Date</th>
                        <th class="px-5 py-3 text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($remontees as $r)
                        @php
                            $sb = [
                                'pret_envoi' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => '📤 Prêt'],
                                'envoye'     => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'label' => '✓ Envoyé'],
                                'erreur'     => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => '⚠ Erreurs'],
                                'erreur_api' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'label' => '⚠ API échec'],
                                'en_cours'   => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => '⏳ En cours'],
                            ][$r->statut] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => $r->statut];
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-bold">{{ $r->trimestre?->libelle ?? '—' }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold bg-orange-100 text-orange-700">{{ strtoupper($r->plateforme) }}</span>
                            </td>
                            <td class="px-5 py-3 text-center font-mono text-xs">{{ $r->eleves_remontes }} / {{ $r->total_eleves }}</td>
                            <td class="px-5 py-3 text-center text-xs">
                                @if($r->eleves_en_erreur > 0)
                                    <span class="text-red-700 font-bold">{{ $r->eleves_en_erreur }}</span>
                                @else
                                    <span class="text-emerald-700">✓</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold {{ $sb['bg'] }} {{ $sb['text'] }}">{{ $sb['label'] }}</span>
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-500">{{ $r->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-right">
                                @if($r->fichier_export_path)
                                    <a href="{{ route('sigfne.fichier', $r->id) }}" class="text-xs font-bold text-blue-600 hover:text-blue-800">📥 CSV</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ═══════════ PARAMÉTRAGE ═══════════ --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-6">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">⚙</span>
            <div>
                <h3 class="font-extrabold text-gray-900">Paramètres SIGFNE</h3>
                <p class="text-xs text-gray-500">Activation et identifiants pour la synchronisation automatique</p>
            </div>
        </div>
        <form method="POST" action="{{ route('sigfne.parametrer') }}" class="grid grid-cols-1 lg:grid-cols-4 gap-3">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Plateforme</label>
                <select name="sigfne_plateforme" class="w-full rounded-xl border-gray-200 text-sm">
                    <option value="">Auto-détecter</option>
                    <option value="agfne" @selected($etab->sigfne_plateforme === 'agfne')>AGFNE (secondaire)</option>
                    <option value="agcp"  @selected($etab->sigfne_plateforme === 'agcp')>AGCP (primaire)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Identifiant SIGFNE</label>
                <input name="sigfne_login" value="{{ $etab->sigfne_login }}" placeholder="ex: {{ $etab->code_desps }}" class="w-full rounded-xl border-gray-200 text-sm font-mono" />
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Token / API key</label>
                <input type="password" name="sigfne_token" placeholder="{{ $etab->sigfne_token ? '••••••••' : 'Token API SIGFNE' }}" class="w-full rounded-xl border-gray-200 text-sm" />
            </div>
            <div class="flex items-end gap-2">
                <label class="flex items-center gap-2 p-2 bg-emerald-50 rounded-xl flex-1">
                    <input type="checkbox" name="sigfne_actif" value="1" @checked($etab->sigfne_actif) class="rounded" />
                    <span class="text-xs font-bold text-emerald-900">Activer sync</span>
                </label>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white text-sm font-bold rounded-xl">Enregistrer</button>
            </div>
        </form>
        <div class="mt-4 p-3 bg-blue-50 border border-blue-100 rounded-xl text-xs text-blue-900">
            <p class="font-bold mb-1">ℹ Mode d'emploi</p>
            <ol class="list-decimal list-inside space-y-0.5 leading-relaxed">
                <li>Sans token : le système génère un <b>fichier CSV</b> que vous uploadez manuellement sur <a href="{{ $urlPlateforme }}/vas/agcs-moyenne/" target="_blank" class="underline font-bold">{{ str_replace('https://', '', $urlPlateforme) }}</a>.</li>
                <li>Avec token SIGFNE configuré : le système <b>pousse automatiquement</b> les moyennes via API à chaque clic « Générer ».</li>
                <li>Format CSV exporté : <code>MATRICULE_DESPS;NOM;PRENOM;SEXE;DATE_NAISSANCE;CLASSE;MOYENNE;RANG</code></li>
            </ol>
        </div>
    </div>
</div>

<script>
function sigfnePage() {
    return {
        modal: false,
        data: null,
        async apercu(trimestreId) {
            this.modal = true;
            this.data = { total: '...', prets: '...', sans_matricule: '...', sans_moyenne: '...', taux: 0 };
            const fd = new FormData();
            fd.append('trimestre_id', trimestreId);
            fd.append('_token', document.querySelector('meta[name="csrf-token"]')?.content);
            const r = await fetch('{{ route('sigfne.preparer') }}', { method: 'POST', body: fd });
            this.data = await r.json();
        }
    };
}
</script>
@push('styles')<style>[x-cloak]{display:none!important}</style>@endpush
@endsection
