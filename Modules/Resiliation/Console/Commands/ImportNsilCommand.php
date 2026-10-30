<?php

namespace Modules\Resiliation\Console\Commands;

use Illuminate\Console\Command;
use Modules\Resiliation\Models\CampagneResiliation;
use Modules\Resiliation\Services\NsilImportService;

class ImportNsilCommand extends Command
{
    protected $signature = 'resiliation:import-nsil
                            {fichier : Chemin vers le fichier TSV extrait de NSIL}
                            {--campagne= : ID de la campagne cible (optionnel, la dernière active par défaut)}
                            {--dry-run : Simuler sans écrire en base}';

    protected $description = 'Importe un fichier TSV NSIL dans une campagne de résiliation';

    public function handle(NsilImportService $service): int
    {
        $chemin = $this->argument('fichier');

        if (!file_exists($chemin)) {
            $this->error("❌ Fichier introuvable : {$chemin}");
            return self::FAILURE;
        }

        // Résolution de la campagne
        $campagneId = $this->option('campagne');
        if ($campagneId) {
            $campagne = CampagneResiliation::find($campagneId);
        } else {
            $campagne = CampagneResiliation::whereIn('statut', ['BROUILLON', 'ACTIVE'])
                ->orderByDesc('id')
                ->first();
        }

        if (!$campagne) {
            $this->error("❌ Aucune campagne BROUILLON ou ACTIVE trouvée.");
            $this->line("   Utilisez --campagne=ID ou créez une campagne avec :");
            $this->line("   php artisan resiliation:init \"Nom de la campagne\"");
            return self::FAILURE;
        }

        $this->info("📂 Fichier    : {$chemin}");
        $this->info("🎯 Campagne   : #{$campagne->id} - {$campagne->nom}");
        $this->info("📊 Statut     : {$campagne->statut}");

        if ($this->option('dry-run')) {
            $this->warn("⚠️  Mode DRY-RUN : aucune écriture en base.");
        }

        if (!$this->confirm('Lancer l\'import ?', true)) {
            $this->line('Import annulé.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('⏳ Import en cours...');

        $debut = microtime(true);

        try {
            $resultat = $service->importer($chemin, $campagne);
        } catch (\Throwable $e) {
            $this->error("❌ Erreur : " . $e->getMessage());
            return self::FAILURE;
        }

        $duree = round(microtime(true) - $debut, 2);

        // --- Rapport ---
        $this->newLine();
        $this->info("✅ Import terminé en {$duree}s");
        $this->newLine();

        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Contrats importés',     $resultat['importes']],
                ['Factures créées',       $resultat['factures_creees']],
                ['Lignes rejetées',       count($resultat['rejetes'])],
                ['Erreurs',               count($resultat['erreurs'])],
            ]
        );

        // Détail des rejets
        if (!empty($resultat['rejetes'])) {
            $this->newLine();
            $this->warn("📋 Lignes rejetées (non éligibles) :");
            $this->table(
                ['Ligne', 'Raison'],
                array_map(fn($r) => [$r['ligne'], $r['raison']], $resultat['rejetes'])
            );
        }

        // Détail des erreurs
        if (!empty($resultat['erreurs'])) {
            $this->newLine();
            $this->error("❌ Erreurs de traitement :");
            $this->table(
                ['Ligne', 'Raison'],
                array_map(fn($e) => [$e['ligne'], $e['raison']], $resultat['erreurs'])
            );
        }

        return self::SUCCESS;
    }
}
