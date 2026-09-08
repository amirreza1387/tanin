# راهنمای اتصال فرانت‌اند به API صدای جنوب

## مبنای اتصال

- نشانی پایه: `/api/v1`
- همه پاسخ‌ها JSON هستند و این ساختار را دارند:

```json
{
  "data": {},
  "meta": {},
  "errors": null
}
```

- در خطا، `data` برابر `null` و `errors` شامل پیام یا خطاهای فیلدی است.
- تمام تاریخ‌ها در API به صورت ISO 8601 و مقدار Jalali آماده نمایش ارائه می‌شوند.
- رابط کاربری باید راست‌به‌چپ و با `lang="fa"` ساخته شود.

## مرجع کامل Endpointها

### احراز هویت

#### ثبت‌نام

`POST /auth/register` — بدون احراز هویت

بدنه:

```json
{
  "name": "علی رضایی",
  "email": "ali@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

پاسخ `201`:

```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "علی رضایی",
      "email": "ali@example.com",
      "role": "user",
      "role_label": "کاربر",
      "created_at": "2026-08-27T12:00:00+00:00"
    },
    "token": "1|توکن-سنکتوم",
    "token_type": "Bearer"
  },
  "meta": {},
  "errors": null
}
```

#### ورود

`POST /auth/login` — بدون احراز هویت

بدنه:

```json
{
  "email": "ali@example.com",
  "password": "password123"
}
```

پاسخ `200` مانند ثبت‌نام است و شامل `data.user`، `data.token` و `data.token_type` می‌شود.

#### خروج

`POST /auth/logout` — نیازمند Bearer Token

بدنه ندارد.

پاسخ:

```json
{
  "data": {
    "message": "با موفقیت خارج شدید."
  },
  "meta": {},
  "errors": null
}
```

#### کاربر جاری

`GET /auth/me` — نیازمند Bearer Token

پاسخ `data` یک شیء `UserResource` است.

#### تغییر گذرواژه

`PUT /auth/password` — نیازمند Bearer Token

بدنه:

```json
{
  "current_password": "password123",
  "password": "new-password123",
  "password_confirmation": "new-password123"
}
```

پاسخ:

```json
{
  "data": {
    "message": "گذرواژه با موفقیت تغییر کرد."
  },
  "meta": {},
  "errors": null
}
```

### محتوای عمومی

#### فهرست اخبار

`GET /articles` — عمومی

پارامترهای اختیاری: `category_id`، `category_slug`، `from`، `to`، `per_page`.

پاسخ:

```json
{
  "data": [
    {
      "id": 10,
      "title": "استان خوزستان میزبان رویداد بزرگ خبری شد",
      "slug": "استان-خوزستان-میزبان-رویداد-بزرگ-خبری-شد",
      "lead": "گزارشی از تازه‌ترین رویدادهای استان خوزستان.",
      "status": "published",
      "status_label": "منتشرشده",
      "publish_at": "2026-08-26T12:00:00+00:00",
      "publish_at_jalali": "1405/06/04 15:30",
      "published_at": "2026-08-26T12:00:00+00:00",
      "published_at_jalali": "1405/06/04 15:30",
      "views": 125,
      "is_breaking": true,
      "seo": {
        "meta_title": "استان خوزستان میزبان رویداد بزرگ خبری شد",
        "meta_description": "آخرین اخبار استان خوزستان را بخوانید.",
        "canonical_path": "/articles/استان-خوزستان-میزبان-رویداد-بزرگ-خبری-شد",
        "json_ld": {}
      },
      "author": {},
      "category": {},
      "tags": [],
      "featured_media": null
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1,
    "from": 1,
    "to": 1
  },
  "errors": null
}
```

#### جزئیات خبر

`GET /articles/{slug}` — عمومی

بدنه ندارد. پاسخ `200` مانند `ArticleResource` است و علاوه بر فیلدهای فهرست، `body` و نظرات تأییدشده را برمی‌گرداند. بازدید خبر در Redis/cache بافر می‌شود و بعداً به دیتابیس منتقل می‌شود.

#### اخبار فوری فعال

`GET /breaking-news` — عمومی

پارامتر ندارد. پاسخ `data` آرایه‌ای از `ArticleResource`های منتشرشده با `is_breaking=true` است.

#### پربازدیدترین اخبار

`GET /most-viewed?period=today` — عمومی

مقدار `period` فقط `today` یا `week` است. پاسخ `data` آرایه‌ای از `ArticleResource` است و پنج دقیقه cache می‌شود.

نمونه:

```json
{
  "data": [
    {
      "id": 10,
      "title": "استان خوزستان میزبان رویداد بزرگ خبری شد",
      "slug": "استان-خوزستان-میزبان-رویداد-بزرگ-خبری-شد",
      "views": 125,
      "status": "published"
    }
  ],
  "meta": {},
  "errors": null
}
```

#### جست‌وجو

`GET /search?q=خوزستان&category_id=2&from=2026-08-01&to=2026-08-27` — عمومی

پارامتر `q` الزامی است؛ `category_id`، `from`، `to` و `per_page` اختیاری هستند. در MySQL از FULLTEXT و در SQLite از LIKE استفاده می‌شود. پاسخ paginated مانند فهرست اخبار است.

#### فهرست دسته‌بندی‌ها

`GET /categories` — عمومی

پاسخ:

```json
{
  "data": [
    {
      "id": 2,
      "name": "ورزش",
      "slug": "ورزش",
      "parent_id": null,
      "sort_order": 2,
      "articles": [],
      "children": [
        {
          "id": 3,
          "name": "فوتبال",
          "slug": "فوتبال",
          "parent_id": 2,
          "sort_order": 1,
          "articles": [],
          "children": []
        }
      ]
    }
  ],
  "meta": {},
  "errors": null
}
```

#### جزئیات دسته‌بندی

`GET /categories/{slug}` — عمومی

پاسخ `data` شامل `id`، `name`، `slug`، `parent_id`، `sort_order`، `children` و اخبار منتشرشده دسته است.

#### فهرست برچسب‌ها

`GET /tags` — عمومی

پاسخ:

```json
{
  "data": [
    {
      "id": 1,
      "name": "اهواز",
      "slug": "اهواز",
      "articles_count": 4,
      "articles": []
    }
  ],
  "meta": {},
  "errors": null
}
```

#### جزئیات برچسب

`GET /tags/{slug}` — عمومی

پاسخ `data` شامل `id`، `name`، `slug` و اخبار منتشرشده دارای برچسب است.

#### فهرست نظرات خبر

`GET /articles/{article}/comments` — عمومی، فقط خبر منتشرشده

بدنه ندارد. فقط نظرات `approved` و پاسخ‌های تأییدشده بازگردانده می‌شوند.

نمونه:

```json
{
  "data": [
    {
      "id": 7,
      "body": "خبر بسیار خوبی بود.",
      "status": "approved",
      "status_label": "تأییدشده",
      "user": {
        "id": 4,
        "name": "مریم احمدی",
        "email": "maryam@example.com",
        "role": "user",
        "role_label": "کاربر",
        "created_at": "2026-08-27T12:00:00+00:00"
      },
      "parent_id": null,
      "replies": [],
      "created_at": "2026-08-27T12:10:00+00:00"
    }
  ],
  "meta": {},
  "errors": null
}
```

### خبرنگار و مدیر

تمام مسیرهای این بخش نیازمند `Authorization: Bearer {token}` هستند.

#### ایجاد خبر

`POST /articles` — خبرنگار یا مدیر

بدنه:

```json
{
  "title": "عنوان خبر جدید",
  "lead": "خلاصه خبر",
  "body": "<p>متن غنی خبر</p>",
  "category_id": 2,
  "featured_media_id": 5,
  "tag_ids": [1, 3],
  "meta_title": "عنوان سئو",
  "meta_description": "توضیحات سئو"
}
```

پاسخ `201` یک `ArticleResource` با وضعیت `draft` است.

#### فهرست اخبار مدیریتی

`GET /management/articles` — خبرنگار یا مدیر

خبرنگار فقط اخبار خودش را می‌بیند؛ مدیر همه اخبار را می‌بیند. پارامتر اختیاری `per_page` دارد و پاسخ paginated است.

#### ویرایش خبر

`PUT /articles/{article}` — صاحب خبر یا مدیر

بدنه مانند ایجاد خبر است. `status` و `publish_at` در این مسیر مجاز نیستند. پاسخ یک `ArticleResource` است.

#### حذف خبر

`DELETE /articles/{article}` — صاحب خبر یا مدیر

بدنه ندارد.

پاسخ:

```json
{
  "data": {
    "message": "خبر با موفقیت حذف شد."
  },
  "meta": {},
  "errors": null
}
```

#### انتشار فوری

`POST /articles/{article}/publish` — صاحب خبر یا مدیر

بدنه ندارد. وضعیت خبر به `published` تغییر می‌کند، تاریخ انتشار ثبت می‌شود، cacheها invalidate می‌شوند و sitemap بازسازی می‌شود.

#### زمان‌بندی انتشار

`POST /articles/{article}/schedule` — صاحب خبر یا مدیر

بدنه:

```json
{
  "publish_at": "2026-08-28T10:00:00+00:00"
}
```

پاسخ یک `ArticleResource` با `status: scheduled` است. job زمان‌بندی‌شده هر دقیقه اخبار آماده انتشار را منتشر می‌کند.

#### بازگردانی به پیش‌نویس

`POST /articles/{article}/revert` — فقط مدیر

بدنه:

```json
{
  "note": "نیازمند اصلاح محتوایی"
}
```

پاسخ یک `ArticleResource` با `status: draft` و `takedown_note` است.

#### فعال یا غیرفعال‌کردن خبر فوری

`PATCH /articles/{article}/breaking` — فقط مدیر

بدنه:

```json
{
  "is_breaking": true
}
```

پاسخ یک `ArticleResource` است. خبرنگار اجازه تنظیم این پرچم را ندارد.

#### آپلود رسانه

`POST /media` — خبرنگار یا مدیر

نوع بدنه `multipart/form-data` است:

- `file`: فایل تصویر یا ویدئو؛ الزامی؛ حداکثر 100MB
- `article_id`: شناسه خبر؛ اختیاری؛ در صورت ارسال باید کاربر اجازه ویرایش خبر را داشته باشد

پاسخ `201`:

```json
{
  "data": {
    "id": 5,
    "type": "image",
    "type_label": "تصویر",
    "url": "http://localhost/storage/media/2026/08/27/photo.jpg",
    "variants": {
      "thumbnail": "http://localhost/storage/media/2026/08/27/photo-thumbnail.jpg",
      "medium": "http://localhost/storage/media/2026/08/27/photo-medium.jpg",
      "large": "http://localhost/storage/media/2026/08/27/photo-large.jpg"
    },
    "original_name": "photo.jpg",
    "mime_type": "image/jpeg",
    "size": 204800,
    "width": 1920,
    "height": 1080
  },
  "meta": {},
  "errors": null
}
```

ساخت variantهای تصویر در queue انجام می‌شود؛ تا پیش از اجرای job ممکن است `variants` خالی باشد.

#### ثبت نظر

`POST /articles/{article}/comments` — کاربر، خبرنگار یا مدیر واردشده

بدنه:

```json
{
  "body": "نظر من درباره این خبر",
  "parent_id": null
}
```

`parent_id` برای پاسخ به یک نظر سطح اول است. پاسخ به پاسخ دیگر مجاز نیست. پاسخ `201` با `status: pending` برمی‌گردد.

### مدیریت مدیر

#### ایجاد دسته‌بندی

`POST /categories` — فقط مدیر

بدنه:

```json
{
  "name": "استان‌ها",
  "parent_id": null,
  "sort_order": 1
}
```

پاسخ `201` یک `CategoryResource` است؛ slug به صورت خودکار ساخته می‌شود.

#### ویرایش دسته‌بندی

`PUT /categories/{category}` — فقط مدیر

بدنه مانند ایجاد دسته‌بندی است. اگر نام عوض شود slug نیز با رعایت یکتایی به‌روزرسانی می‌شود.

#### حذف دسته‌بندی

`DELETE /categories/{category}` — فقط مدیر

پاسخ:

```json
{
  "data": {
    "message": "دسته‌بندی حذف شد."
  },
  "meta": {},
  "errors": null
}
```

#### ایجاد برچسب

`POST /tags` — فقط مدیر

بدنه:

```json
{
  "name": "خبر فوری"
}
```

پاسخ `201` یک `TagResource` است.

#### ویرایش برچسب

`PUT /tags/{tag}` — فقط مدیر

بدنه:

```json
{
  "name": "خبر فوری جنوب"
}
```

پاسخ یک `TagResource` است.

#### حذف برچسب

`DELETE /tags/{tag}` — فقط مدیر

پاسخ مانند حذف دسته‌بندی و با پیام `برچسب حذف شد.` است.

#### صف نظرات

`GET /management/comments` — فقط مدیر

پارامتر اختیاری `per_page` دارد. پاسخ paginated از `CommentResource` است و شامل نظرات pending، approved و rejected می‌شود.

#### تغییر وضعیت نظر

`PATCH /management/comments/{comment}` — فقط مدیر

بدنه:

```json
{
  "status": "approved"
}
```

مقدار وضعیت یکی از `pending`، `approved` یا `rejected` است. پاسخ یک `CommentResource` است.

#### فهرست کاربران

`GET /management/users` — فقط مدیر

پارامتر اختیاری `per_page` دارد. پاسخ paginated از `UserResource` است.

#### تغییر نقش یا نام کاربر

`PATCH /management/users/{user}` — فقط مدیر

بدنه:

```json
{
  "name": "خبرنگار جدید",
  "role": "reporter"
}
```

نقش یکی از `admin`، `reporter` یا `user` است. پاسخ یک `UserResource` است.

## قرارداد داده‌ها

### UserResource

| فیلد | نوع |
|---|---|
| `id` | integer |
| `name` | string |
| `email` | string |
| `role` | `admin` \| `reporter` \| `user` |
| `role_label` | string |
| `created_at` | ISO 8601 string |

### ArticleResource

| فیلد | نوع |
|---|---|
| `id` | integer |
| `title` | string |
| `slug` | string فارسی URL-encoded |
| `lead` | string یا null |
| `body` | string؛ در جزئیات و مدیریت |
| `status` | `draft` \| `scheduled` \| `published` |
| `status_label` | string |
| `publish_at` / `published_at` | ISO 8601 string یا null |
| `publish_at_jalali` / `published_at_jalali` | string Jalali یا null |
| `views` | integer |
| `is_breaking` | boolean |
| `takedown_note` | string یا null؛ برای مدیر |
| `seo` | object شامل metaها، canonical و JSON-LD |
| `author` | `UserResource` |
| `category` | `CategoryResource` |
| `tags` | آرایه `TagResource` |
| `featured_media` | `MediaResource` یا null |
| `comments` | آرایه `CommentResource` |

### CategoryResource

شامل `id: integer`، `name: string`، `slug: string`، `parent_id: integer|null`، `sort_order: integer`، `articles: ArticleResource[]` و `children: CategoryResource[]` است.

### TagResource

شامل `id: integer`، `name: string`، `slug: string`، `articles_count: integer|null` و `articles: ArticleResource[]` است.

### MediaResource

شامل `id: integer`، `type: image|video`، `type_label: string`، `url: string`، `variants: object`، `original_name: string`، `mime_type: string`، `size: integer`، `width: integer|null` و `height: integer|null` است.

### CommentResource

شامل `id: integer`، `body: string`، `status: pending|approved|rejected`، `status_label: string`، `user: UserResource`، `parent_id: integer|null`، `replies: CommentResource[]` و `created_at: ISO 8601 string` است.

## چرخه توکن و خطاها

توکن Sanctum بعد از ورود یا ثبت‌نام دریافت می‌شود. در هر درخواست محافظت‌شده این header را بفرستید:

```http
Authorization: Bearer 1|توکن-سنکتوم
Accept: application/json
```

- خروج فقط توکن جاری را حذف می‌کند.
- تغییر گذرواژه با `current_password` انجام می‌شود.
- ثبت‌نام و ورود rate limit دارند.
- `401`: توکن وجود ندارد یا معتبر نیست.
- `403`: کاربر وارد شده اما اجازه این عملیات را ندارد.
- `404`: رکورد پیدا نشد.
- `422`: اعتبارسنجی ناموفق است؛ جزئیات در `errors` است.
- `429`: تعداد درخواست‌ها بیش از حد مجاز است.

## صفحات پیشنهادی فرانت‌اند

| صفحه | Endpointهای اصلی | الگوی `<title>` |
|---|---|---|
| صفحه اصلی | `/articles`، `/breaking-news`، `/most-viewed?period=today`، `/categories` | `صدای جنوب | آخرین اخبار جنوب` |
| صفحه دسته‌بندی | `/categories/{slug}`، `/articles?category_slug={slug}` | `اخبار {نام دسته} | صدای جنوب` |
| صفحه خبر | `/articles/{slug}`، `/articles/{article}/comments` | `{عنوان خبر} | صدای جنوب` |
| صفحه برچسب | `/tags/{slug}` | `برچسب {نام برچسب} | صدای جنوب` |
| نتایج جست‌وجو | `/search?q={query}` | `جست‌وجو برای «{query}» | صدای جنوب` |
| اخبار فوری | `/breaking-news` | `اخبار فوری | صدای جنوب` |
| پربازدیدترین‌ها | `/most-viewed?period=today` یا `week` | `پربازدیدترین اخبار | صدای جنوب` |
| ورود | `/auth/login` | `ورود | صدای جنوب` |
| ثبت‌نام | `/auth/register` | `ثبت‌نام | صدای جنوب` |
| پروفایل کاربر | `/auth/me`، `/auth/password` | `پروفایل کاربر | صدای جنوب` |
| داشبورد خبرنگار | `/management/articles`، `/articles`، `/media` | `داشبورد خبرنگار | صدای جنوب` |
| مقالات من | `/management/articles` | `مقالات من | صدای جنوب` |
| انتشار خبر جدید | `POST /articles`، `POST /media` | `انتشار خبر جدید | صدای جنوب` |
| داشبورد مدیر | `/management/articles`، `/management/comments`، `/management/users`، `/categories`، `/tags`، `/media` | `داشبورد مدیریت | صدای جنوب` |
| مدیریت اخبار | `/management/articles`، مسیرهای publish/schedule/revert/breaking | `مدیریت اخبار | صدای جنوب` |
| مدیریت نظرات | `/management/comments` | `مدیریت نظرات | صدای جنوب` |
| مدیریت دسته‌بندی و برچسب | `/categories`، `/tags` و مسیرهای مدیریتی آن‌ها | `دسته‌بندی‌ها و برچسب‌ها | صدای جنوب` |
| مدیریت رسانه | `POST /media` | `رسانه‌ها | صدای جنوب` |
| مدیریت کاربران | `/management/users` | `کاربران | صدای جنوب` |

## پیشنهاد فنی برای فرانت‌اند

- Vue 3 با Vue Router و Pinia.
- Tailwind CSS با فعال‌سازی RTL و `dir="rtl"` در ریشه برنامه.
- فونت پیشنهادی: Vazirmatn یا یک فونت فارسی وب‌بهینه.
- برای تاریخ‌ها مقدارهای `*_jalali` را مستقیماً نمایش دهید؛ برای sort و محاسبات از ISO استفاده کنید.
- یک API client مرکزی بسازید که header توکن، envelope پاسخ و خطاهای `401/403/422` را مدیریت کند.
- توکن را در فضای امن مناسب محصول نگهداری کنید و هنگام logout آن را پاک کنید.

## SEO و JSON-LD

- مقدار `seo.meta_title` را برای `<title>` و meta title استفاده کنید.
- مقدار `seo.meta_description` را در `<meta name="description">` قرار دهید.
- مسیر `seo.canonical_path` را با دامنه عمومی سایت ترکیب کرده و در `<link rel="canonical">` بگذارید.
- مقدار `seo.json_ld` را با `JSON.stringify` داخل `<script type="application/ld+json">` رندر کنید.
- `json_ld` از نوع `NewsArticle` است و headline، description، تاریخ انتشار، تاریخ ویرایش، نویسنده و تصویر را آماده دارد.
- در SSR یا prerender، این فیلدها را در HTML اولیه قرار دهید تا crawler پیش از اجرای JavaScript نیز آن‌ها را ببیند.

## سیستم طراحی رابط

برای کاهش پراکندگی کلاس‌ها، اجزای جدید باید از توکن‌های معنایی و primitiveهای مشترک استفاده کنند. توکن‌ها در `resources/css/app.css` تعریف شده‌اند و primitiveها در `resources/js/components` قرار دارند.

- رنگ‌ها: `primary` برای کنش اصلی، `danger` برای حذف/خطا، `success` برای نتیجه موفق، `warning` برای وضعیت نیازمند توجه، `info` برای اطلاعات و `surface` برای سطوح پس‌زمینه.
- فاصله‌ها: از مقیاس ۱، ۲، ۳، ۴، ۵، ۶ و ۸ استفاده کنید؛ فاصله‌های صفحه معمولاً `p-4` تا `p-8` هستند.
- تایپوگرافی: متن پایه `text-sm`، متن کمکی `text-xs` و عنوان صفحه `text-2xl font-black` است.
- شعاع و سایه: کنترل‌ها `radius-md`، کارت‌ها `radius-xl` و مودال‌ها `radius-2xl` دارند؛ سایه پیش‌فرض `shadow-sm` است.
- Primitiveها: برای دکمه از `Button`، ورودی از `Input`، فهرست از `Select`، برچسب از `Badge`/`StatusBadge` و سطح محتوا از `Card` استفاده کنید.
- وضعیت‌ها: وضعیت‌های `published/approved/active` سبز، `pending` زرد، `scheduled` آبی، `rejected` قرمز و وضعیت ناشناخته خنثی هستند.
- اعلان، جدول و صفحه‌بندی: به‌ترتیب از کلاس‌های `ds-alert`، `ds-table` و `ds-pagination` استفاده کنید. صفحه جاری باید `aria-current="page"` داشته باشد.
- آیکن‌ها: به‌جای Unicode از `Icon.vue` و نام‌های ثبت‌شده مانند `menu`, `search`, `plus`, `close`, `upload`, `grid`, `file`, `chat`, `users`, `tag`, `image`, `ad` و آیکن‌های `weather-*` استفاده کنید.

## اجرای محلی

```bash
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
php artisan queue:work
```

برای اجرای زمان‌بندی انتشار اخبار و انتقال بازدیدها، scheduler سیستم باید هر دقیقه `php artisan schedule:run` را اجرا کند یا از worker دائمی scheduler استفاده شود.
