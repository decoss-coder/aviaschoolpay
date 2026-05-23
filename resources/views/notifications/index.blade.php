@extends('layouts.app')

@section('title', 'Notifications')
@section('page-title', 'Notifications')
@section('page-subtitle', 'Paiements, alertes et messages système')

@section('content')
<div class="max-w-3xl mx-auto space-y-3">
    <div class="flex items-center justify-between gap-3 mb-4">
        <p class="text-sm text-gray-600">{{ $notifications->total() }} notification(s)</p>
        <form method="POST" action="{{ route('notifications.read-all') }}" id="mark-all-form">
            @csrf
            <button type="submit" class="text-xs font-bold text-brand-600 hover:text-brand-800">Tout marquer comme lu</button>
        </form>
    </div>

    @forelse($notifications as $n)
        <a href="{{ $n->lien_action ?: '#' }}"
           class="block p-4 rounded-xl border transition-all {{ $n->lue ? 'bg-white border-gray-100' : 'bg-brand-50/80 border-brand-200 shadow-card-brand' }}">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-gray-900 text-sm">{{ $n->titre }}</p>
                    <p class="text-sm text-gray-600 mt-1 leading-relaxed">{{ $n->message }}</p>
                    <p class="text-[11px] text-gray-400 mt-2">{{ $n->created_at->diffForHumans() }} · {{ ucfirst($n->type) }}</p>
                </div>
                @unless($n->lue)
                    <span class="w-2.5 h-2.5 bg-brand-500 rounded-full flex-shrink-0 mt-1"></span>
                @endunless
            </div>
        </a>
    @empty
        <div class="text-center py-16 text-gray-500">
            <p class="text-lg font-semibold">Aucune notification</p>
            <p class="text-sm mt-2">Les paiements et alertes apparaîtront ici.</p>
        </div>
    @endforelse

    <div class="mt-6">{{ $notifications->links() }}</div>
</div>

@push('scripts')
<script>
document.getElementById('mark-all-form')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    await fetch('{{ route('notifications.read-all') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
    });
    window.location.reload();
});
</script>
@endpush
@endsection
