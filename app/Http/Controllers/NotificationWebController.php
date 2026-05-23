<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Paiement;
use App\Services\Notifications\PaiementNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationWebController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $etabId = $user->ecoleActiveId() ?? $user->etablissement_id;

        $query = Notification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($etabId && ! $user->isSuperAdmin()) {
            $query->where('etablissement_id', $etabId);
        }

        if ($etabId && in_array($user->role, [
            'directeur', 'directeur_adjoint', 'gestionnaire', 'comptable', 'super_admin',
        ], true)) {
            Paiement::query()
                ->where('etablissement_id', $etabId)
                ->where('statut', 'en_attente')
                ->orderByDesc('id')
                ->limit(50)
                ->each(fn (Paiement $p) => PaiementNotificationService::notifierInitie($p));
        }

        $notifications = $query->paginate(30);

        return view('notifications.index', compact('notifications'));
    }

    /** Flux JSON pour la cloche navbar (polling). */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        $etabId = $user->ecoleActiveId() ?? $user->etablissement_id;
        $since = $request->input('since');

        $canVoirPaiements = $etabId && in_array($user->role, [
            'directeur', 'directeur_adjoint', 'gestionnaire', 'comptable', 'super_admin',
        ], true);

        $paiementsEnAttente = 0;
        $pendingRows = collect();

        if ($canVoirPaiements) {
            $pendingRows = Paiement::query()
                ->with('eleve:id,nom,prenom')
                ->where('etablissement_id', $etabId)
                ->where('statut', 'en_attente')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(15)
                ->get();

            $paiementsEnAttente = Paiement::query()
                ->where('etablissement_id', $etabId)
                ->where('statut', 'en_attente')
                ->count();
        }

        $query = Notification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(30);

        if ($etabId && ! $user->isSuperAdmin()) {
            $query->where('etablissement_id', $etabId);
        }

        if ($since) {
            $query->where('created_at', '>', $since);
        }

        $dbItems = $query->get();

        $unreadQuery = Notification::query()
            ->where('user_id', $user->id)
            ->where('lue', false);

        if ($etabId && ! $user->isSuperAdmin()) {
            $unreadQuery->where('etablissement_id', $etabId);
        }

        $unreadCount = $unreadQuery->count();

        $notifPaiementIds = $dbItems
            ->filter(fn (Notification $n) => $n->type === 'paiement')
            ->map(fn (Notification $n) => (int) ($n->metadata['paiement_id'] ?? 0))
            ->filter()
            ->flip();

        $pendingFeed = $pendingRows
            ->reject(fn (Paiement $p) => $notifPaiementIds->has($p->id))
            ->map(fn (Paiement $p) => $this->formatPendingPaiementFeedItem($p))
            ->values();

        $notifFeed = $dbItems
            ->take(15)
            ->map(fn (Notification $n) => $this->formatNotificationFeedItem($n))
            ->values();

        // Paiements en attente en premier, puis notifications récentes
        $feed = $pendingFeed
            ->concat($notifFeed)
            ->unique('id')
            ->take(15)
            ->values();

        $badgeCount = max($unreadCount, $paiementsEnAttente);

        return response()->json([
            'unread_count' => $unreadCount,
            'badge_count' => $badgeCount,
            'paiements_en_attente' => $paiementsEnAttente,
            'notifications' => $feed,
            'latest_at' => $dbItems->first()?->created_at?->toIso8601String(),
        ]);
    }

    private function formatNotificationFeedItem(Notification $n): array
    {
        return [
            'id' => (string) $n->id,
            'titre' => $n->titre,
            'message' => $n->message,
            'type' => $n->type,
            'lue' => $n->lue,
            'lien_action' => $n->lien_action,
            'created_at' => $n->created_at?->toIso8601String(),
            'created_human' => $n->created_at?->diffForHumans(),
            'synthetic' => false,
        ];
    }

    private function formatPendingPaiementFeedItem(Paiement $p): array
    {
        $eleve = $p->eleve;

        return [
            'id' => 'paiement-'.$p->id,
            'titre' => 'Paiement en attente',
            'message' => sprintf(
                '%s %s — %s F via %s (%s). À valider.',
                $eleve?->prenom ?? 'Élève',
                $eleve?->nom ?? '',
                number_format((float) $p->montant, 0, ',', ' '),
                strtoupper($p->mode ?? 'wave'),
                $p->reference
            ),
            'type' => 'paiement_en_attente',
            'lue' => false,
            'lien_action' => route('paiements.show', $p),
            'created_at' => $p->created_at?->toIso8601String(),
            'created_human' => $p->created_at?->diffForHumans() ?? 'À l\'instant',
            'synthetic' => true,
        ];
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update([
            'lue' => true,
            'lue_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $etabId = $user->ecoleActiveId() ?? $user->etablissement_id;

        $query = Notification::query()
            ->where('user_id', $user->id)
            ->where('lue', false);

        if ($etabId && ! $user->isSuperAdmin()) {
            $query->where('etablissement_id', $etabId);
        }

        $query->update(['lue' => true, 'lue_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
