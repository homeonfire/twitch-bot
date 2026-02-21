<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ViewerResource\Pages;
use App\Filament\Resources\ViewerResource\RelationManagers;
use App\Models\Viewer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ViewerResource extends Resource
{
    protected static ?string $model = Viewer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('channel')
                    ->label('Канал стримера')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('username')
                    ->label('Ник зрителя')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('messages_count')
                    ->label('Количество сообщений')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('channel')
                    ->label('Канал')
                    ->searchable()
                    ->sortable()
                    ->badge() // Красивая плашка вокруг названия канала
                    ->color('info'),
                Tables\Columns\TextColumn::make('username')
                    ->label('Зритель')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('messages_count')
                    ->label('Сообщений')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Последний актив')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('messages_count', 'desc') // По умолчанию показываем Топ-чаттеран!
            ->filters([
                // Фильтр-выпадашка для быстрого выбора канала
                Tables\Filters\SelectFilter::make('channel')
                    ->label('Фильтр по каналу')
                    ->options(fn () => \App\Models\Viewer::query()
                        ->distinct()
                        ->pluck('channel', 'channel')
                        ->toArray()
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(), // Можно будет удалять спамеров из статы
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // Запрещаем создавать зрителей руками через админку (только бот может их добавлять)
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [
            // 🚀 ПОДКЛЮЧИЛИ ТАБЛИЦУ СООБЩЕНИЙ
            RelationManagers\ChatMessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListViewers::route('/'),
            'create' => Pages\CreateViewer::route('/create'),
            'edit' => Pages\EditViewer::route('/{record}/edit'),
        ];
    }
}
