<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecitationPlanResource\Pages;
use App\Filament\Resources\RecitationPlanResource\RelationManagers\SessionsRelationManager;
use App\Models\RecitationPlan;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RecitationPlanResource extends Resource
{
    protected static ?string $model = RecitationPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'النشاط والتقدّم';
    protected static ?string $label = 'خطة تلاوة';
    protected static ?string $pluralLabel = 'خطط التلاوة';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('period_type')
                    ->label('نوع الفترة'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة'),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('البداية')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('النهاية')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sessions_count')
                    ->counts('sessions')
                    ->label('عدد الجلسات')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('المستخدم')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SessionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecitationPlans::route('/'),
            'view' => Pages\ViewRecitationPlan::route('/{record}'),
        ];
    }
}
