<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resiliation_contrats_campagne', function (Blueprint $table) {
            $table->id();

            // --- Rattachement campagne ---
            // $table->foreignId('campagne_id')
            //     ->constrained('resiliation_campagnes')
            //     ->cascadeOnDelete();
            $table->unsignedBigInteger('campagne_id')->nullable(); // IdProposition
            // --- Identifiants NSIL ---
            $table->unsignedBigInteger('nsil_contrat_id')->unique(); // IdProposition
            $table->string('code_produit', 20)->nullable();
            $table->string('code_formule', 30)->nullable();
            $table->string('numero_police', 30)->index();            // CodeProposition

            // --- Identité client ---
            $table->string('souscripteur_nom', 150);
            $table->string('assure_nom', 150);
            $table->boolean('joignable')->default(false);
            $table->string('moyen_contact', 20)->nullable();
            $table->string('email_1', 150)->nullable();
            $table->string('email_2', 150)->nullable();
            $table->string('telephone_oneci', 20)->nullable();       // ONECI - Téléphone
            $table->string('client_telephone', 20)->index();         // #1 Téléphone (principal)
            $table->string('telephone_2', 20)->nullable();
            $table->string('telephone_3', 20)->nullable();
            $table->string('telephone_4', 20)->nullable();
            $table->string('telephone_5', 20)->nullable();

            // --- Distribution / Conseiller ---
            $table->string('code_conseiller', 20)->nullable();
            $table->string('nom_conseiller', 80)->nullable();
            $table->string('inspection', 30)->nullable();            // -> canal_vente pour KPI
            $table->string('canal_distribution', 30)->nullable();    // BANCASSURANCE, etc.
            $table->boolean('conseiller_manquant')->default(false);

            // --- Caractéristiques du contrat ---
            $table->date('date_naissance_assure')->nullable();
            $table->date('date_effet_reel')->nullable();
            $table->date('date_echeance')->nullable();
            $table->integer('duree_cotisation_ans')->nullable();
            $table->string('code_periodicite', 5)->nullable();
            $table->integer('periodicite')->nullable();
            $table->decimal('total_prime', 15, 2)->nullable();
            $table->string('statut_nsil', 30)->nullable();           // En Veille / En Cours / Echu
            $table->string('etat_contrat_nsil', 20)->nullable();     // Encours / Echu

            // --- Encaissements ---
            $table->integer('nb_encaissements')->nullable();
            $table->date('date_premier_encaissement')->nullable();
            $table->date('date_dernier_encaissement')->nullable();
            $table->decimal('anciennete', 10, 4)->nullable();
            $table->integer('nb_enc_theorique')->nullable();
            $table->decimal('cumul_encaissement', 15, 2)->nullable();
            $table->decimal('duree_courue', 10, 4)->nullable();
            $table->string('code_mode_paiement', 10)->nullable();
            $table->decimal('cotisation_annuelle', 15, 2)->nullable();
            $table->decimal('cumul_cotisation_theorique', 15, 2)->nullable();
            $table->decimal('cumul_cotisation_requis', 15, 2)->nullable();

            // --- Impayés & PM ---
            $table->integer('nb_impayes')->default(0);               // Nombre de cotisations impayées
            $table->decimal('montant_impaye_total', 15, 2)->default(0); // Calculé (factures)
            $table->boolean('flag_impaye_sup_1')->default(false);
            $table->decimal('pm_contrat', 15, 2)->default(0);        // PM_Déc2026

            // --- Genre ---
            $table->enum('genre', ['M', 'F'])->nullable();

            // --- Statut de campagne (≠ statut NSIL) ---
            $table->enum('statut', [
                'INTEGRE',
                'SMS_ENVOYE',
                'SMS_ECHEC',
                'EN_DELAI',
                'REGULARISE',
                'GAIN_CONFIRME',
            ])->default('INTEGRE')->index();

            // --- Dates de campagne ---
            $table->timestamp('date_integration')->nullable();
            $table->timestamp('date_envoi_sms')->nullable();
            $table->timestamp('date_reception_sms')->nullable();
            $table->date('date_limite')->nullable()->index();        // date_reception_sms + 40 jours

            // --- Affectation agent (MG-05) ---
            $table->foreignId('agent_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Index composites pour les KPI et filtres
            $table->index(['campagne_id', 'statut']);
            $table->index(['statut', 'date_limite']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resiliation_contrats_campagne');
    }
};
