<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrivateConversation extends Model
{
    protected $fillable = ['user_one_id', 'user_two_id'];

    public function userOne(): BelongsTo { return $this->belongsTo(User::class, 'user_one_id'); }
    public function userTwo(): BelongsTo { return $this->belongsTo(User::class, 'user_two_id'); }
    public function messages(): HasMany { return $this->hasMany(PrivateMessage::class, 'conversation_id'); }
    public function includes(int $userId): bool { return $this->user_one_id === $userId || $this->user_two_id === $userId; }
}
