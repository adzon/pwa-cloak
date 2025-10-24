<?php

namespace App\Filament\Resources\OtherPixelResource\Pages;

use App\Filament\Resources\OtherPixelResource;
use App\Filament\Resources\OtherPixelResource\Enum\PixelTypeEnum;
use Archilex\AdvancedTables\AdvancedTables;
use Archilex\AdvancedTables\Components\PresetView;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListOtherPixels extends ListRecords
{
    use AdvancedTables;

    protected static string $resource = OtherPixelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('添加应用')
                ->button(),
        ];
    }

    public function getPresetViews(): array
    {
        return [
            'facebook' => PresetView::make('Adjust')
                ->favorite()
                ->modifyQueryUsing(fn($query) => $query->where('channel', PixelTypeEnum::ADJUST_ID)->where('user_id', Auth::id()))
                ->default()
        ];
    }
}
