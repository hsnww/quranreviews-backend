<?php

namespace App\Filament\Resources\StudentMemorizedPartResource\Pages;

use App\Filament\Resources\StudentMemorizedPartResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentMemorizedParts extends ListRecords
{
    protected static string $resource = StudentMemorizedPartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
