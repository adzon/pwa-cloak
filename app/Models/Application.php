<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Query\Builder;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'remark',
        'google_show',
        'official_verified',
        'icon',
        'background_color',
        'theme_color',
        'category',
        'display_mode',
        'orientation',
        'apk_upload_enabled',
        'apk',
        'ercode_show',
        'package_priority',
        'ios_guide',
        'w2a_auto_down',
        'is_iframe',
        'complaint',
        'complaint_config',
        'is_delete'
    ];

    protected $casts = [
        'google_show' => 'boolean',
        'official_verified' => 'boolean',
        'apk_upload_enabled' => 'boolean',
        'ercode_show' => 'boolean',
        'ios_guide' => 'boolean',
        'w2a_auto_down' => 'boolean',
        'is_iframe' => 'boolean',
        'complaint' => 'boolean',
        'is_delete' => 'boolean'
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function localeApplications(): HasMany
    {
        return $this->hasMany(LocaleApplication::class, 'app_id');
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(
            Language::class,
            'locale_application',
            'app_id',
            'language_id'
        );
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class, 'app_id');
    }

    /**
     * 获取应用的所有评论
     * 通过中间表locale_application_comment实现多对多关系
     */
    public function comments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Comment::class,
            LocaleApplication::class,
            'app_id',                    // locale_application 表中的外键
            'locale_application_id',     // locale_application_comment 表中的外键
            'id',                        // applications 表的本地键
            'id'                         // locale_application 表的本地键
        )->distinct();
    }

    // 在 Application 模型中添加以下方法
    public function getComplaintConfigAttribute(): array|false
    {
        return $this->attributes['complaint_config'] ? explode(',', $this->attributes['complaint_config']) : [];
    }

    public function setComplaintConfigAttribute($value): void
    {
        $this->attributes['complaint_config'] = is_array($value) ? implode(',', $value) : $value;
    }


}
