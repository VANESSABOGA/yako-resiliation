<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resiliation_factures_impayees', function (Blueprint $table) {
            $table->id();

            // $table->foreignId('contrat_id')
            //       ->constrained('resiliation_contrats_campagne')
            //       ->cascadeOnDelete();

            $table->unsignedBigInteger('contrat_id')->nullable(); // IdProposition

            $table->string('numero_facture', 50)->nullable();
            $table->date('date_echeance');
            $table->decimal('montant', 15, 2);
            $table->decimal('montant_paye', 15, 2)->default(0);
            $table->enum('statut', ['IMPAYE', 'PARTIELLEMENT_PAYE', 'PAYE'])->default('IMPAYE');

            $table->timestamps();

            $table->index(['contrat_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resiliation_factures_impayees');
    }
};


// resiliation_contrats_campagne
// resiliation_factures_impayees
// resiliation_journal_sms
// resiliation_appels_agents
// resiliation_paiements
