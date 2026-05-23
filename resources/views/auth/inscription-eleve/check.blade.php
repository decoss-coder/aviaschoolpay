<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activation compte élève — AviaSchoolPay</title>
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
                    <svg class="w-9 h-9 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3z"/></svg>
                </div>
                <h1 class="font-display text-2xl font-extrabold text-gray-900">Activer mon compte élève</h1>
                <p class="text-sm text-gray-500 mt-1">Entrez votre matricule DESPS pour vérifier votre éligibilité</p>
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
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                    </div>
                    <div>
                        <h3 class="font-display text-base font-extrabold text-gray-900">Vérification matricule</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Délivré par votre établissement</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('inscription.eleve.check') }}" class="relative space-y-4">
                    @csrf
                    <div>
                        <label for="matricule" class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Matricule DESPS <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="matricule" id="matricule" value="{{ old('matricule') }}" required autofocus
                               placeholder="ex : 21280526P"
                               oninput="this.value = this.value.toUpperCase()"
                               class="w-full px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm font-mono uppercase placeholder:text-gray-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-all shadow-sm tracking-wider">
                        <p class="text-[11px] text-gray-400 mt-1">Matricule officiel délivré par votre établissement (ex. 21280526P).</p>
                    </div>

                    <button type="submit"
                            class="w-full mt-2 inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white text-sm font-bold rounded-xl shadow-brand-glow ring-1 ring-brand-300/40 hover:-translate-y-0.5 hover:shadow-lg transition-all">
                        Vérifier mon matricule
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </form>

                <div class="relative mt-5 pt-5 border-t border-brand-100/60 text-center">
                    <p class="text-[11px] text-gray-500">Vous avez déjà un compte ?</p>
                    <a href="{{ route('login') }}" class="text-sm font-bold text-brand-600 hover:underline">Se connecter →</a>
                </div>
            </div>

            <p class="text-center text-[11px] text-gray-400 mt-6">
                Powered by <span class="font-bold text-brand-600">AviaSchoolPay</span>
            </p>
        </div>
    </div>
</body>
</html>
