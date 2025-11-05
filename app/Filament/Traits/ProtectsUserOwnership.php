<?php

namespace App\Filament\Traits;

/**
 * Trait ProtectsUserOwnership
 *
 * 保护数据的所有者关系（user_id），确保编辑时不会改变原始创建者
 * 用于超级管理员编辑普通用户创建的内容时，保持原始归属关系
 *
 * 使用方法：
 * 在 handleRecordUpdate 方法开始时调用 $this->protectUserOwnership($data);
 */
trait ProtectsUserOwnership
{
    /**
     * 从数据数组中移除 user_id，保护原始创建者
     *
     * @param array &$data 表单数据（引用传递）
     * @return void
     */
    protected function protectUserOwnership(array &$data): void
    {
        // 确保不会修改 user_id（保护原始创建者）
        unset($data['user_id']);
    }
}

