<?php

namespace App\Filament\Resources\ReviewPlanResource\Pages;

use App\Filament\Resources\ReviewPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReviewPlans extends ListRecords
{
    protected static string $resource = ReviewPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
