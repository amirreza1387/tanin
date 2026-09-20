<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_category', function (Blueprint $table): void {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['article_id', 'category_id']);
            $table->index('category_id');
        });

        DB::table('articles')->whereNotNull('category_id')->orderBy('id')->each(function (object $article): void {
            DB::table('article_category')->insertOrIgnore([
                'article_id' => $article->id,
                'category_id' => $article->category_id,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_category');
    }
};
