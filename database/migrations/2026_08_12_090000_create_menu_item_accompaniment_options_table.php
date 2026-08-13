<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_item_accompaniment_options', function (Blueprint $table) {
            $table->id();

            // Dish menu item (usually category "Plats")
            $table->foreignId('dish_id')->constrained('menu_items')->cascadeOnDelete();

            // Accompaniment menu item (usually category "Accompagnements")
            $table->foreignId('accompaniment_id')->constrained('menu_items')->cascadeOnDelete();

            $table->unsignedInteger('extra_price')->default(0);
            $table->boolean('is_available')->default(true);

            $table->timestamps();

            $table->unique(['dish_id', 'accompaniment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_accompaniment_options');
    }
};

