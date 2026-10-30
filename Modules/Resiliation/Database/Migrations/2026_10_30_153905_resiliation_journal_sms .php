<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resiliation_journal_sms', function (Blueprint $table) {
            $table->id();

            // $table->foreignId('contrat_id')
            //       ->constrained('resiliation_contrats_campagne')
            //       ->cascadeOnDelete();

            $table->unsignedBigInteger('contrat_id')->nullable(); // IdProposition

            // Type de SMS : mise en demeure ou lien de paiement
            $table->enum('type_sms', ['MISE_DEMEURE', 'LIEN_PAIEMENT']);

            // Identifiant unique côté fournisseur SMS (pour rapprochement callback)
            $table->string('message_id_fournisseur', 100)->nullable()->unique();

            $table->string('numero_destinataire', 20);
            $table->text('contenu_integral'); // Preuve opposable : texte exact

            // Horodatages (exigence section 9)
            $table->timestamp('date_envoi')->nullable();
            $table->timestamp('date_reception')->nullable();

            // Statut de l'accusé de réception
            $table->enum('statut_accuse', ['EN_ATTENTE', 'REMIS', 'ECHEC', 'EXPIRE'])
                ->default('EN_ATTENTE')->index();

            $table->text('motif_echec')->nullable();

            // Coût du SMS (optionnel, pour suivi)
            $table->decimal('cout', 10, 2)->nullable();

            // Agent ayant déclenché l'envoi (si lien de paiement)
            $table->foreignId('agent_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['contrat_id', 'type_sms']);
            $table->index(['statut_accuse', 'date_envoi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resiliation_journal_sms');
    }
};
