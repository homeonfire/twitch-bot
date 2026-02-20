<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TwitchBotResource\Pages;
use App\Filament\Resources\TwitchBotResource\RelationManagers;
use App\Models\TwitchBot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TwitchBotResource extends Resource
{
    protected static ?string $model = TwitchBot::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('bot_username')
                    ->label('Никнейм бота')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('bot_oauth')
                    ->label('OAuth Токен (oauth:...)')
                    ->password() // Скрываем звездочками
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('twitch_channel')
                    ->label('Канал стримера (без #)')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label('Бот активен')
                    ->default(true),
                Forms\Components\Textarea::make('system_prompt')
                    ->label('Системный промпт для DeepSeek')
                    ->rows(5)
                    ->columnSpanFull()
                    ->default('Ты помощник на Twitch. Отвечай коротко и с юмором.'),
                Forms\Components\TextInput::make('wake_word')
                    ->label('Кодовое слово (для voice.html)')
                    ->required()
                    ->default('бот')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bot_username')
                    ->label('Бот')
                    ->searchable(),
                Tables\Columns\TextColumn::make('twitch_channel')
                    ->label('Канал')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
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
            'index' => Pages\ListTwitchBots::route('/'),
            'create' => Pages\CreateTwitchBot::route('/create'),
            'edit' => Pages\EditTwitchBot::route('/{record}/edit'),
        ];
    }
}
