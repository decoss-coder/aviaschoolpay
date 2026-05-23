@php
    $e = $etablissement;
    $r = $recouvrement;
    $roles = [
        'directeur' => 'Directeur', 'directeur_adjoint' => 'Adjoint', 'gestionnaire' => 'Gestionnaire',
        'secretaire' => 'Secrétaire', 'comptable' => 'Comptable', 'censeur' => 'Censeur',
        'enseignant' => 'Enseignant', 'parent' => 'Parent', 'eleve' => 'Élève',
    ];
@endphp
@extends('layouts.app')
@section('title', $e->nom)
@section('page-title', $e->nom)
@section('page-subtitle', 'DESPS '.$e->code_desps)

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- ═══════════════ ENCART COMPTE DIRECTEUR CRÉÉ ═══════════════ --}}
    @if(session('compte_directeur_cree'))
        @php $cpt = session('compte_directeur_cree'); @endphp
        <div class="relative overflow-hidden bg-gradient-to-br from-violet-500 via-violet-600 to-purple-700 rounded-2xl shadow-card-violet p-6 text-white">
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-gold-300 to-gold-500"></div>
            <div class="absolute -top-6 -right-6 w-32 h-32 bg-gold-400/20 rounded-full blur-2xl"></div>

            <div class="relative flex items-start gap-4">
                <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center text-3xl flex-shrink-0">🔐</div>
                <div class="flex-1">
                    <h3 class="font-display text-lg font-extrabold">Compte de connexion créé pour {{ $cpt['nom'] }}</h3>
                    <p class="text-violet-100 text-sm mt-1">Rôle : <b class="text-white">{{ $cpt['role'] }}</b> · Transmettez ces identifiants au directeur, il devra changer son mot de passe à sa première connexion.</p>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="bg-white/15 backdrop-blur rounded-xl p-3 border border-white/20">
                            <p class="text-[10px] text-violet-100 font-bold uppercase tracking-wider mb-1">Login (email)</p>
                            <p class="font-mono font-bold text-base text-white select-all">{{ $cpt['email'] }}</p>
                        </div>
                        <div class="bg-white/15 backdrop-blur rounded-xl p-3 border border-white/20">
                            <p class="text-[10px] text-violet-100 font-bold uppercase tracking-wider mb-1">Mot de passe initial</p>
                            <p class="font-mono font-extrabold text-2xl text-gold-300 select-all tracking-widest">{{ $cpt['password'] }}</p>
                        </div>
                    </div>

                    <p class="text-[11px] text-violet-100 mt-3 italic">
                        ⚠ <b>Sécurité</b> : à la première connexion, le directeur sera <b>obligatoirement</b> redirigé vers la page de changement de mot de passe. Ce message n'apparaîtra qu'une seule fois.
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            ✓ {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.etablissements.index') }}" class="text-sm font-bold text-brand-600">← Établissements</a>
            <div class="flex flex-wrap items-center gap-2 mt-2">
                @if($e->actif)
                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold">Accès actif</span>
                @else
                    <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-800 text-xs font-bold">Accès suspendu</span>
                @endif
                @if($wave_actif)
                    <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-xs font-bold">Wave configuré</span>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.etablissements.edit', $e) }}" class="px-4 py-2 rounded-xl border border-brand-200 text-brand-700 text-sm font-bold">Modifier</a>
            <a href="{{ route('admin.wave.index') }}?etab={{ $e->id }}" class="px-4 py-2 rounded-xl border text-sm font-bold text-gray-600">Wave</a>
            @if($e->actif)
                <form method="POST" action="{{ route('admin.etablissements.ouvrir', $e) }}">@csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-gold-500 text-white text-sm font-bold">Ouvrir l'espace</button>
                </form>
                <form method="POST" action="{{ route('admin.etablissements.toggle-access', $e) }}" onsubmit="return confirm('Bloquer tous les utilisateurs de cette école ?')">@csrf
                    <input type="hidden" name="bloquer" value="1">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-red-600 text-white text-sm font-bold">Bloquer l'accès</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.etablissements.toggle-access', $e) }}">@csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-bold">Réactiver</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-2xl border p-4 shadow-card">
            <p class="text-[10px] uppercase text-gray-500 font-bold">Élèves</p>
            <p class="text-2xl font-extrabold">{{ $eleves }}</p>
        </div>
        <div class="bg-white rounded-2xl border p-4 shadow-card">
            <p class="text-[10px] uppercase text-gray-500 font-bold">Inscriptions 30j</p>
            <p class="text-2xl font-extrabold">{{ $inscriptions_30j }}</p>
        </div>
        <div class="bg-white rounded-2xl border p-4 shadow-card">
            <p class="text-[10px] uppercase text-gray-500 font-bold">Comptes actifs</p>
            <p class="text-2xl font-extrabold">{{ $utilisateurs_actifs }}</p>
        </div>
        <div class="bg-white rounded-2xl border p-4 shadow-card col-span-2">
            <p class="text-[10px] uppercase text-gray-500 font-bold">Recouvrement · {{ $annee_courante?->libelle ?? '—' }}</p>
            <p class="text-2xl font-extrabold text-brand-600">{{ $r['taux'] }}%</p>
            <p class="text-xs text-gray-500">{{ number_format($r['total_paye'], 0, ',', ' ') }} / {{ number_format($r['total_du'], 0, ',', ' ') }} F</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl border shadow-card p-5 lg:col-span-1">
            <h2 class="font-bold text-gray-900 mb-3">Coordonnées</h2>
            <ul class="text-sm space-y-2 text-gray-600">
                <li>{{ $e->adresse }}</li>
                <li>{{ $e->ville }}{{ $e->commune ? ', '.$e->commune : '' }}{{ $e->region ? ' · '.$e->region : '' }}</li>
                <li>{{ $e->telephone }}</li>
                @if($e->email)<li>{{ $e->email }}</li>@endif
                @if($e->directeur_nom)<li class="pt-2 border-t">Directeur : {{ $e->directeur_nom }} @if($e->directeur_telephone)· {{ $e->directeur_telephone }}@endif</li>@endif
            </ul>
            <p class="text-xs text-gray-400 mt-3 capitalize">{{ str_replace('_', ' ', $e->type) }} · {{ str_replace('_', ' ', $e->statut_juridique) }}</p>
        </div>

        <div class="bg-white rounded-2xl border shadow-card overflow-hidden lg:col-span-2">
            <details class="group" @if($users->isEmpty() || $errors->any()) open @endif>
                <summary class="list-none cursor-pointer select-none">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
                        <span class="font-bold">Utilisateurs <span class="text-xs text-gray-400 font-normal">({{ $users->count() }})</span></span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-gradient-to-r from-violet-500 to-purple-700 text-white text-xs font-bold shadow-card-violet hover:shadow-lg transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            <span>Ajouter un compte</span>
                        </span>
                    </div>
                </summary>

                {{-- ── Formulaire d'ajout (visible si pas d'utilisateurs, si erreurs, ou au clic) ── --}}
                <div class="border-b border-gray-100 bg-gradient-to-br from-violet-50/40 to-white p-5">
                @if($errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 mb-3">
                        @foreach($errors->all() as $err)<p>• {{ $err }}</p>@endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.etablissements.users.store', $e) }}" class="space-y-3">
                    @csrf
                    <p class="text-[11px] text-violet-700 bg-violet-50 border border-violet-200 rounded-lg px-3 py-2 font-semibold">
                        🔐 Le compte sera créé avec le mot de passe initial <span class="font-mono font-extrabold bg-white px-2 py-0.5 rounded">0000</span> — l'utilisateur devra le changer obligatoirement à la première connexion.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nom <span class="text-red-500">*</span></label>
                            <input type="text" name="nom" value="{{ old('nom') }}" required maxlength="100" placeholder="KOUASSI"
                                   class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm uppercase placeholder:text-gray-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Prénom <span class="text-red-500">*</span></label>
                            <input type="text" name="prenom" value="{{ old('prenom') }}" required maxlength="100" placeholder="Jean-Marie"
                                   class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Email (login) <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" required maxlength="120" placeholder="user@ecole.ci"
                                   class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Téléphone <span class="text-red-500">*</span></label>
                            <input type="tel" name="telephone" value="{{ old('telephone') }}" required maxlength="20" placeholder="+225 XX XX XX XX XX"
                                   class="w-full px-3 py-2.5 bg-white border border-violet-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 outline-none transition-all shadow-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Rôle <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            @foreach([
                                'directeur'         => ['Directeur', '👔'],
                                'directeur_adjoint' => ['Adjoint', '👤'],
                                'gestionnaire'      => ['Gestionnaire', '📊'],
                                'secretaire'        => ['Secrétaire', '📝'],
                                'comptable'         => ['Comptable', '💰'],
                                'censeur'           => ['Censeur', '🏫'],
                            ] as $val => [$lbl, $emoji])
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="role" value="{{ $val }}" @checked(old('role', 'directeur') === $val) required class="sr-only peer">
                                    <div class="p-2.5 bg-white border border-violet-100 rounded-xl text-center hover:border-violet-300 transition-all peer-checked:bg-gradient-to-br peer-checked:from-violet-50 peer-checked:to-violet-100/50 peer-checked:border-violet-400 peer-checked:shadow-sm">
                                        <p class="text-lg">{{ $emoji }}</p>
                                        <p class="text-[11px] font-bold text-gray-800 mt-0.5">{{ $lbl }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end pt-2 border-t border-violet-100/60">
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-violet-500 to-purple-700 text-white text-[13px] font-bold rounded-xl shadow-card-violet hover:-translate-y-0.5 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7zM20 8v6M23 11h-6"/></svg>
                            Créer le compte
                        </button>
                    </div>
                </form>
            </div>

            </details>

            {{-- ── Table utilisateurs ── --}}
            <div class="overflow-x-auto max-h-96 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-[10px] uppercase text-gray-500 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left">Nom</th>
                            <th class="px-4 py-2">Rôle</th>
                            <th class="px-4 py-2">Statut</th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                    @forelse($users as $u)
                        <tr class="hover:bg-gray-50/40">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-900">{{ $u->prenom }} {{ $u->nom }}</p>
                                <p class="text-[11px] text-gray-400">{{ $u->email ?? $u->telephone }}</p>
                            </td>
                            <td class="px-4 py-3 text-center text-xs">
                                <span class="inline-block px-2 py-0.5 rounded-full bg-violet-100 text-violet-700 font-bold">{{ $roles[$u->role] ?? $u->role }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($u->premiere_connexion)
                                    <span class="text-xs font-bold bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">⏳ 1re conn.</span>
                                @elseif($u->actif)
                                    <span class="text-emerald-700 text-xs font-bold bg-emerald-100 px-2 py-0.5 rounded-full">✓ Actif</span>
                                @else
                                    <span class="text-red-600 text-xs font-bold bg-red-100 px-2 py-0.5 rounded-full">✗ Inactif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <form method="POST" action="{{ route('admin.etablissements.users.reset-password', [$e, $u]) }}"
                                      onsubmit="return confirm('Réinitialiser le mot de passe à 0000 ?')" class="inline">
                                    @csrf
                                    <button type="submit" class="text-[11px] font-bold text-amber-600 hover:text-amber-800 px-2">🔁 Reset MDP</button>
                                </form>
                                <form method="POST" action="{{ route('admin.etablissements.users.toggle', [$e, $u]) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-[11px] font-bold text-brand-600 hover:text-brand-800 px-2">{{ $u->actif ? 'Désactiver' : 'Activer' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">
                            Aucun utilisateur — cliquez sur <b>« + Ajouter un compte »</b> pour créer le directeur de cet établissement.
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border shadow-card p-5">
            <h2 class="font-bold mb-3">Années scolaires</h2>
            <ul class="text-sm space-y-2">
                @foreach($annees as $a)
                    <li class="flex justify-between border-b border-gray-50 py-2">
                        <span>{{ $a->libelle }}</span>
                        <span class="text-xs text-gray-500">
                            @if($a->en_cours && !$a->cloturee) <span class="text-brand-600 font-bold">En cours</span> @endif
                            @if($a->cloturee) Archivée @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="bg-white rounded-2xl border shadow-card p-5">
            <h2 class="font-bold mb-3">Paiements récents</h2>
            @forelse($paiements_recents as $p)
                <div class="flex justify-between py-2 border-b border-gray-50 text-sm">
                    <span>{{ $p->eleve?->prenom }} {{ $p->eleve?->nom }}</span>
                    <span class="font-bold">{{ number_format($p->montant, 0, ',', ' ') }} F</span>
                </div>
            @empty
                <p class="text-sm text-gray-400">Aucun paiement.</p>
            @endforelse
        </div>
    </div>

    @if(!$eleves)
    <div class="bg-red-50 border border-red-200 rounded-2xl p-5">
        <p class="font-bold text-red-800 text-sm mb-2">Supprimer l'établissement</p>
        <form method="POST" action="{{ route('admin.etablissements.destroy', $e) }}" onsubmit="return confirm('Suppression définitive ?')">
            @csrf @method('DELETE')
            <label class="flex items-center gap-2 text-sm text-red-700 mb-3">
                <input type="checkbox" name="confirm_delete" value="1" required class="rounded">
                Je confirme la suppression
            </label>
            <button type="submit" class="px-4 py-2 rounded-xl bg-red-600 text-white text-sm font-bold">Supprimer</button>
        </form>
    </div>
    @endif
</div>
@endsection
