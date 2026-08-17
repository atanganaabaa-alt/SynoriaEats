<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('last_lat', 10, 7)->nullable()->after('approved_at');
            $table->decimal('last_lng', 10, 7)->nullable()->after('last_lat');
            $table->timestamp('last_seen_at')->nullable()->after('last_lng');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('customer_lat', 10, 7)->nullable()->after('courier_lng');
            $table->decimal('customer_lng', 10, 7)->nullable()->after('customer_lat');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['customer_lat', 'customer_lng']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_lat', 'last_lng', 'last_seen_at']);
        });
    }
};
