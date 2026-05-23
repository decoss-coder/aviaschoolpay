@php
    $user = auth()->user();
    $solde = null;
    if ($user && $user->etablissement_id && ! $user->isSuperAdmin()) {
        try {
            $solde = \App\Models\SmsCredit::where('etablissement_id', $user->etablissement_id)->value('solde') ?? 0;
        } catch (\Throwable) { $solde = null; }
    }
@endphp

@if($solde !== null)
@php
    // Code couleur selon solde
    $palette = $solde >= 100
        ? ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'text' => 'text-emerald-700', 'dot' => 'bg-emerald-500']
        : ($solde >= 20
            ? ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'text' => 'text-amber-700', 'dot' => 'bg-amber-500']
            : ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'text' => 'text-red-700', 'dot' => 'bg-red-500']);
@endphp
<a href="{{ route('sms.index') }}"
   class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl {{ $palette['bg'] }} {{ $palette['border'] }} border {{ $palette['text'] }} hover:shadow-card transition group"
   title="Solde SMS — cliquez pour recharger">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
    </svg>
    <span class="text-xs font-extrabold tabular-nums">{{ number_format($solde, 0, ',', ' ') }}</span>
    <span class="text-[10px] font-bold opacity-70">SMS</span>
    @if($solde < 20)
        <span class="w-1.5 h-1.5 {{ $palette['dot'] }} rounded-full animate-pulse ml-0.5"></span>
    @endif
</a>
@endif
