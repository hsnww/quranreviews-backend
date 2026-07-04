<?php

namespace App\Filament\Resources\QuizSessionResource\Pages;

use App\Filament\Resources\QuizSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuizSessions extends ListRecords
{
    protected static string $resource = QuizSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
