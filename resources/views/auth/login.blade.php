<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — AviaSchoolPay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: {
                brand: { 50:'#E8F5EE', 100:'#C3E6D1', 400:'#2DAA5B', 500:'#0A7B3F', 600:'#086832', 700:'#065526', 800:'#04421A' },
                gold: { 50:'#FFF8E6', 400:'#E8A817', 500:'#C48E0F' },
            }}}
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'DM Sans', system-ui, sans-serif; }</style>
</head>
<body class="h-full bg-gray-50">
    <div class="min-h-full flex">

        {{-- Panneau gauche : illustration --}}
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-brand-600 via-brand-700 to-brand-800 relative overflow-hidden">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 400 400" fill="none">
                    <circle cx="200" cy="200" r="180" stroke="white" stroke-width="0.5"/>
                    <circle cx="200" cy="200" r="130" stroke="white" stroke-width="0.5"/>
                    <circle cx="200" cy="200" r="80" stroke="white" stroke-width="0.5"/>
                    <line x1="0" y1="200" x2="400" y2="200" stroke="white" stroke-width="0.3"/>
                    <line x1="200" y1="0" x2="200" y2="400" stroke="white" stroke-width="0.3"/>
                </svg>
            </div>

            <div class="relative z-10 flex flex-col justify-center px-16">
                <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center mb-8 backdrop-blur-sm">
                    <svg class="w-9 h-9 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/></svg>
                </div>
                <h1 class="text-4xl font-bold text-white leading-tight mb-4">AviaSchoolPay</h1>
                <p class="text-brand-100 text-lg mb-8">ERP Scolaire Intelligent avec IA d'aide à la décision</p>

                <div class="space-y-4">
                    <div class="flex items-center gap-3 text-brand-100">
                        <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <span class="text-sm">Pointage enseignant par QR Code + GPS</span>
                    </div>
                    <div class="flex items-center gap-3 text-brand-100">
                        <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/></svg>
                        </div>
                        <span class="text-sm">Paiement Mobile Money via PayDunya</span>
                    </div>
                    <div class="flex items-center gap-3 text-brand-100">
                        <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <span class="text-sm">Conforme SIGFNE / DESPS — MENA</span>
                    </div>
                    <div class="flex items-center gap-3 text-brand-100">
                        <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                        </div>
                        <span class="text-sm">IA d'aide à la décision intégrée</span>
                    </div>
                </div>

                <div class="mt-12 pt-8 border-t border-white/10">
                    <p class="text-brand-200 text-xs">Conçu pour les écoles de Côte d'Ivoire et d'Afrique de l'Ouest</p>
                </div>
            </div>
        </div>

        {{-- Panneau droit : formulaire --}}
        <div class="w-full lg:w-1/2 flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-md">

                {{-- Logo mobile --}}
                <div class="lg:hidden flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 bg-brand-500 rounded-xl flex items-center justify-center shadow-lg shadow-brand-500/30">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-brand-700">AviaSchoolPay</h1>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">ERP Scolaire Intelligent</p>
                    </div>
                </div>

                <h2 class="text-2xl font-bold text-gray-900 mb-1">Bienvenue</h2>
                <p class="text-gray-500 text-sm mb-8">Connectez-vous à votre espace AviaSchoolPay</p>

                {{-- Erreurs --}}
                @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm text-red-700 font-medium">{{ $errors->first('login') }}</p>
                    </div>
                </div>
                @endif

                {{-- Formulaire --}}
                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    {{-- Email, Téléphone ou Matricule --}}
                    <div>
                        <label for="login" class="block text-sm font-semibold text-gray-700 mb-1.5">Email, Téléphone ou Matricule</label>
                        <div class="relative">
                            <input type="text" name="login" id="login" value="{{ old('login') }}"
                                   placeholder="ex: 07 08 09 10 11 · admin@ecole.ci · 21280526P"
                                   class="w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-300 focus:border-brand-400 outline-none transition-all {{ $errors->has('login') ? 'border-red-300 bg-red-50' : '' }}"
                                   required autofocus>
                            <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                    </div>

                    {{-- Mot de passe --}}
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">Mot de passe</label>
                        <div class="relative">
                            <input type="password" name="password" id="password"
                                   placeholder="••••••••"
                                   class="w-full pl-11 pr-12 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-300 focus:border-brand-400 outline-none transition-all"
                                   required>
                            <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <button type="button" onclick="togglePassword()" class="absolute right-3.5 top-3.5 text-gray-400 hover:text-gray-600">
                                <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Se souvenir + Oublié --}}
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="w-4 h-4 text-brand-500 border-gray-300 rounded focus:ring-brand-400">
                            <span class="text-sm text-gray-600">Se souvenir de moi</span>
                        </label>
                        <a href="#" class="text-sm text-brand-600 hover:text-brand-700 font-medium">Mot de passe oublié ?</a>
                    </div>

                    {{-- Bouton connexion --}}
                    <button type="submit" class="w-full bg-brand-500 hover:bg-brand-600 text-white font-semibold py-3 rounded-xl transition-all shadow-lg shadow-brand-500/25 hover:shadow-brand-500/40 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        Se connecter
                    </button>
                </form>

                {{-- Activation compte élève --}}
                <div class="mt-6 bg-pink-50 border border-pink-100 rounded-xl px-4 py-3 text-center">
                    <p class="text-xs text-pink-700">
                        <span class="font-bold">Élève ?</span> Activez votre compte avec votre matricule DESPS.
                    </p>
                    <a href="{{ route('inscription.eleve.check') }}"
                       class="inline-flex items-center gap-1.5 mt-2 bg-pink-600 hover:bg-pink-700 text-white text-xs font-bold px-4 py-1.5 rounded-lg transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Créer mon mot de passe
                    </a>
                </div>

                {{-- Rôles --}}
                <div class="mt-8 pt-6 border-t border-gray-100">
                    <p class="text-xs text-gray-400 text-center mb-3">Accès par profil</p>
                    <div class="flex justify-center gap-2 flex-wrap">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold bg-brand-50 text-brand-700">Directeur</span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700">Enseignant</span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold bg-purple-50 text-purple-700">Comptable</span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700">Parent</span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold bg-pink-50 text-pink-700">Élève</span>
                    </div>
                </div>

                <p class="text-center text-xs text-gray-400 mt-6">AviaSchoolPay v1.0 — L'intelligence au service de l'éducation africaine</p>
            </div>
        </div>
    </div>

    <script>
    function togglePassword() {
        const input = document.getElementById('password');
        input.type = input.type === 'password' ? 'text' : 'password';
    }
    </script>
</body>
</html>