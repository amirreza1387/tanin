<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Enums\MediaType;
use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class IranNewsSeeder extends Seeder
{
    public function run(): void
    {
        $reporter = User::query()->firstOrCreate(
            ['email' => 'newsdesk@example.com'],
            ['name' => 'تحریریه طنین جنوب', 'password' => 'password123', 'role' => 'reporter'],
        );

        $categories = collect([
            ['name' => 'سیاست ایران', 'slug' => 'سیاست-ایران', 'sort_order' => 1],
            ['name' => 'اقتصاد ایران', 'slug' => 'اقتصاد-ایران', 'sort_order' => 2],
            ['name' => 'جامعه', 'slug' => 'جامعه', 'sort_order' => 3],
            ['name' => 'استان‌ها', 'slug' => 'استان‌ها', 'sort_order' => 4],
            ['name' => 'ورزش ایران', 'slug' => 'ورزش-ایران', 'sort_order' => 5],
            ['name' => 'فرهنگ و هنر', 'slug' => 'فرهنگ-و-هنر', 'sort_order' => 6],
            ['name' => 'جهان و ایران', 'slug' => 'جهان-و-ایران', 'sort_order' => 7],
        ])->mapWithKeys(fn (array $category) => [
            $category['slug'] => Category::query()->updateOrCreate(['slug' => $category['slug']], $category),
        ]);

        $oldArticles = Article::withTrashed()->get();
        $oldMediaIds = $oldArticles->pluck('featured_media_id')->filter();
        $oldArticleIds = $oldArticles->pluck('id');
        $oldMedia = Media::query()
            ->whereIn('id', $oldMediaIds)
            ->orWhere(fn ($query) => $query->where('mediable_type', Article::class)->whereIn('mediable_id', $oldArticleIds))
            ->get();

        foreach ($oldMedia as $media) {
            Storage::disk($media->disk)->delete($media->path);
            $media->delete();
        }

        Article::withTrashed()->forceDelete();

        $news = [
            ['category' => 'سیاست-ایران', 'title' => 'نشست بررسی همکاری‌های منطقه‌ای با محوریت گفت‌وگوی سازنده برگزار شد', 'lead' => 'در این نشست بر نقش گفت‌وگو، همکاری‌های اقتصادی و پیگیری منافع عمومی تأکید شد.', 'topic' => 'government,meeting'],
            ['category' => 'سیاست-ایران', 'title' => 'نمایندگان درباره اولویت‌های برنامه توسعه گفت‌وگو کردند', 'lead' => 'موضوع‌های اشتغال، خدمات عمومی و توسعه متوازن در دستور کار جلسه قرار گرفت.', 'topic' => 'parliament,government'],
            ['category' => 'سیاست-ایران', 'title' => 'گزارش تازه‌ای از روند اجرای طرح‌های ملی منتشر شد', 'lead' => 'دستگاه‌های اجرایی گزارشی از پیشرفت پروژه‌ها و برنامه زمان‌بندی مرحله بعد ارائه کردند.', 'topic' => 'city,government'],
            ['category' => 'سیاست-ایران', 'title' => 'نشست مشترک مدیران استانی برای هماهنگی خدمات عمومی', 'lead' => 'حاضران بر پاسخ‌گویی سریع‌تر و دسترسی عادلانه شهروندان به خدمات تأکید کردند.', 'topic' => 'conference,city'],
            ['category' => 'اقتصاد-ایران', 'title' => 'فعالان بازار بر ثبات زنجیره تأمین کالا تأکید کردند', 'lead' => 'در گفت‌وگو با کسبه و تولیدکنندگان، تأمین پایدار مواد اولیه و شفافیت قیمت‌ها مطرح شد.', 'topic' => 'market,business'],
            ['category' => 'اقتصاد-ایران', 'title' => 'طرح حمایت از کسب‌وکارهای کوچک وارد مرحله تازه شد', 'lead' => 'این طرح بر ساده‌سازی دسترسی به آموزش، مشاوره و بازار فروش تمرکز دارد.', 'topic' => 'small-business,shop'],
            ['category' => 'اقتصاد-ایران', 'title' => 'گزارش میدانی از رونق فروش محصولات محلی در بازارها', 'lead' => 'فروشندگان از استقبال بیشتر از کالاهای بومی و تولیدات خانگی خبر می‌دهند.', 'topic' => 'bazaar,market'],
            ['category' => 'اقتصاد-ایران', 'title' => 'کارگاه‌های تولیدی بر توسعه صادرات منطقه‌ای تمرکز کردند', 'lead' => 'تولیدکنندگان درباره مسیرهای تازه فروش و استانداردسازی محصولات گفت‌وگو کردند.', 'topic' => 'factory,industry'],
            ['category' => 'جامعه', 'title' => 'پویش محلی برای بهسازی فضاهای عمومی آغاز شد', 'lead' => 'داوطلبان و گروه‌های شهری در برنامه‌ای مشترک برای بهبود محیط محله‌ها مشارکت کردند.', 'topic' => 'community,city'],
            ['category' => 'جامعه', 'title' => 'مرکز تازه آموزش مهارت‌های دیجیتال فعالیت خود را آغاز کرد', 'lead' => 'دوره‌های مقدماتی و کاربردی برای نوجوانان و جویندگان کار در نظر گرفته شده است.', 'topic' => 'education,computer'],
            ['category' => 'جامعه', 'title' => 'برنامه ترویج کتاب‌خوانی در کتابخانه‌های محلی گسترش یافت', 'lead' => 'نشست‌های کتاب‌خوانی، قصه‌گویی و امانت کتاب برای گروه‌های سنی مختلف برگزار می‌شود.', 'topic' => 'library,books'],
            ['category' => 'جامعه', 'title' => 'شبکه داوطلبان سلامت محله‌ای خدمات مشاوره‌ای ارائه داد', 'lead' => 'این برنامه با هدف آگاهی‌بخشی و دسترسی آسان‌تر به خدمات اولیه سلامت اجرا شد.', 'topic' => 'health,community'],
            ['category' => 'استان‌ها', 'title' => 'پروژه بهسازی اسکله‌های جنوبی وارد مرحله اجرایی شد', 'lead' => 'با تکمیل عملیات زیرساختی، ظرفیت خدمات‌رسانی و ایمنی مسیرهای دریایی افزایش می‌یابد.', 'topic' => 'harbor,sea'],
            ['category' => 'استان‌ها', 'title' => 'بازارچه صنایع دستی جنوب میزبان هنرمندان محلی شد', 'lead' => 'هنرمندان آثار بومی و دست‌ساز خود را در فضایی برای معرفی تولیدات منطقه عرضه کردند.', 'topic' => 'handicraft,market'],
            ['category' => 'استان‌ها', 'title' => 'طرح حفاظت از نخلستان‌ها با مشارکت کشاورزان دنبال می‌شود', 'lead' => 'کارشناسان بر مدیریت آب، مراقبت از خاک و پشتیبانی از تولیدکنندگان تأکید دارند.', 'topic' => 'palm,farm'],
            ['category' => 'استان‌ها', 'title' => 'جشنواره خوراک‌های دریایی در شهرهای ساحلی برگزار شد', 'lead' => 'این رویداد با حضور آشپزان محلی و معرفی ظرفیت‌های گردشگری دریایی همراه بود.', 'topic' => 'seafood,coast'],
            ['category' => 'استان‌ها', 'title' => 'مسیرهای طبیعت‌گردی جنوب برای فصل جدید آماده‌سازی شد', 'lead' => 'راهنمایان محلی و فعالان گردشگری بر حفظ محیط‌زیست و سفر مسئولانه تأکید کردند.', 'topic' => 'iran,nature'],
            ['category' => 'ورزش-ایران', 'title' => 'آمادگی تیم‌های فوتبال برای هفته تازه رقابت‌ها افزایش یافت', 'lead' => 'مربیان بر هماهنگی تیمی و استفاده از فرصت‌های جوانان در ترکیب اصلی تمرکز دارند.', 'topic' => 'football,stadium'],
            ['category' => 'ورزش-ایران', 'title' => 'رقابت‌های والیبال نوجوانان با استقبال تماشاگران دنبال شد', 'lead' => 'مسابقه‌ها فرصتی برای شناسایی استعدادهای تازه در رده‌های پایه فراهم کرده است.', 'topic' => 'volleyball,sport'],
            ['category' => 'ورزش-ایران', 'title' => 'برنامه توسعه ورزش همگانی در پارک‌های شهری آغاز شد', 'lead' => 'مربیان داوطلب تمرین‌های گروهی و آموزش‌های پایه را برای شهروندان برگزار می‌کنند.', 'topic' => 'running,park'],
            ['category' => 'ورزش-ایران', 'title' => 'باشگاه‌های محلی از راه‌اندازی مدرسه فوتبال خبر دادند', 'lead' => 'ثبت‌نام برای رده‌های سنی پایه با هدف پرورش استعدادهای بومی آغاز شده است.', 'topic' => 'soccer,children'],
            ['category' => 'فرهنگ-و-هنر', 'title' => 'نمایشگاه نقاشی هنرمندان جوان در نگارخانه محلی افتتاح شد', 'lead' => 'این نمایشگاه مجموعه‌ای از آثار تازه با نگاه به زندگی روزمره و طبیعت را ارائه می‌کند.', 'topic' => 'art,gallery'],
            ['category' => 'فرهنگ-و-هنر', 'title' => 'گروه موسیقی بومی اجرای تازه‌ای برای مخاطبان تدارک دید', 'lead' => 'قطعه‌های محلی با تنظیم جدید در برنامه‌ای برای معرفی موسیقی نواحی اجرا شد.', 'topic' => 'music,concert'],
            ['category' => 'فرهنگ-و-هنر', 'title' => 'کارگاه فیلم‌سازی کوتاه با حضور علاقه‌مندان برگزار شد', 'lead' => 'شرکت‌کنندگان با ایده‌پردازی، تصویربرداری و تدوین آثار کوتاه آشنا شدند.', 'topic' => 'film,camera'],
            ['category' => 'فرهنگ-و-هنر', 'title' => 'مرمت یک بنای تاریخی در جنوب به مرحله پایانی رسید', 'lead' => 'متخصصان با حفظ عناصر اصلی بنا، مسیر بازدید عمومی از این اثر را آماده می‌کنند.', 'topic' => 'historic,architecture'],
            ['category' => 'جهان-و-ایران', 'title' => 'گفت‌وگوی کارشناسان درباره نقش ایران در مسیرهای تجاری منطقه', 'lead' => 'تحلیلگران ظرفیت‌های حمل‌ونقل، بنادر و همکاری‌های اقتصادی منطقه‌ای را بررسی کردند.', 'topic' => 'cargo,port'],
            ['category' => 'جهان-و-ایران', 'title' => 'نشست فرهنگی کشورهای منطقه بر تبادل تجربه تمرکز داشت', 'lead' => 'نمایندگان فرهنگی درباره پروژه‌های مشترک، گردشگری و گفت‌وگوی میان‌فرهنگی گفت‌وگو کردند.', 'topic' => 'culture,conference'],
            ['category' => 'جهان-و-ایران', 'title' => 'گزارش تازه از روند همکاری دانشگاهی میان پژوهشگران منطقه', 'lead' => 'دانشگاه‌ها بر گسترش پروژه‌های مشترک و تبادل دانشجو و استاد تأکید کرده‌اند.', 'topic' => 'university,research'],
            ['category' => 'جهان-و-ایران', 'title' => 'کارشناسان انرژی آینده همکاری‌های دریایی را بررسی کردند', 'lead' => 'نشست تخصصی بر فرصت‌های فناوری، ایمنی و توسعه پایدار در حوزه دریا تمرکز داشت.', 'topic' => 'ocean,energy'],
            ['category' => 'جهان-و-ایران', 'title' => 'رویداد نوآوری منطقه‌ای از ایده‌های جوانان میزبانی کرد', 'lead' => 'تیم‌های نوآور راهکارهای خود را در حوزه شهر هوشمند، محیط‌زیست و خدمات دیجیتال ارائه کردند.', 'topic' => 'technology,innovation'],
        ];

        foreach ($news as $index => $item) {
            $slug = str_replace(' ', '-', trim($item['title']));
            $article = Article::query()->create([
                    'slug' => $slug,
                    'author_id' => $reporter->id,
                    'category_id' => $categories[$item['category']]->id,
                    'title' => $item['title'],
                    'lead' => $item['lead'],
                    'body' => $item['lead']."\n\nاین گزارش نمونهٔ تحریریه برای نمایش نسخهٔ آزمایشی سامانه ثبت شده است.",
                    'status' => ArticleStatus::PUBLISHED,
                    'publish_at' => now()->subMinutes($index * 18),
                    'published_at' => now()->subMinutes($index * 18),
                    'meta_title' => $item['title'],
                    'meta_description' => $item['lead'],
                    'canonical_path' => '/articles/'.$slug,
                    'views' => max(10, 240 - ($index * 13)),
                    'is_breaking' => $index < 2,
                ]);

            $this->attachImage($article, $reporter, $item['topic'], $index + 1);
        }
    }

    private function attachImage(Article $article, User $reporter, string $topic, int $index): void
    {
        static $image = null;

        if ($image === null) {
            $response = Http::withoutVerifying()->timeout(25)->retry(2, 750, null, false)->get('https://loremflickr.com/1280/720/government,meeting?lock=1');

            if (! $response->successful() || $response->body() === '') {
                throw new \RuntimeException("Unable to download image for article {$article->id}.");
            }

            $image = $response->body();
        }

        $filename = sprintf('article-%02d.jpg', $index);
        $path = "media/news-seed/{$filename}";
        Storage::disk('public')->put($path, $image);
        $absolutePath = Storage::disk('public')->path($path);
        [$width, $height] = array_pad(getimagesize($absolutePath) ?: [], 2, null);

        $media = Media::query()->create([
            'uploaded_by' => $reporter->id,
            'disk' => 'public',
            'path' => $path,
            'type' => MediaType::IMAGE,
            'original_name' => $filename,
            'mime_type' => 'image/jpeg',
            'size' => Storage::disk('public')->size($path),
            'width' => $width,
            'height' => $height,
            'mediable_type' => Article::class,
            'mediable_id' => $article->id,
        ]);

        $article->update(['featured_media_id' => $media->id]);
    }
}
