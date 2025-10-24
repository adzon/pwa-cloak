<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Domain extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pid',
        'promotion_id',
        'domain',
        'hosting_id',
        'hosting_name_servers',
        'status',
        'is_save',
        'is_delete',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'pid' => 'integer',
        'promotion_id' => 'integer',
        'hosting_name_servers' => 'array',
        'status' => 'boolean',
        'is_delete' => 'boolean',
        'is_save' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    /**
     * 检测域名解析状态（0=证书上传中，1=解析成功）
     */
    public function checkDnsStatus(): int
    {
        // TODO: 你可以用 dig/nslookup 检查解析是否生效，这里先模拟返回
        return $this->status; // 0=上传中, 1=成功
    }

    public function checkUsage(): bool
    {
        return $this->promotion_id > 0;
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function scopeAvailablePlatform($query)
    {
        return $query
            ->where('user_id', 0)
            ->where('is_delete', false)
            ->where('status', 1)
            ->where('is_save', false)
            ->where('promotion_id', 0);
    }

// 获取当前用户的托管域名
    public function scopeAvailableHosting($query, $userId)
    {
        return $query
            ->where('user_id', $userId)
            ->where('is_delete', false)
            ->where('status', 1)
            ->where('is_save', true)
            ->where('promotion_id', 0);
    }
}
