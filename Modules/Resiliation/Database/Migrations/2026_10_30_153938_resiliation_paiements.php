<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resiliation_paiements', function (Blueprint $table) {
            $table->id();

            // $table->foreignId('contrat_id')
            //     ->constrained('resiliation_contrats_campagne')
            //     ->cascadeOnDelete();


            $table->unsignedBigInteger('contrat_id')->nullable(); // IdProposition

            // Référence unique de la transaction (JEKO ou manuelle)
            $table->string('reference_transaction', 100)->nullable()->unique();

            $table->decimal('montant', 15, 2);

            // Canal de paiement (RG-04)
            $table->enum('canal', ['LIEN_JEKO', 'VIREMENT', 'ESPECES', 'AUTRE']);

            $table->timestamp('date_paiement');

            $table->enum('statut', ['EN_ATTENTE', 'CONFIRME', 'ECHOUE'])
                ->default('EN_ATTENTE')->index();

            // Si paiement rapproché manuellement
            $table->foreignId('rapproche_par')->nullable()
                ->constrained('users')->nullOnDelete();

            // Factures liées à ce paiement (si connu)
            $table->json('factures_ids')->nullable();

            $table->timestamps();

            $table->index(['contrat_id', 'statut']);
            $table->index(['canal', 'date_paiement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resiliation_paiements');
    }
};
