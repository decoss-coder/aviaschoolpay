<?php

namespace App\Http\Controllers;

use App\Services\Rentabilite\RentabiliteService;
use Illuminate\Http\Request;

class RentabiliteWebController extends Controller
{
    public function __construct(private RentabiliteService $service) {}

    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        $synthese = $this->service->syntheseGlobale($etab);
        $topClasses = $this->service->parClasse($etab)->take(5);
        return view('rentabilite.index', compact('synthese', 'topClasses', 'etab'));
    }

    public function parClasse(Request $request)
    {
        $etab = $request->user()->etablissement;
        $classes = $this->service->parClasse($etab);
        $synthese = $this->service->syntheseGlobale($etab);
        return view('rentabilite.classes', compact('classes', 'synthese', 'etab'));
    }

    public function parService(Request $request)
    {
        $etab = $request->user()->etablissement;
        $services = $this->service->parService($etab);
        $synthese = $this->service->syntheseGlobale($etab);
        return view('rentabilite.services', compact('services', 'synthese', 'etab'));
    }
}
