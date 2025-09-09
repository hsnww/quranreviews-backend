<?php

namespace App\Filament\Resources\StudentMemorizedPartResource\Pages;

use App\Filament\Resources\StudentMemorizedPartResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentMemorizedPart extends EditRecord
{
    protected static string $resource = StudentMemorizedPartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
