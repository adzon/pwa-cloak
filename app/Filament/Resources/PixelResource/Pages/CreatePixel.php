<?php

namespace App\Filament\Resources\PixelResource\Pages;

use App\Filament\Resources\PixelResource;
use App\Filament\Resources\PixelResource\Enum\ChannelEnum;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class CreatePixel extends CreateRecord
{
    protected static string $resource = PixelResource::class;

    public function mount(): void
    {
        parent::mount();
        
        // 从 URL 参数获取预设视图
        $preset = request()->query('preset', 'facebook');
        
        // 根据预设视图自动填充 channel
        $channel = match($preset) {
            'facebook' => ChannelEnum::FACEBOOK_ID,
            'tiktok' => ChannelEnum::TIKTOK_ID,
            'google' => ChannelEnum::GOOGLE_ID,
            default => ChannelEnum::FACEBOOK_ID,
        };
        
        // 自动填充表单数据
        $this->form->fill([
            'channel' => $channel,
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        // 创建后返回到对应的预设视图
        $preset = request()->query('preset', 'facebook');
        return static::$resource::getUrl('index', ['preset' => $preset]);
    }
}
