<?php

namespace App\Filament\Resources\OtherPixelResource\Pages;

use App\Filament\Resources\OtherPixelResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOtherPixel extends CreateRecord
{
    protected static string $resource = OtherPixelResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }
}
