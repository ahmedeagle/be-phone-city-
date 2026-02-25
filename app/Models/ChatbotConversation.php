<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotConversation extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'metadata',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'last_activity_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class, 'conversation_id');
    }

    public function updateActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }
}
