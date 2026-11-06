<?php

namespace App\Filament\Pages;

use App\Models\StoreSetting as StoreSettingModel;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class StoreSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Store settings';

    protected static ?string $title = 'Store settings';

    protected static string $view = 'filament.pages.store-settings';

    public ?array $data = [];

    public ?StoreSettingModel $record = null;

    public function mount(): void
    {
        $this->record = StoreSettingModel::query()->firstOrCreate(
            ['id' => 1],
            ['store_name' => 'F-commerce', 'copyright_name' => 'Bikrify'],
        );

        $state = $this->record->attributesToArray();
        $state['service_highlights'] ??= config('storefront.services');

        $this->form->fill($state);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Store identity')
                    ->description('These details appear on this shop’s storefront.')
                    ->schema([
                        TextInput::make('store_name')->required()->maxLength(120),
                        TextInput::make('owner_name')->label('Owner name')->maxLength(120),
                        TextInput::make('platform_credit')
                            ->label('Platform copyright')
                            ->default('Bikrify')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('This is fixed to Bikrify and cannot be changed by store owners.'),
                        TextInput::make('phone')->label('Contact phone')->tel()->maxLength(30),
                        TextInput::make('whatsapp')->label('WhatsApp number')->tel()->maxLength(30),
                        TextInput::make('email')->label('Public email')->email()->maxLength(255),
                        FileUpload::make('logo_path')
                            ->label('Store logo')
                            ->image()
                            ->disk('public')
                            ->directory('store-branding')
                            ->maxSize(3072)
                            ->columnSpanFull(),
                    ])->columns(2),
                Section::make('Announcement rail')
                    ->description('Separate announcements with a | character. The rail scrolls across the storefront header.')
                    ->schema([
                        Textarea::make('announcements')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),
                Section::make('Storefront colors')
                    ->description('Choose the colors used across the announcement bar, buttons, backgrounds, and footer.')
                    ->schema([
                        ColorPicker::make('theme_primary')->label('Main brand color')->hex()->default('#294f3f'),
                        ColorPicker::make('theme_dark')->label('Dark brand color')->hex()->default('#1f3c31'),
                        ColorPicker::make('theme_accent')->label('Accent color')->hex()->default('#edb298'),
                        ColorPicker::make('theme_background')->label('Page background')->hex()->default('#f8f5ee'),
                        ColorPicker::make('theme_surface')->label('Card and header background')->hex()->default('#fffdf9'),
                        ColorPicker::make('theme_text')->label('Text color')->hex()->default('#24382f'),
                    ])->columns(2),
                Section::make('Page typography')
                    ->description('Choose the heading and body font style used across the storefront.')
                    ->schema([
                        Select::make('theme_font')
                            ->label('Page font')
                            ->options([
                                'editorial' => 'Editorial — serif headings and clean body text',
                                'modern' => 'Modern — clean sans-serif throughout',
                                'classic' => 'Classic — serif throughout',
                                'friendly' => 'Friendly — rounded sans-serif throughout',
                                'system' => 'System — native device fonts throughout',
                            ])
                            ->default('editorial')
                            ->required()
                            ->helperText('The selected font preset updates headings, product details, forms, and navigation.'),
                    ]),
                Section::make('Shopping highlights')
                    ->description('Choose the three short benefits and icons shown below the homepage banner.')
                    ->schema([
                        Repeater::make('service_highlights')
                            ->label('Highlights')
                            ->schema([
                                Select::make('icon')->required()->options([
                                    'cash' => 'Cash / payment',
                                    'delivery' => 'Delivery',
                                    'heart' => 'Care / favourite',
                                    'shield' => 'Trust / protection',
                                    'package' => 'Package',
                                    'support' => 'Customer support',
                                    'spark' => 'Sparkle',
                                ]),
                                TextInput::make('label')->required()->maxLength(60),
                            ])
                            ->default(config('storefront.services'))
                            ->minItems(1)
                            ->maxItems(4)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Shopping highlight')
                            ->columnSpanFull(),
                    ]),
                Section::make('Full image carousel')
                    ->description('Add up to five promotional banners. Each image is automatically scaled to fit without cropping. Optional text can be enabled for slides that need it.')
                    ->schema([
                        Repeater::make('promotions')
                            ->label('Carousel slides')
                            ->schema([
                                FileUpload::make('image')
                                    ->label('Banner image')
                                    ->required()
                                    ->image()
                                    ->disk('public')
                                    ->directory('store-promotions')
                                    ->maxSize(5120)
                                    ->columnSpanFull(),
                                TextInput::make('eyebrow')->label('Small label')->maxLength(50),
                                TextInput::make('title')->required()->maxLength(120),
                                Textarea::make('description')->rows(2)->maxLength(300)->columnSpanFull(),
                                TextInput::make('button_text')->label('Button label')->maxLength(40),
                                TextInput::make('button_link')
                                    ->label('Button link')
                                    ->placeholder('https://... or #collection')
                                    ->maxLength(500),
                                TextInput::make('image_alt')->label('Image description')->maxLength(160),
                                Toggle::make('show_content')->label('Show text over the banner')->default(false),
                            ])
                            ->defaultItems(0)
                            ->maxItems(5)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'New banner')
                            ->columnSpanFull(),
                    ]),
                Section::make('Social media')
                    ->description('Only links you fill in are shown in the storefront footer.')
                    ->schema([
                        TextInput::make('instagram_url')->label('Instagram profile')->url()->maxLength(500),
                        TextInput::make('tiktok_url')->label('TikTok profile')->url()->maxLength(500),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $this->record?->update($this->form->getState());

        Notification::make()
            ->title('Store settings saved')
            ->success()
            ->send();
    }
}
