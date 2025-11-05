<?php

namespace App\Filament\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait HasUserAccess
 * 
 * 为 Filament Resource 提供基于用户的数据访问控制
 * - 超级管理员可以访问所有数据
 * - 普通用户只能访问自己创建的数据
 */
trait HasUserAccess
{
    /**
     * 应用 Eloquent 查询作用域
     * 限制普通用户只能看到自己的数据
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        return applyUserDataScope($query);
    }
    
    /**
     * 检查用户是否可以查看记录
     */
    public static function canView(Model $record): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        
        return canAccessRecord($record);
    }
    
    /**
     * 检查用户是否可以编辑记录
     */
    public static function canEdit(Model $record): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        
        return canAccessRecord($record);
    }
    
    /**
     * 检查用户是否可以删除记录
     */
    public static function canDelete(Model $record): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        
        return canAccessRecord($record);
    }
    
    /**
     * 检查用户是否可以批量删除
     */
    public static function canDeleteAny(): bool
    {
        // 所有登录用户都可以批量删除（只能删除自己的数据）
        return true;
    }
}

