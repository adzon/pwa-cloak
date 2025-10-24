<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'language_id', // 评论语言
        'nickname',
        'content',
    ];

    public function localeApplications(): BelongsToMany
    {
        return $this->belongsToMany(
            LocaleApplication::class,
            'locale_application_comment',
            'comment_id',
            'locale_application_id'
        )->withTimestamps();
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
