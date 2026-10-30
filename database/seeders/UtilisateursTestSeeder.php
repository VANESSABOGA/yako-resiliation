<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UtilisateursTestSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'agent@yako.ci'],
            [
                'name'       => 'Agent Call Center',
                'password'   => Hash::make('password'),
                'role'       => 'agent',
                'agent_code' => 'AG001',
                'is_active'  => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'manager@yako.ci'],
            [
                'name'       => 'Manager Campagne',
                'password'   => Hash::make('password'),
                'role'       => 'manager',
                'agent_code' => 'MG001',
                'is_active'  => true,
            ]
        );

        $this->command->info('✅ Utilisateurs de test créés :');
        $this->command->line('   - agent@yako.ci / password (agent)');
        $this->command->line('   - manager@yako.ci / password (manager)');
    }
}
