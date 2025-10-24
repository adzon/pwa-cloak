<?php

namespace App\Filament\Resources\DomainResource\Pages;

use App\Filament\Resources\DomainResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDomain extends EditRecord
{
    protected static string $resource = DomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // 获取当前记录
        $record = $this->getRecord();


        $data['hosting_name_servers'] = $record->hosting_name_servers ?? [];
        $data['hosting_id'] = $record->hosting_id ?? null;
        $data['status'] = $record->status ?? 0;
        $data['domain'] = $record->domain ?? null;
        return $data;
    }

    public function checkNsRecords(callable $get, callable $set): void
    {
        DomainResource::checkNsRecords($get, $set, $this->getRecord());
    }
}
