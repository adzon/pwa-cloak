<?php

namespace App\Filament\Resources\CommentResource\Pages;

use App\Filament\Resources\CommentResource;
use App\Filament\Traits\ProtectsUserOwnership;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditComment extends EditRecord
{
    use ProtectsUserOwnership;
    
    protected static string $resource = CommentResource::class;
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $this->protectUserOwnership($data);
        return parent::handleRecordUpdate($record, $data);
    }
}
