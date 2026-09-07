<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobResource\Pages;
use App\Models\Category;
use App\Models\Company;
use App\Models\Job;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class JobResource extends Resource
{
    protected static ?string $model = Job::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('is_remote_project', false);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic Information / সাধারণ তথ্য')
                ->schema([
                    Forms\Components\Select::make('category_id')
                        ->label('Job Category / চাকরির ক্যাটাগরি')
                        ->relationship('category', 'name_en')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->name_bn ? "{$record->name_bn} ({$record->name_en})" : ($record->name_en ?? "Category #{$record->id}"))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('সরকারি চাকরির জন্য "Govt. Jobs (সরকারি চাকরি)" নির্বাচন করুন।'),

                    Forms\Components\Select::make('company_id')
                        ->label('Company / Organization / প্রতিষ্ঠান বা মন্ত্রণালয়')
                        ->relationship('company', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->name_bn ? "{$record->name} ({$record->name_bn})" : $record->name)
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('সরকারি চাকরির জন্য "বাংলাদেশ সরকার (Government of Bangladesh)" বা সংশ্লিষ্ট মন্ত্রণালয় নির্বাচন করুন। না থাকলে (+) দিয়ে নতুন তৈরি করুন।')
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label('Organization / Ministry Name (ইংরেজি বা বাংলা)')
                                ->required(),
                            Forms\Components\TextInput::make('name_bn')
                                ->label('বাংলা নাম (যেমন: গণপ্রজাতন্ত্রী বাংলাদেশ সরকার / জনপ্রশাসন মন্ত্রণালয়)')
                                ->nullable(),
                            Forms\Components\Select::make('company_type')
                                ->label('Organization Type')
                                ->options([
                                    'Government' => 'সরকারি (Government / Ministry / Department)',
                                    'Private' => 'বেসরকারি (Private Limited)',
                                    'Semi-Government' => 'আধাসরকারি (Semi-Government)',
                                    'Autonomous' => 'স্বায়ত্তশাসিত (Autonomous)',
                                    'NGO' => 'এনজিও (NGO)',
                                ])
                                ->default('Government')
                                ->required(),
                            Forms\Components\TextInput::make('location')
                                ->default('Dhaka, Bangladesh')
                                ->required(),
                            Forms\Components\TextInput::make('website')
                                ->url()
                                ->nullable(),
                        ])
                        ->createOptionUsing(function (array $data) {
                            $slug = Str::slug($data['name']);
                            if (empty($slug)) {
                                $slug = 'org-' . time();
                            }
                            $count = Company::where('slug', 'LIKE', "{$slug}%")->count();
                            if ($count > 0) {
                                $slug .= '-' . ($count + 1);
                            }
                            $userId = auth()->id() ?? (\App\Models\User::where('role', 'admin')->value('id') ?? 1);
                            return Company::create([
                                'user_id' => $userId,
                                'name' => $data['name'],
                                'name_bn' => $data['name_bn'] ?? null,
                                'slug' => $slug,
                                'company_type' => $data['company_type'] ?? 'Government',
                                'location' => $data['location'] ?? 'Dhaka, Bangladesh',
                                'website' => $data['website'] ?? null,
                                'is_verified' => true,
                                'allow_job_posting' => true,
                            ])->id;
                        }),

                    Forms\Components\TextInput::make('title')
                        ->label('Job Title / পদের নাম')
                        ->placeholder('যেমন: সহকারী পরিচালক (Assistant Director)')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, Forms\Set $set, $operation) => $operation === 'create' ? $set('slug', Str::slug($state) . '-' . Str::random(5)) : null),

                    Forms\Components\TextInput::make('slug')
                        ->label('URL Slug')
                        ->required()
                        ->unique(Job::class, 'slug', ignoreRecord: true),

                    Forms\Components\Select::make('job_type')
                        ->label('Job Type / চাকরির ধরন')
                        ->options([
                            'Government' => 'সরকারি চাকরি (Government Job)',
                            'Full-time' => 'ফুল-টাইম (Full-time)',
                            'Part-time' => 'পার্ট-টাইম (Part-time)',
                            'Contractual' => 'চুক্তিভিত্তিক (Contractual)',
                            'Internship' => 'ইন্টার্নশিপ (Internship)',
                            'Remote' => 'রিমোট (Remote)',
                        ])
                        ->default('Government')
                        ->required()
                        ->searchable(),

                    Forms\Components\TextInput::make('vacancies')
                        ->label('Vacancies / পদ সংখ্যা')
                        ->numeric()
                        ->placeholder('e.g. 5, 20 বা খালি রাখুন')
                        ->nullable(),

                    Forms\Components\TextInput::make('salary_range')
                        ->label('Salary / বেতন স্কেল')
                        ->placeholder('e.g. ২২,০০০ - ৫৩,০৬০ টাকা (গ্রেড-৯) বা Negotiable'),

                    Forms\Components\TextInput::make('location')
                        ->label('Location / কর্মস্থল')
                        ->default('Dhaka, Bangladesh')
                        ->required(),

                    Forms\Components\DatePicker::make('deadline')
                        ->label('Application Deadline / আবেদনের শেষ সময়')
                        ->required(),

                    Forms\Components\TextInput::make('application_url')
                        ->label('Circular / Online Application Link (অনলাইন আবেদনের বা সার্কুলারের লিংক)')
                        ->placeholder('https://alljobs.teletalk.com.bd/... অথবা সরকারি ওয়েবসাইটের লিংক')
                        ->url()
                        ->nullable()
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active / প্রকাশিত')
                        ->default(true)
                        ->afterStateUpdated(function ($state, $old, $set, $record) {
                            if (!$record) return;
                            $brandName = Setting::where('key', 'site_name')->value('value') ?? config('app.name', 'eJobs');
                            $employer = $record->company?->owner;
                            if (!$employer || !$employer->email) return;

                            $dashboardUrl = config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/employer/manage-jobs';

                            if ($state && !$old) {
                                // Job approved
                                try {
                                    Mail::send('emails.job_approved', [
                                        'brandName' => $brandName,
                                        'employerName' => $employer->name,
                                        'jobTitle' => $record->title,
                                        'dashboardUrl' => $dashboardUrl,
                                    ], function ($message) use ($employer, $brandName) {
                                        $message->to($employer->email)
                                                ->subject("{$brandName} — Your job has been approved!");
                                    });
                                } catch (\Throwable $e) {
                                    Log::error("Job approval email failed for job {$record->id}: " . $e->getMessage());
                                }
                            } elseif (!$state && $old) {
                                // Job disabled/rejected
                                try {
                                    Mail::send('emails.job_removed', [
                                        'brandName' => $brandName,
                                        'employerName' => $employer->name,
                                        'jobTitle' => $record->title,
                                        'action' => 'rejected',
                                        'reason' => '',
                                        'dashboardUrl' => $dashboardUrl,
                                    ], function ($message) use ($employer, $brandName) {
                                        $message->to($employer->email)
                                                ->subject("{$brandName} — Your job has been removed");
                                    });
                                } catch (\Throwable $e) {
                                    Log::error("Job removal email failed for job {$record->id}: " . $e->getMessage());
                                }
                            }
                        }),
                ])->columns(2),

            Forms\Components\Section::make('Job Details & Requirements / বিস্তারিত বিবরণ ও যোগ্যতা')
                ->schema([
                    Forms\Components\RichEditor::make('description')
                        ->label('Job Description / সার্কুলার বিবরণ')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('education_requirements')
                        ->label('Educational Requirements / শিক্ষাগত যোগ্যতা')
                        ->rows(3),

                    Forms\Components\Textarea::make('experience_requirements')
                        ->label('Experience / অন্যান্য শর্তাবলী')
                        ->rows(3),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('company.name')->label('Company / Organization')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('category.name_bn')
                ->label('Category')
                ->badge()
                ->color(fn ($state) => $state === 'সরকারি চাকরি' ? 'success' : 'gray')
                ->sortable(),
            Tables\Columns\TextColumn::make('job_type')->badge()->sortable(),
            Tables\Columns\IconColumn::make('is_active')->boolean()->sortable(),
            Tables\Columns\TextColumn::make('deadline')->date()->sortable(),
        ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name_en')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name_bn ? "{$record->name_bn} ({$record->name_en})" : $record->name_en),
                Tables\Filters\SelectFilter::make('job_type')
                    ->options([
                        'Government' => 'সরকারি চাকরি (Government Job)',
                        'Full-time' => 'ফুল-টাইম (Full-time)',
                        'Part-time' => 'পার্ট-টাইম (Part-time)',
                        'Contractual' => 'চুক্তিভিত্তিক (Contractual)',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobs::route('/'),
            'create' => Pages\CreateJob::route('/create'), // Changed from CreateJobs
            'edit' => Pages\EditJob::route('/{record}/edit'),
        ];
    }
}
