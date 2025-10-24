<?php

namespace App\Filament\Resources\PixelResource\Pages;

use App\Filament\Resources\PixelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPixel extends EditRecord
{
    protected static string $resource = PixelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

}
