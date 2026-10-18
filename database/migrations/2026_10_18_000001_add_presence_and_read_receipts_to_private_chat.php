<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('private_chat_profiles', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->index();
        });

        Schema::table('private_messages', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('private_messages', function (Blueprint $table) {
            $table->dropIndex(['read_at']);
            $table->dropColumn('read_at');
        });

        Schema::table('private_chat_profiles', function (Blueprint $table) {
            $table->dropIndex(['last_seen_at']);
            $table->dropColumn('last_seen_at');
        });
    }
};
