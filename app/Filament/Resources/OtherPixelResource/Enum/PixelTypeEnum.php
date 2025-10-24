<?php

namespace App\Filament\Resources\OtherPixelResource\Enum;

enum PixelTypeEnum
{
    public const ADJUST = 'adjust';
    public const ADJUST_ID = 1;

    public const PIXEL_LIST = [
        self::ADJUST_ID => self::ADJUST,
    ];
}
