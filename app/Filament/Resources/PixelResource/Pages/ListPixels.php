<?php

namespace App\Filament\Resources\PixelResource\Pages;

use App\Filament\Resources\PixelResource;
use App\Filament\Resources\PixelResource\Enum\ChannelEnum;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Archilex\AdvancedTables\AdvancedTables;
use Archilex\AdvancedTables\Components\PresetView;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ListPixels extends ListRecords
{
    use AdvancedTables;

    protected static string $resource = PixelResource::class;

    // 监听 activePresetView 属性的变化
    public function updatedActivePresetView($value): void
    {
        // 当预设视图切换时，这个方法会被自动调用
        // Livewire 会自动重新渲染组件，包括 header actions
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make('像素')
                ->label(fn(): string => match($this->activePresetView ?? 'facebook') {
                    'facebook' => '添加 Facebook 广告像素',
                    'tiktok' => '添加 TikTok 广告像素',
                    'google' => '添加 Google 广告像素',
                    default => '添加广告像素',
                })
                ->button()
                ->url(fn(): string => static::$resource::getUrl('create', [
                    'preset' => $this->activePresetView ?? 'facebook'
                ])),
        ];
    }

    public function getPresetViews(): array
    {
        return [
            'facebook' => PresetView::make('Facebook')
                ->favorite()
                ->modifyQueryUsing(fn($query) => $query->where('channel', ChannelEnum::FACEBOOK_ID)->where('user_id', Auth::id()))
                ->default(),
            'tiktok' => PresetView::make('TikTok')
                ->favorite()
                ->modifyQueryUsing(fn($query) => $query->where('channel', ChannelEnum::TIKTOK_ID)->where('user_id', Auth::id())),
            'google' => PresetView::make('Google')
                ->favorite()
                ->modifyQueryUsing(fn($query) => $query->where('channel', ChannelEnum::GOOGLE_ID)->where('user_id', Auth::id())),
        ];
    }
}
