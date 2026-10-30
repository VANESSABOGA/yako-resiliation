<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resiliation_campagnes', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->text('description')->nullable();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->enum('statut', ['BROUILLON', 'ACTIVE', 'CLOTUREE'])->default('BROUILLON');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resiliation_campagnes');
    }
};
