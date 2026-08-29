<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Enums\CommentStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(IranNewsSeeder::class);

        $admin = User::factory()->admin()->create([
            'name' => 'مدیر سامانه',
            'email' => 'admin@example.com',
        ]);
        $reporter = User::factory()->reporter()->create([
            'name' => 'خبرنگار جنوب',
            'email' => 'reporter@example.com',
        ]);
        $users = User::factory(5)->create();

        $politics = Category::create(['name' => 'سیاست', 'slug' => 'سیاست', 'sort_order' => 1]);
        $province = Category::create([
            'name' => 'خوزستان',
            'slug' => 'خوزستان',
            'parent_id' => $politics->id,
            'sort_order' => 1,
        ]);
        $sports = Category::create(['name' => 'ورزش', 'slug' => 'ورزش', 'sort_order' => 2]);
        Category::create([
            'name' => 'فوتبال',
            'slug' => 'فوتبال',
            'parent_id' => $sports->id,
            'sort_order' => 1,
        ]);

        $tags = collect(['اهواز', 'خبر فوری', 'جامعه', 'اقتصاد'])
            ->map(fn (string $name) => Tag::create(['name' => $name, 'slug' => $name]));

        $published = Article::create([
            'author_id' => $reporter->id,
            'category_id' => $province->id,
            'title' => 'استان خوزستان میزبان رویداد بزرگ خبری شد',
            'slug' => 'استان-خوزستان-میزبان-رویداد-بزرگ-خبری-شد',
            'lead' => 'گزارشی از تازه‌ترین رویدادهای استان خوزستان.',
            'body' => 'این متن نمونه برای خبر منتشرشده سامانه است.',
            'status' => ArticleStatus::PUBLISHED,
            'publish_at' => now()->subDay(),
            'published_at' => now()->subDay(),
            'meta_title' => 'استان خوزستان میزبان رویداد بزرگ خبری شد',
            'meta_description' => 'آخرین اخبار استان خوزستان را بخوانید.',
            'canonical_path' => '/articles/استان-خوزستان-میزبان-رویداد-بزرگ-خبری-شد',
            'is_breaking' => true,
        ]);
        $published->tags()->attach($tags->take(2)->pluck('id'));

        $draft = Article::create([
            'author_id' => $reporter->id,
            'category_id' => $sports->id,
            'title' => 'پیش‌نویس خبر ورزشی',
            'slug' => 'پیش‌نویس-خبر-ورزشی',
            'lead' => 'خلاصه خبر ورزشی.',
            'body' => 'متن پیش‌نویس خبر ورزشی.',
            'status' => ArticleStatus::DRAFT,
        ]);
        $draft->tags()->attach($tags->last()->id);

        $comment = Comment::create([
            'article_id' => $published->id,
            'user_id' => $users->first()->id,
            'body' => 'خبر بسیار خوبی بود.',
            'status' => CommentStatus::APPROVED,
        ]);
        Comment::create([
            'article_id' => $published->id,
            'user_id' => $users->get(1)->id,
            'parent_id' => $comment->id,
            'body' => 'من هم با این نظر موافقم.',
            'status' => CommentStatus::PENDING,
        ]);

        $admin->forceFill(['email_verified_at' => now()])->save();
    }
}
