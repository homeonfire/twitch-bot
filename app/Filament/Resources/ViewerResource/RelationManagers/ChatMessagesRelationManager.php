<?php

namespace App\Filament\Resources\ViewerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChatMessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'chatMessages';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('message')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->columns([
                Tables\Columns\TextColumn::make('message')
                    ->label('Текст сообщения')
                    ->wrap() // Разрешаем перенос текста на новую строку, если оно длинное
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата и время')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc') // Показываем самые свежие сверху
            ->filters([
                //
            ])
            ->headerActions([
                // Убрали кнопку Create
            ])
            ->actions([
                // Убрали кнопки Edit и Delete
            ])
            ->bulkActions([
                // Убрали массовое удаление
            ]);
    }
}
