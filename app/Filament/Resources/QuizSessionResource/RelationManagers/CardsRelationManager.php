<?php

namespace App\Filament\Resources\QuizSessionResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CardsRelationManager extends RelationManager
{
    protected static string $relationship = 'cards';

    protected static ?string $title = 'بطاقات الاختبار';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_index')
            ->defaultSort('order_index')
            ->columns([
                Tables\Columns\TextColumn::make('order_index')
                    ->label('الترتيب')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sora_number')
                    ->label('رقم السورة'),

                Tables\Columns\TextColumn::make('jozo')
                    ->label('الجزء'),

                Tables\Columns\TextColumn::make('verse_ids')
                    ->label('عدد الآيات')
                    ->getStateUsing(fn ($record): int => is_array($record->verse_ids) ? count($record->verse_ids) : 0),

                Tables\Columns\TextColumn::make('mistake_count')
                    ->label('عدد الأخطاء')
                    ->badge()
                    ->color(fn ($state): string => $state > 0 ? 'danger' : 'success'),
            ])
            ->paginated([10, 25, 50]);
    }
}
