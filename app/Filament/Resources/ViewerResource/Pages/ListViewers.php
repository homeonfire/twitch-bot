<?php

namespace App\Filament\Resources\ViewerResource\Pages;

use App\Filament\Resources\ViewerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListViewers extends ListRecords
{
    protected static string $resource = ViewerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
