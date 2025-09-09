<?php

namespace App\Filament\Resources\SurahResource\Pages;

use App\Filament\Resources\SurahResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSurahs extends ListRecords
{
    protected static string $resource = SurahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
