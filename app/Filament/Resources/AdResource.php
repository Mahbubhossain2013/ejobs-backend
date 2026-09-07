<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdResource\Pages;
use App\Models\Ad;
use App\Services\Ad\AdModerationService;
use App\Services\Ad\AdServingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdResource extends Resource
{
    protected static ?string $model = Ad::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'Website Ad Placements';
    protected static ?string $modelLabel = 'Website Placement Ad';
    protected static ?string $pluralModelLabel = 'Website Placement Ads';
    protected static ?string $slug = 'marketing/ads';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Ad Details')->tabs([
                
                Forms\Components\Tabs\Tab::make('General Configuration')->schema([
                    Forms\Components\Card::make()->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->nullable(),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'draft' => 'Draft',
                                    'active' => 'Active',
                                    'paused' => 'Paused',
                                ])
                                ->default('draft')
                                ->required(),
                            Forms\Components\Select::make('approval_status')
                                ->options([
                                    'pending' => 'Pending Review',
                                    'approved' => 'Approved',
                                    'rejected' => 'Rejected',
                                ])
                                ->default('approved')
                                ->required(),
                            Forms\Components\TextInput::make('priority')
                                ->numeric()
                                ->default(0)
                                ->required()
                                ->helperText('Higher values served first'),
                        ]),
                    ])
                ]),

                Forms\Components\Tabs\Tab::make('Creative & Media')->schema([
                    Forms\Components\Card::make()->schema([
                        Forms\Components\Select::make('media_type')
                            ->options([
                                'image' => 'Image File Upload',
                                'video' => 'Video File Upload',
                                'html' => 'HTML Code Snippet',
                                'script' => 'Javascript/Script Tag',
                                'url' => 'External Media Image URL',
                            ])
                            ->default('image')
                            ->reactive()
                            ->required(),
                        Forms\Components\FileUpload::make('media_file')
                            ->label('Upload File (Image/Video)')
                            ->directory('ads')
                            ->reactive()
                            ->visible(fn (callable $get) => in_array($get('media_type'), ['image', 'video'])),
                        Forms\Components\Textarea::make('media_path')
                            ->label('Media Path / Code Text')
                            ->rows(6)
                            ->placeholder('https://domain.com/banner.png or <div>Ad Code</div>')
                            ->visible(fn (callable $get) => in_array($get('media_type'), ['html', 'script', 'url']))
                            ->required(fn (callable $get) => in_array($get('media_type'), ['html', 'script', 'url']))
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        $safety = AdModerationService::checkSafety($value, 'html');
                                        if (!$safety['status']) {
                                            $fail($safety['reason']);
                                        }
                                    };
                                }
                            ]),
                    ])
                ]),

                Forms\Components\Tabs\Tab::make('Targeting & Placement')->schema([
                    Forms\Components\Card::make()->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('target_url')
                                ->url()
                                ->placeholder('https://')
                                ->rules([
                                    function () {
                                        return function (string $attribute, $value, \Closure $fail) {
                                            if (AdModerationService::isUrlBlacklisted($value)) {
                                                $fail("The target URL resides on a suspicious or spam domain blacklisted by platform policies.");
                                            }
                                        };
                                    }
                                ])
                                ->nullable(),
                            Forms\Components\TextInput::make('cta_text')
                                ->default('Learn More')
                                ->required(),
                        ]),
                        Forms\Components\Section::make('Predefined Slots Placement')
                            ->schema([
                                Forms\Components\Placeholder::make('placements_hint')
                                    ->content('Assign which slots this ad is enabled in:'),
                                Forms\Components\CheckboxList::make('placed_slots')
                                    ->label('Ad Slots')
                                    ->options([
                                        'homepage_hero' => 'Homepage Hero Banner (Exact Place Only)',
                                        'sidebar_candidate' => 'Candidate Dashboard Sidebar',
                                        'sidebar_employer' => 'Employer Dashboard Sidebar',
                                        'inline_jobs' => 'Job Listings Between Items',
                                        'feed' => 'Feed Sections',
                                        'widget' => 'Dashboard Widgets',
                                        'popup' => 'Global Popup Modal',
                                        'sticky_banner' => 'Global Sticky Bottom Banner',
                                    ])
                                    ->columns(2)
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function (Forms\Components\CheckboxList $component, ?Ad $record) {
                                        if ($record) {
                                            $component->state($record->placements()->where('is_enabled', true)->pluck('slot')->toArray());
                                        }
                                    }),
                            ]),
                        Forms\Components\Section::make('Audience Targeting')
                            ->schema([
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\Select::make('role_target')
                                        ->options([
                                            'all' => 'All Visitors',
                                            'candidate' => 'Candidates Only',
                                            'employer' => 'Employers Only',
                                            'guest' => 'Guests Only',
                                        ])
                                        ->default('all')
                                        ->required(),
                                    Forms\Components\Select::make('language_target')
                                        ->options([
                                            'all' => 'All Languages',
                                            'en' => 'English Only',
                                            'bn' => 'Bangla Only',
                                        ])
                                        ->default('all')
                                        ->required(),
                                    Forms\Components\Select::make('device_target')
                                        ->options([
                                            'all' => 'All Devices',
                                            'mobile' => 'Mobile Only',
                                            'desktop' => 'Desktop Only',
                                        ])
                                        ->default('all')
                                        ->required(),
                                ]),
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('subscription_target')
                                        ->label('Target Subscription Plan')
                                        ->placeholder('e.g. Premium, Pro'),
                                    Forms\Components\TextInput::make('location_target')
                                        ->label('Target Location')
                                        ->placeholder('e.g. Dhaka, Banani'),
                                ]),
                            ]),
                    ])
                ]),

                Forms\Components\Tabs\Tab::make('Monetization & Limits')->schema([
                    Forms\Components\Card::make()->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('monetization_model')
                                ->options([
                                    'fixed' => 'Fixed Duration (Flat)',
                                    'cpc' => 'Cost Per Click (CPC)',
                                    'cpm' => 'Cost Per 1000 Impressions (CPM)',
                                ])
                                ->default('fixed')
                                ->required(),
                            Forms\Components\TextInput::make('rate')
                                ->numeric()
                                ->prefix('৳')
                                ->default(0.00)
                                ->required(),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->default(now())
                                    ->required(),
                                Forms\Components\DatePicker::make('end_date')
                                    ->nullable(),
                            ])->columnSpan(1),
                        ]),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('max_impressions')
                                ->label('Max Impressions Limit')
                                ->numeric()
                                ->nullable(),
                            Forms\Components\TextInput::make('max_clicks')
                                ->label('Max Clicks Limit')
                                ->numeric()
                                ->nullable(),
                        ]),
                    ])
                ]),

            ])->columnSpanFull()
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->searchable(),
            Tables\Columns\TextColumn::make('media_type')->label('Media Type'),
            Tables\Columns\TextColumn::make('monetization_model')->label('Model'),
            Tables\Columns\TextColumn::make('rate')->money('BDT'),
            Tables\Columns\TextColumn::make('total_impressions')->label('Imps')->sortable(),
            Tables\Columns\TextColumn::make('total_clicks')->label('Clicks')->sortable(),
            Tables\Columns\TextColumn::make('ctr')
                ->label('CTR %')
                ->state(function (Ad $record) {
                    if ($record->total_impressions === 0) return '0.00%';
                    $ctr = ($record->total_clicks / $record->total_impressions) * 100;
                    return number_format($ctr, 2) . '%';
                }),
            Tables\Columns\SelectColumn::make('status')
                ->options([
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'paused' => 'Paused',
                ])
                ->updateStateUsing(function (Ad $record, $state) {
                    $old = $record->status;
                    $record->update(['status' => $state]);
                    
                    // Audit and invalidate
                    AdServingService::invalidateAllCaches();
                    
                    \App\Models\AdAudit::create([
                        'ad_id' => $record->id,
                        'user_id' => auth()->id(),
                        'action' => 'status_change',
                        'old_values' => ['status' => $old],
                        'new_values' => ['status' => $state],
                    ]);
                }),
        ])
        ->filters([])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()
                ->after(function () {
                    AdServingService::invalidateAllCaches();
                }),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAds::route('/'),
            'create' => Pages\CreateAd::route('/create'),
            'edit' => Pages\EditAd::route('/{record}/edit'),
        ];
    }
}
