<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pixel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pixel_code',
        'pixel_name',
        'channel',
        'test_event_code',
        'access_token',
        'is_delete',
        'status',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'channel' => 'integer',
        'is_delete' => 'boolean',
        'status' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class, 'pixel_id');
    }
}
