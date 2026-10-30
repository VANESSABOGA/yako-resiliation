<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['agent', 'manager', 'admin'])
                  ->default('agent')
                  ->after('email');
            $table->string('agent_code', 20)->nullable()->unique()->after('role');
            $table->boolean('is_active')->default(true)->after('agent_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'agent_code', 'is_active']);
        });
    }
};
