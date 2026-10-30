<?php

namespace Modules\Resiliation\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Resiliation\Models\CampagneResiliation;
use Modules\Resiliation\Models\ContratCampagne;
use Modules\Resiliation\Models\FactureImpayee;
use Carbon\Carbon;

class NsilImportService
{
    /** @var array<int, array{ ligne: int, raison: string }> */
    protected array $erreurs = [];

    /** @var array<int, array{ ligne: int, raison: string }> */
    protected array $rejetes = [];

    protected int $importes = 0;
    protected int $facturesCreees = 0;

    /**
     * Importe un fichier TSV NSIL dans une campagne.
     */
    public function importer2(string $cheminFichier, CampagneResiliation $campagne): array
    {
        $this->reset();

        if (!file_exists($cheminFichier)) {
            throw new \RuntimeException("Fichier introuvable : {$cheminFichier}");
        }

        $handle = fopen($cheminFichier, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Impossible d'ouvrir le fichier : {$cheminFichier}");
        }

        // Lire l'en-tête (1re ligne)
        $entete = fgetcsv($handle, 0, "\t");
        if ($entete === false) {
            fclose($handle);
            throw new \RuntimeException("Fichier vide ou en-tête illisible.");
        }

        $entete = array_map('trim', $entete);
        $colonnesAttendues = [
            'IdProposition',
            'CodeProduit',
            'CodeProduitFormule',
            'CodeProposition',
            'SOUSCRIPTEUR',
            'Assure Principal',
            'Joignable',
            'Moyen de contact',
            'Nb emails',
            'Email 1',
            'Email 2',
            'Nb téléphones',
            'ONECI - Téléphone',
            '#1 Téléphone',
            '#2 Téléphone',
            '#3 Téléphone',
            '#4 Téléphone',
            '#5 Téléphone',
            'CodeConseiller',
            'NomConseiller',
            'Inspection',
            'ConseillerManquant',
            'Naissance Assure Principal',
            'DateEffetReel',
            'AnneeffetReel',
            'echeance',
            'AnneEcheance',
            'DureeCotisationAns',
            'CodePerodicite',
            'Periodicite',
            'TotalPrime',
            'Canal Distribution',
            'Statut',
            'Nbre Encaissement',
            'Premier Encaissement',
            'Date Dernier Encaissement',
            'Date Evaluation Ancienneté',
            'Ancienneté',
            'Nbre Enc qui devrait etre réalisé',
            'Impayés',
            'Cumul Encaissement',
            'duree_courue',
            'CodeModePaiement',
            'Cotisation Annuelle',
            'Cumul cotisation theorique',
            'Cumul cotisation Requis',
            'genre',
            'Date effet theorique',
            'Impayés Sup à un (1)',
            'Etat contrat',
            'PM_Déc2026 Sup_01_impayé',
        ];

        // Mapper l'en-tête → index (tolérant aux variations d'espaces)
        $index = [];
        foreach ($entete as $i => $col) {
            $col = trim($col);
            $index[$col] = $i;
        }

        // Vérifier les colonnes critiques
        $critiques = ['IdProposition', 'CodeProposition', 'SOUSCRIPTEUR', '#1 Téléphone', 'Impayés', 'PM_Déc2026 Sup_01_impayé'];
        foreach ($critiques as $col) {
            if (!isset($index[$col])) {
                fclose($handle);
                throw new \RuntimeException("Colonne critique manquante dans l'en-tête : {$col}");
            }
        }

        $numeroLigne = 1;
        DB::beginTransaction();

        try {
            while (($ligne = fgetcsv($handle, 0, "\t")) !== false) {
                $numeroLigne++;

                // Ignorer les lignes vides
                if (empty(array_filter($ligne, fn($v) => trim((string) $v) !== ''))) {
                    continue;
                }

                try {
                    $this->traiterLigne($ligne, $index, $campagne, $numeroLigne);
                } catch (\Throwable $e) {
                    $this->erreurs[] = [
                        'ligne'  => $numeroLigne,
                        'raison' => $e->getMessage(),
                    ];
                    Log::warning("Import NSIL - ligne {$numeroLigne} : " . $e->getMessage());
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }

        fclose($handle);

        return [
            'importes'        => $this->importes,
            'factures_creees' => $this->facturesCreees,
            'rejetes'         => $this->rejetes,
            'erreurs'         => $this->erreurs,
        ];
    }

    public function importer(string $cheminFichier, CampagneResiliation $campagne): array
    {
        $this->reset();

        if (!file_exists($cheminFichier)) {
            throw new \RuntimeException("Fichier introuvable : {$cheminFichier}");
        }

        // Lire tout le contenu et retirer le BOM UTF-8 si présent
        $contenu = file_get_contents($cheminFichier);
        if ($contenu === false) {
            throw new \RuntimeException("Impossible de lire le fichier : {$cheminFichier}");
        }
        $contenu = $this->retirerBom($contenu);

        // Écrire dans un fichier temporaire nettoyé (pour fgetcsv)
        $tmp = tempnam(sys_get_temp_dir(), 'nsil_');
        file_put_contents($tmp, $contenu);

        $handle = fopen($tmp, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Impossible d'ouvrir le fichier temporaire.");
        }

        try {
            // Lire l'en-tête
            $entete = fgetcsv($handle, 0, "\t");
            if ($entete === false || $entete === [null]) {
                throw new \RuntimeException("Fichier vide ou en-tête illisible.");
            }

            // Normaliser l'en-tête
            $index = [];
            foreach ($entete as $i => $col) {
                $col = $this->normaliserNomColonne($col);
                if ($col !== '') {
                    $index[$col] = $i;
                }
            }

            // Vérifier les colonnes critiques
            $critiques = [
                'IdProposition',
                'CodeProposition',
                'SOUSCRIPTEUR',
                '#1 Téléphone',
                'Impayés',
                'PM_Déc2026 Sup_01_impayé',
            ];
            foreach ($critiques as $col) {
                if (!isset($index[$col])) {
                    throw new \RuntimeException(
                        "Colonne critique manquante : '{$col}'. " .
                            "Colonnes détectées : " . implode(', ', array_keys($index))
                    );
                }
            }

            $numeroLigne = 1;
            DB::beginTransaction();

            try {
                while (($ligne = fgetcsv($handle, 0, "\t")) !== false) {
                    $numeroLigne++;

                    // Ignorer les lignes vides
                    if (empty(array_filter($ligne, fn($v) => trim((string) $v) !== ''))) {
                        continue;
                    }

                    // Ignorer les lignes dont le nombre de colonnes ne correspond pas
                    if (count($ligne) < count($entete) / 2) {
                        $this->erreurs[] = [
                            'ligne'  => $numeroLigne,
                            'raison' => "Nombre de colonnes insuffisant (" . count($ligne) . " < " . count($entete) . ")",
                        ];
                        continue;
                    }

                    try {
                        $this->traiterLigne($ligne, $index, $campagne, $numeroLigne);
                    } catch (\Throwable $e) {
                        $this->erreurs[] = [
                            'ligne'  => $numeroLigne,
                            'raison' => $e->getMessage(),
                        ];
                        Log::warning("Import NSIL - ligne {$numeroLigne} : " . $e->getMessage());
                    }
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            return [
                'importes'        => $this->importes,
                'factures_creees' => $this->facturesCreees,
                'rejetes'         => $this->rejetes,
                'erreurs'         => $this->erreurs,
            ];
        } finally {
            fclose($handle);
            @unlink($tmp);
        }
    }


    /**
     * Retire le BOM UTF-8 (EF BB BF) en début de fichier.
     */
    protected function retirerBom(string $contenu): string
    {
        if (substr($contenu, 0, 3) === "\xEF\xBB\xBF") {
            $contenu = substr($contenu, 3);
        }
        return $contenu;
    }

    /**
     * Normalise un nom de colonne :
     * - retire le BOM résiduel
     * - retire les espaces/tabulations en début/fin
     * - remplace les espaces insécables par un espace normal
     * - remplace les apostrophes typographiques (’) par (')
     * - compresse les espaces multiples en un seul
     */
    protected function normaliserNomColonne(?string $col): string
    {
        if ($col === null) {
            return '';
        }
        $col = str_replace("\xEF\xBB\xBF", '', $col);
        $col = str_replace(["\u{00A0}", "\u{202F}"], ' ', $col);
        $col = str_replace(['’', '‘', '´'], "'", $col);
        $col = preg_replace('/\s+/u', ' ', $col);
        return trim($col);
    }

    protected function reset(): void
    {
        $this->importes = 0;
        $this->facturesCreees = 0;
        $this->erreurs = [];
        $this->rejetes = [];
    }

    /**
     * Traite une ligne du fichier.
     */
    protected function traiterLigne(array $ligne, array $index, CampagneResiliation $campagne, int $numeroLigne): void
    {
        $get = fn(string $col) => isset($index[$col]) ? trim((string) ($ligne[$index[$col]] ?? '')) : '';

        // --- Identifiants ---
        $nsilId = (int) $get('IdProposition');
        if ($nsilId <= 0) {
            throw new \RuntimeException('IdProposition manquant ou invalide.');
        }

        // --- Filtre d'éligibilité ---
        $nbImpayes = (int) $this->nettoyerNombre($get('Impayés'));
        $pm        = (float) $this->nettoyerNombre($get('PM_Déc2026 Sup_01_impayé'));
        $telephone = $this->nettoyerTelephone($get('#1 Téléphone'));

        if ($nbImpayes <= 0) {
            $this->rejetes[] = [
                'ligne'  => $numeroLigne,
                'raison' => "Aucun impayé (Impayés = {$nbImpayes})",
            ];
            return;
        }

        if ($telephone === null) {
            $this->rejetes[] = [
                'ligne'  => $numeroLigne,
                'raison' => "Téléphone principal invalide (#1 Téléphone = '{$get('#1 Téléphone')}')",
            ];
            return;
        }

        // --- Vérifier si le contrat existe déjà (idempotence) ---
        $existant = ContratCampagne::where('nsil_contrat_id', $nsilId)
            ->where('campagne_id', $campagne->id)
            ->first();

        if ($existant) {
            $this->rejetes[] = [
                'ligne'  => $numeroLigne,
                'raison' => "Contrat déjà présent dans la campagne (nsil_contrat_id={$nsilId})",
            ];
            return;
        }

        // --- Création du contrat ---
        $contrat = ContratCampagne::create([
            'campagne_id'              => $campagne->id,
            'nsil_contrat_id'          => $nsilId,
            'code_produit'             => $get('CodeProduit') ?: null,
            'code_formule'             => $get('CodeProduitFormule') ?: null,
            'numero_police'            => $get('CodeProposition'),
            'souscripteur_nom'         => $get('SOUSCRIPTEUR'),
            'assure_nom'               => $get('Assure Principal') ?: $get('SOUSCRIPTEUR'),
            'joignable'                => strtolower($get('Joignable')) === 'oui',
            'moyen_contact'            => $get('Moyen de contact') ?: null,
            'email_1'                  => $get('Email 1') ?: null,
            'email_2'                  => $get('Email 2') ?: null,
            'telephone_oneci'          => $this->nettoyerTelephone($get('ONECI - Téléphone')),
            'client_telephone'         => $telephone,
            'telephone_2'              => $this->nettoyerTelephone($get('#2 Téléphone')),
            'telephone_3'              => $this->nettoyerTelephone($get('#3 Téléphone')),
            'telephone_4'              => $this->nettoyerTelephone($get('#4 Téléphone')),
            'telephone_5'              => $this->nettoyerTelephone($get('#5 Téléphone')),
            'code_conseiller'          => $get('CodeConseiller') ?: null,
            'nom_conseiller'           => $get('NomConseiller') ?: null,
            'inspection'               => $get('Inspection') ?: null,
            'canal_distribution'       => $get('Canal Distribution') ?: null,
            'conseiller_manquant'      => strtolower($get('ConseillerManquant')) === 'oui',
            'date_naissance_assure'    => $this->parserDate($get('Naissance Assure Principal')),
            'date_effet_reel'          => $this->parserDate($get('DateEffetReel')),
            'date_echeance'            => $this->parserDate($get('echeance')),
            'duree_cotisation_ans'     => $this->nettoyerEntier($get('DureeCotisationAns')),
            'code_periodicite'         => $get('CodePerodicite') ?: null,
            'periodicite'              => $this->nettoyerEntier($get('Periodicite')),
            'total_prime'              => $this->nettoyerNombre($get('TotalPrime')),
            'statut_nsil'              => $get('Statut') ?: null,
            'etat_contrat_nsil'        => $get('Etat contrat') ?: null,
            'nb_encaissements'         => $this->nettoyerEntier($get('Nbre Encaissement')),
            'date_premier_encaissement' => $this->parserDate($get('Premier Encaissement')),
            'date_dernier_encaissement' => $this->parserDate($get('Date Dernier Encaissement')),
            'anciennete'               => $this->nettoyerNombre($get('Ancienneté')),
            'nb_enc_theorique'         => $this->nettoyerEntier($get('Nbre Enc qui devrait etre réalisé')),
            'cumul_encaissement'       => $this->nettoyerNombre($get('Cumul Encaissement')),
            'duree_courue'             => $this->nettoyerNombre($get('duree_courue')),
            'code_mode_paiement'       => $get('CodeModePaiement') ?: null,
            'cotisation_annuelle'      => $this->nettoyerNombre($get('Cotisation Annuelle')),
            'cumul_cotisation_theorique' => $this->nettoyerNombre($get('Cumul cotisation theorique')),
            'cumul_cotisation_requis'  => $this->nettoyerNombre($get('Cumul cotisation Requis')),
            'genre'                    => in_array($get('genre'), ['M', 'F']) ? $get('genre') : null,
            'nb_impayes'               => $nbImpayes,
            'flag_impaye_sup_1'        => $this->nettoyerEntier($get('Impayés Sup à un (1)')) === 1,
            'pm_contrat'               => $pm,
            'statut'                   => 'INTEGRE',
            'date_integration'         => now(),
        ]);

        // --- Génération des factures impayées ---
        $this->creerFactures($contrat, $nbImpayes);

        $this->importes++;
    }

    /**
     * Génère les factures impayées pour un contrat.
     *
     * ⚠️ Hypothèse de travail en attendant la réponse DNA :
     * on découpe le montant total impayé en N factures égales
     * (N = nb_impayes). À remplacer par un import réel NSIL quand
     * la règle métier sera confirmée.
     */
    protected function creerFactures(ContratCampagne $contrat, int $nbImpayes): void
    {
        if ($nbImpayes <= 0) {
            return;
        }

        // Calcul du montant total impayé
        // Hypothèse : montant = nb_impayes * (cotisation_annuelle / periodicite)
        $cotisation = (float) ($contrat->cotisation_annuelle ?? 0);
        $periodicite = (int) ($contrat->periodicite ?? 12);

        if ($cotisation <= 0 || $periodicite <= 0) {
            // Impossible de calculer → on crée une seule facture "agrégée"
            $montantUnitaire = 0;
        } else {
            $montantUnitaire = round($cotisation / $periodicite, 2);
        }

        $dateBase = $contrat->date_dernier_encaissement ?? now();
        $montantTotal = $montantUnitaire * $nbImpayes;

        // Créer une facture par impayé, échéance mensuelle
        for ($i = 1; $i <= $nbImpayes; $i++) {
            FactureImpayee::create([
                'contrat_id'     => $contrat->id,
                'numero_facture' => $contrat->numero_police . '-IMP-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'date_echeance'  => Carbon::parse($dateBase)->addMonthsNoOverflow($i),
                'montant'        => $montantUnitaire,
                'montant_paye'   => 0,
                'statut'         => 'IMPAYE',
            ]);
            $this->facturesCreees++;
        }

        // Mettre à jour le montant total impayé
        $contrat->update([
            'montant_impaye_total' => round($montantTotal, 2),
        ]);
    }

    // -----------------------------------------------------------------
    // Helpers de nettoyage
    // -----------------------------------------------------------------

    /**
     * Nettoie un nombre au format FR ("2 184,00" → 2184.00)
     */
    protected function nettoyerNombre(?string $valeur): ?float
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }
        $valeur = str_replace([' ', "\u{00A0}", "\u{202F}"], '', $valeur); // espaces + insécables
        $valeur = str_replace(',', '.', $valeur);
        return is_numeric($valeur) ? (float) $valeur : null;
    }

    /**
     * Nettoie un entier.
     */
    protected function nettoyerEntier(?string $valeur): ?int
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }
        $valeur = trim($valeur);
        return is_numeric($valeur) ? (int) $valeur : null;
    }

    /**
     * Nettoie un numéro de téléphone.
     * Retourne null si invalide.
     */
    protected function nettoyerTelephone(?string $valeur): ?string
    {
        if ($valeur === null) {
            return null;
        }
        // Garder uniquement les chiffres
        $chiffres = preg_replace('/[^0-9]/', '', $valeur);

        // Doit faire au moins 8 chiffres pour être valide (CI : 10 chiffres)
        if (strlen($chiffres) < 8) {
            return null;
        }

        return $chiffres;
    }

    /**
     * Parse une date au format dd/mm/yyyy.
     */
    protected function parserDate(?string $valeur): ?string
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }
        $valeur = trim($valeur);
        if ($valeur === '' || $valeur === '00/00/0000') {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $valeur)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
