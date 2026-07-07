<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecitationPlanResource\Pages;
use App\Filament\Resources\RecitationPlanResource\RelationManagers\SessionsRelationManager;
use App\Models\RecitationPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RecitationPlanResource extends Resource
{
    protected static ?string $model = RecitationPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'الخطط';
    protected static ?int $navigationSort = 3;
    protected static ?string $label = 'خطة قراءة إمام';
    protected static ?string $pluralLabel = 'خطط قراءة الإمام';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('عنوان الخطة')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'نشطة',
                        'completed' => 'مكتملة',
                        'archived' => 'مؤرشفة',
                    ]),

                Forms\Components\DatePicker::make('start_date')
                    ->label('تاريخ البداية'),

                Forms\Components\DatePicker::make('end_date')
                    ->label('تاريخ النهاية'),

                Forms\Components\Placeholder::make('user_name')
                    ->label('المستخدم')
                    ->content(fn (?RecitationPlan $record): string => $record?->user?->name ?? '—'),
            ]);
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
                Tables\Actions\EditAction::make(),
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
            'edit' => Pages\EditRecitationPlan::route('/{record}/edit'),
        ];
    }
}
