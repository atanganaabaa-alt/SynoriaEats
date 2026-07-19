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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('courier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('delivery_address');
            $table->string('delivery_phone', 30);
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('delivery_fee')->default(0);
            $table->unsignedInteger('commission')->default(0);
            $table->unsignedInteger('total');
            $table->string('payment_method', 32);
            $table->string('payment_status', 32)->default('pending')->index();
            $table->string('payment_reference')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->decimal('courier_lat', 10, 7)->nullable();
            $table->decimal('courier_lng', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
