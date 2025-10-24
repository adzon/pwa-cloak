<?php

namespace App\Models;

use App\Filament\Resources\PromotionResource\Enum\TemplateEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'promotion_name',
        'app_id',
        'channel',
        'pixel_id',
        'other_pixel_id',
        'region_ids',
        'is_open_cloak',
        'template_id',
        'hast_result',
        'link_address',
        'ios_link_address',
        'is_delete'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'app_id' => 'integer',
        'channel' => 'integer',
        'pixel_id' => 'integer',
        'other_pixel_id' => 'integer',
        'is_open_cloak' => 'boolean',
        'template_id' => 'integer',
        'is_delete' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'app_id');
    }

    public function pixel(): BelongsTo
    {
        return $this->belongsTo(Pixel::class, 'pixel_id');
    }

    public function otherPixel(): hasOne
    {
        return $this->hasOne(OtherPixel::class);
    }

    public function getRegionCodesAttribute(): array|false
    {
        return $this->region_ids ? explode(',', $this->region_ids) : [];
    }

    public function setRegionCodesAttribute($value): void
    {
        $this->attributes['region_ids'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'promotion_id');
    }

    // 获取所有平台域名（user_id=0 且 is_save=false）
    public function platformDomains(): HasMany
    {
        return $this->hasMany(Domain::class, 'promotion_id')
            ->where('user_id', 0)
            ->where('is_save', false);
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        }

    // 获取所有托管域名（user_id=当前用户ID 且 is_save=true）
    public function hostingDomains(): HasMany
    {
        return $this->hasMany(Domain::class, 'promotion_id')
            ->where('user_id', $this->user_id)
            ->where('is_save', true);
    }

    public function getTemplateNameAttribute(): string
    {
        return TemplateEnum::TEMPLATE_LIST[$this->template_id] ?? '无';
    }

    public function getRegionNamesAttribute(): string
    {
        // 根据 region_ids 返回地区名称列表
        if (empty($this->region_ids)) {
            return '无';
        }

        $regionCodes = explode(',', $this->region_ids);
        $regionNames = [];
        foreach ($regionCodes as $code) {
            // 根据你的地区数据获取名称
            $region = Region::where('code', $code)->first();
            if ($region) {
                $regionNames[] = $region->name;
            }
        }

        return implode(', ', $regionNames) ?: '无';
    }

    public function getBoundDomainsAttribute(): array
    {
        // 获取所有绑定的平台域名
        $platformDomains = $this->platformDomains()->pluck('domain')->toArray();

        // 获取所有绑定的托管域名
        $hostingDomains = $this->hostingDomains()->pluck('domain')->toArray();

        // 合并所有域名
        return array_merge($platformDomains, $hostingDomains);
    }

    public function getFullPromotionUrlsAttribute(): array
    {
        $domains = $this->bound_domains;
        $urls = [];

        if (!empty($domains)) {
            foreach ($domains as $domain) {
                $urls[] = 'https://' . $domain;
            }
        }

        return $urls;
    }
}
