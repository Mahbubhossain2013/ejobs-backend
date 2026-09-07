<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class HomepageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Homepage';
    protected static string $view = 'filament.pages.homepage-settings';
    protected static ?string $title = 'Homepage Control Panel';
    protected static ?string $slug = 'menu/homepage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::all();
        $data = [];
        foreach ($settings as $setting) {
            $decoded = json_decode($setting->value, true);
            $data[$setting->key] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $setting->value;
        }

        if (!isset($data['homepage_trending_searches'])) {
            $data['homepage_trending_searches'] = ['Bank', 'NGO', 'Govt', 'IT'];
        }
        if (!isset($data['homepage_trending_searches_bn'])) {
            $data['homepage_trending_searches_bn'] = ['ব্যাংক চাকরি', 'এনজিও চাকরি', 'সরকারি চাকরি', 'আইটি চাকরি'];
        }

        if (!isset($data['homepage_notices'])) {
            $data['homepage_notices'] = [
                [
                    'category' => 'Recruitment',
                    'category_bn' => 'নিয়োগ',
                    'title' => 'Bangladesh Police 1,000+ Constable Openings',
                    'title_bn' => 'বাংলাদেশ পুলিশে ১,০০০+ কনস্টেবল নিয়োগ ২০২৪',
                    'published_at' => '2026-05-26',
                ],
                [
                    'category' => 'Recruitment',
                    'category_bn' => 'নিয়োগ',
                    'title' => 'BCS 47th Competitive Circular Published',
                    'title_bn' => 'বিসিএস ৪৭তম বিসিএস নিয়োগ বিজ্ঞপ্তি প্রকাশ',
                    'published_at' => '2026-05-25',
                ],
                [
                    'category' => 'Notice',
                    'category_bn' => 'নোটিশ',
                    'title' => 'Primary School Teacher Exam Schedule Updated',
                    'title_bn' => 'প্রাথমিক শিক্ষক নিয়োগ পরীক্ষার তারিখ পরিবর্তন',
                    'published_at' => '2026-05-23',
                ]
            ];
        }

        if (!isset($data['homepage_features_grid'])) {
            $data['homepage_features_grid'] = [
                [
                    'title' => 'Video CV',
                    'title_bn' => 'ভিডিও সিভি',
                    'description' => 'Introduce yourself through video and boost application callback rates.',
                    'description_bn' => 'ভিডিওর মাধ্যমে নিজেকে উপস্থাপন করুন এবং চাকরির সুযোগ বাড়ান',
                    'bg_color' => '#ECFDF5',
                    'text_color' => '#1F2937',
                    'bg_color_dark' => '#064E3B',
                    'text_color_dark' => '#F3F4F6',
                    'button_text' => 'Generate Video CV',
                    'button_text_bn' => 'ভিডিও সিভি তৈরি করুন',
                    'button_bg' => '#10B981',
                    'button_text_color' => '#FFFFFF',
                    'button_bg_dark' => '#059669',
                    'button_text_color_dark' => '#FFFFFF',
                    'icon' => 'monitor-play',
                    'url' => '/dashboard/cv-builder',
                    'enabled' => true,
                ],
                [
                    'title' => 'CV Builder',
                    'title_bn' => 'সিভি তৈরি',
                    'description' => 'Create dynamic, tailored professional CVs easily with AI.',
                    'description_bn' => 'প্রফেশনাল সিভি তৈরি করুন খুব সহজেই!',
                    'bg_color' => '#EFF6FF',
                    'text_color' => '#1F2937',
                    'bg_color_dark' => '#1E3A8A',
                    'text_color_dark' => '#F3F4F6',
                    'button_text' => 'Build Resume',
                    'button_text_bn' => 'সিভি তৈরি করুন',
                    'button_bg' => '#2563EB',
                    'button_text_color' => '#FFFFFF',
                    'button_bg_dark' => '#1D4ED8',
                    'button_text_color_dark' => '#FFFFFF',
                    'icon' => 'file-text',
                    'url' => '/dashboard/cv-builder',
                    'enabled' => true,
                ],
                [
                    'title' => 'Post a Job',
                    'title_bn' => 'চাকরি পোস্ট করুন',
                    'description' => 'Find and secure standard talent for your organizations effortlessly.',
                    'description_bn' => 'আপনার প্রতিষ্ঠানের জন্য সেরা প্রার্থী খুঁজে নিন সহজেই',
                    'bg_color' => '#F5F3FF',
                    'text_color' => '#1F2937',
                    'bg_color_dark' => '#581C87',
                    'text_color_dark' => '#F3F4F6',
                    'button_text' => 'Hire Talent',
                    'button_text_bn' => 'চাকরি পোস্ট করুন',
                    'button_bg' => '#9333EA',
                    'button_text_color' => '#FFFFFF',
                    'button_bg_dark' => '#7E22CE',
                    'button_text_color_dark' => '#FFFFFF',
                    'icon' => 'briefcase',
                    'url' => '/login',
                    'enabled' => true,
                ],
                [
                    'title' => 'Job Alert',
                    'title_bn' => 'জব এলার্ট',
                    'description' => 'Get customized daily alert updates matching your specific interest parameters.',
                    'description_bn' => 'আপনার পছন্দের চাকরির জন্য জব এলার্ট সেট করুন।',
                    'bg_color' => '#FEF3C7',
                    'text_color' => '#1F2937',
                    'bg_color_dark' => '#78350F',
                    'text_color_dark' => '#F3F4F6',
                    'button_text' => 'Set Alert',
                    'button_text_bn' => 'এলার্ট সেট করুন',
                    'button_bg' => '#D97706',
                    'button_text_color' => '#FFFFFF',
                    'button_bg_dark' => '#B45309',
                    'button_text_color_dark' => '#FFFFFF',
                    'icon' => 'bell',
                    'url' => '/dashboard/job-alerts',
                    'enabled' => true,
                ],
                [
                    'title' => 'Premium Banner',
                    'title_bn' => 'প্রিমিয়াম ব্যানার',
                    'description' => 'Boost company branding visibility with featured banner slots.',
                    'description_bn' => 'আপনার প্রতিষ্ঠানের ব্র্যান্ডিং বাড়ান প্রিমিয়াম ব্যানারের মাধ্যমে',
                    'bg_color' => '#EFF6FF',
                    'text_color' => '#1F2937',
                    'bg_color_dark' => '#0C4A6E',
                    'text_color_dark' => '#F3F4F6',
                    'button_text' => 'Advertise',
                    'button_text_bn' => 'বিজ্ঞাপন দিন',
                    'button_bg' => '#3B82F6',
                    'button_text_color' => '#FFFFFF',
                    'button_bg_dark' => '#0284C7',
                    'button_text_color_dark' => '#FFFFFF',
                    'icon' => 'presentation',
                    'url' => '/login',
                    'enabled' => true,
                ]
            ];
        }

        if (!isset($data['homepage_offer_banner'])) {
            $data['homepage_offer_banner'] = [
                'title' => 'Advertise Your Company Vacancy',
                'title_bn' => 'আপনার প্রতিষ্ঠানের বিজ্ঞাপন দিন',
                'subtitle' => 'Reach Millions of Active Job Seekers',
                'subtitle_bn' => 'লক্ষ লক্ষ প্রার্থীর কাছে পৌঁছে যান',
                'button_text' => 'Advertise Now',
                'button_text_bn' => 'বিজ্ঞাপন দিন',
                'bg_color' => '#0F172A',
                'text_color' => '#FFFFFF',
                'discount_tag' => 'Special Offer',
                'discount_tag_bn' => 'বিশেষ অফার',
                'discount_text' => '50% OFF',
                'discount_text_bn' => '৫০% ছাড়',
                'discount_subtext' => 'Limited Time Period Only',
                'discount_subtext_bn' => 'সীমিত সময়ের জন্য',
                'gradient_from' => '#0F4C81',
                'gradient_via' => '#1E6CA8',
                'gradient_to' => '#2563EB',
            ];
        }

        if (!isset($data['homepage_quick_links'])) {
            $data['homepage_quick_links'] = [
                [
                    'title' => 'All Govt. Job Notices',
                    'title_bn' => 'সরকারি চাকরির সকল নোটিশ',
                    'url' => '/jobs?type=Govt',
                    'icon' => 'file-text',
                    'bg_color' => '#ECFDF5',
                    'text_color' => '#065F46',
                    'icon_color' => '#059669',
                ],
                [
                    'title' => 'All Bank Career Listings',
                    'title_bn' => 'ব্যাংক চাকরির নিয়োগ বিজ্ঞপ্তি',
                    'url' => '/jobs?search=Bank',
                    'icon' => 'landmark',
                    'bg_color' => '#EFF6FF',
                    'text_color' => '#1E3A8A',
                    'icon_color' => '#2563EB',
                ],
                [
                    'title' => 'NGO Jobs & Openings',
                    'title_bn' => 'এনজিও চাকরির খবর',
                    'url' => '/jobs?type=NGO',
                    'icon' => 'users',
                    'bg_color' => '#F5F3FF',
                    'text_color' => '#581C87',
                    'icon_color' => '#7C3AED',
                ],
                [
                    'title' => 'Academic & Teaching Jobs',
                    'title_bn' => 'শিক্ষক নিয়োগ ও বিজ্ঞপ্তি',
                    'url' => '/jobs?search=Teacher',
                    'icon' => 'book-open',
                    'bg_color' => '#FEF3C7',
                    'text_color' => '#78350F',
                    'icon_color' => '#D97706',
                ],
                [
                    'title' => 'AI Career Prep Guide',
                    'title_bn' => 'চাকরির প্রস্তুতি গাইড (AI)',
                    'url' => '/ai-assistant',
                    'icon' => 'target',
                    'bg_color' => '#FDF2F8',
                    'text_color' => '#9D174D',
                    'icon_color' => '#EC4899',
                ],
                [
                    'title' => 'Free Dynamic CV Templates',
                    'title_bn' => 'ফ্রি সিভি টেমপ্লেট',
                    'url' => '/dashboard/cv-templates',
                    'icon' => 'layout-template',
                    'bg_color' => '#F0FDF4',
                    'text_color' => '#166534',
                    'icon_color' => '#16A34A',
                ]
            ];
        }

        if (!isset($data['homepage_recruitment_callout'])) {
            $data['homepage_recruitment_callout'] = [
                'title' => 'Looking for Top Talent?',
                'title_bn' => 'প্রতিভা খুঁজছেন?',
                'description' => 'Connect with the standard qualified applicants today.',
                'description_bn' => 'সেরা প্রার্থীদের সাথে ক্যারিয়ার গড়ুন',
                'button_text' => 'Post Vacancy',
                'button_text_bn' => 'পোস্ট জব করুন',
                'button_url' => '/login',
                'bg_color' => '#12684F',
                'text_color' => '#FFFFFF',
                'icon' => 'megaphone',
                'gradient_from' => '#10B981',
                'gradient_via' => '#059669',
                'gradient_to' => '#047857',
            ];
        }

        if (!isset($data['homepage_hero_section'])) {
            $data['homepage_hero_section'] = [
                'image' => null,
                'show_box' => true,
                'box_offset' => -64,
                'title_line1' => "Bangladesh's Best",
                'title_line1_bn' => 'বাংলাদেশের সেরা',
                'title_line2' => 'Job Search Here',
                'title_line2_bn' => 'চাকরির খোঁজ এখানেই',
                'subtitle' => 'Search easily, apply fast, build your dream career',
                'subtitle_bn' => 'সহজে খুঁজুন, দ্রুত আবেদন করুন, স্বপ্নের ক্যারিয়ার গড়ুন',
                'box_title' => 'Why join us?',
                'box_title_bn' => 'আমাদের সাথে কেন থাকবেন?',
                'bullet_points' => [
                    ['text' => 'All types of job updates', 'text_bn' => 'সকল ধরনের চাকরির আপডেট'],
                    ['text' => 'Direct hiring from top companies', 'text_bn' => 'শীর্ষ কোম্পানির সরাসরি নিয়োগ'],
                    ['text' => 'Easy CV creation & application process', 'text_bn' => 'সহজে সিভি তৈরি ও আবেদন প্রক্রিয়া'],
                    ['text' => 'Quick connection with employers', 'text_bn' => 'নিয়োগকর্তাদের সাথে দ্রুত সংযোগ'],
                ],
                'gradient_from' => '#0F172A',
                'gradient_via' => '#1E293B',
                'gradient_to' => '#334155',
            ];
        }

        if (!isset($data['homepage_ai_assistant'])) {
            $data['homepage_ai_assistant'] = [
                'enabled' => true,
                'badge' => 'New Feature',
                'badge_bn' => 'নতুন ফিচার',
                'title' => 'Smart AI Career Assistant',
                'title_bn' => 'স্মার্ট এআই ক্যারিয়ার সহকারী',
                'description' => 'Chat with our AI career assistant to prepare for interviews, optimize your CV, predict salaries, and receive personalized career guidance.',
                'description_bn' => 'আপনার জীবনবৃত্তান্ত তৈরি, ইন্টারভিউ প্রস্তুতি, বেতন সম্পর্কে ধারণা এবং ক্যারিয়ার গাইডেন্সের জন্য চ্যাট করুন আমাদের এআই সহকারীর সাথে।',
                'button_text' => 'Chat with AI Now',
                'button_text_bn' => 'এআই এর সাথে কথা বলুন',
                'button_url' => '/ai-assistant',
                'button2_text' => 'Career Roadmap',
                'button2_text_bn' => 'ক্যারিয়ার রোডম্যাপ',
                'button2_url' => '/ai-assistant',
                'gradient_from' => '#6366F1',
                'gradient_via' => '#7C3AED',
                'gradient_to' => '#D946EF',
            ];
        }

        if (!isset($data['homepage_newsletter'])) {
            $data['homepage_newsletter'] = [
                'enabled' => true,
                'title' => 'Subscribe for Job Updates',
                'title_bn' => 'চাকরির খবর পেতে সাবস্ক্রাইব করুন',
                'description' => 'Subscribe now to get regular job news and updates',
                'description_bn' => 'নিয়মিত চাকরির খবর ও আপডেট পেতে এখনই সাবস্ক্রাইব করুন',
                'bg_color' => '#059669',
                'button_text' => 'Subscribe',
                'button_text_bn' => 'সাবস্ক্রাইব করুন',
            ];
        }

        if (!isset($data['homepage_categories'])) {
            $data['homepage_categories'] = [
                'enabled' => true,
                'card_bg' => '#F0FDF4',
                'card_text' => '#1F2937',
                'card_bg_dark' => '#022C22',
                'card_text_dark' => '#F3F4F6',
                'icon_bg' => '#FFFFFF',
                'icon_color' => '#059669',
                'button_border' => '#059669',
                'button_text' => '#059669',
                'heading_color' => '#1F2937',
                'count_color' => '#6B7280',
                'use_gradient' => false,
                'gradient_from' => '#0F172A',
                'gradient_via' => '#1E293B',
                'gradient_to' => '#334155',
            ];
        }

        if (!isset($data['homepage_hot_jobs'])) {
            $data['homepage_hot_jobs'] = [
                'enabled' => true,
                'heading_color' => '#1F2937',
                'heading_icon_color' => '#F97316',
                'card_bg' => '#FFFFFF',
                'card_text' => '#1F2937',
                'card_bg_dark' => '#18181B',
                'card_text_dark' => '#F3F4F6',
                'salary_color' => '#059669',
                'badge_bg' => '#FEF3C7',
                'badge_text' => '#D97706',
                'view_all_color' => '#059669',
            ];
        }

        if (!isset($data['homepage_remote_jobs'])) {
            $data['homepage_remote_jobs'] = [
                'enabled' => true,
                'heading_color' => '#1F2937',
                'heading_icon_color' => '#3B82F6',
                'card_bg' => '#FFFFFF',
                'card_text' => '#1F2937',
                'card_bg_dark' => '#18181B',
                'card_text_dark' => '#F3F4F6',
                'salary_color' => '#059669',
                'date_color' => '#6B7280',
                'badge_bg' => '#DBEAFE',
                'badge_text' => '#1D4ED8',
            ];
        }

        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Homepage Configuration')->tabs([

                Forms\Components\Tabs\Tab::make('Hero Section')->schema([
                    Forms\Components\Section::make('Hero Section Configuration')
                        ->description('Configure the main hero banner at the top of the homepage.')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\FileUpload::make('homepage_hero_section.image')
                                    ->label('Hero Background Image')
                                    ->image()
                                    ->directory('hero')
                                    ->helperText('Recommended: 1920x1080px. Gradient fallback if empty.')
                                    ->columnSpan(1),
                                Forms\Components\Placeholder::make('hero_preview')
                                    ->label('Image Preview')
                                    ->content(function (callable $get): string {
                                        $image = $get('homepage_hero_section.image');

                                        if (!$image) {
                                            return 'No image (gradient fallback)';
                                        }

                                        $path = is_array($image) ? reset($image) : $image;

                                        return 'Image: ' . $path;
                                    })
                                    ->columnSpan(1),
                            ]),
                            Forms\Components\Toggle::make('homepage_hero_section.show_box')
                                ->label('Show "Why Join Us" Box')
                                ->default(true),
                            Forms\Components\TextInput::make('homepage_hero_section.box_offset')
                                ->label('Box Position Offset (px)')
                                ->default(-64)
                                ->numeric()
                                ->suffix('px'),
                            Forms\Components\Section::make('Title Text')->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('homepage_hero_section.title_line1')->label('Title Line 1 (EN)')->required(),
                                    Forms\Components\TextInput::make('homepage_hero_section.title_line1_bn')->label('Title Line 1 (BN)')->required(),
                                ]),
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('homepage_hero_section.title_line2')->label('Title Line 2 (EN)')->required(),
                                    Forms\Components\TextInput::make('homepage_hero_section.title_line2_bn')->label('Title Line 2 (BN)')->required(),
                                ]),
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('homepage_hero_section.subtitle')->label('Subtitle (EN)')->required(),
                                    Forms\Components\TextInput::make('homepage_hero_section.subtitle_bn')->label('Subtitle (BN)')->required(),
                                ]),
                            ]),
                            Forms\Components\Section::make('Glass Box (Join Us)')->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('homepage_hero_section.box_title')->label('Box Title (EN)')->required(),
                                    Forms\Components\TextInput::make('homepage_hero_section.box_title_bn')->label('Box Title (BN)')->required(),
                                ]),
                                Forms\Components\Repeater::make('homepage_hero_section.bullet_points')
                                    ->label('Bullet Points')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('text')->label('Text (EN)')->required(),
                                            Forms\Components\TextInput::make('text_bn')->label('Text (BN)')->required(),
                                        ]),
                                    ])
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['text'] ?? null),
                            ]),
                        ])
                ]),

                Forms\Components\Tabs\Tab::make('Notice Board')->schema([
                    Forms\Components\Section::make('Homepage Notices')
                        ->description('Configure dynamic notices for the Notice Board section.')
                        ->schema([
                            Forms\Components\Repeater::make('homepage_notices')
                                ->label('Notices')
                                ->schema([
                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\Select::make('category')
                                            ->label('Category (English)')
                                            ->options(['Recruitment' => 'Recruitment', 'Notice' => 'Notice'])
                                            ->default('Notice')
                                            ->required(),
                                        Forms\Components\TextInput::make('category_bn')->label('Category (Bangla)')->required(),
                                    ]),
                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\TextInput::make('title')->label('Title (English)')->required(),
                                        Forms\Components\TextInput::make('title_bn')->label('Title (Bangla)')->required(),
                                    ]),
                                    Forms\Components\DatePicker::make('published_at')->label('Published Date')->default(now())->required(),
                                ])
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                        ])
                ]),

                Forms\Components\Tabs\Tab::make('Trending Searches')->schema([
                    Forms\Components\Section::make('Search Keywords')
                        ->description('Popular terms candidates can click to quick search.')
                        ->headerActions([
                            \Filament\Forms\Components\Actions\Action::make('save_trending')
                                ->label('Save Trending')
                                ->icon('heroicon-o-check')
                                ->color('primary')
                                ->action(fn () => $this->saveTab(['homepage_trending_searches', 'homepage_trending_searches_bn'])),
                        ])
                        ->schema([
                            Forms\Components\TagsInput::make('homepage_trending_searches')->label('Trending Searches (English)')->required(),
                            Forms\Components\TagsInput::make('homepage_trending_searches_bn')->label('Trending Searches (Bangla)')->required(),
                        ])
                ]),

                Forms\Components\Tabs\Tab::make('Service Feature Grid')->schema([
                    Forms\Components\Section::make('Feature Cards')
                        ->description('Configure the core feature cards at the top.')
                        ->headerActions([
                            \Filament\Forms\Components\Actions\Action::make('save_features')
                                ->label('Save Feature Grid')
                                ->icon('heroicon-o-check')
                                ->color('primary')
                                ->action(fn () => $this->saveTab(['homepage_features_grid'])),
                        ])
                        ->schema([
                            Forms\Components\Repeater::make('homepage_features_grid')
                                ->label('Grid Cards')
                                ->schema([
                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\TextInput::make('title')->label('Title (EN)')->required(),
                                        Forms\Components\TextInput::make('title_bn')->label('Title (BN)')->required(),
                                        Forms\Components\Select::make('icon')
                                            ->label('Icon')
                                            ->options([
                                                'monitor-play' => 'MonitorPlay', 'file-text' => 'FileText', 'briefcase' => 'Briefcase',
                                                'bell' => 'Bell', 'presentation' => 'Presentation', 'megaphone' => 'Megaphone',
                                                'target' => 'Target', 'sparkles' => 'Sparkles', 'search' => 'Search',
                                                'building-2' => 'Building2', 'trending-up' => 'TrendingUp', 'landmark' => 'Landmark',
                                                'users' => 'Users', 'globe' => 'Globe',
                                            ])
                                            ->required(),
                                    ]),
                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\Textarea::make('description')->label('Description (EN)')->rows(2)->required(),
                                        Forms\Components\Textarea::make('description_bn')->label('Description (BN)')->rows(2)->required(),
                                    ]),
                                    Forms\Components\Grid::make(4)->schema([
                                        Forms\Components\ColorPicker::make('bg_color')->label('Card BG (Light)')->required(),
                                        Forms\Components\ColorPicker::make('text_color')->label('Card Text (Light)')->required(),
                                        Forms\Components\ColorPicker::make('button_bg')->label('Button BG (Light)')->required(),
                                        Forms\Components\ColorPicker::make('button_text_color')->label('Button Text (Light)')->required(),
                                    ]),
                                    Forms\Components\Grid::make(4)->schema([
                                        Forms\Components\ColorPicker::make('bg_color_dark')->label('Card BG (Dark)')->required(),
                                        Forms\Components\ColorPicker::make('text_color_dark')->label('Card Text (Dark)')->required(),
                                        Forms\Components\ColorPicker::make('button_bg_dark')->label('Button BG (Dark)')->required(),
                                        Forms\Components\ColorPicker::make('button_text_color_dark')->label('Button Text (Dark)')->required(),
                                    ]),
                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\TextInput::make('button_text')->label('Button Text (EN)')->required(),
                                        Forms\Components\TextInput::make('button_text_bn')->label('Button Text (BN)')->required(),
                                        Forms\Components\TextInput::make('url')->label('Target URL')->required(),
                                    ]),
                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\ColorPicker::make('gradient_from')->label('Gradient From')->default(null),
                                        Forms\Components\ColorPicker::make('gradient_via')->label('Gradient Via')->default(null),
                                        Forms\Components\ColorPicker::make('gradient_to')->label('Gradient To')->default(null),
                                    ]),
                                    Forms\Components\Toggle::make('enabled')->label('Card Enabled')->default(true),
                                ])
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                        ])
                ]),

                Forms\Components\Tabs\Tab::make('Advertise Banner')->schema([
                    Forms\Components\Section::make('Corporate Offer Banner')
                        ->description('Configure the middle full-width offer banner.')
                        ->headerActions([
                            \Filament\Forms\Components\Actions\Action::make('save_banner')
                                ->label('Save Banner')
                                ->icon('heroicon-o-check')
                                ->color('primary')
                                ->action(fn () => $this->saveTab(['homepage_offer_banner'])),
                        ])
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('homepage_offer_banner.title')->label('Banner Title (EN)')->required(),
                                Forms\Components\TextInput::make('homepage_offer_banner.title_bn')->label('Banner Title (BN)')->required(),
                                Forms\Components\TextInput::make('homepage_offer_banner.subtitle')->label('Subtitle (EN)')->required(),
                                Forms\Components\TextInput::make('homepage_offer_banner.subtitle_bn')->label('Subtitle (BN)')->required(),
                            ]),
                            Forms\Components\Grid::make(4)->schema([
                                Forms\Components\ColorPicker::make('homepage_offer_banner.bg_color')->label('BG Color')->required(),
                                Forms\Components\ColorPicker::make('homepage_offer_banner.text_color')->label('Text Color')->required(),
                                Forms\Components\TextInput::make('homepage_offer_banner.button_text')->label('Button (EN)')->required(),
                                Forms\Components\TextInput::make('homepage_offer_banner.button_text_bn')->label('Button (BN)')->required(),
                            ]),
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\TextInput::make('homepage_offer_banner.discount_tag')->label('Discount Tag (EN)')->required(),
                                Forms\Components\TextInput::make('homepage_offer_banner.discount_tag_bn')->label('Discount Tag (BN)')->required(),
                                Forms\Components\TextInput::make('homepage_offer_banner.discount_text')->label('Discount Text (e.g. 50% OFF)')->required(),
                            ]),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('homepage_offer_banner.discount_subtext')->label('Subtext (EN)')->required(),
                                Forms\Components\TextInput::make('homepage_offer_banner.discount_subtext_bn')->label('Subtext (BN)')->required(),
                            ])
                        ])
                ]),

                Forms\Components\Tabs\Tab::make('Quick Links Directory')->schema([
                    Forms\Components\Section::make('Quick Links')
                        ->description('Customize important quick links.')
                        ->headerActions([
                            \Filament\Forms\Components\Actions\Action::make('save_links')
                                ->label('Save Quick Links')
                                ->icon('heroicon-o-check')
                                ->color('primary')
                                ->action(fn () => $this->saveTab(['homepage_quick_links'])),
                        ])
                        ->schema([
                            Forms\Components\Repeater::make('homepage_quick_links')
                                ->label('Directory Links')
                                ->schema([
                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\TextInput::make('title')->label('Link Title (EN)')->required(),
                                        Forms\Components\TextInput::make('title_bn')->label('Link Title (BN)')->required(),
                                        Forms\Components\TextInput::make('url')->label('URL Path')->required(),
                                    ]),
                                    Forms\Components\Grid::make(4)->schema([
                                        Forms\Components\Select::make('icon')
                                            ->label('Icon')
                                            ->options([
                                                'file-text' => 'FileText', 'landmark' => 'Landmark', 'users' => 'Users',
                                                'book-open' => 'BookOpen', 'target' => 'Target', 'layout-template' => 'LayoutTemplate',
                                                'cpu' => 'Cpu', 'globe' => 'Globe',
                                            ])
                                            ->required(),
                                        Forms\Components\ColorPicker::make('bg_color')->label('Card BG'),
                                        Forms\Components\ColorPicker::make('text_color')->label('Card Text'),
                                        Forms\Components\ColorPicker::make('icon_color')->label('Icon Color'),
                                    ])
                                ])
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                        ])
                ]),

                Forms\Components\Tabs\Tab::make('Recruitment Callout')->schema([
                    Forms\Components\Section::make('Callout Configuration')
                        ->description('Customize the recruitment callout card.')
                        ->headerActions([
                            \Filament\Forms\Components\Actions\Action::make('save_callout')
                                ->label('Save Callout')
                                ->icon('heroicon-o-check')
                                ->color('primary')
                                ->action(fn () => $this->saveTab(['homepage_recruitment_callout'])),
                        ])
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('homepage_recruitment_callout.title')->label('Title (EN)')->required(),
                                Forms\Components\TextInput::make('homepage_recruitment_callout.title_bn')->label('Title (BN)')->required(),
                            ]),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('homepage_recruitment_callout.description')->label('Description (EN)')->required(),
                                Forms\Components\TextInput::make('homepage_recruitment_callout.description_bn')->label('Description (BN)')->required(),
                            ]),
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\TextInput::make('homepage_recruitment_callout.button_text')->label('Button (EN)')->required(),
                                Forms\Components\TextInput::make('homepage_recruitment_callout.button_text_bn')->label('Button (BN)')->required(),
                                Forms\Components\TextInput::make('homepage_recruitment_callout.button_url')->label('Button URL')->required(),
                            ]),
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\ColorPicker::make('homepage_recruitment_callout.bg_color')->label('BG Color')->required(),
                                Forms\Components\ColorPicker::make('homepage_recruitment_callout.text_color')->label('Text Color')->required(),
                                Forms\Components\Select::make('homepage_recruitment_callout.icon')
                                    ->label('Icon')
                                    ->options(['megaphone' => 'Megaphone', 'briefcase' => 'Briefcase', 'users' => 'Users', 'bell' => 'Bell'])
                                    ->required(),
                            ])
                        ])
                ]),

                Forms\Components\Tabs\Tab::make('AI Assistant')->schema([
                    Forms\Components\Section::make('AI Assistant Banner')
                        ->description('Configure the AI assistant promotional section.')
                        ->headerActions([
                            \Filament\Forms\Components\Actions\Action::make('save_ai')
                                ->label('Save AI Assistant')
                                ->icon('heroicon-o-check')
                                ->color('primary')
                                ->action(fn () => $this->saveTab(['homepage_ai_assistant'])),
                        ])
                        ->schema([
                            Forms\Components\Toggle::make('homepage_ai_assistant.enabled')->label('Show AI Assistant Section')->default(true),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('homepage_ai_assistant.badge')->label('Badge (EN)')->required(),
                                Forms\Components\TextInput::make('homepage_ai_assistant.badge_bn')->label('Badge (BN)')->required(),
                            ]),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('homepage_ai_assistant.title')->label('Title (EN)')->required(),
                                Forms\Components\TextInput::make('homepage_ai_assistant.title_bn')->label('Title (BN)')->required(),
                            ]),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Textarea::make('homepage_ai_assistant.description')->label('Description (EN)')->rows(2)->required(),
                                Forms\Components\Textarea::make('homepage_ai_assistant.description_bn')->label('Description (BN)')->rows(2)->required(),
                            ]),
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\TextInput::make('homepage_ai_assistant.button_text')->label('Primary Button (EN)')->required(),
                                Forms\Components\TextInput::make('homepage_ai_assistant.button_text_bn')->label('Primary Button (BN)')->required(),
                                Forms\Components\TextInput::make('homepage_ai_assistant.button_url')->label('Primary URL')->required(),
                            ]),
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\TextInput::make('homepage_ai_assistant.button2_text')->label('Secondary Button (EN)')->required(),
                                Forms\Components\TextInput::make('homepage_ai_assistant.button2_text_bn')->label('Secondary Button (BN)')->required(),
                                Forms\Components\TextInput::make('homepage_ai_assistant.button2_url')->label('Secondary URL')->required(),
                            ]),
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\ColorPicker::make('homepage_ai_assistant.gradient_from')->label('Gradient From')->required(),
                                Forms\Components\ColorPicker::make('homepage_ai_assistant.gradient_via')->label('Gradient Via')->required(),
                                Forms\Components\ColorPicker::make('homepage_ai_assistant.gradient_to')->label('Gradient To')->required(),
                            ]),
                        ])
                ]),

                Forms\Components\Tabs\Tab::make('Newsletter')->schema([
                    Forms\Components\Section::make('Newsletter Subscription Bar')
                        ->description('Configure the newsletter bar at the bottom.')
                        ->headerActions([
                            \Filament\Forms\Components\Actions\Action::make('save_newsletter')
                                ->label('Save Newsletter')
                                ->icon('heroicon-o-check')
                                ->color('primary')
                                ->action(fn () => $this->saveTab(['homepage_newsletter'])),
                        ])
                        ->schema([
                            Forms\Components\Toggle::make('homepage_newsletter.enabled')->label('Show Newsletter Section')->default(true),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('homepage_newsletter.title')->label('Title (EN)')->required(),
                                Forms\Components\TextInput::make('homepage_newsletter.title_bn')->label('Title (BN)')->required(),
                            ]),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('homepage_newsletter.description')->label('Description (EN)')->required(),
                                Forms\Components\TextInput::make('homepage_newsletter.description_bn')->label('Description (BN)')->required(),
                            ]),
                            Forms\Components\Grid::make(4)->schema([
                                Forms\Components\ColorPicker::make('homepage_newsletter.bg_color')->label('Background Color')->required(),
                                Forms\Components\ColorPicker::make('homepage_newsletter.text_color')->label('Text Color')->default('#FFFFFF'),
                                Forms\Components\TextInput::make('homepage_newsletter.button_text')->label('Button (EN)')->required(),
                                Forms\Components\TextInput::make('homepage_newsletter.button_text_bn')->label('Button (BN)')->required(),
                            ]),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\ColorPicker::make('homepage_newsletter.button_color')->label('Button Color')->default('#FFFFFF'),
                            ]),
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\ColorPicker::make('homepage_newsletter.gradient_from')->label('Gradient From')->default(null),
                                Forms\Components\ColorPicker::make('homepage_newsletter.gradient_via')->label('Gradient Via')->default(null),
                                Forms\Components\ColorPicker::make('homepage_newsletter.gradient_to')->label('Gradient To')->default(null),
                            ]),
                        ])
                ]),

                Forms\Components\Tabs\Tab::make('Categories Section')->schema([
                    Forms\Components\Section::make('Category Cards')
                        ->description('Configure colors for the category grid cards on the homepage.')
                        ->headerActions([
                            \Filament\Forms\Components\Actions\Action::make('save_categories')
                                ->label('Save Categories')
                                ->icon('heroicon-o-check')
                                ->color('primary')
                                ->action(fn () => $this->saveTab(['homepage_categories'])),
                        ])
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\ColorPicker::make('homepage_categories.card_bg')->label('Card Background (Light)')->default('#F0FDF4'),
                                Forms\Components\ColorPicker::make('homepage_categories.card_text')->label('Card Text (Light)')->default('#1F2937'),
                            ]),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\ColorPicker::make('homepage_categories.card_bg_dark')->label('Card Background (Dark)')->default('#022C22'),
                                Forms\Components\ColorPicker::make('homepage_categories.card_text_dark')->label('Card Text (Dark)')->default('#F3F4F6'),
                            ]),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\ColorPicker::make('homepage_categories.icon_bg')->label('Icon Box Background')->default('#FFFFFF'),
                                Forms\Components\ColorPicker::make('homepage_categories.icon_color')->label('Icon Color')->default('#059669'),
                            ]),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\ColorPicker::make('homepage_categories.button_border')->label('Button Border')->default('#059669'),
                                Forms\Components\ColorPicker::make('homepage_categories.button_text')->label('Button Text')->default('#059669'),
                            ]),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\ColorPicker::make('homepage_categories.heading_color')->label('Section Heading Color')->default('#1F2937'),
                                Forms\Components\ColorPicker::make('homepage_categories.count_color')->label('Job Count Color')->default('#6B7280'),
                            ]),
                            Forms\Components\Toggle::make('homepage_categories.use_gradient')->label('Use Gradient Background')->default(false),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\ColorPicker::make('homepage_categories.gradient_from')->label('Gradient From')->default('#0F172A'),
                                Forms\Components\ColorPicker::make('homepage_categories.gradient_via')->label('Gradient Via')->default('#1E293B'),
                            ]),
                            Forms\Components\Grid::make(1)->schema([
                                Forms\Components\ColorPicker::make('homepage_categories.gradient_to')->label('Gradient To')->default('#334155'),
                            ]),
                            Forms\Components\Toggle::make('homepage_categories.enabled')->label('Show Categories Section')->default(true),
                        ])
                ]),

                Forms\Components\Tabs\Tab::make('Hot Jobs Section')->schema([
                    Forms\Components\Section::make('Hot Jobs Cards')
                        ->description('Configure colors for the hot jobs grid cards on the homepage.')
                        ->headerActions([
                            \Filament\Forms\Components\Actions\Action::make('save_hot_jobs')
                                ->label('Save Hot Jobs')
                                ->icon('heroicon-o-check')
                                ->color('primary')
                                ->action(fn () => $this->saveTab(['homepage_hot_jobs'])),
                        ])
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\ColorPicker::make('homepage_hot_jobs.heading_color')->label('Section Heading Color')->default('#1F2937'),
                                Forms\Components\ColorPicker::make('homepage_hot_jobs.heading_icon_color')->label('Flame Icon Color')->default('#F97316'),
                            ]),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\ColorPicker::make('homepage_hot_jobs.card_bg')->label('Card Background (Light)')->default('#FFFFFF'),
                                Forms\Components\ColorPicker::make('homepage_hot_jobs.card_text')->label('Card Text (Light)')->default('#1F2937'),
                            ]),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\ColorPicker::make('homepage_hot_jobs.card_bg_dark')->label('Card Background (Dark)')->default('#18181B'),
                                Forms\Components\ColorPicker::make('homepage_hot_jobs.card_text_dark')->label('Card Text (Dark)')->default('#F3F4F6'),
                            ]),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\ColorPicker::make('homepage_hot_jobs.salary_color')->label('Salary Text Color')->default('#059669'),
                                Forms\Components\ColorPicker::make('homepage_hot_jobs.badge_bg')->label('Promoted Badge BG')->default('#FEF3C7'),
                            ]),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\ColorPicker::make('homepage_hot_jobs.badge_text')->label('Promoted Badge Text')->default('#D97706'),
                                Forms\Components\ColorPicker::make('homepage_hot_jobs.view_all_color')->label('View All Link Color')->default('#059669'),
                            ]),
                            Forms\Components\Toggle::make('homepage_hot_jobs.enabled')->label('Show Hot Jobs Section')->default(true),
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\ColorPicker::make('homepage_hot_jobs.gradient_from')->label('Gradient From')->default(null),
                                Forms\Components\ColorPicker::make('homepage_hot_jobs.gradient_via')->label('Gradient Via')->default(null),
                                Forms\Components\ColorPicker::make('homepage_hot_jobs.gradient_to')->label('Gradient To')->default(null),
                            ]),
                        ])
                ]),

                Forms\Components\Tabs\Tab::make('Remote Jobs Section')->schema([
                    Forms\Components\Section::make('Remote Jobs Cards')
                        ->description('Configure colors for the remote jobs grid cards on the homepage.')
                        ->headerActions([
                            \Filament\Forms\Components\Actions\Action::make('save_remote_jobs')
                                ->label('Save Remote Jobs')
                                ->icon('heroicon-o-check')
                                ->color('primary')
                                ->action(fn () => $this->saveTab(['homepage_remote_jobs'])),
                        ])
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\ColorPicker::make('homepage_remote_jobs.heading_color')->label('Section Heading Color')->default('#1F2937'),
                                Forms\Components\ColorPicker::make('homepage_remote_jobs.heading_icon_color')->label('Globe Icon Color')->default('#3B82F6'),
                            ]),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\ColorPicker::make('homepage_remote_jobs.card_bg')->label('Card Background (Light)')->default('#FFFFFF'),
                                Forms\Components\ColorPicker::make('homepage_remote_jobs.card_text')->label('Card Text (Light)')->default('#1F2937'),
                            ]),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\ColorPicker::make('homepage_remote_jobs.card_bg_dark')->label('Card Background (Dark)')->default('#18181B'),
                                Forms\Components\ColorPicker::make('homepage_remote_jobs.card_text_dark')->label('Card Text (Dark)')->default('#F3F4F6'),
                            ]),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\ColorPicker::make('homepage_remote_jobs.salary_color')->label('Salary Text Color')->default('#059669'),
                                Forms\Components\ColorPicker::make('homepage_remote_jobs.date_color')->label('Date Text Color')->default('#6B7280'),
                            ]),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\ColorPicker::make('homepage_remote_jobs.badge_bg')->label('Remote Badge BG')->default('#DBEAFE'),
                                Forms\Components\ColorPicker::make('homepage_remote_jobs.badge_text')->label('Remote Badge Text')->default('#1D4ED8'),
                            ]),
                            Forms\Components\Toggle::make('homepage_remote_jobs.enabled')->label('Show Remote Jobs Section')->default(true),
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\ColorPicker::make('homepage_remote_jobs.gradient_from')->label('Gradient From')->default(null),
                                Forms\Components\ColorPicker::make('homepage_remote_jobs.gradient_via')->label('Gradient Via')->default(null),
                                Forms\Components\ColorPicker::make('homepage_remote_jobs.gradient_to')->label('Gradient To')->default(null),
                            ]),
                        ])
                ]),

            ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        $this->saveSettings(array_keys($this->form->getState()));
    }

    public function saveTab(array $keys): void
    {
        $this->saveSettings($keys);
    }

    protected function saveSettings(array $keys): void
    {
        $state = $this->form->getState();
        foreach ($keys as $key) {
            if (!array_key_exists($key, $state)) continue;
            $value = $state[$key];
            $storedValue = is_array($value) ? json_encode($value) : $value;
            Setting::updateOrCreate(['key' => $key], ['value' => $storedValue]);
        }

        Cache::forget('homepage_settings');
        Cache::forget('homepage_notices');
        Cache::forget('homepage_quick_links');
        Cache::forget('homepage_remote_jobs');

        Notification::make()
            ->title('Homepage settings saved')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_homepage_settings');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('manage_homepage_settings');
    }
}
