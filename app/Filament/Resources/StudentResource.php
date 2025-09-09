<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'إدارة الطلاب';
    protected static ?string $label = 'الطالب';
    protected static ?string $pluralLabel = 'الطلاب';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('اسم الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('رقم الجوال'),

                TextColumn::make('dob')
                    ->label('تاريخ الميلاد')
                    ->date('d M Y'),

                TextColumn::make('institution')
                    ->label('الجهة التعليمية'),

                TextColumn::make('memorized_parts')
                    ->label('الأجزاء المحفوظة')
                    ->sortable(),

                TextColumn::make('preferred_review_days')
                    ->label('أيام دورة المراجعة'),

                TextColumn::make('review_quarters_per_day')
                    ->label('مقدار المراجعة اليومي'),

                BadgeColumn::make('new_memorization_mode')
                    ->label('نمط الحفظ الجديد')
                    ->colors([
                        'primary' => 'quarter',
                        'warning' => 'half-quarter',
                        'danger' => 'quarter-quarter',
                    ]),
            ])
            ->filters([
                //
            ])
            ->actions([
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
