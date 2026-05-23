<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe — AviaSchoolPay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: {
                    brand: { 50:'#E8F5EE', 100:'#C3E6D1', 200:'#8FD4A8', 300:'#5BBF7F', 400:'#2DAA5B', 500:'#0A7B3F', 600:'#086832', 700:'#065526', 800:'#04421A' },
                    gold:  { 50:'#FFF8E6', 200:'#FFD97F', 300:'#FFC94C', 400:'#E8A817', 500:'#C48E0F' },
                },
                fontFamily: {
                    sans: ['DM Sans', 'system-ui', 'sans-serif'],
                    display: ['Bricolage Grotesque', 'DM Sans', 'system-ui', 'sans-serif'],
                },
                boxShadow: {
                    'brand-glow': '0 8px 24px -8px rgba(10, 123, 63, 0.4), 0 2px 4px -1px rgba(10, 123, 63, 0.1)',
                    'card-brand': '0 8px 24px -8px rgba(10, 123, 63, 0.18), 0 2px 4px -1px rgba(10, 123, 63, 0.06)',
                },
            }}
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Bricolage+Grotesque:wght@700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'DM Sans', system-ui, sans-serif; }</style>
</head>
<body class="h-full bg-gradient-to-br from-brand-50 via-white to-gold-50/30">
    <div class="min-h-full flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">

            {{-- En-tête --}}
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-gradient-to-br from-brand-400 to-brand-600 rounded-2xl mx-auto flex items-center justify-center mb-4 shadow-brand-glow">
                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <h1 class="font-display text-2xl font-extrabold text-gray-900">Créer mon mot de passe</h1>
                <p class="text-sm text-gray-500 mt-1">Confirmez votre identité pour finaliser votre compte</p>
            </div>

            {{-- Récap élève (carte brand dégradée) --}}
            <div class="relative overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-brand-800 rounded-2xl shadow-brand-glow p-5 text-white mb-4">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-gold-300 to-gold-500"></div>
                <div class="absolute -top-6 -right-6 w-24 h-24 bg-gold-400/20 rounded-full blur-xl"></div>

                <div class="relative flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center font-display font-extrabold text-lg">
                        {{ strtoupper(substr($eleve->prenom, 0, 1)) }}{{ strtoupper(substr($eleve->nom, 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-display font-extrabold text-base">{{ strtoupper($eleve->nom) }} {{ $eleve->prenom }}</p>
                        <p class="text-[11px] text-brand-100 mt-0.5">
                            <span class="font-mono font-bold">{{ $eleve->matricule_desps ?: $eleve->matricule_interne }}</span>
                            @if($eleve->classe)<span class="mx-1">·</span>{{ $eleve->classe->nom }}@endif
                        </p>
                    </div>
                </div>
            </div>

            @if($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 mb-4">
                    <ul class="list-disc list-inside text-xs space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            {{-- Carte formulaire ─ pattern référence ─ --}}
            <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand p-6">
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-200/20 rounded-full blur-3xl"></div>

                <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-brand-100/60">
                    <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center shadow-brand-glow">
                        <span class="font-display text-white font-extrabold text-sm">1</span>
                    </div>
                    <div>
                        <h3 class="font-display text-base font-extrabold text-gray-900">Sécurisation du compte</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Vérification anti-usurpation</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('inscription.eleve.create', ['token' => $token]) }}" class="relative space-y-4">
                    @csrf

                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            🎂 Date de naissance
                            @if($dateNaissanceRequise)
                                <span class="text-red-500">*</span>
                            @else
                                <span class="text-gray-400 normal-case font-normal">(facultatif)</span>
                            @endif
                        </label>
                        @include('components.date-naissance-fr', [
                            'name' => 'date_naissance',
                            'required' => $dateNaissanceRequise,
                            'value' => old('date_naissance'),
                        ])
                        <p class="text-[11px] text-gray-400 mt-1">
                            @if($dateNaissanceRequise)
                                Sélectionnez la date enregistrée par l'école (JJ/MM/AAAA).
                            @else
                                Laissez vide si non renseignée par votre établissement.
                            @endif
                        </p>
                    </div>

                    <div>
                        <label for="telephone_parent" class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            📱 Téléphone du parent / tuteur <span class="text-red-500">*</span>
                        </label>
                        <input type="tel" name="telephone_parent" id="telephone_parent" value="{{ old('telephone_parent') }}" required
                               inputmode="tel" autocomplete="tel"
                               placeholder="Ex : 07 12 34 56 78"
                               class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                        <p class="text-[11px] text-gray-400 mt-1">Doit correspondre au numéro enregistré à l'école.</p>
                    </div>

                    <div>
                        <label for="password" class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            🔐 Mot de passe <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="password" id="password" required minlength="6"
                               placeholder="Au moins 6 caractères"
                               class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            🔁 Confirmer le mot de passe <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required minlength="6"
                               placeholder="Retapez votre mot de passe"
                               class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                    </div>

                    <div class="flex items-center justify-between gap-3 pt-3 border-t border-brand-100/60">
                        <a href="{{ route('inscription.eleve.check') }}"
                           class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-[13px] font-bold rounded-xl shadow-sm hover:bg-gray-50 transition-all">
                            ← Retour
                        </a>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-[13px] font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:-translate-y-0.5 hover:shadow-lg transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Créer mon compte
                        </button>
                    </div>
                </form>
            </div>

            <p class="text-center text-[11px] text-gray-400 mt-6">
                🔒 Vos données restent dans votre établissement. <span class="font-bold text-brand-600">AviaSchoolPay</span>
            </p>
        </div>
    </div>
</body>
</html>
