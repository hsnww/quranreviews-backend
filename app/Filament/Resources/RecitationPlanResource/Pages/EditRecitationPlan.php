<?php

namespace App\Filament\Resources\RecitationPlanResource\Pages;

use App\Filament\Resources\RecitationPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecitationPlan extends EditRecord
{
    protected static string $resource = RecitationPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
