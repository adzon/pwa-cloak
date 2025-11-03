<?php

namespace App\Filament\Resources\PixelResource\Pages;

use App\Filament\Resources\PixelResource;
use App\Filament\Traits\ProtectsUserOwnership;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPixel extends EditRecord
{
    use ProtectsUserOwnership;
    
    protected static string $resource = PixelResource::class;
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $this->protectUserOwnership($data);
        return parent::handleRecordUpdate($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

}
