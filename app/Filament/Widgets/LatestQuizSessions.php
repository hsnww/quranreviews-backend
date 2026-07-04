<?php

namespace App\Filament\Widgets;

use App\Models\QuizSession;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestQuizSessions extends BaseWidget
{
    protected static ?string $heading = 'أحدث جلسات الاختبار';

    protected static ?int $sort = -1;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                QuizSession::query()->latest()->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم'),

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
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('total_errors')
                    ->label('الأخطاء'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('d M Y - H:i'),
            ])
            ->paginated(false);
    }
}
