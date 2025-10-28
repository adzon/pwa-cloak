<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocaleApplication extends Model
{
    use HasFactory;

    protected $table = 'locale_application';

    protected $fillable = [
        'language_id',
        'app_id',
        'name',
        'manufacturer',
        'icon',
        'downloads',
        'age_limit',
        'comment_count',
        'introduction',
        'images',
        'label',
    ];

    protected $casts = [
        'age_limit' => 'integer',
        'comment_count' => 'integer',
        'images' => 'array',
        'label' => 'array',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'app_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public function comments(): BelongsToMany
    {
        return $this->belongsToMany(
            Comment::class,
            'locale_application_comment',
            'locale_application_id',
            'comment_id'
        )->withTimestamps();
    }
}
