<?php

namespace Modules\Resiliation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Resiliation\Models\ContratCampagne;

class AgentController extends Controller
{
    /**
     * Liste paginée des contrats (AC-01, AC-02, AC-03).
     */
    public function liste(Request $request)
    {
        $query = ContratCampagne::query()
            ->with(['campagne'])
            ->withCount('appels');

        // --- Recherche (AC-02) ---
        if ($recherche = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($recherche) {
                $q->where('numero_police', 'like', "%{$recherche}%")
                  ->orWhere('souscripteur_nom', 'like', "%{$recherche}%")
                  ->orWhere('assure_nom', 'like', "%{$recherche}%")
                  ->orWhere('client_telephone', 'like', "%{$recherche}%")
                  ->orWhere('nsil_contrat_id', $recherche);
            });
        }

        // --- Filtre statut (AC-03) ---
        if ($statut = $request->input('statut')) {
            $query->where('statut', $statut);
        }

        // --- Tri : les contrats avec date limite proche d'abord, puis les autres ---
        $query->orderByRaw('
            CASE
                WHEN date_limite IS NULL THEN 1
                ELSE 0
            END
        ')->orderBy('date_limite', 'asc');

        // --- Pagination serveur ---
        $contrats = $query->paginate(25)->withQueryString();

        // --- Stats globales ---
        $stats = [
            'total'         => ContratCampagne::count(),
            'en_delai'      => ContratCampagne::where('statut', 'EN_DELAI')->count(),
            'sms_echec'     => ContratCampagne::where('statut', 'SMS_ECHEC')->count(),
            'regularises'   => ContratCampagne::where('statut', 'REGULARISE')->count(),
            'gain_confirme' => ContratCampagne::where('statut', 'GAIN_CONFIRME')->count(),
        ];

        return view('resiliation::agent.liste', compact('contrats', 'stats'));
    }
}
