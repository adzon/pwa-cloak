<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

/**
 * 检查当前用户是否为超级管理员
 *
 * @return bool
 */
function isSuperAdmin(): bool
{
    /** @var \App\Models\User|null $user */
    $user = Auth::user();

    if (!$user) {
        return false;
    }

    return $user->hasRole('super_admin');
}

/**
 * 检查当前用户是否为普通用户
 *
 * @return bool
 */
function isPanelUser(): bool
{
    /** @var \App\Models\User|null $user */
    $user = Auth::user();

    if (!$user) {
        return false;
    }

    return $user->hasRole('panel_user') && !$user->hasRole('super_admin');
}

/**
 * 应用用户数据权限过滤
 * 超级管理员可以看到所有数据，普通用户只能看到自己的数据
 *
 * @param Builder $query
 * @return Builder
 */
function applyUserDataScope(Builder $query): Builder
{
    if (isSuperAdmin()) {
        // 超级管理员可以查看所有数据
        return $query;
    }

    // 普通用户只能查看自己创建的数据
    return $query->where('user_id', Auth::id());
}

/**
 * 检查当前用户是否可以访问指定记录
 *
 * @param mixed $record 需要检查的记录
 * @return bool
 */
function canAccessRecord($record): bool
{
    if (isSuperAdmin()) {
        return true;
    }

    // 检查记录是否有 user_id 字段
    if (!isset($record->user_id)) {
        return true; // 如果没有 user_id，默认允许访问
    }

    return $record->user_id === Auth::id();
}
