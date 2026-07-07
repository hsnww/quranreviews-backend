<?php

namespace App\Filament\Resources\MemorizationPlanResource\Pages;

use App\Filament\Resources\MemorizationPlanResource;
use Filament\Resources\Pages\ListRecords;

class ListMemorizationPlans extends ListRecords
{
    protected static string $resource = MemorizationPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
