<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Language extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'en_name', 'status'];

    protected $casts = ['status' => 'boolean'];

    public function localeApplications(): HasMany
    {
        return $this->hasMany(LocaleApplication::class, 'language_id');
    }

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(
            Application::class,
            'locale_application',
            'language_id',
            'app_id'
        );
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'language_id');
    }
}
