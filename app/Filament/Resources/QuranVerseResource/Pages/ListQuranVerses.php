<?php

namespace App\Filament\Resources\QuranVerseResource\Pages;

use App\Filament\Resources\QuranVerseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuranVerses extends ListRecords
{
    protected static string $resource = QuranVerseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
