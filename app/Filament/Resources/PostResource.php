<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Filament\Resources\PostResource\RelationManagers;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Web Stories';

    protected static ?string $modelLabel = 'Web Story';

    protected static ?string $pluralModelLabel = 'Web Stories';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Story Details')
                    ->description('Basic information about your web story')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Story Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter an engaging title for your story')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Publish Story')
                            ->default(true)
                            ->helperText('Make this story visible to visitors')
                            ->inline(false),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Story Slides')
                    ->description('Create engaging slides for your web story')
                    ->schema([
                        Forms\Components\Repeater::make('content.slides')
                            ->label('')
                            ->schema([
                                Forms\Components\Card::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('slide_title')
                                            ->label('Slide Title (Optional)')
                                            ->placeholder('Give this slide a title')
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Toggle::make('text_active')
                                                    ->label('Enable Text')
                                                    ->default(true)
                                                    ->live()
                                                    ->inline(false),

                                                Forms\Components\Toggle::make('image_active')
                                                    ->label('Enable Image')
                                                    ->default(true)
                                                    ->live()
                                                    ->inline(false),

                                                Forms\Components\Toggle::make('zoom_effect')
                                                    ->label('Zoom Effect')
                                                    ->default(false)
                                                    ->helperText('Add zoom animation to images')
                                                    ->visible(fn (Forms\Get $get) => $get('image_active'))
                                                    ->inline(false),
                                            ]),

                                        Forms\Components\RichEditor::make('content')
                                            ->label('Slide Content')
                                            ->placeholder('Write your engaging content here...')
                                            ->toolbarButtons([
                                                'blockquote',
                                                'bold',
                                                'bulletList',
                                                'codeBlock',
                                                'h2',
                                                'h3',
                                                'italic',
                                                'link',
                                                'orderedList',
                                                'redo',
                                                'strike',
                                                'underline',
                                                'undo',
                                            ])
                                            ->columnSpanFull()
                                            ->visible(fn (Forms\Get $get) => $get('text_active')),

                                        SpatieMediaLibraryFileUpload::make('slide_images')
                                            ->label('Slide Images')
                                            ->collection('slides')
                                            ->image()
                                            ->imageEditor()
                                            ->imageEditorAspectRatios([
                                                '16:9',
                                                '4:3',
                                                '1:1',
                                                '9:16',
                                            ])
                                            ->conversion('story')
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                            ->columnSpanFull()
                                            ->visible(fn (Forms\Get $get) => $get('image_active'))
                                            ->helperText('Upload image - will be optimized for web delivery'),
                                    ])
                            ])
                            ->itemLabel(fn (array $state): ?string =>
                            !empty($state['slide_title'])
                                ? $state['slide_title']
                                : 'Slide ' . (count(request()->input('data.content.slides', [])) > 0 ?
                                    array_search($state, request()->input('data.content.slides', [])) + 1 : 1)
                            )
                            ->addActionLabel('Add New Slide')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->deleteAction(
                                fn (Forms\Components\Actions\Action $action) => $action
                                    ->requiresConfirmation()
                                    ->modalHeading('Delete slide')
                                    ->modalDescription('Are you sure you want to delete this slide?')
                            )
                            ->columnSpanFull()
                            ->minItems(1)
                            ->defaultItems(1)
                            ->helperText('Create multiple slides for your web story. Each slide can contain text, images, or both.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->size('sm'),

                SpatieMediaLibraryImageColumn::make('preview')
                    ->label('Preview')
                    ->collection('slides')
                    ->conversion('thumb')
                    ->height(60)
                    ->width(60)
                    ->defaultImageUrl(url('/images/placeholder.png'))
                    ->extraAttributes(['class' => 'rounded-lg shadow-sm']),

                Tables\Columns\TextColumn::make('title')
                    ->label('Story Title')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 40 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('slides_count')
                    ->label('Slides')
                    ->state(fn (Post $record) => $record->slides_count)
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-squares-plus'),

                Tables\Columns\IconColumn::make('has_zoom_effects')
                    ->label('Effects')
                    ->getStateUsing(fn (Post $record) => $record->has_zoom_effects)
                    ->boolean()
                    ->trueIcon('heroicon-o-sparkles')
                    ->falseIcon('heroicon-o-photo')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn (Post $record) => $record->has_zoom_effects ? 'Has zoom effects' : 'No special effects'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Status')
                    ->onColor('success')
                    ->offColor('danger')
                    ->beforeStateUpdated(function ($record, $state) {
                        // You can add any validation here if needed
                        return $state;
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->size('sm')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable()
                    ->size('sm')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All Stories')
                    ->trueLabel('Published Stories')
                    ->falseLabel('Draft Stories')
                    ->native(false),

                Tables\Filters\Filter::make('has_effects')
                    ->label('Has Effects')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('has_zoom_effects', true)),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation(),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => true]);
                            }
                        })
                        ->deselectRecordsAfterCompletion()
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
                        ->deselectRecordsAfterCompletion()
                        ->color('warning')
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateHeading('No Web Stories Yet')
            ->emptyStateDescription('Create your first engaging web story to get started.')
            ->emptyStateIcon('heroicon-o-photo')
            ->defaultSort('created_at', 'desc')
            ->striped();
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
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
