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
use Filament\Notifications\Notification;

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
                // 🚀 ДОБАВЛЯЕМ ПОЛЕ ДЛЯ ГОЛОСА
                Forms\Components\Textarea::make('voice_system_prompt')
                    ->label('Промпт для ГОЛОСА (микрофон стримера)')
                    ->rows(4)
                    ->columnSpanFull()
                    ->default('Ты личный голосовой ассистент стримера. Отвечай очень коротко, в одно-два предложения. Никаких смайликов, спецсимволов и списков, так как текст будет озвучиваться роботом.'),
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
                
                // 🚀 КНОПКА ЗАПУСКА
                Tables\Actions\Action::make('start')
                    ->label('Запустить')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation() // Спрашиваем "Вы уверены?"
                    ->modalHeading('Запуск бота')
                    ->modalDescription('Бот подключится к каналу и начнет слушать чат.')
                    ->visible(fn ($record) => !$record->is_active) // Показываем, только если бот выключен
                    ->action(function ($record) {
                        // 1. Защита от двойного запуска: проверяем, не висит ли уже такой процесс в памяти
                        exec("ps aux | grep 'artisan twitch:listen {$record->id}' | grep -v grep", $output);
                        if (!empty($output)) {
                            Notification::make()->title('Бот уже запущен в фоне!')->warning()->send();
                            $record->update(['is_active' => true]);
                            return;
                        }

                        // 2. Включаем бота в базе
                        $record->update(['is_active' => true]);

                        // 3. Формируем консольную команду (как nohup на сервере, только из PHP)
                        $artisan = base_path('artisan');
                        $logPath = storage_path("logs/bot_{$record->id}.log");
                        $command = "nohup php {$artisan} twitch:listen {$record->id} > {$logPath} 2>&1 &";
                        
                        // 4. Запускаем!
                        exec($command);
                        
                        Notification::make()->title('Бот успешно запущен в фоне!')->success()->send();
                    }),
                    
                // 🛑 КНОПКА ОСТАНОВКИ
                Tables\Actions\Action::make('stop')
                    ->label('Остановить')
                    ->icon('heroicon-o-stop')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Остановка бота')
                    ->modalDescription('Бот отключится от чата. Это займет пару секунд.')
                    ->visible(fn ($record) => $record->is_active) // Показываем, только если бот включен
                    ->action(function ($record) {
                        // Просто выключаем тумблер в БД. 
                        // Цикл while(true) в TwitchListen.php сам увидит это и завершится!
                        $record->update(['is_active' => false]);
                        
                        Notification::make()->title('Отправлен сигнал на остановку')->success()->send();
                    }),
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
