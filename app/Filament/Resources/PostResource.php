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
                                    Forms\Components\Grid::make(2)
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
                                                    $set('slug', Str::slug($state));
                                                    // Auto-fill meta_title if empty
                                                    if (empty($get('meta_title'))) {
                                                        $set('meta_title', $state);
                                                    }
                                                })

                                                ->columnSpan(1),

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
                                ])
                        ]),

                    Forms\Components\Wizard\Step::make('SEO Settings')
                        ->icon('heroicon-o-magnifying-glass')
                        ->description('Optimize for search engines')
                        ->schema([
                            Forms\Components\Section::make('Search Engine Optimization')
                                ->description('Help your story rank better in search results')
                                ->schema([
                                    Forms\Components\TextInput::make('meta_title')
                                        ->label('Meta Title')
                                        ->maxLength(60)
                                        ->placeholder('Leave blank to use story title')
                                        ->helperText('Recommended: 50-60 characters. This appears as the clickable headline in search results.')
                                        ->suffixAction(
                                            Forms\Components\Actions\Action::make('copyFromTitle')
                                                ->icon('heroicon-o-document-duplicate')
                                                ->action(function (Forms\Set $set, Forms\Get $get) {
                                                    $set('meta_title', $get('title'));
                                                })
                                                ->tooltip('Copy from story title')
                                        )
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            $set('meta_title_length', strlen($state ?? ''));
                                        }),

                                   Forms\Components\Placeholder::make('meta_title_length')
    ->label('Meta Title Length')
    ->content(function (Forms\Get $get) {
        $length = strlen($get('meta_title') ?? '');
        return new \Illuminate\Support\HtmlString(
            "<span class='text-gray-700 font-medium'>{$length} characters</span>"
        );
    }),

                                    Forms\Components\Textarea::make('meta_description')
                                        ->label('Meta Description')
                                        ->maxLength(250)
                                        ->rows(3)
                                        ->placeholder('Write a compelling description that summarizes your story...')
                                        ->helperText('Recommended: 150-250 characters. This appears below the title in search results.')
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            $set('meta_description_length', strlen($state ?? ''));
                                        }),

                                    Forms\Components\Placeholder::make('meta_description_length')
                                        ->label('Meta Description Length')
                                        ->content(function (Forms\Get $get) {
                                            $length = strlen($get('meta_description') ?? '');
                                            $color = $length > 249 ? 'danger' : ($length > 150 ? 'warning' : 'success');
                                            return new \Illuminate\Support\HtmlString(
                                                "<span class='text-{$color}-600 font-medium'>{$length} characters</span>"
                                            );
                                        }),

                                    Forms\Components\TagsInput::make('meta_keywords')
                                        ->label('Meta Keywords')
                                        ->placeholder('Add keywords separated by commas')
                                        ->helperText('Add relevant keywords that describe your story content. Keep it natural and relevant.')
                                        ->separator(',')
                                        ->splitKeys(['Tab', ','])
                                        ->columnSpanFull(),

                                    Forms\Components\Card::make()
                                        ->schema([
                                            Forms\Components\Placeholder::make('seo_preview')
                                                ->label('Search Engine Preview')
                                                ->content(function (Forms\Get $get) {
                                                    $title = $get('meta_title') ?: $get('title') ?: 'Your Story Title';
                                                    $description = $get('meta_description') ?: 'Your story description will appear here...';
                                                    $slug = $get('slug') ?: 'your-story-slug';

                                                    return new \Illuminate\Support\HtmlString("
                                                        <div class='bg-white p-4 rounded-lg border border-gray-200'>
                                                            <div class='text-blue-600 text-lg font-medium hover:underline cursor-pointer'>
                                                                {$title}
                                                            </div>
                                                            <div class='text-green-700 text-sm mt-1'>
                                                                yourdomain.com/stories/{$slug}
                                                            </div>
                                                            <div class='text-gray-600 text-sm mt-2'>
                                                                {$description}
                                                            </div>
                                                        </div>
                                                    ");
                                                })
                                        ])
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
                                        ->maxFiles(1)
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
                                    Forms\Components\Repeater::make('slides')
                                        ->label('')
                                        ->relationship('slides')
                                        ->schema([
                                            Forms\Components\Card::make()
                                                ->schema([
                                                    Forms\Components\TextInput::make('title')
                                                        ->label('Slide Title (Optional)')
                                                        ->maxLength(255)
                                                        ->placeholder('e.g., "Introduction", "The Journey Begins"')
                                                        ->columnSpanFull(),

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

                                                            Forms\Components\Toggle::make('zoom_effect')
                                                                ->label('Zoom Animation')
                                                                ->default(false)
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
                                                                ->default('center'),

                                                            Forms\Components\Toggle::make('cta_button_show')
                                                                ->label('CTA Button')
                                                                ->default(false)
                                                                ->live()
                                                                ->onIcon('heroicon-o-cursor-arrow-rays')
                                                                ->offIcon('heroicon-o-cursor-arrow-rays')
                                                                ->onColor('warning')
                                                                ->inline(false),
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

                                                    Forms\Components\TextInput::make('cta_link')
                                                        ->label('Call-to-Action Link')
                                                        ->url()
                                                        ->placeholder('https://example.com/action')
                                                        ->helperText('Enter a valid URL where users should be redirected when they click the CTA button')
                                                        ->prefixIcon('heroicon-o-link')
                                                        ->visible(fn (Forms\Get $get) => $get('cta_button_show'))
                                                        ->columnSpanFull(),

                                                    SpatieMediaLibraryFileUpload::make('slide_image')
                                                        ->label('Slide Image')
                                                        ->collection('slide_image')
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
                                                        ->maxFiles(1)
                                                        ->helperText('Upload one image for this slide')
                                                        ->columnSpanFull(),

                                                    // CTA Preview Card
                                                    Forms\Components\Card::make()
                                                        ->schema([
                                                            Forms\Components\Placeholder::make('cta_preview')
                                                                ->label('CTA Button Preview')
                                                                ->content(function (Forms\Get $get) {
                                                                    if (!$get('cta_button_show')) {
                                                                        return new \Illuminate\Support\HtmlString(
                                                                            "<div class='text-gray-500 text-sm'>Enable CTA Button to see preview</div>"
                                                                        );
                                                                    }

                                                                    $link = $get('cta_link') ?: 'https://example.com';
                                                                    $isValidUrl = filter_var($link, FILTER_VALIDATE_URL);

                                                                    $buttonClass = $isValidUrl ?
                                                                        'bg-blue-600 hover:bg-blue-700 text-white' :
                                                                        'bg-gray-400 text-gray-700 cursor-not-allowed';

                                                                    return new \Illuminate\Support\HtmlString("
                                                                        <div class='bg-gray-50 p-4 rounded-lg border border-gray-200'>
                                                                            <div class='text-sm text-gray-600 mb-2'>Button Preview:</div>
                                                                            <button class='{$buttonClass} px-6 py-3 rounded-lg font-medium transition-colors duration-200'>
                                                                                Call to Action
                                                                            </button>
                                                                            <div class='text-xs text-gray-500 mt-2'>
                                                                                Link: " . ($isValidUrl ? $link : 'Invalid URL') . "
                                                                            </div>
                                                                        </div>
                                                                    ");
                                                                })
                                                        ])
                                                        ->visible(fn (Forms\Get $get) => $get('cta_button_show'))
                                                        ->columnSpanFull(),
                                                ])
                                        ])
                                        ->itemLabel(fn (array $state): ?string =>
                                        !empty($state['title'])
                                            ? $state['title']
                                            : 'Slide #' . (array_search($state, request()->input('data.slides', [])) + 1)
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
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slides_count')
                    ->label('Slides')
                    ->counts('slides')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-squares-plus'),

                Tables\Columns\TextColumn::make('cta_slides_count')
                    ->label('CTA Slides')
                    ->getStateUsing(fn (Post $record): int => $record->slides()->where('cta_button_show', true)->count())
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-o-cursor-arrow-rays')
                    ->tooltip('Number of slides with CTA buttons'),

                Tables\Columns\IconColumn::make('has_complete_seo')
                    ->label('SEO')
                    ->icon(fn (string $state): string => match ($state) {
                        '1' => 'heroicon-o-check-circle',
                        '0' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-minus-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'success',
                        '0' => 'danger',
                        default => 'warning',
                    })
                    ->tooltip(fn (Post $record): string =>
                    $record->has_complete_seo
                        ? 'SEO Complete'
                        : 'Missing SEO data'
                    )
                    ->sortable(),

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

                Tables\Filters\TernaryFilter::make('has_complete_seo')
                    ->label('SEO Status')
                    ->placeholder('All Stories')
                    ->trueLabel('SEO Complete')
                    ->falseLabel('SEO Incomplete')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('meta_title')->whereNotNull('meta_description'),
                        false: fn ($query) => $query->whereNull('meta_title')->orWhereNull('meta_description'),
                    )
                    ->native(false),

                Tables\Filters\TernaryFilter::make('has_cta_slides')
                    ->label('CTA Status')
                    ->placeholder('All Stories')
                    ->trueLabel('Has CTA Slides')
                    ->falseLabel('No CTA Slides')
                    ->queries(
                        true: fn ($query) => $query->whereHas('slides', fn ($q) => $q->where('cta_button_show', true)),
                        false: fn ($query) => $query->whereDoesntHave('slides', fn ($q) => $q->where('cta_button_show', true)),
                    )
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
