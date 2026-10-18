<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateMessage extends Model
{
    protected $fillable = ['conversation_id', 'sender_id', 'body', 'attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size', 'reply_to_id', 'pinned_at', 'edited_at'];

    protected function body(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null ? null : decrypt($value),
            set: fn (?string $value) => $value === null ? null : encrypt($value),
        );
    }

    public function conversation(): BelongsTo { return $this->belongsTo(PrivateConversation::class, 'conversation_id'); }
    public function sender(): BelongsTo { return $this->belongsTo(User::class, 'sender_id'); }
    public function replyTo(): BelongsTo { return $this->belongsTo(self::class, 'reply_to_id'); }
}
