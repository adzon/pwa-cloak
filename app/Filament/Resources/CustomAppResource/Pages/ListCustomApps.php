<?php

namespace App\Filament\Resources\CustomAppResource\Pages;

use App\Filament\Resources\CustomAppResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomApps extends ListRecords
{
    protected static string $resource = CustomAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('新增图文落地页')
                ->button(),
        ];
    }

    public function getTitle(): string
    {
        return '自定义应用';
    }
}
