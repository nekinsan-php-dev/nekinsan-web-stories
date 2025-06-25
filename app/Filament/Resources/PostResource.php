<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Web Stories';

    protected static ?string $modelLabel = 'Web Story';

    protected static ?string $pluralModelLabel = 'Web Stories';

    protected static ?string $navigationGroup = 'Content Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Basic Information')
                        ->icon('heroicon-o-document-text')
                        ->description('Set up your story basics')
                        ->schema([
                            Forms\Components\Section::make()
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('title')
                                                ->label('Story Title')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('e.g., "Amazing Journey Through Time"')
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (string $operation, $state, Forms\Set $set, Forms\Get $get) {
                                                    if ($operation !== 'create') {
                                                        return;
                                                    }

                                                    $slug = Str::slug($state);
                                                    $set('slug', $slug);

                                                    // Auto-populate meta_title if empty
                                                    if (empty($get('meta_title'))) {
                                                        $set('meta_title', $state);
                                                    }

                                                    // Auto-generate basic meta description
                                                    if (empty($get('meta_description')) && !empty($state)) {
                                                        $set('meta_description', 'Discover ' . $state . ' - An engaging web story that will captivate your audience.');
                                                    }
                                                })
                                                ->columnSpan(2),

                                            Forms\Components\Select::make('category_id')
                                                ->label('Category')
                                                ->relationship('category', 'name')
                                                ->searchable()
                                                ->preload()
                                                ->createOptionForm([
                                                    Forms\Components\TextInput::make('name')
                                                        ->required()
                                                        ->maxLength(255)
                                                        ->live(onBlur: true)
                                                        ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                                            if ($operation !== 'create') {
                                                                return;
                                                            }
                                                            $set('slug', Str::slug($state));
                                                        }),
                                                    Forms\Components\TextInput::make('slug')
                                                        ->required()
                                                        ->maxLength(255)
                                                        ->unique(Category::class, 'slug', ignoreRecord: true),
                                                    Forms\Components\Toggle::make('is_active')
                                                        ->required()
                                                        ->default(true),
                                                ])
                                                ->required()
                                                ->placeholder('Choose a category')
                                                ->columnSpan(1),
                                        ]),

                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('slug')
                                                ->label('URL Slug')
                                                ->required()
                                                ->maxLength(255)
                                                ->unique(Post::class, 'slug', ignoreRecord: true)
                                                ->prefix('yourdomain.com/stories/')
                                                ->suffixIcon('heroicon-o-link')
                                                ->rules(['alpha_dash'])
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                    $set('slug', Str::slug($state));
                                                }),

                                            Forms\Components\Toggle::make('is_active')
                                                ->label('Publish Story')
                                                ->default(true)
                                                ->onIcon('heroicon-o-eye')
                                                ->offIcon('heroicon-o-eye-slash')
                                                ->onColor('success')
                                                ->offColor('gray')
                                                ->inline(false),
                                        ]),
                                ])
                        ]),

                    Forms\Components\Wizard\Step::make('SEO Optimization')
                        ->icon('heroicon-o-magnifying-glass')
                        ->description('Optimize for search engines')
                        ->schema([
                            Forms\Components\Section::make('Search Engine Optimization')
                                ->description('Help people find your story online')
                                ->schema([
                                    Forms\Components\TextInput::make('meta_title')
                                        ->label('Meta Title')
                                        ->maxLength(60)
                                        ->placeholder('Enter SEO-friendly title (50-60 characters)')
                                        ->suffixIcon('heroicon-o-tag')
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            // Auto-trigger SEO score update
                                            $set('seo_score', time());
                                        })
                                        ->columnSpanFull(),

                                    Forms\Components\Textarea::make('meta_description')
                                        ->label('Meta Description')
                                        ->maxLength(160)
                                        ->rows(3)
                                        ->placeholder('Write a compelling description (120-160 characters)')
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            $set('seo_score', time());
                                        })
                                        ->columnSpanFull(),

                                    Forms\Components\TagsInput::make('meta_keywords')
                                        ->label('Keywords')
                                        ->placeholder('Add relevant keywords (press Enter after each)')
                                        ->suggestions([
                                            'web story', 'digital story', 'interactive content', 'visual story',
                                            'storytelling', 'multimedia', 'engaging content', 'narrative',
                                            'creative story', 'immersive experience'
                                        ])
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            $set('seo_score', time());
                                        })
                                        ->columnSpanFull(),
                                ])
                        ]),

                    Forms\Components\Wizard\Step::make('Cover Image')
                        ->icon('heroicon-o-photo')
                        ->description('Add an eye-catching cover')
                        ->schema([
                            Forms\Components\Section::make('Featured Image')
                                ->description('Upload a stunning cover image that represents your story')
                                ->schema([
                                    SpatieMediaLibraryFileUpload::make('cover_image')
                                        ->label('Cover Image')
                                        ->collection('cover')
                                        ->image()
                                        ->imageEditor()
                                        ->imageEditorAspectRatios([
                                            '16:9' => '16:9 (Recommended)',
                                            '4:3' => '4:3 (Classic)',
                                            '1:1' => '1:1 (Square)',
                                        ])
                                        ->conversion('story')
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                        ->maxSize(5120) // 5MB
                                        ->helperText('This image will be used for social media sharing and as the main visual for your story')
                                        ->columnSpanFull(),
                                ])
                        ]),

                    Forms\Components\Wizard\Step::make('Story Content')
                        ->icon('heroicon-o-squares-plus')
                        ->description('Create your story slides')
                        ->schema([
                            Forms\Components\Section::make('Story Slides')
                                ->description('Create engaging slides that tell your story')
                                ->schema([
                                    Forms\Components\Repeater::make('content.slides')
                                        ->label('')
                                        ->schema([
                                            Forms\Components\Card::make()
                                                ->schema([
                                                    Forms\Components\Grid::make(1)
                                                        ->schema([
                                                            Forms\Components\TextInput::make('slide_title')
                                                                ->label('Slide Title (Optional)')
                                                                ->maxLength(100)
                                                                ->placeholder('e.g., "Introduction", "The Journey Begins"'),
                                                        ]),

                                                    Forms\Components\Grid::make(4)
                                                        ->schema([
                                                            Forms\Components\Toggle::make('text_active')
                                                                ->label('Text Content')
                                                                ->default(true)
                                                                ->live()
                                                                ->onIcon('heroicon-o-document-text')
                                                                ->offIcon('heroicon-o-document-text')
                                                                ->onColor('success')
                                                                ->inline(false),

                                                            Forms\Components\Toggle::make('image_active')
                                                                ->label('Image Content')
                                                                ->default(true)
                                                                ->live()
                                                                ->onIcon('heroicon-o-photo')
                                                                ->offIcon('heroicon-o-photo')
                                                                ->onColor('success')
                                                                ->inline(false),

                                                            Forms\Components\Toggle::make('zoom_effect')
                                                                ->label('Zoom Animation')
                                                                ->default(false)
                                                                ->visible(fn (Forms\Get $get) => $get('image_active'))
                                                                ->onIcon('heroicon-o-magnifying-glass-plus')
                                                                ->offIcon('heroicon-o-magnifying-glass-plus')
                                                                ->onColor('primary')
                                                                ->inline(false),

                                                            Forms\Components\Select::make('text_position')
                                                                ->label('Text Position')
                                                                ->options([
                                                                    'center' => 'Center',
                                                                    'left' => 'Left',
                                                                    'right' => 'Right',
                                                                    'bottom' => 'Bottom',
                                                                ])
                                                                ->default('center')
                                                                ->visible(fn (Forms\Get $get) => $get('text_active') && $get('image_active')),
                                                        ]),

                                                    Forms\Components\RichEditor::make('content')
                                                        ->label('Slide Content')
                                                        ->placeholder('Write engaging content that captivates your audience...')
                                                        ->toolbarButtons([
                                                            'blockquote',
                                                            'bold',
                                                            'bulletList',
                                                            'h2',
                                                            'h3',
                                                            'italic',
                                                            'link',
                                                            'orderedList',
                                                            'strike',
                                                            'underline',
                                                        ])
                                                        ->columnSpanFull()
                                                        ->visible(fn (Forms\Get $get) => $get('text_active')),

                                                    SpatieMediaLibraryFileUpload::make('slide_images')
                                                        ->label('Slide Images')
                                                        ->collection('slides')
                                                        ->image()
                                                        ->imageEditor()
                                                        ->imageEditorAspectRatios([
                                                            '16:9' => '16:9 (Widescreen)',
                                                            '4:3' => '4:3 (Standard)',
                                                            '1:1' => '1:1 (Square)',
                                                            '9:16' => '9:16 (Portrait)',
                                                        ])
                                                        ->conversion('story')
                                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                                        ->maxSize(5120)
                                                        ->columnSpanFull()
                                                        ->visible(fn (Forms\Get $get) => $get('image_active')),
                                                ])
                                        ])
                                        ->itemLabel(fn (array $state): ?string =>
                                        !empty($state['slide_title'])
                                            ? $state['slide_title']
                                            : 'Slide #' . (array_search($state, request()->input('data.content.slides', [])) + 1)
                                        )
                                        ->addActionLabel('Add New Slide')
                                        ->reorderableWithButtons()
                                        ->collapsible()
                                        ->cloneable()
                                        ->deleteAction(
                                            fn (Forms\Components\Actions\Action $action) => $action
                                                ->requiresConfirmation()
                                                ->modalHeading('Delete slide')
                                                ->modalDescription('Are you sure you want to delete this slide? This action cannot be undone.')
                                                ->modalSubmitActionLabel('Yes, delete it')
                                        )
                                        ->columnSpanFull()
                                        ->minItems(1)
                                        ->defaultItems(1),
                                ])
                        ])
                ])
                    ->columnSpanFull()
                    ->skippable()
                    ->persistStepInQueryString()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('preview')
                    ->label('Preview')
                    ->collection('cover')
                    ->conversion('thumb')
                    ->height(60)
                    ->width(60)
                    ->extraAttributes(['class' => 'rounded-lg shadow-sm']),

                Tables\Columns\TextColumn::make('title')
                    ->label('Story Title')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->weight('medium')
                    ->description(fn (Post $record): string =>
                    $record->meta_title ? 'SEO: ' . Str::limit($record->meta_title, 30) : 'No SEO title'
                    ),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slides_count')
                    ->label('Slides')
                    ->state(fn (Post $record) => $record->slides_count)
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-squares-plus'),

                Tables\Columns\IconColumn::make('seo_status')
                    ->label('SEO')
                    ->state(function (Post $record): string {
                        $hasTitle = !empty($record->meta_title);
                        $hasDescription = !empty($record->meta_description);
                        $hasKeywords = !empty($record->meta_keywords);

                        if ($hasTitle && $hasDescription && $hasKeywords) {
                            return 'complete';
                        } elseif ($hasTitle || $hasDescription) {
                            return 'partial';
                        }
                        return 'none';
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'complete' => 'heroicon-o-check-circle',
                        'partial' => 'heroicon-o-exclamation-triangle',
                        'none' => 'heroicon-o-x-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'complete' => 'success',
                        'partial' => 'warning',
                        'none' => 'danger',
                    })
                    ->tooltip(fn (string $state): string => match ($state) {
                        'complete' => 'SEO fully optimized',
                        'partial' => 'SEO partially optimized',
                        'none' => 'SEO not optimized',
                    }),

                Tables\Columns\TextColumn::make('meta_description')
                    ->label('Meta Description')
                    ->limit(50)
                    ->placeholder('No meta description')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('meta_keywords')
                    ->label('Keywords')
                    ->state(fn (Post $record) =>
                    is_array($record->meta_keywords)
                        ? implode(', ', array_slice($record->meta_keywords, 0, 3)) . (count($record->meta_keywords) > 3 ? '...' : '')
                        : ($record->meta_keywords ?? 'No keywords')
                    )
                    ->placeholder('No keywords')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Status')
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-o-eye')
                    ->offIcon('heroicon-o-eye-slash')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->size('sm')
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->label('Categories'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All Stories')
                    ->trueLabel('Published Stories')
                    ->falseLabel('Draft Stories')
                    ->native(false),

                Tables\Filters\SelectFilter::make('seo_status')
                    ->label('SEO Status')
                    ->options([
                        'complete' => 'Fully Optimized',
                        'partial' => 'Partially Optimized',
                        'none' => 'Not Optimized',
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'complete' => $query->whereNotNull('meta_title')
                                ->whereNotNull('meta_description')
                                ->whereNotNull('meta_keywords'),
                            'partial' => $query->where(function ($q) {
                                $q->whereNotNull('meta_title')
                                    ->orWhereNotNull('meta_description');
                            })->where(function ($q) {
                                $q->whereNull('meta_title')
                                    ->orWhereNull('meta_description')
                                    ->orWhereNull('meta_keywords');
                            }),
                            'none' => $query->whereNull('meta_title')
                                ->whereNull('meta_description')
                                ->whereNull('meta_keywords'),
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('optimize_seo')
                    ->label('Optimize SEO')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('warning')
                    ->visible(fn (Post $record) =>
                        empty($record->meta_title) ||
                        empty($record->meta_description) ||
                        empty($record->meta_keywords)
                    )
                    ->url(fn (Post $record) => static::getUrl('edit', ['record' => $record]))
                    ->tooltip('Complete SEO optimization'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => true]);
                            }
                        })
                        ->color('success')
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Move to Draft')
                        ->icon('heroicon-o-x-circle')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => false]);
                            }
                        })
                        ->color('warning')
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('auto_seo')
                        ->label('Auto-Generate SEO')
                        ->icon('heroicon-o-sparkles')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $updates = [];

                                // Auto-generate meta_title if missing
                                if (empty($record->meta_title)) {
                                    $updates['meta_title'] = Str::limit($record->title, 60);
                                }

                                // Auto-generate meta_description if missing
                                if (empty($record->meta_description)) {
                                    // Extract text from first slide if available
                                    $slides = $record->content['slides'] ?? [];
                                    $firstSlideContent = $slides[0]['content'] ?? '';
                                    $description = strip_tags($firstSlideContent);
                                    $updates['meta_description'] = Str::limit($description, 160);
                                }

                                if (!empty($updates)) {
                                    $record->update($updates);
                                }
                            }
                        })
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Auto-Generate SEO Data')
                        ->modalDescription('This will automatically generate meta titles and descriptions for selected stories that don\'t have them. Existing SEO data will not be overwritten.'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
