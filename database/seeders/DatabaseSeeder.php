<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Pas de comptes démo / mots de passe partagés.
        // Les utilisateurs s'inscrivent (email+mdp) ou via Google OAuth.
    }
}
