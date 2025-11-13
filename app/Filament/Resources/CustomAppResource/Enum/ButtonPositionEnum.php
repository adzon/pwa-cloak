<?php

namespace App\Filament\Resources\CustomAppResource\Enum;

enum ButtonPositionEnum
{
    // 上
    public const TOP = 1;
    // 中
    public const MIDDLE = 2;
    // 下
    public const BOTTOM = 3;
    // 下拉列表枚举
    public const SELECT = [
        self::TOP => '上',
        self::MIDDLE => '中',
        self::BOTTOM => '下',
    ];
}
