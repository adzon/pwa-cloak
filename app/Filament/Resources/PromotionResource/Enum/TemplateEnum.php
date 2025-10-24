<?php

namespace App\Filament\Resources\PromotionResource\Enum;

enum TemplateEnum
{
    public const GAME = 'Game';
    public const GAME_ID = 1;
    public const SKITS = 'Skits';
    public const SKITS_ID = 2;
    public const SHOP = 'Shop';
    public const SHOP_ID = 3;

    public const TEMPLATE_LIST = [
        self::GAME_ID => self::GAME,
        self::SKITS_ID => self::SKITS,
        self::SHOP_ID => self::SHOP,
    ];

}
