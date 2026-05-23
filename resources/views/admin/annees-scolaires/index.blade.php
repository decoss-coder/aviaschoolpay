@extends('layouts.app')
@section('title', 'Années scolaires')
@section('page-title', 'Années scolaires')
@section('page-subtitle', 'Gestion, clôture et archivage chiffré des années')

@section('content')
@php
    $totalAnnees   = $annees->count();
    $nbArchivees   = $annees->where('archivee', true)->count();
    $nbCloturees   = $annees->where('cloturee', true)->where('archivee', false)->count();
    $nbDemandes    = $demandes->where('statut', 'en_attente_paiement')->count();
    $anneesArchivees = $annees->where('archivee', true);

    $statutLabels = [
        'en_attente_paiement' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => '⏳ Paiement attendu'],
        'paye'                => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => '💵 Payé'],
        'cle_livree'          => ['bg' => 'bg-violet-100', 'text' => 'text-violet-700', 'label' => '🔑 Clé livrée'],
        'restauree'           => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'label' => '✓ Restaurée'],
        'annulee'             => ['bg' => 'bg-gray-100', 'text' => 'text-gray-500', 'label' => 'Annulée'],
    ];
@endphp

<div class="space-y-6" x-data="{
    modalCreer: false,
    modalCloturer: null,
    modalRestaurer: {{ ($errors->has('cle_restauration') || $errors->has('confirm_restauration')) ? 'true' : 'false' }},
    anneeRestaurerId: {{ session('restaurer_annee_id') ?? $anneesArchivees->first()?->id ?? 'null' }},
    cleRestauration: ''
}">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
    @endif

    @if(session('annee_restauree'))
        <div class="rounded-2xl border-2 border-blue-300 bg-gradient-to-br from-blue-50 to-cyan-50 p-5 shadow-card">
            <div class="flex items-start gap-3">
                <span class="text-2xl">🔓</span>
                <div>
                    <p class="font-display text-lg font-extrabold text-blue-900">Restauration réussie — {{ session('annee_restauree') }}</p>
                    <p class="text-sm text-blue-800 mt-1">Les données chiffrées ont été déchiffrées et réimportées. L'année n'est plus en mode « archivée ».</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('annee_basculee'))
        <div class="rounded-2xl border-2 border-emerald-300 bg-gradient-to-br from-emerald-50 to-brand-50 p-5 shadow-card-brand">
            <div class="flex items-start gap-3">
                <span class="text-2xl">✓</span>
                <div>
                    <p class="font-display text-lg font-extrabold text-emerald-900">Bascule vers {{ session('annee_basculee') }}</p>
                    <p class="text-sm text-emerald-800 mt-1">
                        Toute l'application (tableau de bord, classes, finances, pointage…) affiche désormais cette année.
                        Les fiches élèves sont conservées : réinscrivez-les avec leurs <b>matricules existants</b> via l'import ou la saisie manuelle.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Clé visible uniquement gestionnaire / super admin --}}
    @if(session('archive_restoration_key') && ($peutVoirCle ?? false))
        <div class="rounded-2xl border-4 border-amber-400 bg-gradient-to-br from-amber-50 via-yellow-50 to-orange-50 p-6 shadow-card-gold relative overflow-hidden">
            <div class="relative">
                <div class="flex items-center gap-3 mb-3">
                    <span class="text-3xl">🔐</span>
                    <div>
                        <p class="font-display text-lg font-extrabold text-amber-900">Clé de restauration (accès restreint)</p>
                        <p class="text-xs text-amber-800">Affichée une seule fois — copiez-la et transmettez-la au service technique si besoin</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-4 border-2 border-amber-300">
                    <p class="font-mono text-2xl font-extrabold text-amber-900 tracking-wider text-center select-all">{{ session('archive_restoration_key') }}</p>
                </div>
                <p class="text-xs text-amber-800 mt-3">La direction n'a pas accès à cette clé. Restauration possible via Avia Technologie (500 FCFA).</p>
            </div>
        </div>
    @elseif(session('archive_key_stored'))
        <div class="rounded-2xl border border-brand-200 bg-brand-50/80 p-4 flex items-start gap-3">
            <span class="text-xl">🔒</span>
            <div class="text-sm text-brand-900">
                <p class="font-bold">Archive sécurisée</p>
                <p class="mt-1 text-brand-800">
                    La clé de restauration est conservée par <b>Avia Technologie</b> (coffre chiffré).
                    Pour une récupération, utilisez « Demander la clé (500 F) » sur l'année archivée.
                </p>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-brand-500 to-brand-700 rounded-xl flex items-center justify-center shadow-brand-glow">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">{{ $etab->nom }}</p>
                <h2 class="font-display text-2xl font-extrabold text-gray-900">Années scolaires</h2>
            </div>
        </div>
        <button @click="modalCreer = true" class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-brand-500 to-brand-700 text-white shadow-brand-glow hover:shadow-lg transition flex items-center gap-2 self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Nouvelle année
        </button>
    </div>

    {{-- KPIs --}}
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Total années</p>
            <p class="text-2xl font-extrabold text-gray-900 mt-2">{{ $totalAnnees }}</p>
        </div>
        @if($courante)
        <div class="bg-gradient-to-br {{ $courante->estArchiveConsultation() ? 'from-violet-500 to-indigo-700' : 'from-emerald-500 to-brand-700' }} rounded-2xl p-5 shadow-card-brand text-white">
            <p class="text-xs font-bold uppercase text-white/80 tracking-wider">Année en cours</p>
            <p class="text-xl font-extrabold mt-2">{{ $courante->libelle }}</p>
            <p class="text-xs text-white/80 mt-1">{{ $courante->date_debut->format('d/m/Y') }} → {{ $courante->date_fin->format('d/m/Y') }}</p>
            @if($courante->estArchiveConsultation())
                <div class="mt-2 flex flex-col gap-1">
                    <form method="POST" action="{{ route('admin.annees.resynchroniser', $courante) }}">
                        @csrf
                        <button type="submit" class="text-[10px] font-bold underline text-white/90 hover:text-white text-left">
                            ↻ Resynchroniser élèves / effectifs
                        </button>
                    </form>
                    @if($courante->archive_path && ($edtEnBase[$courante->id] ?? 0) === 0 && ($edtDansArchive[$courante->id] ?? -1) > 0)
                        <form method="POST" action="{{ route('admin.annees.reimporter-edt', $courante) }}"
                              onsubmit="return confirm('Importer {{ $edtDansArchive[$courante->id] }} créneaux d\'emploi du temps depuis l\'archive ?');">
                            @csrf
                            <button type="submit" class="text-[10px] font-bold underline text-amber-200 hover:text-white text-left">
                                📅 Récupérer l'emploi du temps ({{ $edtDansArchive[$courante->id] }} dans l'archive)
                            </button>
                        </form>
                    @elseif($courante->archive_path && ($edtEnBase[$courante->id] ?? 0) === 0 && ($edtDansArchive[$courante->id] ?? -1) === 0)
                        <p class="text-[10px] text-white/75">Emploi du temps : absent de l'archive (perdu à l'archivage initial).</p>
                    @endif
                </div>
            @endif
        </div>
        @else
        <div class="bg-white rounded-2xl border-2 border-red-200 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-red-600 tracking-wider">⚠ Aucune année active</p>
            <p class="text-sm font-bold text-red-700 mt-2">Activez une année</p>
        </div>
        @endif
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Archivées 🔒</p>
            <p class="text-2xl font-extrabold text-gray-700 mt-2">{{ $nbArchivees }}</p>
            <p class="text-xs text-gray-500">{{ $nbCloturees }} clôturée(s) non archivée(s)</p>
        </div>
        <div class="bg-white rounded-2xl border border-amber-100 p-5 shadow-card-gold">
            <p class="text-xs font-bold uppercase text-amber-600 tracking-wider">Demandes restauration</p>
            <p class="text-2xl font-extrabold text-amber-700 mt-2">{{ $nbDemandes }}</p>
            <p class="text-xs text-amber-600">en attente de paiement</p>
        </div>
    </section>

    {{-- Restauration par clé de chiffrement --}}
    @if($anneesArchivees->isNotEmpty())
        <section class="rounded-2xl border-2 border-blue-200 bg-gradient-to-br from-blue-50 via-white to-cyan-50/40 shadow-card overflow-hidden">
            <div class="px-5 py-4 border-b border-blue-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white text-xl shadow-md flex-shrink-0">🔓</div>
                    <div>
                        <h3 class="font-display text-lg font-extrabold text-blue-900">Restaurer une année archivée</h3>
                        <p class="text-sm text-blue-800 mt-0.5">
                            Saisissez la <b>clé de chiffrement</b> (format <span class="font-mono">XXXX-XXXX-XXXX-XXXX</span>)
                            pour déchiffrer et réimporter classes, inscriptions et paiements.
                        </p>
                    </div>
                </div>
                <button type="button"
                        @click="modalRestaurer = true"
                        class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold shadow-md flex items-center gap-2 self-start">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    Restaurer avec une clé
                </button>
            </div>
            <div class="px-5 py-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($anneesArchivees as $arch)
                    <div class="rounded-xl border border-blue-100 bg-white p-4 flex flex-col gap-3">
                        <div>
                            <p class="font-extrabold text-gray-900">{{ $arch->libelle }}</p>
                            <p class="text-xs text-gray-500 mt-1">
                                Archivée {{ $arch->archived_at?->format('d/m/Y') ?? '—' }}
                                @if(!empty($arch->archive_meta['counts']))
                                    · {{ array_sum($arch->archive_meta['counts']) }} enregistrement(s)
                                @endif
                            </p>
                        </div>
                        <button type="button"
                                @click="anneeRestaurerId = {{ $arch->id }}; modalRestaurer = true"
                                class="w-full px-3 py-2 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-800 text-xs font-bold border border-blue-200 transition">
                            🔑 Restaurer cette année
                        </button>
                    </div>
                @endforeach
            </div>
            <div class="px-5 py-3 bg-blue-50/80 border-t border-blue-100 text-xs text-blue-800">
                @if($peutVoirCle ?? false)
                    En tant que gestionnaire, la clé vous a pu être affichée à la clôture. Sinon : demande à Avia Technologie (500 F) ou import du fichier <code class="bg-white px-1 rounded">.enc</code>.
                @else
                    La direction ne voit pas la clé à la clôture. Obtenez-la via <b>Demander la clé (500 F)</b> ou auprès du gestionnaire / Avia Technologie.
                @endif
            </div>
        </section>
    @endif

    {{-- Encart explication workflow --}}
    @if($courante && $annees->where('id', '!=', $courante->id)->where('archivee', false)->isEmpty())
        <div class="rounded-2xl border-2 border-blue-200 bg-blue-50 p-4 flex items-start gap-3">
            <span class="text-2xl">💡</span>
            <div class="flex-1">
                <p class="font-bold text-blue-900">Comment clôturer et archiver une année ?</p>
                <ol class="text-sm text-blue-800 mt-1 space-y-1 list-decimal list-inside">
                    <li><b>Créez la prochaine année</b> (ex: 2027-2028) via le bouton « Nouvelle année »</li>
                    <li><b>Activez-la</b> via le bouton vert « ✓ Activer cette année »</li>
                    <li>Le bouton ambre <b>« 🔒 Clôturer & archiver »</b> apparaîtra alors sur l'ancienne année</li>
                </ol>
                <p class="text-xs text-blue-700 mt-2 italic">⚠ Sécurité : l'année active ne peut pas être clôturée — il faut toujours qu'une autre année soit déjà active pour assurer la continuité.</p>
            </div>
        </div>
    @endif

    {{-- Liste des années --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-extrabold text-gray-900">📅 Toutes les années</h3>
            <span class="text-xs font-semibold text-gray-500">{{ $annees->count() }} année(s)</span>
        </div>

        @if($annees->isEmpty())
            <div class="px-5 py-16 text-center">
                <p class="text-4xl mb-3">📚</p>
                <p class="font-bold text-gray-800">Aucune année créée</p>
                <p class="text-sm text-gray-500 mt-1 mb-4">Créez votre première année scolaire pour démarrer.</p>
                <button @click="modalCreer = true" class="inline-flex items-center px-4 py-2 bg-brand-600 text-white text-sm font-bold rounded-xl">Créer une année</button>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($annees as $annee)
                    @php
                        $estRestauree = ! $annee->archivee && ! empty($annee->archive_meta['restaurer_le']);
                        $statutBadge = $annee->en_cours
                            ? ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'label' => '✓ En cours', 'border' => 'border-l-emerald-500']
                            : ($annee->archivee
                                ? ['bg' => 'bg-gray-200', 'text' => 'text-gray-700', 'label' => '🔒 Archivée chiffrée', 'border' => 'border-l-gray-400']
                                : ($estRestauree
                                    ? ['bg' => 'bg-violet-100', 'text' => 'text-violet-700', 'label' => '🔓 Restaurée — à activer', 'border' => 'border-l-violet-500']
                                    : ($annee->cloturee
                                        ? ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => 'Clôturée', 'border' => 'border-l-amber-500']
                                        : ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Disponible', 'border' => 'border-l-blue-500'])));
                    @endphp
                    <div class="px-5 py-5 hover:bg-gray-50 transition border-l-4 {{ $statutBadge['border'] }}">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="font-display text-lg font-extrabold text-gray-900">{{ $annee->libelle }}</h4>
                                    <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-bold {{ $statutBadge['bg'] }} {{ $statutBadge['text'] }}">{{ $statutBadge['label'] }}</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    📆 {{ $annee->date_debut->format('d/m/Y') }} → {{ $annee->date_fin->format('d/m/Y') }}
                                    @if($annee->archivee && $annee->archived_at)
                                        · 🔒 Archivée le {{ $annee->archived_at->format('d/m/Y') }}
                                    @endif
                                </p>
                                @if($annee->archivee && ! empty($annee->archive_meta['counts']))
                                    <div class="flex flex-wrap gap-2 mt-2 text-xs">
                                        @foreach($annee->archive_meta['counts'] as $type => $nb)
                                            @if($nb > 0)
                                                <span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded font-bold">{{ $nb }} {{ $type }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @if(! $annee->en_cours && ! $annee->archivee)
                                    <form method="POST" action="{{ route('admin.annees.activer', $annee) }}">@csrf
                                        <button class="px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center gap-1.5">
                                            ✓ Activer cette année
                                        </button>
                                    </form>
                                @endif

                                @if(! $annee->archivee && ! $annee->en_cours)
                                    <button @click="modalCloturer = {{ $annee->id }}"
                                            class="px-3 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold flex items-center gap-1.5 shadow-card-gold">
                                        🔒 Clôturer & archiver (chiffré)
                                    </button>
                                @elseif($annee->en_cours)
                                    {{-- Année en cours : on ne peut pas la clôturer directement --}}
                                    <button type="button" disabled
                                            title="Pour clôturer cette année, activez d'abord une autre année (ou créez-en une nouvelle)."
                                            class="px-3 py-2 rounded-xl bg-gray-200 text-gray-500 text-xs font-bold flex items-center gap-1.5 cursor-not-allowed">
                                        🔒 Clôturer (activer d'abord une autre année)
                                    </button>
                                @endif

                                @if($annee->archivee)
                                    <form method="POST" action="{{ route('admin.annees.demander-restauration', $annee) }}">@csrf
                                        <button class="px-3 py-2 rounded-xl bg-white border-2 border-blue-300 hover:bg-blue-50 text-blue-700 text-xs font-bold flex items-center gap-1.5">
                                            💰 Demander la clé (500 F)
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        @if($annee->archivee)
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <button type="button"
                                        @click="anneeRestaurerId = {{ $annee->id }}; modalRestaurer = true"
                                        class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold inline-flex items-center gap-2">
                                    🔓 Restaurer avec la clé de chiffrement
                                </button>
                            </div>
                        @elseif($annee->cloturee && ! $annee->archivee && ! empty($annee->archive_meta['restaurer_le']))
                            <p class="mt-3 text-xs font-semibold text-emerald-700 bg-emerald-50 inline-flex px-2 py-1 rounded-lg">
                                ✓ Données restaurées le {{ \Illuminate\Support\Carbon::parse($annee->archive_meta['restaurer_le'])->format('d/m/Y H:i') }}
                            </p>
                        @endif

                        {{-- Modal clôture (par année) --}}
                        <div x-show="modalCloturer === {{ $annee->id }}" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="modalCloturer = null">
                            <form method="POST" action="{{ route('admin.annees.cloturer', $annee) }}" class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
                                @csrf
                                <div class="px-6 py-4 border-b border-gray-100 bg-amber-50 rounded-t-2xl">
                                    <h3 class="font-extrabold text-amber-900 flex items-center gap-2">
                                        <span class="text-2xl">⚠</span> Clôturer et archiver {{ $annee->libelle }}
                                    </h3>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="rounded-xl bg-amber-50 border border-amber-200 p-3 text-sm text-amber-900">
                                        <p class="font-bold mb-1">Cette action va :</p>
                                        <ul class="list-disc list-inside space-y-0.5 text-xs">
                                            <li>Archiver chiffré les données de l'année (classes, inscriptions, paiements…)</li>
                                            <li>Basculer automatiquement l'application sur l'année <b>en cours</b></li>
                                            <li>Recréer la structure des classes pour la nouvelle année (sans élèves)</li>
                                            <li>Conserver les fiches élèves — réinscription par matricule existant</li>
                                            <li>Clé de restauration : coffre Avia Technologie (visible gestionnaire / super admin uniquement)</li>
                                        </ul>
                                    </div>

                                    <label class="flex items-start gap-2 p-3 bg-gray-50 rounded-xl">
                                        <input type="checkbox" name="purger_donnees" value="1" checked class="mt-1 rounded" />
                                        <span class="text-sm text-gray-700"><b>Purger les données opérationnelles</b><br><span class="text-xs text-gray-500">Recommandé pour libérer la base. Les données restent récupérables via la clé.</span></span>
                                    </label>

                                    <label class="flex items-start gap-2 p-3 bg-red-50 border border-red-200 rounded-xl">
                                        <input type="checkbox" name="confirm_cloture" value="1" required class="mt-1 rounded" />
                                        <span class="text-sm text-red-900"><b>Je confirme la clôture et la bascule vers l'année en cours</b><br><span class="text-xs">Les inscriptions de l'année clôturée seront purgées ; les élèves restent dans le référentiel.</span></span>
                                    </label>
                                </div>
                                <div class="px-6 py-4 border-t border-gray-100 flex gap-2 justify-end">
                                    <button type="button" @click="modalCloturer = null" class="px-4 py-2 text-sm font-bold text-gray-600">Annuler</button>
                                    <button type="submit" class="px-6 py-2 bg-amber-600 text-white text-sm font-bold rounded-xl hover:bg-amber-700">🔒 Clôturer & archiver</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Historique des demandes de restauration --}}
    @if($demandes->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-gray-900">📋 Demandes de restauration</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Historique des demandes adressées à Avia Technologie</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-5 py-3 text-left font-bold">Référence</th>
                            <th class="px-5 py-3 text-left font-bold">Année</th>
                            <th class="px-5 py-3 text-left font-bold">Demandeur</th>
                            <th class="px-5 py-3 text-right font-bold">Montant</th>
                            <th class="px-5 py-3 text-center font-bold">Statut</th>
                            <th class="px-5 py-3 text-left font-bold">Date</th>
                            <th class="px-5 py-3 text-right font-bold">Wave</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($demandes as $d)
                            @php $sb = $statutLabels[$d->statut] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => $d->statut]; @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 font-mono text-xs text-gray-600">{{ $d->reference }}</td>
                                <td class="px-5 py-3 font-bold text-gray-900">{{ $d->anneeScolaire?->libelle ?? '—' }}</td>
                                <td class="px-5 py-3 text-xs text-gray-700">{{ $d->demandeur?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-right font-extrabold text-amber-700">{{ number_format($d->montant_fcfa, 0, ',', ' ') }} F</td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold {{ $sb['bg'] }} {{ $sb['text'] }}">{{ $sb['label'] }}</span>
                                </td>
                                <td class="px-5 py-3 text-xs text-gray-500">{{ $d->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-3 text-right">
                                    @if($d->wave_checkout_url && $d->statut === 'en_attente_paiement')
                                        <a href="{{ $d->wave_checkout_url }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs font-bold">💳 Payer →</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Info Wave / Avia Technologie --}}
    @if($lienWaveRestauration)
        <div class="bg-gradient-to-br from-blue-50 via-cyan-50 to-blue-50 rounded-2xl border border-blue-200 p-5">
            <div class="flex items-start gap-3">
                <span class="text-3xl">💳</span>
                <div class="flex-1">
                    <h3 class="font-extrabold text-blue-900">Service de restauration Avia Technologie</h3>
                    <p class="text-sm text-blue-800 mt-1">
                        Si vous avez perdu votre clé de restauration, payez <b>500 FCFA via Wave</b> au compte
                        <b>Avia Technologie</b>. Notre support vous communiquera la clé sous 24h.
                    </p>
                    <a href="{{ $lienWaveRestauration }}" target="_blank" class="inline-flex items-center gap-2 mt-3 px-4 py-2 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700">
                        🌊 Lien de paiement Wave
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal restauration par clé --}}
    <div x-show="modalRestaurer" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         @keydown.escape.window="modalRestaurer = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-blue-100 bg-gradient-to-r from-blue-50 to-cyan-50 rounded-t-2xl flex items-center justify-between">
                <h3 class="font-extrabold text-blue-900 flex items-center gap-2">
                    <span class="text-xl">🔓</span> Restauration par clé
                </h3>
                <button type="button" @click="modalRestaurer = false" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>

            <form method="POST"
                  :action="'{{ url('/admin/annees-scolaires') }}/' + anneeRestaurerId + '/restaurer'"
                  class="p-6 space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Année à restaurer *</label>
                    <select x-model.number="anneeRestaurerId" required
                            class="w-full rounded-xl border-gray-200 text-sm focus:border-blue-400 focus:ring-blue-100">
                        @foreach($anneesArchivees as $arch)
                            <option value="{{ $arch->id }}">{{ $arch->libelle }} (archivée {{ $arch->archived_at?->format('d/m/Y') }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-blue-700 mb-1">Clé de chiffrement *</label>
                    <input type="text" name="cle_restauration" x-model="cleRestauration" required maxlength="50"
                           autocomplete="off"
                           class="w-full rounded-xl border-2 border-blue-200 text-lg font-mono uppercase tracking-wider text-center focus:border-blue-500 focus:ring-blue-100 @error('cle_restauration') border-red-400 @enderror"
                           placeholder="XXXX-XXXX-XXXX-XXXX" />
                    @error('cle_restauration')
                        <p class="text-xs text-red-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-2">La clé déchiffre le fichier <code>.enc</code> et réimporte trimestres, classes, inscriptions et paiements.</p>
                </div>

                <label class="flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                    <input type="checkbox" name="confirm_restauration" value="1" required class="mt-1 rounded" />
                    <span class="text-sm text-amber-900">
                        <b>Je confirme la restauration</b><br>
                        <span class="text-xs">Les données seront réinjectées dans la base. L'année ne sera plus marquée comme archivée.</span>
                    </span>
                </label>

                <div class="flex gap-2 justify-end pt-2 border-t border-gray-100">
                    <button type="button" @click="modalRestaurer = false" class="px-4 py-2 text-sm font-bold text-gray-600">Annuler</button>
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl shadow-md">
                        🔓 Déchiffrer et restaurer
                    </button>
                </div>
            </form>

            <div class="px-6 pb-6">
                <details class="rounded-xl border border-gray-200 bg-gray-50 text-sm">
                    <summary class="px-4 py-3 font-bold text-gray-700 cursor-pointer">Import depuis un fichier .enc (secours)</summary>
                    <form method="POST" action="{{ route('admin.annees.restaurer-fichier') }}" enctype="multipart/form-data" class="p-4 space-y-3 border-t border-gray-200">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Fichier archive (.enc)</label>
                            <input type="file" name="fichier_archive" accept=".enc,.txt" required class="w-full text-sm" />
                            @error('fichier_archive')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Clé de chiffrement</label>
                            <input type="text" name="cle_restauration" required maxlength="50"
                                   class="w-full rounded-lg border-gray-200 font-mono uppercase text-sm"
                                   placeholder="XXXX-XXXX-XXXX-XXXX" />
                        </div>
                        <select name="annee_id" class="w-full rounded-lg border-gray-200 text-sm">
                            <option value="">— Créer / détecter depuis l'archive —</option>
                            @foreach($anneesArchivees as $arch)
                                <option value="{{ $arch->id }}">{{ $arch->libelle }}</option>
                            @endforeach
                        </select>
                        <label class="flex items-center gap-2 text-xs">
                            <input type="checkbox" name="confirm_restauration" value="1" required class="rounded" />
                            Je confirme l'import depuis fichier
                        </label>
                        <button type="submit" class="w-full py-2 rounded-lg bg-gray-800 text-white text-xs font-bold hover:bg-gray-900">
                            Importer le fichier chiffré
                        </button>
                    </form>
                </details>
            </div>
        </div>
    </div>

    {{-- Modal création année --}}
    <div x-show="modalCreer" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="modalCreer = false">
        <form method="POST" action="{{ route('admin.annees.store') }}" class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
            @csrf
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-extrabold text-gray-900">Créer une nouvelle année scolaire</h3>
                <button type="button" @click="modalCreer = false" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-6 space-y-4">
                @php
                    // Suggestion intelligente : année scolaire prochaine (septembre N → juillet N+1)
                    $today = now();
                    $anneeBase = $today->month >= 8 ? $today->year : $today->year - 1;
                    $suggestionLibelle = ($anneeBase + 1) . '-' . ($anneeBase + 2);
                    $suggestionDebut = ($anneeBase + 1) . '-09-01';
                    $suggestionFin   = ($anneeBase + 2) . '-07-31';
                @endphp
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Libellé *</label>
                    <input name="libelle" value="{{ old('libelle', $suggestionLibelle) }}" required maxlength="20" placeholder="2026-2027" class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Date début *</label>
                        <input type="date" name="date_debut" value="{{ old('date_debut', $suggestionDebut) }}" required class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Date fin *</label>
                        <input type="date" name="date_fin" value="{{ old('date_fin', $suggestionFin) }}" required class="w-full rounded-xl border-gray-200 text-sm focus:border-brand-400 focus:ring-brand-100" />
                    </div>
                </div>
                <div class="text-xs text-gray-500 bg-blue-50 border border-blue-100 rounded-xl p-3">
                    💡 La date de fin doit être <b>strictement postérieure</b> à la date de début. Convention : 1er septembre → 31 juillet.
                </div>
                <label class="flex items-center gap-2 p-3 bg-emerald-50 rounded-xl">
                    <input type="checkbox" name="activer" value="1" checked class="rounded text-emerald-600" />
                    <span class="text-sm font-semibold text-emerald-900">✓ Activer immédiatement comme année en cours</span>
                </label>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-2 justify-end">
                <button type="button" @click="modalCreer = false" class="px-4 py-2 text-sm font-bold text-gray-600">Annuler</button>
                <button type="submit" class="px-6 py-2 bg-brand-600 text-white text-sm font-bold rounded-xl hover:bg-brand-700">Créer l'année</button>
            </div>
        </form>
    </div>
</div>

@push('styles')<style>[x-cloak]{display:none!important}</style>@endpush
@endsection
