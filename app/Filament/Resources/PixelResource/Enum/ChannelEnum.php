<?php

namespace App\Filament\Resources\PixelResource\Enum;

use Illuminate\Validation\Rules\Enum;

enum ChannelEnum
{
    public const FACEBOOK = 'facebook';
    public const FACEBOOK_ID = 1;
    public const TIKTOK = 'tiktok';
    public const TIKTOK_ID = 2;
    public const GOOGLE = 'google';
    public const GOOGLE_ID = 3;
    public const CHANNEL_LIST = [
        self::FACEBOOK_ID => self::FACEBOOK,
        self::TIKTOK_ID => self::TIKTOK,
        self::GOOGLE_ID => self::GOOGLE,
    ];

    public static function getLabel($id): string
    {
        return self::CHANNEL_LIST[$id];
    }

    public static function getId($value): ?int
    {
        return match ($value) {
            self::FACEBOOK => self::FACEBOOK_ID,
            self::TIKTOK => self::TIKTOK_ID,
            self::GOOGLE => self::GOOGLE_ID,
            default => null,
        };
    }

}
