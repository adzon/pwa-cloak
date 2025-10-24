<?php

namespace App\Filament\Resources\OtherPixelResource\Pages;

use App\Filament\Resources\OtherPixelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOtherPixel extends EditRecord
{
    protected static string $resource = OtherPixelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
