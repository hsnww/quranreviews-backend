<?php

namespace App\Filament\Resources\QuranVerseResource\Pages;

use App\Filament\Resources\QuranVerseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuranVerse extends EditRecord
{
    protected static string $resource = QuranVerseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
