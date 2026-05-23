<?php

namespace App\Http\Controllers;

use App\Models\Annonce;
use App\Models\Message;
use App\Models\Notification;
use Illuminate\Http\Request;

class CommunicationWebController extends Controller
{
    public function index(Request $request)
    {
        $etab = $request->user()->etablissement_id;
        $tab = $request->get('tab', 'annonces');

        $stats = [
            'annonces_publiees'  => Annonce::where('etablissement_id', $etab)->where('publiee', true)->count(),
            'annonces_brouillon' => Annonce::where('etablissement_id', $etab)->where('publiee', false)->count(),
            'messages_total'     => Message::where('etablissement_id', $etab)->count(),
            'notif_total'        => Notification::where('etablissement_id', $etab)->count(),
            'notif_envoyees'     => Notification::where('etablissement_id', $etab)->where('envoyee', true)->count(),
            'notif_non_lues'     => Notification::where('etablissement_id', $etab)->where('lue', false)->count(),
        ];

        $annonces = Annonce::where('etablissement_id', $etab)
            ->with('auteur:id,nom,prenom')
            ->latest()
            ->paginate(15, ['*'], 'annonces_page');

        $messages = Message::where('etablissement_id', $etab)
            ->with(['expediteur:id,nom,prenom', 'destinataire:id,nom,prenom'])
            ->latest()
            ->paginate(15, ['*'], 'messages_page');

        $notifications = Notification::where('etablissement_id', $etab)
            ->with('user:id,nom,prenom')
            ->latest()
            ->paginate(20, ['*'], 'notif_page');

        return view('communication.index', compact('stats', 'annonces', 'messages', 'notifications', 'tab'));
    }

    public function storeAnnonce(Request $request)
    {
        $validated = $request->validate([
            'titre'                => 'required|string|max:200',
            'contenu'              => 'required|string',
            'type'                 => 'required|in:annonce,circulaire,convocation,evenement,urgent',
            'audience'             => 'required|in:tous,parents,enseignants,eleves,personnel',
            'date_debut_affichage' => 'required|date',
            'date_fin_affichage'   => 'nullable|date|after_or_equal:date_debut_affichage',
            'envoyer_sms'          => 'nullable|boolean',
            'envoyer_notification' => 'nullable|boolean',
            'publier_maintenant'   => 'nullable|boolean',
        ]);

        $annonce = Annonce::create([
            'etablissement_id'     => $request->user()->etablissement_id,
            'auteur_id'            => $request->user()->id,
            'titre'                => $validated['titre'],
            'contenu'              => $validated['contenu'],
            'type'                 => $validated['type'],
            'audience'             => $validated['audience'],
            'date_debut_affichage' => $validated['date_debut_affichage'],
            'date_fin_affichage'   => $validated['date_fin_affichage'] ?? null,
            'envoyer_sms'          => ! empty($validated['envoyer_sms']),
            'envoyer_notification' => $validated['envoyer_notification'] ?? true,
            'publiee'              => ! empty($validated['publier_maintenant']),
        ]);

        return redirect()->route('communication.index', ['tab' => 'annonces'])
            ->with('success', $annonce->publiee ? 'Annonce publiée.' : 'Annonce enregistrée en brouillon.');
    }

    public function publierAnnonce(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $annonce = Annonce::where('etablissement_id', $etab)->findOrFail($id);
        $annonce->update(['publiee' => true]);
        return back()->with('success', 'Annonce publiée.');
    }

    public function destroyAnnonce(Request $request, $id)
    {
        $etab = $request->user()->etablissement_id;
        $annonce = Annonce::where('etablissement_id', $etab)->findOrFail($id);
        $annonce->delete();
        return back()->with('success', 'Annonce supprimée.');
    }
}
