<?php

namespace Modules\Resiliation\Console\Commands;

use Illuminate\Console\Command;
use Modules\Resiliation\Models\CampagneResiliation;

class InitCampagneCommand extends Command
{
    protected $signature = 'resiliation:init
                            {nom : Nom de la campagne}
                            {--date-debut= : Date de début (Y-m-d), par défaut aujourd\'hui}';

    protected $description = 'Crée une nouvelle campagne de résiliation impayés';

    public function handle(): int
    {
        $nom = $this->argument('nom');
        $dateDebut = $this->option('date-debut') ?? now()->toDateString();

        $campagne = CampagneResiliation::create([
            'nom'        => $nom,
            'date_debut' => $dateDebut,
            'statut'     => 'BROUILLON',
        ]);

        $this->info("✅ Campagne créée : #{$campagne->id} - {$campagne->nom}");
        $this->line("   Date de début : {$campagne->date_debut->format('d/m/Y')}");
        $this->line("   Statut        : {$campagne->statut}");

        return self::SUCCESS;
    }
}
