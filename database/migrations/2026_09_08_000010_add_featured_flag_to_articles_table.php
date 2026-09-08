<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void { Schema::table('articles', fn (Blueprint $table) => $table->boolean('is_featured')->default(false)->index()->after('is_breaking')); }
    public function down(): void { Schema::table('articles', function (Blueprint $table) { $table->dropIndex(['is_featured']); $table->dropColumn('is_featured'); }); }
};
