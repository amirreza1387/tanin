<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_view_stats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->date('view_date');
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();
            $table->unique(['article_id', 'view_date']);
            $table->index(['view_date', 'views']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_view_stats');
    }
};
