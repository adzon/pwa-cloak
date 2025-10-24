<?php

namespace App\Filament\Resources\ApplicationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables\Table;

class LanguagesRelationManager extends RelationManager
{
    protected static string $relationship = 'languages';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $label = '语言';
    protected static ?string $pluralLabel = '语言';

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('id')
                ->label('语言')
                ->options(function () {
                    return \App\Models\Language::pluck('name', 'id')->toArray();
                })
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('name')->label('语言代码')->getStateUsing(fn ($record) => $record->code),
                TextColumn::make('name')->label('名称'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make(),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make(),
            ]);
    }
}
