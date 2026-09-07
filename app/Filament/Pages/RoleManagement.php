<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class RoleManagement extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 0;
    protected static ?string $title = 'Role & Permission Management';
    protected static string $view = 'filament.pages.role-management';

    public ?string $selectedRoleId = null;
    public array $formData = [];

    public function mount(): void
    {
        $this->form->fill([
            'role_name' => '',
            'role_label' => '',
            'permissions' => [],
        ]);
    }

    public function form(Form $form): Form
    {
        $groupedPermissions = $this->getGroupedPermissions();

        return $form
            ->schema([
                Forms\Components\Section::make('Role Details')
                    ->schema([
                        Forms\Components\Select::make('existing_role')
                            ->label('Edit Existing Role')
                            ->options(fn () => Role::where('guard_name', 'web')
                                ->where('name', '!=', 'super_admin')
                                ->pluck('name', 'name'))
                            ->placeholder('Create New Role')
                            ->reactive()
                            ->afterStateUpdated(fn ($state) => $this->loadRole($state)),

                        Forms\Components\TextInput::make('role_name')
                            ->label('Role Slug')
                            ->required()
                            ->regex('/^[a-z_]+$/')
                            ->helperText('Lowercase letters and underscores only'),

                        Forms\Components\TextInput::make('role_label')
                            ->label('Display Name')
                            ->required(),
                    ]),

                Forms\Components\Section::make('Permissions')
                    ->description('Toggle access for each admin panel section')
                    ->schema($this->buildPermissionCheckboxes($groupedPermissions)),
            ])
            ->statePath('formData');
    }

    private function getGroupedPermissions(): array
    {
        return [
            'Dashboard' => ['view_dashboard'],
            'User Management' => ['view_candidates', 'view_employers', 'view_companies', 'view_company_reviews'],
            'Jobs' => ['view_remote_jobs'],
            'Finance' => [
                'view_invoices', 'manage_invoice_templates', 'manage_payout_gateways',
                'manage_subscriptions', 'manage_tax_settings', 'view_wallet_ledger',
                'manage_withdrawals', 'view_billing_dashboard', 'manage_financial_settings',
            ],
            'Trust & Safety' => ['view_support_tickets', 'manage_verifications', 'view_activity_logs'],
            'Moderation' => ['view_reports'],
            'Marketing' => ['view_ads_dashboard'],
            'Content' => ['manage_homepage_settings', 'manage_footer_settings', 'manage_notices', 'manage_static_pages'],
            'Skill Center' => ['manage_skill_courses'],
            'System' => [
                'manage_system_settings', 'manage_gateways', 'manage_ai_settings',
                'manage_currencies', 'manage_general_settings', 'manage_mail_settings',
                'manage_notification_settings', 'manage_backups', 'manage_cron_jobs',
                'manage_infrastructure', 'manage_addons', 'view_developer_portal',
                'manage_profile_intelligence', 'view_admin_guide',
            ],
        ];
    }

    private function buildPermissionCheckboxes(array $groupedPermissions): array
    {
        $schemas = [];
        foreach ($groupedPermissions as $group => $permissions) {
            $schemas[] = Forms\Components\Grid::make(3)
                ->label($group)
                ->schema(array_map(fn ($perm) =>
                    Forms\Components\Toggle::make("permissions.{$perm}")
                        ->label(str($perm)->replace('_', ' ')->title())
                        ->columnSpan(1),
                    $permissions
                ));
        }
        return $schemas;
    }

    public function loadRole(?string $roleName): void
    {
        if (!$roleName) {
            $this->form->fill([
                'role_name' => '',
                'role_label' => '',
                'permissions' => [],
            ]);
            return;
        }

        $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
        if (!$role) return;

        $allPermissions = Permission::where('guard_name', 'web')->pluck('name')->toArray();
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        $permState = [];
        foreach ($allPermissions as $p) {
            $permState[$p] = in_array($p, $rolePermissions);
        }

        $this->form->fill([
            'existing_role' => $role->name,
            'role_name' => $role->name,
            'role_label' => ucfirst(str_replace('_', ' ', $role->name)),
            'permissions' => $permState,
        ]);
    }

    public function save(): void
    {
        $data = $this->formData;

        if (empty($data['role_name'])) {
            Notification::make()->title('Role name is required')->danger()->send();
            return;
        }

        $roleName = $data['role_name'];
        $permissions = $data['permissions'] ?? [];
        $enabledPerms = array_keys(array_filter($permissions));

        $role = Role::firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'web'],
            ['name' => $roleName, 'guard_name' => 'web']
        );
        $role->syncPermissions($enabledPerms);

        Notification::make()->title("Role '{$roleName}' saved with " . count($enabledPerms) . " permissions")->success()->send();
    }

    public function delete(): void
    {
        $roleName = $this->formData['existing_role'] ?? null;
        if (!$roleName || $roleName === 'super_admin') {
            Notification::make()->title('Cannot delete this role')->danger()->send();
            return;
        }

        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $role->delete();
            Notification::make()->title("Role '{$roleName}' deleted")->success()->send();
            $this->form->fill([]);
        }
    }

    public function getRoleUsers(): array
    {
        $adminRoles = ['super_admin', 'admin', 'manager'];
        $usersByRole = [];

        foreach ($adminRoles as $roleName) {
            $usersByRole[$roleName] = User::where('admin_role', $roleName)
                ->select('id', 'name', 'email', 'admin_role')
                ->get();
        }

        return $usersByRole;
    }

    public function getAvailableRoles(): array
    {
        return Role::where('guard_name', 'web')
            ->where('name', '!=', 'super_admin')
            ->pluck('name', 'name')
            ->toArray();
    }

    public function changeUserRole(int $userId, string $newRole): void
    {
        $user = User::find($userId);
        if (!$user || $user->id === auth()->id()) {
            Notification::make()->title('Cannot change your own role')->danger()->send();
            return;
        }

        $user->admin_role = $newRole;
        $user->save();
        $user->syncRoles([$newRole]);

        Notification::make()->title("{$user->name} is now '{$newRole}'")->success()->send();
    }

    public function removeFromPanel(int $userId): void
    {
        $user = User::find($userId);
        if (!$user || $user->id === auth()->id()) {
            Notification::make()->title('Cannot remove yourself')->danger()->send();
            return;
        }

        $user->admin_role = null;
        $user->save();
        $user->removeRole($user->getRoleNames()->first());

        Notification::make()->title("{$user->name} removed from admin panel")->success()->send();
    }

    public function addToPanel(int $userId, string $role): void
    {
        $user = User::find($userId);
        if (!$user) {
            Notification::make()->title('User not found')->danger()->send();
            return;
        }

        $user->admin_role = $role;
        $user->save();
        $user->assignRole($role);

        Notification::make()->title("{$user->name} added as '{$role}'")->success()->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }
}
