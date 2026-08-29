<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('featured_media_id')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('lead')->nullable();
            $table->longText('body');
            $table->string('status')->default('draft')->index();
            $table->timestamp('publish_at')->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_path')->nullable();
            $table->unsignedBigInteger('views')->default(0);
            $table->boolean('is_breaking')->default(false)->index();
            $table->text('takedown_note')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'published_at']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('articles', function (Blueprint $table): void {
                $table->fullText(['title', 'lead', 'body']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
