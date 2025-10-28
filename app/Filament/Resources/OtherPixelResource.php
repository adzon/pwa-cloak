<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OtherPixelResource\Pages;
use App\Filament\Resources\OtherPixelResource\RelationManagers;
use App\Models\OtherPixel;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OtherPixelResource extends Resource
{
    protected static ?string $model = OtherPixel::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = '推广';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = '归因平台';
    protected static ?string $slug = 'attribution';
    protected static ?string $pluralModelLabel = '归因平台';
    protected static ?string $modelLabel = '应用';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('app_name')
                    ->label('应用名称')
                    ->maxLength(80)
                    ->required(),
                Forms\Components\TextInput::make('app_code')
                    ->label('应用识别码')
                    ->maxLength(80)
                    ->required(),
                Forms\Components\TextInput::make('api_code')
                    ->label('api识别码')
                    ->maxLength(80)
                    ->required(),
                Forms\Components\TextInput::make('access_code')
                    ->label('访问时间识别码')
                    ->maxLength(50)
                    ->required(),
                Forms\Components\TextInput::make('install_code')
                    ->label('安装事件识别码')
                    ->maxLength(50)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('app_name')
                    ->label('应用名称')
                    ->sortable(),
                Tables\Columns\TextColumn::make('app_code')
                    ->label('应用识别码')
                    ->sortable(),
                Tables\Columns\TextColumn::make('api_code')
                    ->label('api识别码')
                    ->sortable(),
                Tables\Columns\TextColumn::make('access_code')
                    ->label('访问时间识别码')
                    ->sortable(),
                Tables\Columns\TextColumn::make('install_code')
                    ->label('安装事件识别码')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('app_name')
                    ->form([
                        TextInput::make('app_name')
                            ->label('应用名称')
                            ->inlineLabel()
                            ->placeholder('请输入应用名称'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['app_name'],
                            fn(Builder $query, $app_name): Builder => $query->where('app_name', 'like', "%{$app_name}%")
                        );
                    }),

            ])
            ->filtersFormWidth('full')
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListOtherPixels::route('/'),
            'create' => Pages\CreateOtherPixel::route('/create'),
            'edit' => Pages\EditOtherPixel::route('/{record}/edit'),
        ];
    }
}
