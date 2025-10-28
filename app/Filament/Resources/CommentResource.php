<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages;
use App\Models\Comment;
use App\Models\Language;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = '评论库';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('nickname')->label('昵称')->required(),
            Textarea::make('content')->label('内容')->required()->rows(4),
            Select::make('language_id')
                ->label('语言')
                ->options(Language::pluck('name', 'id'))
                ->preload()
                ->searchable()
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label('ID'),
            TextColumn::make('nickname')->label('作者')->searchable(),
            TextColumn::make('content')->label('内容')->limit(60),
            TextColumn::make('language.name')->label('语言'),
            TextColumn::make('created_at')->label('创建时间')->dateTime(),
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
            'index' => Pages\ListComments::route('/'),
            'create' => Pages\CreateComment::route('/create'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }
}
