<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtherPixel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'channel',
        'app_name',
        'app_code',
        'api_code',
        'access_code',
        'install_code'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'channel' => 'integer'
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

}
