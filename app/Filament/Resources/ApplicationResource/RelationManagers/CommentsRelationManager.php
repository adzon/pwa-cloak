<?php

namespace App\Filament\Resources\ApplicationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';
    protected static ?string $recordTitleAttribute = 'content';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nickname')->label('昵称'),
                TextColumn::make('content')->label('内容')->limit(50),
                TextColumn::make('language.name')->label('语言'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
