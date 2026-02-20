<?php

namespace App\Filament\Resources\TwitchBotResource\Pages;

use App\Filament\Resources\TwitchBotResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTwitchBot extends EditRecord
{
    protected static string $resource = TwitchBotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
