<?php

namespace App\Filament\Resources\RecitationPlanResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $title = 'جلسات التلاوة';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('date')
            ->defaultSort('date')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('التاريخ')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('اليوم'),

                Tables\Columns\TextColumn::make('prayer_name')
                    ->label('الصلاة'),

                Tables\Columns\BadgeColumn::make('execution_status')
                    ->label('حالة التنفيذ'),

                Tables\Columns\TextColumn::make('segments_count')
                    ->counts('segments')
                    ->label('عدد المقاطع'),
            ])
            ->paginated([10, 25, 50]);
    }
}
