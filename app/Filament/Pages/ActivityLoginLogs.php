<?php

namespace App\Filament\Pages;

use App\Models\SecurityLog;
use App\Models\UserSecurityLog;
use App\Models\UserBehaviorLog;
use App\Models\AuditLog;
use App\Models\TrustedDevice;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\DB;

class ActivityLoginLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Trust & Safety';
    protected static ?string $title = 'Activity & Login Logs';
    protected static ?string $slug = 'trust-safety/activity-login-logs';
    protected static ?string $navigationLabel = 'Activity & Login Logs';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.activity-login-logs';

    public ?string $activeTab = 'all';

    public function mount(): void
    {
        $this->activeTab = 'all';
    }

    // ─── Stats ───

    public function getStats(): array
    {
        $now = now();
        $dayAgo = $now->copy()->subDay();

        return [
            [
                'label' => 'Logins (24h)',
                'value' => SecurityLog::where('event_type', 'successful_login')->where('created_at', '>=', $dayAgo)->count(),
                'icon' => 'heroicon-o-arrow-right-on-rectangle',
                'color' => 'success',
            ],
            [
                'label' => 'Failed Logins (24h)',
                'value' => SecurityLog::where('event_type', 'failed_otp')->where('created_at', '>=', $dayAgo)->count(),
                'icon' => 'heroicon-o-x-circle',
                'color' => 'danger',
            ],
            [
                'label' => 'Suspicious (24h)',
                'value' => UserSecurityLog::where('risk_score', '>', 50)->where('created_at', '>=', $dayAgo)->count(),
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'warning',
            ],
            [
                'label' => 'VPN Detected (24h)',
                'value' => UserSecurityLog::where('vpn_detected', true)->where('created_at', '>=', $dayAgo)->count(),
                'icon' => 'heroicon-o-shield-exclamation',
                'color' => 'info',
            ],
            [
                'label' => 'Behavior Events (24h)',
                'value' => number_format(UserBehaviorLog::where('created_at', '>=', $dayAgo)->count()),
                'icon' => 'heroicon-o-chart-bar-square',
                'color' => 'gray',
            ],
            [
                'label' => 'Trusted Devices',
                'value' => TrustedDevice::count(),
                'icon' => 'heroicon-o-device-phone-mobile',
                'color' => 'gray',
            ],
        ];
    }

    // ─── Unified Table ───

    public function table(Table $table): Table
    {
        return $table
            ->query(
                UserSecurityLog::query()
                    ->leftJoin('users', 'users.id', '=', 'user_security_logs.user_id')
                    ->select(
                        'user_security_logs.*',
                        DB::raw('users.name as user_name'),
                        DB::raw('users.email as user_email')
                    )
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('user_name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $state ?? 'Unknown')
                    ->description(fn ($record) => $record->user_email),

                Tables\Columns\TextColumn::make('activity_type')
                    ->label('Activity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'login' => 'success',
                        'suspicious_activity' => 'danger',
                        'brute_force' => 'danger',
                        'moderation_action' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => str($state)->replace('_', ' ')->title()),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->fontFamily('mono')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('user_agent')
                    ->label('Device')
                    ->limit(35)
                    ->tooltip(fn ($state) => $state)
                    ->size('sm'),

                Tables\Columns\TextColumn::make('risk_score')
                    ->label('Risk')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        ($state ?? 0) > 70 => 'danger',
                        ($state ?? 0) > 40 => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn ($state) => ($state ?? 0) . '%'),

                Tables\Columns\IconColumn::make('vpn_detected')
                    ->label('VPN')
                    ->boolean(),

                Tables\Columns\TextColumn::make('moderation_details')
                    ->label('Details')
                    ->limit(40)
                    ->tooltip(fn ($state) => $state)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('M d, H:i')
                    ->sortable()
                    ->size('sm'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('activity_type')
                    ->label('Activity Type')
                    ->options([
                        'login' => 'Login',
                        'suspicious_activity' => 'Suspicious Activity',
                        'brute_force' => 'Brute Force',
                        'moderation_action' => 'Moderation Action',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('high_risk')
                    ->label('High Risk Only')
                    ->query(fn ($query) => $query->where('risk_score', '>', 70))
                    ->toggle(),

                Tables\Filters\Filter::make('vpn')
                    ->label('VPN Users')
                    ->query(fn ($query) => $query->where('vpn_detected', true))
                    ->toggle(),

                Tables\Filters\Filter::make('last_24h')
                    ->label('Last 24 Hours')
                    ->query(fn ($query) => $query->where('user_security_logs.created_at', '>=', now()->subDay()))
                    ->toggle(),

                Tables\Filters\Filter::make('last_7d')
                    ->label('Last 7 Days')
                    ->query(fn ($query) => $query->where('user_security_logs.created_at', '>=', now()->subWeek()))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_user')
                    ->label('View User')
                    ->icon('heroicon-o-user')
                    ->color('gray')
                    ->url(fn ($record) => $record->user_id ? '/admin/users/' . $record->user_id . '/edit' : '#')
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->poll('30s');
    }
}
