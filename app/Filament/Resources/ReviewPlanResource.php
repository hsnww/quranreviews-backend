<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewPlanResource\Pages;
use App\Models\ReviewPlan;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewPlanResource extends Resource
{
    protected static ?string $model = ReviewPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'الخطط';
    protected static ?int $navigationSort = 2;
    protected static ?string $label = 'خطة مراجعة';
    protected static ?string $pluralLabel = 'خطط المراجعة';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'review');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.user.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('study_plan')
                    ->label('الخطة')
                    ->getStateUsing(fn (ReviewPlan $record): string => $record->studyPlan
                        ? $record->studyPlan->displayTitle() . ($record->studyPlan->start_date ? ' (' . $record->studyPlan->start_date->format('Y-m-d') . ')' : '')
                        : '—'),

                Tables\Columns\TextColumn::make('day_number')
                    ->label('رقم اليوم')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('النوع'),

                Tables\Columns\TextColumn::make('from_range')
                    ->label('من')
                    ->getStateUsing(fn (ReviewPlan $record): string => $record->fromVerse
                        ? "سورة {$record->fromVerse->sora} - آية {$record->fromVerse->ayah}"
                        : '—'),

                Tables\Columns\TextColumn::make('to_range')
                    ->label('إلى')
                    ->getStateUsing(fn (ReviewPlan $record): string => $record->toVerse
                        ? "سورة {$record->toVerse->sora} - آية {$record->toVerse->ayah}"
                        : '—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('day_number')
            ->filters([
                Tables\Filters\SelectFilter::make('student_id')
                    ->label('الطالب')
                    ->relationship('student.user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviewPlans::route('/'),
        ];
    }
}
