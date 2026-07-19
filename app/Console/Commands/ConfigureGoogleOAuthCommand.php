<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ConfigureGoogleOAuthCommand extends Command
{
    protected $signature = 'synoria:google
                            {client_id : OAuth Client ID Google}
                            {client_secret : OAuth Client Secret Google}';

    protected $description = 'Écrit GOOGLE_CLIENT_ID / SECRET dans .env et vide le cache config';

    public function handle(): int
    {
        $clientId = trim((string) $this->argument('client_id'));
        $clientSecret = trim((string) $this->argument('client_secret'));
        $redirect = rtrim((string) config('app.url'), '/').'/auth/google/callback';

        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            $this->error('.env introuvable.');

            return self::FAILURE;
        }

        $env = File::get($envPath);
        $env = $this->upsertEnv($env, 'GOOGLE_CLIENT_ID', $clientId);
        $env = $this->upsertEnv($env, 'GOOGLE_CLIENT_SECRET', $clientSecret);
        $env = $this->upsertEnv($env, 'GOOGLE_REDIRECT_URI', $redirect);

        File::put($envPath, $env);

        $this->call('config:clear');

        $this->info('Google OAuth configuré.');
        $this->line('Redirect URI à coller dans Google Cloud Console :');
        $this->line('  '.$redirect);

        return self::SUCCESS;
    }

    private function upsertEnv(string $env, string $key, string $value): string
    {
        $line = $key.'='.$this->escapeEnv($value);

        if (preg_match("/^{$key}=.*/m", $env)) {
            return preg_replace("/^{$key}=.*/m", $line, $env) ?? $env;
        }

        return rtrim($env).PHP_EOL.$line.PHP_EOL;
    }

    private function escapeEnv(string $value): string
    {
        if (preg_match('/\s|#|"/', $value)) {
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }
}
