<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companion_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_key', 64)->nullable()->index();
            $table->foreignId('restaurant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role', 16); // user | assistant
            $table->text('content');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['session_key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companion_messages');
    }
};
