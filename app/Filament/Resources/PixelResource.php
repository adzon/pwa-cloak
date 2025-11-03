<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PixelResource\Enum\ChannelEnum;
use App\Filament\Resources\PixelResource\Pages;
use App\Filament\Resources\PixelResource\RelationManagers;
use App\Models\Pixel;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PixelResource extends Resource
{
    protected static ?string $model = Pixel::class;

    protected static ?string $navigationIcon = 'heroicon-o-eye';
    protected static ?string $navigationGroup = '推广';
    protected static ?string $navigationLabel = '像素配置';
    protected static ?int $navigationSort = 2;
    protected static ?string $pluralModelLabel = '像素';
    protected static ?string $modelLabel = '像素';
    protected static ?string $slug = 'pixels';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('channel')
                    ->label('广告渠道')
                    ->options(ChannelEnum::CHANNEL_LIST)
                    ->required()
                    ->disabled(fn($livewire) => $livewire instanceof Pages\CreatePixel)
                    ->dehydrated()
                    ->helperText(fn($livewire) => $livewire instanceof Pages\CreatePixel
                        ? '渠道已自动选择'
                        : null
                    ),

                TextInput::make('pixel_code')
                    ->label('像素ID')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->placeholder('请输入像素ID'),

                TextInput::make('pixel_name')
                    ->label('像素名称')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('请输入像素名称，便于识别'),

                // TODO 验证像素配置token
                TextInput::make('access_token')
                    ->label('Access Token')
                    ->required()
                    ->maxLength(255)
                    ->password()
                    ->placeholder('请输入访问令牌'),

                TextInput::make('test_event_code')
                    ->label('测试事件ID')
                    ->maxLength(255)
                    ->placeholder('选填，用于测试事件'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('pixel_code')
                    ->label('像素ID')
                    ->sortable(),

                TextColumn::make('pixel_name')
                    ->label('像素名称')
                    ->sortable(),

                TextColumn::make('access_token')
                    ->label('AccessToken')
                    ->sortable(),
                TextColumn::make('test_event_code')
                    ->label('Test_Event_Code')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->formatStateUsing(fn($state) => $state == 0 ? '无API上报' : '正常')
                    ->color(fn($state) => $state == 0 ? 'warning' : 'success')
                    ->sortable(),
            ])
            ->filters([
                // 像素ID筛选
                Filter::make('pixel_code')
                    ->form([
                        TextInput::make('pixel_code')
                            ->label('像素ID')
                            ->placeholder('请输入像素ID'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['pixel_code'] ?? null,
                            fn(Builder $query, $pixel_code): Builder => $query->where('pixel_code', 'like', "%{$pixel_code}%")
                        );
                    })
                    ->indicateUsing(function (array $data): array {
                        return ($data['pixel_code'] ?? null) ? ['像素ID: ' . $data['pixel_code']] : [];
                    })
                    ->columnSpan(1),

                // 像素名称筛选
                Filter::make('pixel_name')
                    ->form([
                        TextInput::make('pixel_name')
                            ->label('像素名称')
                            ->placeholder('请输入像素名称'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['pixel_name'] ?? null,
                            fn(Builder $query, $pixel_name): Builder => $query->where('pixel_name', 'like', "%{$pixel_name}%")
                        );
                    })
                    ->indicateUsing(function (array $data): array {
                        return ($data['pixel_name'] ?? null) ? ['像素名称: ' . $data['pixel_name']] : [];
                    })
                    ->columnSpan(1),

                // 显示隐藏筛选
                Filter::make('is_delete')
                    ->form([
                        Forms\Components\Toggle::make('is_delete')
                            ->label('显示全部(含隐藏)')
                            ->inline()
                            ->default(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['is_delete']) || !$data['is_delete']) {
                            return $query->where('is_delete', false);
                        }
                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        return ($data['is_delete'] ?? false) ? ['包含隐藏项目'] : [];
                    })
                    ->columnSpan(1),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->filtersFormWidth('full')
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleVisibility')
                    ->label(fn($record) => $record->is_delete ? '显示' : '隐藏')
                    ->icon(fn($record) => $record->is_delete ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                    ->color(fn($record) => $record->is_delete ? 'success' : 'warning')
                    ->action(function ($record) {
                        $record->is_delete = !$record->is_delete;
                        $record->save();
                        \Filament\Notifications\Notification::make()
                            ->title('操作成功')
                            ->body($record->is_delete ? '项目已隐藏' : '项目已显示')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn($record) => $record->is_delete ? '确认显示该像素？' : '确认隐藏该像素？')
                    ->modalDescription('注意！隐藏后的像素将无法新建推广链接，但是不影响已创建的推广链接正常使用。')
                    ->modalSubmitActionLabel(fn($record) => $record->is_delete ? '显示' : '隐藏'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPixels::route('/'),
            'create' => Pages\CreatePixel::route('/create'),
            'edit' => Pages\EditPixel::route('/{record}/edit'),
        ];
    }

    protected static function getCurrentChannelFromContext(): int
    {
        // 从路由参数获取 channel
        $currentView = request()->query('currentPresetView');

        // 根据预设视图返回对应的 channel ID
        switch ($currentView) {
            case 'facebook':
                return ChannelEnum::FACEBOOK_ID;
            case 'tiktok':
                return ChannelEnum::TIKTOK_ID;
            case 'google':
                return ChannelEnum::GOOGLE_ID;
            default:
                // 默认返回 Facebook
                return ChannelEnum::FACEBOOK_ID;
        }
    }
}
