<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companion_messages', function (Blueprint $table) {
            $table->foreignId('conversation_id')
                ->nullable()
                ->after('id')
                ->constrained('companion_conversations')
                ->cascadeOnDelete();
        });

        // Rattache les anciens messages à un fil par utilisateur / session
        $groups = DB::table('companion_messages')
            ->select('user_id', 'session_key')
            ->whereNull('conversation_id')
            ->groupBy('user_id', 'session_key')
            ->get();

        foreach ($groups as $group) {
            $title = 'Discussion';
            $firstUser = DB::table('companion_messages')
                ->where('user_id', $group->user_id)
                ->where('session_key', $group->session_key)
                ->where('role', 'user')
                ->orderBy('id')
                ->value('content');

            if (is_string($firstUser) && $firstUser !== '') {
                $title = mb_substr($firstUser, 0, 60);
            }

            $conversationId = DB::table('companion_conversations')->insertGetId([
                'user_id' => $group->user_id,
                'session_key' => $group->session_key,
                'title' => $title,
                'last_message_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('companion_messages')
                ->where('user_id', $group->user_id)
                ->where('session_key', $group->session_key)
                ->whereNull('conversation_id')
                ->update(['conversation_id' => $conversationId]);
        }
    }

    public function down(): void
    {
        Schema::table('companion_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conversation_id');
        });
    }
};
