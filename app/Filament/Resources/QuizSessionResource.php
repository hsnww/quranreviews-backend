<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuizSessionResource\Pages;
use App\Filament\Resources\QuizSessionResource\RelationManagers\CardsRelationManager;
use App\Models\QuizSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuizSessionResource extends Resource
{
    protected static ?string $model = QuizSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'النشاط والتقدّم';
    protected static ?string $label = 'جلسة اختبار';
    protected static ?string $pluralLabel = 'جلسات الاختبار';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('المستخدم')
                    ->relationship('user', 'name')
                    ->disabled(),

                Forms\Components\TextInput::make('status')
                    ->label('الحالة')
                    ->disabled(),

                Forms\Components\TextInput::make('score')
                    ->label('الدرجة')
                    ->disabled(),

                Forms\Components\TextInput::make('total_errors')
                    ->label('إجمالي الأخطاء')
                    ->disabled(),

                Forms\Components\TextInput::make('requested_card_count')
                    ->label('عدد البطاقات المطلوب')
                    ->disabled(),

                Forms\Components\TextInput::make('actual_card_count')
                    ->label('عدد البطاقات الفعلي')
                    ->disabled(),

                Forms\Components\TextInput::make('verses_per_card')
                    ->label('آيات لكل بطاقة')
                    ->disabled(),

                Forms\Components\DateTimePicker::make('completed_at')
                    ->label('وقت الإكمال')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        QuizSession::STATUS_COMPLETED => 'مكتملة',
                        QuizSession::STATUS_IN_PROGRESS => 'جارية',
                        default => $state,
                    })
                    ->colors([
                        'success' => QuizSession::STATUS_COMPLETED,
                        'warning' => QuizSession::STATUS_IN_PROGRESS,
                    ]),

                Tables\Columns\TextColumn::make('score')
                    ->label('الدرجة')
                    ->numeric(decimalPlaces: 1)
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 90 => 'success',
                        $state >= 70 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_errors')
                    ->label('الأخطاء')
                    ->sortable(),

                Tables\Columns\TextColumn::make('actual_card_count')
                    ->label('البطاقات')
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('وقت الإكمال')
                    ->dateTime('d M Y - H:i')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        QuizSession::STATUS_COMPLETED => 'مكتملة',
                        QuizSession::STATUS_IN_PROGRESS => 'جارية',
                    ]),
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
            CardsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuizSessions::route('/'),
            'view' => Pages\ViewQuizSession::route('/{record}'),
        ];
    }
}
