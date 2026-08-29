<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleViewStat extends Model
{
    protected $fillable = ['article_id', 'view_date', 'views'];

    protected function casts(): array
    {
        return ['view_date' => 'date', 'views' => 'integer'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
