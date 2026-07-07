<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemorizationPlanResource\Pages;
use App\Models\ReviewPlan;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MemorizationPlanResource extends Resource
{
    protected static ?string $model = ReviewPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-bookmark-square';
    protected static ?string $navigationGroup = 'الخطط';
    protected static ?int $navigationSort = 1;
    protected static ?string $label = 'خطة حفظ';
    protected static ?string $pluralLabel = 'خطط الحفظ';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'new');
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

                Tables\Columns\TextColumn::make('day_number')
                    ->label('رقم اليوم')
                    ->sortable(),

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
            'index' => Pages\ListMemorizationPlans::route('/'),
        ];
    }
}
