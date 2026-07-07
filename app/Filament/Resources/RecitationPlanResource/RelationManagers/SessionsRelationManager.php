<?php

namespace App\Filament\Resources\RecitationPlanResource\RelationManagers;

use App\Models\RecitationSession;
use App\Models\Surah;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $title = 'جلسات التلاوة';

    protected static ?Collection $surahNames = null;

    protected static function surahName($id): string
    {
        if (static::$surahNames === null) {
            static::$surahNames = Surah::pluck('name', 'id');
        }

        return static::$surahNames[$id] ?? "سورة {$id}";
    }

    protected static function formatRakaa(RecitationSession $session, int $rakaaNumber): string
    {
        $segments = $session->segments
            ->where('rakaa_number', $rakaaNumber)
            ->sortBy('order_index');

        if ($segments->isEmpty()) {
            return '—';
        }

        $first = $segments->first();
        $last = $segments->last();

        return static::surahName($first->start_surah) . " : آية {$first->start_ayah}"
            . " ← " . static::surahName($last->end_surah) . " : آية {$last->end_ayah}";
    }

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('date')
            ->defaultSort('date')
            ->modifyQueryUsing(fn ($query) => $query->with('segments'))
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('التاريخ')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('اليوم'),

                Tables\Columns\TextColumn::make('prayer_name')
                    ->label('الصلاة'),

                Tables\Columns\TextColumn::make('rakaa_1')
                    ->label('الركعة الأولى')
                    ->getStateUsing(fn (RecitationSession $record): string => static::formatRakaa($record, 1))
                    ->wrap(),

                Tables\Columns\TextColumn::make('rakaa_2')
                    ->label('الركعة الثانية')
                    ->getStateUsing(fn (RecitationSession $record): string => static::formatRakaa($record, 2))
                    ->wrap(),

                Tables\Columns\BadgeColumn::make('execution_status')
                    ->label('حالة التنفيذ'),
            ])
            ->paginated([10, 25, 50]);
    }
}
