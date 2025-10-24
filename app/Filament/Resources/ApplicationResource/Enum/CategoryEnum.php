<?php

namespace App\Filament\Resources\ApplicationResource\Enum;

enum CategoryEnum
{
    public const CATEGORY_GAMES = 'games';
    public const CATEGORY_APPS = 'apps';
    public const CATEGORY_FILMS = 'films';
    public const CATEGORY_BOOKS = 'books';
    public const CATEGORY_CHILDREN = 'children';

    public const CATEGORY_LIST = [
        self::CATEGORY_GAMES => 'Games',
        self::CATEGORY_APPS => 'Apps',
        self::CATEGORY_FILMS => 'Films',
        self::CATEGORY_BOOKS => 'Books',
        self::CATEGORY_CHILDREN => 'Children',
    ];

}
