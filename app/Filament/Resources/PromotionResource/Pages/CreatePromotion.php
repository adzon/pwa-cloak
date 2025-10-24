<?php

namespace App\Filament\Resources\PromotionResource\Pages;

use App\Filament\Resources\PromotionResource;
use App\Models\Domain;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePromotion extends CreateRecord
{
    protected static string $resource = PromotionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        return $data;
    }


    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        // 从表单数据中获取域名选择
        $formData = $this->form->getState();

        // 设置平台域名关联
        if (!empty($formData['platform_domain_id']) && is_array($formData['platform_domain_id'])) {
            Domain::query()
                ->whereIn('id', $formData['platform_domain_id'])
                ->where('user_id', 0)
                ->where('is_save', false)
                ->update(['promotion_id' => $record->id]);
        }

        // 设置托管域名关联
        if (!empty($formData['hosting_domain_id']) && is_array($formData['hosting_domain_id'])) {
            Domain::query()
                ->whereIn('id', $formData['hosting_domain_id'])
                ->where('user_id', Auth::id())
                ->where('is_save', true)
                ->update(['promotion_id' => $record->id]);
        }
    }
}
