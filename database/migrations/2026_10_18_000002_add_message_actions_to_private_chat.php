<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('private_messages', function (Blueprint $table): void {
            $table->foreignId('reply_to_id')->nullable()->after('sender_id')->constrained('private_messages')->nullOnDelete();
            $table->timestamp('pinned_at')->nullable()->after('read_at');
            $table->timestamp('edited_at')->nullable()->after('pinned_at');
        });
    }

    public function down(): void
    {
        Schema::table('private_messages', function (Blueprint $table): void {
            $table->dropForeign(['reply_to_id']);
            $table->dropColumn(['reply_to_id', 'pinned_at', 'edited_at']);
        });
    }
};
