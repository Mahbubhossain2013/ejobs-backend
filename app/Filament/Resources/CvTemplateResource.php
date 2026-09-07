<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CvTemplateResource\Pages;
use App\Models\CvTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CvTemplateResource extends Resource
{
    protected static ?string $model = CvTemplate::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationGroup = 'CV Builder';

    // --- 1. FORM SCHEMA ---
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Template Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state))),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true),

                    Forms\Components\Select::make('category')
                        ->options([
                            'Corporate' => 'Corporate',
                            'Executive' => 'Executive',
                            'Creative' => 'Creative',
                            'Developer' => 'Developer',
                            'Designer' => 'Designer',
                            'Academic' => 'Academic',
                            'Elegant' => 'Elegant',
                        ])
                        ->default('Corporate')
                        ->required(),

                    Forms\Components\TextInput::make('author')
                        ->default('Admin')
                        ->required(),

                    Forms\Components\TextInput::make('version')
                        ->default('1.0.0')
                        ->required(),

                    Forms\Components\FileUpload::make('preview_image_path')
                        ->image()
                        ->disk('public')
                        ->directory('cv-templates')
                        ->required(),
                ])->columns(2),

            Forms\Components\Section::make('Theme & Monetization')
                ->schema([
                    Forms\Components\Toggle::make('is_premium')
                        ->reactive()
                        ->label('Is Premium Template?'),

                    Forms\Components\TextInput::make('price')
                        ->numeric()
                        ->prefix('BDT')
                        ->required(fn (Get $get) => $get('is_premium'))
                        ->visible(fn (Get $get) => $get('is_premium')),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),

                    Forms\Components\Toggle::make('is_featured')
                        ->label('Featured Template')
                        ->default(false),

                    Forms\Components\Toggle::make('ats_compatible')
                        ->label('ATS Compatible')
                        ->default(true),

                    Forms\Components\Toggle::make('dark_mode_supported')
                        ->label('Dark Mode Supported')
                        ->default(false),
                ])->columns(2),

            Forms\Components\Section::make('Code Customizer Workspace')
                ->description('Dynamic Laravel Blade variables and layout customizers are supported here.')
                ->schema([
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('template_docs')
                            ->label('View Template Creation Guide (Docs)')
                            ->icon('heroicon-o-document-text')
                            ->color('success')
                            ->modalHeading('CV Template Creation Guide')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close')
                            ->modalContent(fn () => new \Illuminate\Support\HtmlString('
                                <div class="prose dark:prose-invert max-w-none text-sm space-y-4 leading-relaxed">
                                    <p class="font-semibold text-gray-700 dark:text-gray-300">Welcome to the CV Template Developer Guide! You can build custom layouts using standard HTML, Tailwind CSS classnames, and Laravel Blade directives.</p>
                                    
                                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border dark:border-gray-700">
                                        <h4 class="font-bold text-success-600 mb-2">1. Data Structure Reference</h4>
                                        <p class="mb-2">Your template has access to a primary <code>$data</code> array containing the candidate details:</p>
                                        <ul class="list-disc pl-5 space-y-1.5 text-xs">
                                            <li><code>$data[\'personal\']</code>: Personal info (<code>full_name</code>, <code>title</code>, <code>email</code>, <code>phone</code>, <code>bio</code>, <code>city</code>, <code>social_links</code>)</li>
                                            <li><code>$data[\'skills\']</code>: A list of text strings representing the candidate\'s skills.</li>
                                            <li><code>$data[\'experience\']</code>: Array of objects (<code>company</code>, <code>title</code>, <code>duration</code>, <code>description</code>)</li>
                                            <li><code>$data[\'education\']</code>: Array of objects (<code>institution</code>, <code>degree</code>, <code>passing_year</code>)</li>
                                            <li><code>$data[\'projects\']</code>: Array of objects (<code>name</code>, <code>role</code>, <code>description</code>)</li>
                                            <li><code>$data[\'certifications\']</code>: Array of objects (<code>name</code>, <code>issuer</code>)</li>
                                        </ul>
                                    </div>

                                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border dark:border-gray-700">
                                        <h4 class="font-bold text-success-600 mb-2">2. Dynamic CSS & Typography</h4>
                                        <p class="text-xs">The compilation engine generates CSS customizer root variables automatically. Bind them in your custom stylesheet like this:</p>
                                        <pre class="bg-gray-100 dark:bg-gray-900 p-2 rounded text-xs mt-2 overflow-x-auto">
:root {
  --primary-color: var(--primary-color);
  --secondary-color: var(--secondary-color);
}
.theme-header { color: var(--primary-color); }</pre>
                                    </div>

                                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border dark:border-gray-700">
                                        <h4 class="font-bold text-success-600 mb-2">3. Example Loop Structure</h4>
                                        <pre class="bg-gray-100 dark:bg-gray-900 p-2 rounded text-xs overflow-x-auto">
&lt;h2&gt;Work Experience&lt;/h2&gt;
@foreach($data[\'experience\'] ?? [] as $exp)
  &lt;div class="mb-4"&gt;
    &lt;h4 class="font-bold text-indigo-600"&gt;{{ $exp[\'title\'] }}&lt;/h4&gt;
    &lt;p class="text-xs font-bold"&gt;{{ $exp[\'company\'] }}&lt;/p&gt;
    &lt;p class="text-xs"&gt;{{ $exp[\'description\'] }}&lt;/p&gt;
  &lt;/div&gt;
@endforeach</pre>
                                    </div>
                                </div>
                            ')),
                    ]),

                    Forms\Components\Textarea::make('html_content')
                        ->label('HTML / Blade Content')
                        ->rows(20)
                        ->extraInputAttributes(['style' => 'font-family: monospace; font-size: 13px;'])
                        ->columnSpanFull()
                        ->placeholder("<!DOCTYPE html>\n<html>\n<body>\n  <h1>{{ \$data['personal']['full_name'] }}</h1>\n</body>\n</html>")
                        ->formatStateUsing(function ($state, $record) {
                            if ($record && empty($state)) {
                                $filePath = resource_path("views/cv_templates/{$record->slug}.blade.php");
                                if (file_exists($filePath)) {
                                    return file_get_contents($filePath);
                                }
                            }
                            return $state;
                        }),

                    Forms\Components\Textarea::make('css_content')
                        ->label('CSS Content')
                        ->rows(12)
                        ->extraInputAttributes(['style' => 'font-family: monospace; font-size: 13px;'])
                        ->columnSpanFull()
                        ->placeholder(":root {\n  --primary-color: #2563eb;\n}"),
                ])->columnSpanFull(),
        ]);
    }

    // --- 2. TABLE COLUMNS & ACTIONS ---
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('preview_image_path')
                    ->label('Preview')
                    ->disk('public'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_premium')
                    ->label('Premium')
                    ->boolean(),

                Tables\Columns\TextColumn::make('price')
                    ->money('BDT')
                    ->fontFamily('mono'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                // Add filtration filters here when your database expands
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Add custom relation managers here if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCvTemplates::route('/'),
            'create' => Pages\CreateCvTemplate::route('/create'),
            'edit' => Pages\EditCvTemplate::route('/{record}/edit'),
        ];
    }
}