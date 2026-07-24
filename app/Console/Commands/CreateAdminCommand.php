<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class CreateAdminCommand extends Command
{
    protected $signature = 'synoria:admin
                            {email : Email admin}
                            {password : Mot de passe}
                            {--name=Admin Synoria : Nom affiché}';

    protected $description = 'Crée ou met à jour un compte administrateur SynoriaEats';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $password = (string) $this->argument('password');
        $name = (string) $this->option('name');

        if (strlen($password) < 8) {
            $this->error('Le mot de passe doit faire au moins 8 caractères.');

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => UserRole::Admin,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->info($user->wasRecentlyCreated ? 'Admin créé.' : 'Admin mis à jour.');
        $this->line('Email : '.$email);

        return self::SUCCESS;
    }
}
