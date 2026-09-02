<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetUserPasswordCommand extends Command
{
    protected $signature = 'synoria:reset-password
                            {email : Email du compte}
                            {password : Nouveau mot de passe}';

    protected $description = 'Réinitialise le mot de passe d’un utilisateur (sans email)';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $password = (string) $this->argument('password');

        if (strlen($password) < 8) {
            $this->error('Le mot de passe doit faire au moins 8 caractères.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("Aucun compte pour {$email}.");

            return self::FAILURE;
        }

        $user->forceFill(['password' => $password])->save();

        $this->info("Mot de passe mis à jour pour {$email} ({$user->role->label()}).");

        return self::SUCCESS;
    }
}
