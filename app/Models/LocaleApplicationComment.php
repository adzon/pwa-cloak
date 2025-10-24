<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocaleApplicationComment extends Model
{
    use HasFactory;

    protected $table = 'locale_application_comment';

    protected $fillable = [
        'locale_application_id',
        'comment_id',
    ];

    public function localeApplication()
    {
        return $this->belongsTo(LocaleApplication::class, 'locale_application_id');
    }

    public function comment()
    {
        return $this->belongsTo(Comment::class, 'comment_id');
    }
}
