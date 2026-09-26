<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('subscription_plan')->nullable()->after('reviewed_at');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_plan')->index();
            $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at')->index();
        });

        // Restos déjà validés : essai 30 jours à compter de maintenant
        $trialDays = (int) (config('synoria.subscriptions.trial_days') ?? 30);
        DB::table('restaurants')
            ->where('is_validated', true)
            ->whereNull('trial_ends_at')
            ->update([
                'trial_ends_at' => now()->addDays($trialDays),
            ]);
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['subscription_plan', 'trial_ends_at', 'subscription_ends_at']);
        });
    }
};
