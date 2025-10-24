<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,

    ...app()->environment('local') ? [
        Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class,
    ] : [],
];
