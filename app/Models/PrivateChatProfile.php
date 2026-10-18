<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class PrivateChatProfile extends Model
{
    protected $fillable = ['user_id', 'nickname', 'avatar_path', 'avatar_mime'];

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime'];
    }

    protected function nickname(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null ? null : Crypt::decryptString($value),
            set: fn (?string $value) => $value === null ? null : Crypt::encryptString($value),
        );
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
