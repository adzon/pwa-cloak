<?php

use Illuminate\Support\Facades\Auth;

/**
 * 检查当前用户是否为超级管理员
 *
 * @return bool
 */
function isSuperAdmin(): bool
{
    $user = Auth::user();

    // 假设超级管理员有特定角色或权限
    // 根据项目实际情况调整此处逻辑
    if (!$user) {
        return false;
    }

    return $user->hasRole('super_admin');
}
