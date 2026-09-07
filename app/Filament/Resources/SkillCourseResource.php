<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SkillCourseResource\Pages;
use App\Models\SkillCourse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SkillCourseResource extends Resource
{
    protected static ?string $model = SkillCourse::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Skill Center';

    protected static ?string $navigationLabel = 'Courses';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Course Details')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('description')
                        ->required()
                        ->rows(3),
                    Forms\Components\Select::make('category')
                        ->options([
                            'welding' => 'Welding',
                            'driving' => 'Driving',
                            'catering' => 'Catering',
                            'electrician' => 'Electrician',
                            'plumbing' => 'Plumbing',
                            'carpentry' => 'Carpentry',
                            'painting' => 'Painting',
                            'masonry' => 'Masonry',
                            'tailoring' => 'Tailoring',
                            'computer' => 'Computer Skills',
                            'other' => 'Other',
                        ])
                        ->required()
                        ->searchable(),
                    Forms\Components\Select::make('difficulty')
                        ->options([
                            'beginner' => 'Beginner',
                            'intermediate' => 'Intermediate',
                            'advanced' => 'Advanced',
                        ])
                        ->required()
                        ->default('beginner'),
                ])->columns(2),

            Forms\Components\Section::make('Pricing & Schedule')
                ->schema([
                    Forms\Components\TextInput::make('duration_hours')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('price')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('currency')
                        ->default('BDT')
                        ->maxLength(3),
                    Forms\Components\TextInput::make('instructor_name')
                        ->maxLength(255),
                ])->columns(4),

            Forms\Components\Section::make('Settings')
                ->schema([
                    Forms\Components\TextInput::make('max_enrollments')
                        ->numeric()
                        ->nullable(),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                    Forms\Components\Toggle::make('is_certified')
                        ->default(true),
                ])->columns(3),

            Forms\Components\Section::make('Lessons & Videos')
                ->schema([
                    Forms\Components\Repeater::make('lessons')
                        ->relationship('lessons')
                        ->defaultItems(1)
                        ->collapsible()
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Textarea::make('content')
                                ->rows(3),
                            Forms\Components\TextInput::make('video_url')
                                ->placeholder('YouTube URL or video URL'),
                            Forms\Components\TextInput::make('duration_minutes')
                                ->numeric(),
                            Forms\Components\TextInput::make('order')
                                ->numeric()
                                ->default(0),
                        ]),

                ]),

            Forms\Components\Section::make('Assessment')
                ->schema([
                    Forms\Components\Repeater::make('assessment')
                        ->relationship('assessment')
                        ->maxItems(1)
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Textarea::make('description')
                                ->rows(2),
                            Forms\Components\TextInput::make('passing_score')
                                ->numeric()
                                ->default(70)
                                ->suffix('%'),
                            Forms\Components\TextInput::make('time_limit_minutes')
                                ->numeric()
                                ->nullable()
                                ->suffix('min'),
                            Forms\Components\TextInput::make('max_attempts')
                                ->numeric()
                                ->default(3),
                            Forms\Components\Repeater::make('questions')
                                ->relationship('questions')
                                ->schema([
                                    Forms\Components\Textarea::make('question')
                                        ->required()
                                        ->rows(2),
                                    Forms\Components\Select::make('type')
                                        ->options([
                                            'multiple_choice' => 'Multiple Choice',
                                            'true_false' => 'True/False',
                                            'short_answer' => 'Short Answer',
                                        ])
                                        ->required()
                                        ->default('multiple_choice'),
                                    Forms\Components\KeyValue::make('options')
                                        ->label('Options (for Multiple Choice)')
                                        ->nullable(),
                                    Forms\Components\TextInput::make('correct_answer')
                                        ->required(),
                                    Forms\Components\TextInput::make('points')
                                        ->numeric()
                                        ->default(1),
                                    Forms\Components\TextInput::make('order')
                                        ->numeric()
                                        ->default(0),
                                ])
                                ->defaultItems(0)
                                ->collapsible(),
                        ]),
                ]),

            Forms\Components\Section::make('AI Generate Questions')
                ->schema([
                    Forms\Components\TextInput::make('ai_question_count')
                        ->label('Number of questions')
                        ->numeric()
                        ->default(5)
                        ->minValue(1)
                        ->maxValue(20),
                    Forms\Components\Select::make('ai_question_type')
                        ->label('Question type')
                        ->options([
                            'multiple_choice' => 'Multiple Choice',
                            'true_false' => 'True/False',
                            'mixed' => 'Mixed',
                        ])
                        ->default('multiple_choice'),
                    Forms\Components\Actions\Action::make('generateQuestions')
                        ->label('Generate with AI')
                        ->icon('heroicon-o-sparkles')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Generate Assessment Questions')
                        ->modalDescription('AI will generate questions based on the course title and description. This will add questions to the Assessment section above.')
                        ->action(function (Forms\Get $get, Forms\Set $set, $record) {
                            $title = $get('title') ?? '';
                            $description = $get('description') ?? '';
                            $count = $get('ai_question_count') ?? 5;
                            $type = $get('ai_question_type') ?? 'multiple_choice';
                            $category = $get('category') ?? '';

                            $typeMap = $type === 'mixed'
                                ? "mix of multiple choice and true/false"
                                : ($type === 'true_false' ? "true/false" : "multiple choice");

                            $prompt = "Generate exactly {$count} {$typeMap} assessment questions for a skill course.\n\n";
                            $prompt .= "Course: {$title}\n";
                            $prompt .= "Description: {$description}\n";
                            $prompt .= "Category: {$category}\n\n";
                            $prompt .= "Return ONLY a valid JSON array (no markdown, no explanation). Each object must have:\n";
                            $prompt .= '"question" (string), "type" ("multiple_choice" or "true_false"), "options" (object with keys a,b,c,d for MC or a,b for TF), "correct_answer" (the correct option key), "points" (1), "order" (number starting from 1).';

                            try {
                                $response = \App\Services\Ai\AiManagerService::ask($prompt, 0.7, 4000);
                                $response = trim($response);
                                // Strip markdown code fence if present
                                if (str_starts_with($response, '```')) {
                                    $response = preg_replace('/^```(?:json)?\s*/i', '', $response);
                                    $response = preg_replace('/\s*```$/', '', $response);
                                }
                                $questions = json_decode($response, true);
                                if (!is_array($questions)) {
                                    throw new \Exception('AI returned invalid JSON');
                                }
                                // Ensure assessment exists
                                $assessment = $record->assessment;
                                if (!$assessment) {
                                    $assessment = $record->assessment()->create([
                                        'title' => $title . ' Assessment',
                                        'description' => 'Auto-generated assessment',
                                        'passing_score' => 70,
                                        'max_attempts' => 3,
                                        'is_active' => true,
                                    ]);
                                }
                                $existingCount = $assessment->questions()->count();
                                foreach ($questions as $i => $q) {
                                    $assessment->questions()->create([
                                        'question' => $q['question'] ?? '',
                                        'type' => $q['type'] ?? 'multiple_choice',
                                        'options' => $q['options'] ?? [],
                                        'correct_answer' => $q['correct_answer'] ?? '',
                                        'points' => $q['points'] ?? 1,
                                        'order' => $existingCount + $i + 1,
                                    ]);
                                }
                                \Filament\Notifications\Notification::make()
                                    ->title(count($questions) . ' questions generated')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('AI generation failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('category')
                ->sortable(),
            Tables\Columns\TextColumn::make('difficulty')
                ->badge()
                ->color(fn(string $state): string => match ($state) {
                    'beginner' => 'success',
                    'intermediate' => 'warning',
                    'advanced' => 'danger',
                }),
            Tables\Columns\TextColumn::make('price')
                ->money('BDT'),
            Tables\Columns\TextColumn::make('enrollment_count')
                ->label('Enrollments')
                ->sortable(),
            Tables\Columns\IconColumn::make('is_active')
                ->boolean(),
            Tables\Columns\IconColumn::make('is_certified')
                ->boolean()
                ->label('Certified'),
        ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSkillCourses::route('/'),
            'create' => Pages\CreateSkillCourse::route('/create'),
            'edit' => Pages\EditSkillCourse::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_skill_courses');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('manage_skill_courses');
    }
}
