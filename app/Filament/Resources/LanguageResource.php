<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LanguageResource\Pages;
use App\Models\Language;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;

class LanguageResource extends Resource
{
    protected static ?string $model = Language::class;
    protected static ?string $navigationIcon = 'heroicon-o-language';
    protected static ?string $navigationLabel = '语言';
    protected static ?string $pluralModelLabel = '语言';
    protected static ?string $navigationGroup = '系统管理';

    /**
     * 限制只有超级管理员可以访问语言管理
     */
    public static function canViewAny(): bool
    {
        return \isSuperAdmin();
    }

    /**
     * 限制只有超级管理员可以创建语言
     */
    public static function canCreate(): bool
    {
        return \isSuperAdmin();
    }

    /**
     * 限制只有超级管理员可以查看语言
     */
    public static function canView($record): bool
    {
        return \isSuperAdmin();
    }

    /**
     * 限制只有超级管理员可以编辑语言
     */
    public static function canEdit($record): bool
    {
        return \isSuperAdmin();
    }

    /**
     * 限制只有超级管理员可以删除语言
     */
    public static function canDelete($record): bool
    {
        return \isSuperAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label('名称')->required()->maxLength(50),
            TextInput::make('en_name')->label('英文名称')->required()->maxLength(50),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label('ID'),
            TextColumn::make('name')->label('名称'),
            TextColumn::make('en_name')->label('英文名称'),
        ])->actions([
            EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])->headerActions([
            Tables\Actions\CreateAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLanguages::route('/'),
            'create' => Pages\CreateLanguage::route('/create'),
            'edit' => Pages\EditLanguage::route('/{record}/edit'),
        ];
    }
}
