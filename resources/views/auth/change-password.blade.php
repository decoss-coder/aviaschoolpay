<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changer votre mot de passe — AviaSchoolPay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: {
                    brand: { 50:'#E8F5EE', 100:'#C3E6D1', 200:'#8FD4A8', 300:'#5BBF7F', 400:'#2DAA5B', 500:'#0A7B3F', 600:'#086832', 700:'#065526', 800:'#04421A', 900:'#022F0E' },
                    gold:  { 50:'#FFF8E6', 100:'#FFE9B3', 200:'#FFD97F', 300:'#FFC94C', 400:'#E8A817', 500:'#C48E0F', 600:'#A37708' },
                },
                fontFamily: {
                    sans: ['DM Sans', 'system-ui', 'sans-serif'],
                    display: ['Bricolage Grotesque', 'DM Sans', 'system-ui', 'sans-serif'],
                },
                boxShadow: {
                    'brand-glow':  '0 8px 24px -8px rgba(10, 123, 63, 0.4), 0 2px 4px -1px rgba(10, 123, 63, 0.1)',
                    'card-brand':  '0 8px 24px -8px rgba(10, 123, 63, 0.18), 0 2px 4px -1px rgba(10, 123, 63, 0.06)',
                },
            }}
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Bricolage+Grotesque:wght@700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'DM Sans', system-ui, sans-serif; }</style>
</head>
<body class="min-h-full bg-gradient-to-br from-brand-50 via-white to-gold-50/30 flex items-center justify-center p-4">
    <div class="w-full max-w-md">

        {{-- En-tête --}}
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-gradient-to-br from-brand-400 to-brand-600 rounded-2xl mx-auto flex items-center justify-center mb-4 shadow-brand-glow text-white text-2xl">
                🔐
            </div>
            <h1 class="font-display text-2xl font-extrabold text-gray-900">Première connexion</h1>
            <p class="text-sm text-gray-500 mt-2">
                Bonjour <span class="font-bold text-brand-700">{{ $user->prenom }} {{ $user->nom }}</span>,
                définissez un mot de passe personnel pour votre espace.
            </p>
        </div>

        {{-- Erreurs --}}
        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 mb-4">
                <p class="font-bold mb-1">Veuillez corriger les erreurs :</p>
                <ul class="list-disc list-inside text-xs space-y-0.5">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        {{-- Carte formulaire ─ pattern référence ─ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand p-6">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-brand-200/20 rounded-full blur-3xl"></div>

            <div class="relative flex items-center gap-3 mb-6 pb-4 border-b border-brand-100/60">
                <div class="w-10 h-10 bg-gradient-to-br from-brand-400 to-brand-600 rounded-xl flex items-center justify-center shadow-brand-glow">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <div>
                    <h3 class="font-display text-base font-extrabold text-gray-900">Nouveau mot de passe</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Sécurisez votre compte</p>
                </div>
            </div>

            <p class="relative text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 mb-4">
                ⚠ Remplacez le mot de passe temporaire <strong class="font-mono">0000</strong> par un mot de passe que vous seul connaissez.
            </p>

            <form method="POST" action="{{ route('password.premiere.update') }}" class="relative space-y-4">
                @csrf

                <div>
                    <label for="password" class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Nouveau mot de passe <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="password" id="password" required minlength="4"
                           placeholder="Minimum 4 caractères"
                           class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Confirmation <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required minlength="4"
                           placeholder="Ressaisissez le mot de passe"
                           class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm">
                </div>

                <button type="submit"
                        class="w-full mt-2 inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-sm font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:-translate-y-0.5 hover:shadow-lg transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Enregistrer et accéder à mon espace
                </button>
            </form>
        </div>

        <p class="text-center text-[11px] text-gray-400 mt-6">
            Powered by <span class="font-bold text-brand-600">AviaSchoolPay</span>
        </p>
    </div>
</body>
</html>
