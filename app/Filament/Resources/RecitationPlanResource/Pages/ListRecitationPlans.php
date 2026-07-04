<?php

namespace App\Filament\Resources\RecitationPlanResource\Pages;

use App\Filament\Resources\RecitationPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecitationPlans extends ListRecords
{
    protected static string $resource = RecitationPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
