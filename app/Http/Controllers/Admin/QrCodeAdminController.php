<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Enseignant;
use App\Models\Pointage;
use App\Models\QrCode as QrCodeModel;
use App\Models\Salle;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;

class QrCodeAdminController extends Controller
{
    /**
     * Liste des salles avec leur QR code actif (ou non).
     */
    public function index(Request $request)
    {
        $etabId = $request->user()->etablissement_id;
        $annee  = \App\Services\Scolarite\AnneeScolaireContext::courantePourEtablissement($etabId);

        $salles = Salle::where('etablissement_id', $etabId)
            ->where('active', true)
            ->with('qrCodeActif')
            ->orderBy('batiment')
            ->orderBy('nom')
            ->get();

        // Enseignants affectés cette année (qui pourront scanner)
        $enseignantsCount = $annee
            ? Enseignant::where('etablissement_id', $etabId)
                ->where('actif', true)
                ->affectesCetteAnnee($annee->id)
                ->count()
            : 0;

        // Pointages effectués cette année scolaire (santé du dispositif)
        $pointagesAnnee = $annee
            ? Pointage::where('etablissement_id', $etabId)
                ->whereBetween('date', [$annee->date_debut, $annee->date_fin])
                ->count()
            : 0;

        $stats = [
            'total_salles'      => $salles->count(),
            'avec_qr'           => $salles->whereNotNull('qrCodeActif')->count(),
            'sans_qr'           => $salles->whereNull('qrCodeActif')->count(),
            'total_qr_actifs'   => QrCodeModel::where('etablissement_id', $etabId)->where('actif', true)->count(),
            'enseignants_actifs'=> $enseignantsCount,
            'pointages_annee'   => $pointagesAnnee,
        ];

        return view('admin.rh.qr-codes.index', compact('salles', 'stats', 'annee'));
    }

    /**
     * Générer un QR pour une salle qui n'en a pas (ou régénérer).
     */
    public function generate(Request $request, Salle $salle)
    {
        abort_unless($salle->etablissement_id === $request->user()->etablissement_id, 404);

        $qr = QrCodeModel::genererPourSalle($salle);

        return back()->with('success', "QR Code généré pour la salle {$salle->nom}.");
    }

    /**
     * Génère un QR pour TOUTES les salles qui n'en ont pas.
     */
    public function generateAll(Request $request)
    {
        $etabId = $request->user()->etablissement_id;

        $count = 0;
        Salle::where('etablissement_id', $etabId)
            ->where('active', true)
            ->whereDoesntHave('qrCodeActif')
            ->get()
            ->each(function (Salle $salle) use (&$count) {
                QrCodeModel::genererPourSalle($salle);
                $count++;
            });

        return back()->with('success', "{$count} QR Code(s) généré(s).");
    }

    /**
     * Désactiver un QR (ex : compromis, perdu).
     */
    public function deactivate(Request $request, QrCodeModel $qrCode)
    {
        abort_unless($qrCode->etablissement_id === $request->user()->etablissement_id, 404);

        $request->validate(['motif' => 'nullable|string|max:255']);

        $qrCode->update([
            'actif' => false,
            'date_desactivation' => now(),
            'motif_desactivation' => $request->input('motif', 'Désactivé manuellement'),
        ]);

        return back()->with('success', 'QR Code désactivé.');
    }

    /**
     * Image PNG d'un QR (preview inline).
     */
    public function image(Request $request, QrCodeModel $qrCode)
    {
        abort_unless($qrCode->etablissement_id === $request->user()->etablissement_id, 404);

        $png = $this->buildPng($qrCode->contenu_qr, 400);

        return response($png)->header('Content-Type', 'image/png');
    }

    private function buildPng(string $contenu, int $size = 400): string
    {
        return Builder::create()
            ->writer(new PngWriter())
            ->data($contenu)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size($size)
            ->margin(10)
            ->build()
            ->getString();
    }

    /**
     * PDF affichable : 1 ou plusieurs salles par feuille selon le format.
     */
    public function pdfPoster(Request $request)
    {
        $etabId = $request->user()->etablissement_id;

        $request->validate([
            'salles'  => 'nullable|array',
            'salles.*'=> 'integer',
            'format'  => 'nullable|in:1,4',
        ]);

        $format = (int) $request->input('format', 1); // 1 par page (gros) ou 4 par page

        $query = Salle::where('etablissement_id', $etabId)
            ->where('active', true)
            ->with('qrCodeActif', 'etablissement');

        if ($request->filled('salles')) {
            $query->whereIn('id', $request->input('salles'));
        }

        $salles = $query->whereHas('qrCodeActif')
            ->orderBy('batiment')
            ->orderBy('nom')
            ->get();

        // Générer base64 PNG pour chaque salle
        $salles = $salles->map(function (Salle $s) {
            $qr = $s->qrCodeActif;
            $png = $this->buildPng($qr->contenu_qr, 500);
            return [
                'salle'      => $s,
                'qr'         => $qr,
                'qr_base64'  => 'data:image/png;base64,' . base64_encode($png),
            ];
        });

        $pdf = Pdf::loadView('admin.rh.qr-codes.poster', [
            'salles' => $salles,
            'format' => $format,
            'etab'   => $request->user()->etablissement,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("qr-codes-{$format}p_" . now()->format('Y-m-d') . ".pdf");
    }
}
