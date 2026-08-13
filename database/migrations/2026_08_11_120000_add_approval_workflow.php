<?php

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('approval_status', 32)->default(ApprovalStatus::Approved->value)->after('is_active')->index();
            $table->string('partner_name')->nullable()->after('approval_status');
            $table->text('approval_notes')->nullable()->after('partner_name');
            $table->timestamp('approved_at')->nullable()->after('approval_notes');
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('status', 32)->default(ApprovalStatus::Pending->value)->after('is_validated')->index();
            $table->text('rejection_reason')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('rejection_reason');
        });

        if (Schema::hasTable('restaurants')) {
            DB::table('restaurants')->where('is_validated', true)->update([
                'status' => ApprovalStatus::Approved->value,
                'reviewed_at' => now(),
            ]);
            DB::table('restaurants')->where('is_validated', false)->update([
                'status' => ApprovalStatus::Pending->value,
            ]);
        }

        DB::table('users')->whereIn('role', [
            UserRole::Customer->value,
            UserRole::Admin->value,
            UserRole::Courier->value,
            UserRole::RestaurantOwner->value,
        ])->update([
            'approval_status' => ApprovalStatus::Approved->value,
            'approved_at' => now(),
        ]);

        Schema::create('restaurant_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40)->index();
            $table->string('url');
            $table->string('original_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_documents');

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['status', 'rejection_reason', 'reviewed_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'partner_name', 'approval_notes', 'approved_at']);
        });
    }
};
