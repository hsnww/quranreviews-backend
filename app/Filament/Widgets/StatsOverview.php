<?php

namespace App\Filament\Widgets;

use App\Models\QuizSession;
use App\Models\RecitationPlan;
use App\Models\Student;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = -3;

    protected function getStats(): array
    {
        $quizzesThisWeek = QuizSession::where('created_at', '>=', now()->startOfWeek())->count();

        $avgScore = QuizSession::where('status', QuizSession::STATUS_COMPLETED)->avg('score');

        return [
            Stat::make('إجمالي المستخدمين', User::count())
                ->description('عدد الحسابات المسجّلة')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('الطلاب', Student::count())
                ->description('الطلاب المرتبطون بحسابات')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('اختبارات هذا الأسبوع', $quizzesThisWeek)
                ->description('جلسات الاختبار منذ بداية الأسبوع')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('warning'),

            Stat::make('متوسط الدرجات', $avgScore !== null ? round($avgScore, 1) : '—')
                ->description('لجلسات الاختبار المكتملة')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('خطط التلاوة', RecitationPlan::count())
                ->description('إجمالي خطط التلاوة')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('gray'),
        ];
    }
}
