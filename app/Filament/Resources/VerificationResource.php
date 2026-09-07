<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VerificationResource\Pages;
use App\Models\Verification;
use App\Models\Setting;
use App\Models\SecurityLog;
use App\Services\Billing\InvoiceService;
use App\Services\Notification\NotificationService;
use App\Services\Notification\AdminEmailService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VerificationResource extends Resource
{
    protected static ?string $model = Verification::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Trust & Safety';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('Identity & Request Telemetry')
                        ->schema([
                            Forms\Components\Select::make('user_id')
                                ->relationship('user', 'name')
                                ->disabled()
                                ->required(),
                            Forms\Components\Select::make('company_id')
                                ->relationship('company', 'name')
                                ->disabled()
                                ->placeholder('N/A'),
                            Forms\Components\TextInput::make('verification_type')
                                ->label('Verification Type')
                                ->disabled(),
                            Forms\Components\TextInput::make('ip_address')
                                ->label('Submission IP Address')
                                ->disabled()
                                ->placeholder('Unknown'),
                            Forms\Components\TextInput::make('device_fingerprint')
                                ->label('Device & User Agent Log')
                                ->disabled()
                                ->columnSpanFull()
                                ->placeholder('Unknown'),
                        ])->columns(2),

                    Forms\Components\Section::make('Document Upload Proofs')
                        ->schema([
                            Forms\Components\Placeholder::make('nid_number_display')
                                ->label('NID Number')
                                ->content(fn ($record) => $record?->nid_number ? new HtmlString('<strong class="text-lg tracking-wider">' . $record->nid_number . '</strong>') : 'N/A'),
                            Forms\Components\Placeholder::make('dob_display')
                                ->label('Date of Birth')
                                ->content(fn ($record) => $record?->dob ? $record->dob->format('Y-m-d') : 'N/A'),
                            
                            Forms\Components\Placeholder::make('document_front_preview')
                                ->label('Front Document Proof')
                                ->columnSpanFull()
                                ->content(fn ($record) => $record && $record->document_path ? new HtmlString(
                                    '<div class="space-y-2">' .
                                    '<img src="' . Storage::url($record->document_path) . '" class="w-full max-h-96 object-contain rounded-lg shadow-md border dark:border-gray-700" />' .
                                    '<a href="' . Storage::url($record->document_path) . '" target="_blank" class="text-xs text-primary-600 dark:text-primary-400 underline font-medium inline-flex items-center gap-1">Open original front file in new tab</a>' .
                                    '</div>'
                                ) : 'No document front file uploaded.'),

                            Forms\Components\Placeholder::make('document_back_preview')
                                ->label('Back Document Proof (NID Only)')
                                ->columnSpanFull()
                                ->content(fn ($record) => $record && $record->document_back_path ? new HtmlString(
                                    '<div class="space-y-2">' .
                                    '<img src="' . Storage::url($record->document_back_path) . '" class="w-full max-h-96 object-contain rounded-lg shadow-md border dark:border-gray-700" />' .
                                    '<a href="' . Storage::url($record->document_back_path) . '" target="_blank" class="text-xs text-primary-600 dark:text-primary-400 underline font-medium inline-flex items-center gap-1">Open original back file in new tab</a>' .
                                    '</div>'
                                ) : 'No NID back side file uploaded.'),
                        ])->columns(2),
                ])->columnSpan(2),

                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('AI Verification Engine Report')
                        ->schema([
                            Forms\Components\Placeholder::make('ai_confidence_meter')
                                ->label('AI Confidence Score')
                                ->content(fn ($record) => $record?->ai_confidence_score !== null ? new HtmlString(
                                    '<div class="flex items-center gap-2">' .
                                    '<div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">' .
                                    '<div class="h-4 rounded-full bg-gradient-to-r from-danger-500 via-warning-500 to-success-500" style="width: ' . $record->ai_confidence_score . '%"></div>' .
                                    '</div>' .
                                    '<span class="font-bold text-sm">' . $record->ai_confidence_score . '%</span>' .
                                    '</div>'
                                ) : 'AI not run or disabled.'),

                            Forms\Components\Placeholder::make('ai_reasoning_display')
                                ->label('AI Reasoning & Findings')
                                ->content(fn ($record) => $record?->notes ? new HtmlString('<p class="text-sm italic text-gray-600 dark:text-gray-400 leading-relaxed whitespace-pre-line">' . e($record->notes) . '</p>') : 'No AI reasoning details recorded.'),

                            Forms\Components\Placeholder::make('anti_fraud_telemetry')
                                ->label('Anti-Fraud Match Results')
                                ->content(function ($record) {
                                    if (!$record || !$record->ai_analysis_data) return 'N/A';
                                    $data = $record->ai_analysis_data;
                                    $duplicateWarning = ($data['duplicate_detected'] ?? ($data['analysis_data']['duplicate_detected'] ?? false)) 
                                        ? '<div class="text-danger-600 font-bold flex items-center gap-1">⚠️ Duplicate NID detected!</div>' 
                                        : '<div class="text-success-600 font-medium">✅ No duplicates detected</div>';

                                    $ocrName = $data['ocr_name'] ?? 'N/A';
                                    $ocrDob = $data['ocr_dob'] ?? 'N/A';
                                    $nameMatch = $data['name_match_score'] ?? 0;
                                    $dobMatch = $data['dob_match_score'] ?? 0;
                                    $fakeProb = $data['fake_document_probability'] ?? 0;

                                    return new HtmlString(
                                        '<div class="text-xs space-y-2 leading-relaxed bg-gray-50 dark:bg-gray-800/40 p-3 rounded-lg border border-gray-100 dark:border-gray-800">' .
                                        '<div>' . $duplicateWarning . '</div>' .
                                        '<div><strong>OCR Extracted Name:</strong> ' . e($ocrName) . ' (' . $nameMatch . '% match)</div>' .
                                        '<div><strong>OCR Extracted DOB:</strong> ' . e($ocrDob) . ' (' . $dobMatch . '% match)</div>' .
                                        '<div><strong>Fake Card Probability:</strong> <span class="' . ($fakeProb > 30 ? 'text-danger-600 font-bold' : 'text-success-600') . '">' . $fakeProb . '%</span></div>' .
                                        '</div>'
                                    );
                                }),
                        ]),

                    Forms\Components\Section::make('Verified NID Government Data')
                        ->description('Official NID holder details retrieved from the Government database.')
                        ->icon('heroicon-o-identification')
                        ->collapsible()
                        ->visible(fn ($record) => $record && $record->ai_analysis_data && 
                            (isset($record->ai_analysis_data['analysis_data']['nid_raw_data']) || isset($record->ai_analysis_data['analysis_data']['apon_raw_data']))
                        )
                        ->schema([
                            Forms\Components\Placeholder::make('nid_holder_photo')
                                ->label('NID Holder Photo')
                                ->content(function ($record) {
                                    $nidData = $record->ai_analysis_data['analysis_data']['nid_raw_data'] 
                                        ?? $record->ai_analysis_data['analysis_data']['apon_raw_data'] 
                                        ?? [];
                                    $photoUrl = $nidData['photo'] ?? null;
                                    if ($photoUrl) {
                                        return new HtmlString(
                                            '<img src="' . e($photoUrl) . '" class="w-24 h-28 rounded-lg shadow-md border dark:border-gray-700 object-cover" alt="NID Photo" />'
                                        );
                                    }
                                    return 'No photo available.';
                                }),

                            Forms\Components\Placeholder::make('nid_holder_details')
                                ->label('Identity Details')
                                ->content(function ($record) {
                                    $nidData = $record->ai_analysis_data['analysis_data']['nid_raw_data'] 
                                        ?? $record->ai_analysis_data['analysis_data']['apon_raw_data'] 
                                        ?? [];
                                    if (empty($nidData)) return 'No data available.';

                                    $rows = [
                                        ['National ID', $nidData['nationalId'] ?? 'N/A'],
                                        ['Old NID', $nidData['oldId'] ?? 'N/A'],
                                        ['Name (English)', $nidData['nameEnglish'] ?? 'N/A'],
                                        ['Name (Bangla)', $nidData['nameBangla'] ?? 'N/A'],
                                        ['Date of Birth', $nidData['dateOfBirth'] ?? 'N/A'],
                                        ['Gender', isset($nidData['gender']) ? ($nidData['gender'] === 'male' ? 'Male' : 'Female') : 'N/A'],
                                        ['Father Name', $nidData['fatherName'] ?? 'N/A'],
                                        ['Mother Name', $nidData['motherName'] ?? 'N/A'],
                                        ['Spouse Name', $nidData['spouseName'] ?? 'N/A'],
                                        ['Voter Area', $nidData['voterArea'] ?? 'N/A'],
                                        ['Blood Group', $nidData['bloodGroup'] ?? 'N/A'],
                                    ];

                                    $html = '<div class="space-y-1.5 text-xs">';
                                    foreach ($rows as [$label, $value]) {
                                        $html .= '<div class="flex gap-2"><strong class="min-w-[110px] text-gray-500 dark:text-gray-400">' . $label . ':</strong> <span class="text-foreground font-medium">' . e($value) . '</span></div>';
                                    }
                                    $html .= '</div>';
                                    return new HtmlString($html);
                                }),

                            Forms\Components\Placeholder::make('nid_holder_addresses')
                                ->label('Address Information')
                                ->content(function ($record) {
                                    $nidData = $record->ai_analysis_data['analysis_data']['nid_raw_data'] 
                                        ?? $record->ai_analysis_data['analysis_data']['apon_raw_data'] 
                                        ?? [];
                                    if (empty($nidData)) return 'No data available.';

                                    $preAddr = $nidData['preAddress'] ?? [];
                                    $perAddr = $nidData['perAddress'] ?? [];

                                    $formatAddress = function ($addr) {
                                        if (empty($addr)) return 'N/A';
                                        $parts = array_filter([
                                            $addr['addressLine'] ?? null,
                                            isset($addr['upozila']) ? 'Upazila: ' . $addr['upozila'] : null,
                                            isset($addr['district']) ? 'District: ' . $addr['district'] : null,
                                            isset($addr['division']) ? 'Division: ' . $addr['division'] : null,
                                        ]);
                                        return implode(', ', $parts) ?: 'N/A';
                                    };

                                    return new HtmlString(
                                        '<div class="space-y-3 text-xs">' .
                                        '<div><strong class="text-gray-500 dark:text-gray-400 block mb-0.5">Present Address:</strong> <span class="text-foreground">' . e($formatAddress($preAddr)) . '</span></div>' .
                                        '<div><strong class="text-gray-500 dark:text-gray-400 block mb-0.5">Permanent Address:</strong> <span class="text-foreground">' . e($formatAddress($perAddr)) . '</span></div>' .
                                        '</div>'
                                    );
                                }),
                        ]),

                    Forms\Components\Section::make('Admin Review Manual Override')
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'pending' => 'Pending Review',
                                    'manual_review' => 'Manual Review Required',
                                    'approved' => 'Approved / Verified',
                                    'rejected' => 'Rejected',
                                    'failed' => 'Failed / Fraud Banned',
                                ])
                                ->required(),
                            Forms\Components\Textarea::make('admin_notes')
                                ->label('Admin Review Feedback / Reason')
                                ->columnSpanFull(),
                        ]),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('verification_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'nid' => 'primary',
                        'phone' => 'info',
                        'email' => 'warning',
                        'employer' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                Tables\Columns\TextColumn::make('nid_number')
                    ->label('NID / License Number')
                    ->searchable()
                    ->placeholder('N/A')
                    ->copyable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'manual_review' => 'warning',
                        'rejected' => 'danger',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'manual_review' => 'Manual Review',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('ai_confidence_score')
                    ->label('AI Match %')
                    ->numeric()
                    ->sortable()
                    ->placeholder('N/A')
                    ->badge()
                    ->color(fn ($state) => $state >= 75 ? 'success' : ($state >= 40 ? 'warning' : 'danger')),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP / Device')
                    ->placeholder('Unknown')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'manual_review' => 'Manual Review Required',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'failed' => 'Failed / Fraud Banned',
                    ]),
                Tables\Filters\SelectFilter::make('verification_type')
                    ->options([
                        'nid' => 'NID',
                        'phone' => 'Phone',
                        'email' => 'Email',
                        'employer' => 'Employer',
                    ]),
            ])
            ->actions([
                // 1. Approve Override Action
                Tables\Actions\Action::make('manual_approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(fn (Verification $record) => in_array($record->status, ['pending', 'manual_review', 'rejected']))
                    ->action(function (Verification $record) {
                        $record->update([
                            'status' => 'approved',
                            'verified_at' => now(),
                            'reviewed_by' => auth()->id(),
                        ]);

                        // Sync specific badges based on verification type
                        $user = $record->user;
                        $type = $record->verification_type;

                        if ($type === 'nid') {
                            $user->assignBadge('nid_verified', 'admin');
                            if ($user->hasBadge('phone_verified')) {
                                $user->assignBadge('verified', 'admin');
                            }
                        } elseif ($type === 'phone') {
                            $user->assignBadge('phone_verified', 'admin');
                            if ($user->hasBadge('nid_verified')) {
                                $user->assignBadge('verified', 'admin');
                            }
                        } elseif ($type === 'email') {
                            $user->assignBadge('email_verified', 'admin');
                        } elseif ($type === 'employer') {
                            $user->assignBadge('employer_verified', 'admin');
                            $user->assignBadge('verified', 'admin');
                            
                            // Verify active company details
                            if ($record->company) {
                                $record->company->update(['is_verified' => true]);
                            }
                        }

                        app(NotificationService::class)->sendNotification(
                            $user,
                            '🎉 Identity Document Verified',
                            'Congratulations! The platform administrators have reviewed and manually approved your verification documents.',
                            'success',
                            '/dashboard'
                        );

                        AdminEmailService::notifyUser(
                            $user,
                            'Your ' . strtoupper($type) . ' verification has been approved',
                            'emails.verification_status_changed',
                            [
                                'userName' => $user->name,
                                'type' => $type,
                                'status' => 'verified',
                                'reason' => 'Your verification documents have been reviewed and approved by an administrator.',
                            ]
                        );

                        FilamentNotification::make()
                            ->title('Verification Request Approved')
                            ->body("Assigned appropriate verification badges to {$user->name}.")
                            ->success()
                            ->send();
                    }),

                // 2. Reject Override Action
                Tables\Actions\Action::make('manual_reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->visible(fn (Verification $record) => in_array($record->status, ['pending', 'manual_review', 'approved']))
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Rejection Reason / Feedback')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (Verification $record, array $data) {
                        $feeAmount = $record->verification_type === 'nid' ? 20.00 : 50.00;
                        
                        $record->update([
                            'status' => 'rejected',
                            'admin_notes' => $data['admin_notes'],
                            'reviewed_by' => auth()->id(),
                        ]);

                        // Revoke badges
                        $user = $record->user;
                        $type = $record->verification_type;

                        if ($type === 'nid') {
                            $user->revokeBadge('nid_verified');
                            $user->revokeBadge('verified');
                        } elseif ($type === 'phone') {
                            $user->revokeBadge('phone_verified');
                            $user->revokeBadge('verified');
                        } elseif ($type === 'email') {
                            $user->revokeBadge('email_verified');
                        } elseif ($type === 'employer') {
                            $user->revokeBadge('employer_verified');
                            $user->revokeBadge('verified');
                            if ($record->company) {
                                $record->company->update(['is_verified' => false]);
                            }
                        }

                        // Auto refund fee if active
                        $refundEnabled = Setting::where('key', 'verification_refund_on_rejection')->value('value') ?? '1';
                        $refundMsg = "";

                        if ($refundEnabled === '1' || $refundEnabled === 'true') {
                            try {
                                // Find paid manual invoice of user
                                $invoice = Invoice::where('user_id', $user->id)
                                    ->where('status', 'paid')
                                    ->where('notes', 'like', '%' . $type . '%')
                                    ->latest()
                                    ->first();

                                if ($invoice) {
                                    app(InvoiceService::class)->refundInvoice($invoice, $feeAmount, "Manual admin rejection: " . $data['admin_notes']);
                                    $refundMsg = " Fee of {$feeAmount} BDT refunded to wallet.";
                                }
                            } catch (\Throwable $e) {
                                Log::warning("Manual Rejection Refund Error: " . $e->getMessage());
                            }
                        }

                        app(NotificationService::class)->sendNotification(
                            $user,
                            '❌ Verification Request Rejected',
                            'Your ' . strtoupper($type) . ' verification request was rejected. Reason: ' . $data['admin_notes'] . $refundMsg,
                            'danger',
                            '/dashboard'
                        );

                        AdminEmailService::notifyUser(
                            $user,
                            'Your ' . strtoupper($type) . ' verification has been rejected',
                            'emails.verification_status_changed',
                            [
                                'userName' => $user->name,
                                'type' => $type,
                                'status' => 'rejected',
                                'reason' => $data['admin_notes'] . $refundMsg,
                            ]
                        );

                        FilamentNotification::make()
                            ->title('Verification Request Rejected')
                            ->body('Revoked active badges.' . $refundMsg)
                            ->danger()
                            ->send();
                    }),

                // 3. User Fraud suspension ban Override Action
                Tables\Actions\Action::make('manual_ban')
                    ->label('Ban User')
                    ->color('danger')
                    ->icon('heroicon-o-no-symbol')
                    ->requiresConfirmation()
                    ->visible(fn (Verification $record) => $record->status !== 'failed')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Suspension / Fraud Audit Reason')
                            ->required(),
                    ])
                    ->action(function (Verification $record, array $data) {
                        $user = $record->user;
                        
                        // Update profile suspension fields
                        if ($user->profile) {
                            $user->profile->update([
                                'ban_status' => true,
                                'restriction_status' => 'suspended',
                                'moderation_notes' => 'Banned by admin due to document verification identity fraud: ' . $data['reason']
                            ]);
                        }
                        if ($user->company) {
                            $user->company->update([
                                'ban_status' => true,
                                'restriction_status' => 'suspended',
                                'moderation_notes' => 'Banned by admin due to document verification identity fraud: ' . $data['reason']
                            ]);
                        }

                        // Revoke all trust badges
                        $user->revokeBadge('verified');
                        $user->revokeBadge('nid_verified');
                        $user->revokeBadge('employer_verified');
                        $user->revokeBadge('email_verified');
                        $user->revokeBadge('phone_verified');

                        // Mark verification entry as failed
                        $record->update([
                            'status' => 'failed',
                            'admin_notes' => 'Fraud Banned. Details: ' . $data['reason'],
                            'reviewed_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);

                        // Audit log to platform SecurityLog
                        SecurityLog::create([
                            'user_id' => $user->id,
                            'event_type' => 'verification_fraud_ban',
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                            'status' => 'success',
                            'description' => "User banned by admin for verification identity fraud. Details: " . $data['reason'],
                            'created_at' => now(),
                        ]);

                        AdminEmailService::notifyUser(
                            $user,
                            'Your account has been suspended for fraud',
                            'emails.account_status_changed',
                            [
                                'userName' => $user->name,
                                'status' => 'banned',
                                'reason' => 'Your account has been suspended due to fraudulent verification activity: ' . $data['reason'],
                                'supportUrl' => config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/support',
                            ]
                        );

                        FilamentNotification::make()
                            ->title('User Suspended / Banned')
                            ->body("Banned {$user->name} and logged security audit telemetry successfully.")
                            ->danger()
                            ->send();
                    }),

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
            'index' => Pages\ListVerifications::route('/'),
            'edit' => Pages\EditVerification::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_verifications');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('manage_verifications');
    }
}
