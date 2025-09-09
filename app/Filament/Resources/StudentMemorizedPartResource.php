<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentMemorizedPartResource\Pages;
use App\Models\StudentMemorization;
use App\Models\QuranVerse;
use App\Models\Student;
use App\Models\Surah;
use Filament\Forms\Components\Textarea;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;


class StudentMemorizedPartResource extends Resource
{
    protected static ?string $model = StudentMemorization::class;

    protected static ?string $navigationGroup = 'إدارة الطلاب';
    protected static ?string $navigationLabel = 'المحفوظ';
    protected static ?string $modelLabel = 'محفوظ الطالب';
    protected static ?string $pluralModelLabel = 'محفوظ الطلاب';
    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    public static function form(Form $form): Form
    {
        return $form->schema([

            Select::make('student_id')
                ->label('الطالب')
                ->relationship('student.user', 'name')
                ->searchable()
                ->required(),

            Forms\Components\Grid::make(2)->schema([
                Select::make('from_surah')
                    ->label('من السورة')
                    ->options(Surah::orderBy('id')->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->reactive(),

                Select::make('from_verse_id')
                    ->label('من الآية')
                    ->options(function (callable $get) {
                        return QuranVerse::where('sora', $get('from_surah'))
                            ->pluck('text', 'id'); // ← هنا التعديل
                    })
                    ->required()
                    ->reactive(),

            ]),


            Forms\Components\Grid::make(2)->schema([
                Select::make('to_surah')
                    ->label('إلى السورة')
                    ->options(Surah::orderBy('id')->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->reactive(),

                Select::make('to_verse_id')
                    ->label('إلى الآية')
                    ->options(function (callable $get) {
                        return QuranVerse::where('sora', $get('to_surah'))
                            ->pluck('text', 'id'); // ← هنا التعديل
                    })
                    ->required(),

            ]),

            Select::make('type')
                ->label('نوع الحفظ')
                ->options([
                    'initial' => 'حفظ أولي',
                    'review' => 'مراجعة',
                ])
                ->required(),

            Select::make('verified')
                ->label('تم التحقق؟')
                ->options([
                    1 => 'نعم',
                    0 => 'لا',
                ])
                ->required(),

            Textarea::make('note')
                ->label('ملاحظات')
                ->maxLength(500)
                ->columnSpan('full'),
        ]);
    }
    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.user.name')
                    ->label('الطالب')
                    ->searchable(),

                // من الآية: السورة + رقم الآية
                Tables\Columns\TextColumn::make('fromVerse.text')
                    ->label('من الآية')
                    ->description(fn ($record) =>
                        'سورة: ' . ($record->fromVerse->surah->name ?? '-') .
                        ' - آية: ' . $record->fromVerse->ayah
                    )
                    ->wrap(),
                // التفاصيل الإضافية (hidden by default)
                Tables\Columns\TextColumn::make('fromVerse.jozo')
                    ->label('من الجزء')
                    ->toggleable(), // ✅ إخفاء افتراضي

                Tables\Columns\TextColumn::make('fromVerse.hizb')
                    ->label('من الحزب')
                    ->toggleable(), // ✅ إخفاء افتراضي

                Tables\Columns\TextColumn::make('fromVerse.qrtr')
                    ->label('من الربع')
                    ->toggleable(), // ✅ إخفاء افتراضي

                Tables\Columns\TextColumn::make('fromVerse.page')
                    ->label('من الصفحة')
                    ->toggleable(), // ✅ إخفاء افتراضي


                // إلى الآية: السورة + رقم الآية
                Tables\Columns\TextColumn::make('toVerse.text')
                    ->label('إلى الآية')
                    ->description(fn ($record) =>
                        'سورة: ' . ($record->toVerse->surah->name ?? '-') .
                        ' - آية: ' . $record->toVerse->ayah
                    )
                    ->wrap(),

                // التفاصيل الإضافية (hidden by default)
                Tables\Columns\TextColumn::make('toVerse.jozo')
                    ->label('إلى الجزء')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('toVerse.hizb')
                    ->label('إلى الحزب')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('toVerse.qrtr')
                    ->label('إلى الربع')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('toVerse.page')
                    ->label('إلى الصفحة')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('نوع الحفظ')
                    ->badge(),

                Tables\Columns\IconColumn::make('verified')
                    ->label('تم التحقق')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('أضيفت بتاريخ')
                    ->date(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListStudentMemorizedParts::route('/'),
            'create' => Pages\CreateStudentMemorizedPart::route('/create'),
            'edit' => Pages\EditStudentMemorizedPart::route('/{record}/edit'),
        ];
    }
}
