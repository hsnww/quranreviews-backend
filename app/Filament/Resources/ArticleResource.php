<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Models\Article;
use App\Support\ArabicSlug;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationGroup = 'المدونة';
    protected static ?string $modelLabel = 'مقال';
    protected static ?string $pluralModelLabel = 'المقالات';
    protected static ?int $navigationSort = 1;

    /** رابط المقال في الموقع العام */
    public static function publicUrl(Article $article): string
    {
        return rtrim(config('app.frontend_url', 'https://quranrevision.app'), '/') . '/blog/' . rawurlencode($article->slug);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make([
                Forms\Components\Section::make('المحتوى')->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('العنوان')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                            if (blank($get('slug')) && filled($state)) {
                                $set('slug', ArabicSlug::make($state));
                            }
                        }),
                    Forms\Components\TextInput::make('slug')
                        ->label('الرابط')
                        ->helperText('عربي، والكلمات مفصولة بشرطة. يُولَّد من العنوان إن تُرك فارغًا. تغييره بعد النشر يكسر الروابط القديمة.')
                        ->prefix('/blog/')
                        ->maxLength(150)
                        ->unique(ignoreRecord: true)
                        ->dehydrateStateUsing(fn (?string $state) => filled($state) ? ArabicSlug::make($state) : null),
                    Forms\Components\Textarea::make('excerpt')
                        ->label('الملخص')
                        ->helperText('يظهر في قائمة المقالات، ويُستعمل وصفًا لمحركات البحث إن لم يُحدَّد وصف SEO.')
                        ->rows(3)
                        ->maxLength(500),
                    Forms\Components\MarkdownEditor::make('body')
                        ->label('نص المقال (Markdown)')
                        ->helperText('استعمل ## للعناوين الرئيسية و### للفرعية؛ تظهر في فهرس المقال.')
                        ->required()
                        ->disableToolbarButtons(['attachFiles'])
                        ->columnSpanFull(),
                ]),

                Forms\Components\Section::make('الأسئلة الشائعة')
                    ->description('تظهر أسفل المقال، وقد يعرضها Google مباشرة في نتائج البحث.')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Repeater::make('faqs')
                            ->hiddenLabel()
                            ->schema([
                                Forms\Components\TextInput::make('question')->label('السؤال')->required(),
                                Forms\Components\Textarea::make('answer')->label('الجواب')->rows(3)->required(),
                            ])
                            ->addActionLabel('إضافة سؤال')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state) => $state['question'] ?? null)
                            ->defaultItems(0),
                    ]),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make([
                Forms\Components\Section::make('النشر')->schema([
                    Forms\Components\Select::make('status')
                        ->label('الحالة')
                        ->options(['draft' => 'مسودة', 'published' => 'منشور'])
                        ->default('draft')
                        ->required(),
                    Forms\Components\DateTimePicker::make('published_at')
                        ->label('تاريخ النشر')
                        ->helperText('يُعبّأ تلقائيًا عند النشر. تاريخ مستقبلي = نشر مجدول.'),
                    Forms\Components\Select::make('article_category_id')
                        ->label('التصنيف')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->label('الاسم')->required(),
                        ]),
                    Forms\Components\FileUpload::make('cover_image')
                        ->label('صورة الغلاف')
                        ->helperText('تظهر عند مشاركة الرابط. يفضَّل 1200×630.')
                        ->image()
                        ->disk('public')
                        ->directory('articles')
                        ->maxSize(2048),
                ]),

                Forms\Components\Section::make('تحسين محركات البحث (SEO)')->schema([
                    Forms\Components\TextInput::make('focus_keyword')
                        ->label('الكلمة المستهدفة')
                        ->helperText('مثل: جدول حفظ القرآن الكريم'),
                    Forms\Components\TextInput::make('meta_title')
                        ->label('عنوان SEO')
                        ->maxLength(255)
                        ->live(debounce: 500)
                        ->helperText(fn (?string $state) => 'الأفضل 60 حرفًا أو أقل — الحالي: ' . mb_strlen((string) $state)),
                    Forms\Components\Textarea::make('meta_description')
                        ->label('وصف SEO')
                        ->rows(3)
                        ->maxLength(500)
                        ->live(debounce: 500)
                        ->helperText(fn (?string $state) => 'الأفضل 150–160 حرفًا — الحالي: ' . mb_strlen((string) $state)),
                ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(60)
                    ->wrap(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('التصنيف')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'published' ? 'منشور' : 'مسودة')
                    ->color(fn (string $state) => $state === 'published' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('focus_keyword')
                    ->label('الكلمة المستهدفة')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('reading_minutes')
                    ->label('القراءة')
                    ->suffix(' د')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('تاريخ النشر')
                    ->date('Y/m/d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخر تعديل')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(['draft' => 'مسودة', 'published' => 'منشور']),
                Tables\Filters\SelectFilter::make('article_category_id')
                    ->label('التصنيف')
                    ->relationship('category', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('عرض في الموقع')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Article $record) => static::publicUrl($record))
                    ->openUrlInNewTab()
                    ->visible(fn (Article $record) => $record->status === 'published'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
