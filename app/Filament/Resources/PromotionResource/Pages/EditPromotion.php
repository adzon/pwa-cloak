<?php

namespace App\Filament\Resources\PromotionResource\Pages;

use App\Filament\Resources\PromotionResource;
use App\Filament\Traits\ProtectsUserOwnership;
use App\Models\Domain;
use App\Models\OtherPixel;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditPromotion extends EditRecord
{
    use ProtectsUserOwnership;
    
    protected static string $resource = PromotionResource::class;
    
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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        // 获取关联的域名信息用于回显
        $associatedDomains = Domain::query()
            ->where('promotion_id', $record->id)
            ->get();

        $platformDomainIds = [];
        $hostingDomainIds = [];

        foreach ($associatedDomains as $domain) {
            if ($domain->user_id == 0 && !$domain->is_save) {
                // 平台域名
                $platformDomainIds[] = $domain->id;
            } elseif ($domain->user_id == Auth::id() && $domain->is_save) {
                // 托管域名
                $hostingDomainIds[] = $domain->id;
            }
        }

        \Log::debug(json_encode($hostingDomainIds, JSON_THROW_ON_ERROR));
        if (!empty($platformDomainIds)) {
            $data['platform_domain_id'] = $platformDomainIds;  // 改为单数形式
        }

        if (!empty($hostingDomainIds)) {
            $data['hosting_domain_id'] = $hostingDomainIds;   // 改为单数形式
        }

        // 投放地区
        if (!empty($record->region_ids)) {
            if (is_string($record->region_ids)) {
                $data['region_ids'] = explode(',', $record->region_ids);
            } else {
                $data['region_ids'] = $record->region_ids;
            }
        }

        // 处理第三方归因平台回显
        if (!empty($record->other_pixel_id)) {
            $otherPixel = OtherPixel::find($record->other_pixel_id);
            if ($otherPixel) {
                $data['attribution_platform'] = $otherPixel->channel;
                $data['other_pixel_id'] = $record->other_pixel_id;
            }
        }

        return $data;
    }

    public function hasHostingDomains(): bool
    {
        $record = $this->getRecord();
        return $record && $record->hostingDomains()->exists();
    }

    public function hasPlatformDomains(): bool
    {
        $record = $this->getRecord();
        return $record && $record->platformDomains()->exists();
    }


}
