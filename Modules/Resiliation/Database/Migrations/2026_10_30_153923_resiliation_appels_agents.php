<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resiliation_appels_agents', function (Blueprint $table) {
            $table->id();

            // $table->foreignId('contrat_id')
            //       ->constrained('resiliation_contrats_campagne')
            //       ->cascadeOnDelete();

            // $table->foreignId('agent_id')
            //       ->constrained('users')
            //       ->restrictOnDelete();

            $table->unsignedBigInteger('contrat_id')->nullable(); // IdProposition

            $table->unsignedBigInteger('agent_id')->nullable(); // IdProposition

            // AC-06
            $table->timestamp('date_heure_appel');          // auto, mais modifiable si saisie a posteriori
            $table->string('motif', 50);                    // liste (CONTESTATION, DEMANDE_INFO, PROMESSE_PAIEMENT, etc.)
            $table->boolean('souhaite_payer')->nullable();  // oui / non
            $table->text('commentaire')->nullable();

            $table->timestamps();

            $table->index(['contrat_id', 'date_heure_appel']);
            $table->index(['agent_id', 'date_heure_appel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resiliation_appels_agents');
    }
};
