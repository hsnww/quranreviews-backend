<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleCategoryResource\Pages;
use App\Models\ArticleCategory;
use App\Support\ArabicSlug;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ArticleCategoryResource extends Resource
{
    protected static ?string $model = ArticleCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'المدونة';
    protected static ?string $modelLabel = 'تصنيف';
    protected static ?string $pluralModelLabel = 'التصنيفات';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('الاسم')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('slug')
                ->label('الرابط')
                ->helperText('يُولَّد من الاسم إن تُرك فارغًا.')
                ->prefix('/blog/category/')
                ->maxLength(150)
                ->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn (?string $state) => filled($state) ? ArabicSlug::make($state) : null),
            Forms\Components\Textarea::make('description')
                ->label('الوصف')
                ->helperText('يظهر أعلى صفحة التصنيف ووصفًا لمحركات البحث.')
                ->rows(3)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('sort_order')
                ->label('الترتيب')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('الرابط'),
                Tables\Columns\TextColumn::make('articles_count')->label('المقالات')->counts('articles'),
                Tables\Columns\TextColumn::make('sort_order')->label('الترتيب')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageArticleCategories::route('/'),
        ];
    }
}
