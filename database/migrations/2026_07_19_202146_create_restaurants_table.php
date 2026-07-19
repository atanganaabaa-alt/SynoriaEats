<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('address');
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('cover_url')->nullable();
            $table->string('category', 60)->nullable()->index();
            $table->string('opening_hours')->nullable();
            $table->decimal('rating', 2, 1)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->unsignedSmallInteger('prep_time_min')->default(20);
            $table->unsignedSmallInteger('prep_time_max')->default(40);
            $table->unsignedInteger('delivery_fee')->default(0);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_open')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
