@if(!empty($waveActif) && ($resteWave ?? 0) > 0)
<div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl p-5 mt-4">
    <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold shrink-0">W</div>
        <div class="flex-1 min-w-0">
            <h3 class="font-bold text-gray-900">Payer avec Wave</h3>
            <p class="text-xs text-gray-600 mt-0.5">Montant plafonné au reste à payer ({{ number_format($resteWave, 0, ',', ' ') }} FCFA).</p>

            @if(session('wave_url'))
                <div class="mt-3 p-3 bg-white rounded-xl border border-blue-100">
                    <p class="text-xs font-semibold text-emerald-700 mb-2">Lien généré — à partager au parent</p>
                    <a href="{{ session('wave_url') }}" target="_blank" rel="noopener"
                       class="text-sm text-blue-700 font-mono break-all underline">{{ session('wave_url') }}</a>
                    @if(session('wave_message'))
                        <p class="text-xs text-gray-500 mt-2 select-all">{{ session('wave_message') }}</p>
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ $waveFormAction }}" class="mt-3 flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Montant (FCFA)</label>
                    <input type="number" name="montant" min="100" max="{{ $resteWave }}" value="{{ min($resteWave, max(100, $resteWave)) }}"
                           class="w-36 rounded-lg border-gray-200 text-sm" required>
                </div>
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-bold hover:bg-blue-700">
                    Générer le lien Wave
                </button>
            </form>
        </div>
    </div>
</div>
@elseif(empty($waveActif))
    <p class="text-xs text-gray-400 mt-4">Paiement Wave : non configuré pour cet établissement (contactez AviaSchoolPay).</p>
@endif
