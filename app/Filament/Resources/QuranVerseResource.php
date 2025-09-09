<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuranVerseResource\Pages;
use App\Filament\Resources\QuranVerseResource\RelationManagers;
use App\Models\QuranVerse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuranVerseResource extends Resource
{
    protected static ?string $model = QuranVerse::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                TextColumn::make('id')
                    ->label('رقم تسلسل الآية')
                    ->sortable(),
                TextColumn::make('surah.name')
                    ->label('اسم السورة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('ayah')
                    ->label(' رقم الآية')
                    ->searchable(),
                TextColumn::make('text')
                    ->label(' نص الآية')
                    ->wrap()  // يجعل النص يلتف داخل الخلية
//                    ->limit(100)
                    ->extraAttributes(['class' => 'whitespace-pre-line'])

                    ->searchable(),
                TextColumn::make('page')
                    ->label(' الصفحة')
                    ->searchable(),
                TextColumn::make('hizb')
                    ->label(' الحزب'),
                TextColumn::make('qrtr')
                    ->label(' الربع'),
                TextColumn::make('jozo')
                    ->label(' الجزء'),
            ])
            ->filters([
                // فلتر السورة
                SelectFilter::make('sora')
                    ->label('السورة')
                    ->relationship('surah', 'name', fn ($query) => $query->orderBy('id')),

                // فلتر الجزء
                SelectFilter::make('jozo')
                    ->label('الجزء')
                    ->options(
                        collect(range(1, 30))->mapWithKeys(fn ($v) => [$v => "الجزء $v"])
                    ),

                // فلتر الحزب
                SelectFilter::make('hizb')
                    ->label('الحزب')
                    ->options(
                        collect(range(1, 60))->mapWithKeys(fn ($v) => [$v => "الحزب $v"])
                    ),
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
            'index' => Pages\ListQuranVerses::route('/'),
            'create' => Pages\CreateQuranVerse::route('/create'),
            'edit' => Pages\EditQuranVerse::route('/{record}/edit'),
        ];
    }
}
