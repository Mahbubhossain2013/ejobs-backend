<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <div class="flex items-center gap-3 mb-2">
                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                    @svg('heroicon-o-code-bracket', 'w-5 h-5 text-primary-600 dark:text-primary-400')
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">API Base URL</h3>
                    <code class="text-sm font-mono text-primary-600 dark:text-primary-400">{{ $baseUrl }}</code>
                </div>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                All endpoints require <code class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-white/10 text-xs font-mono">Authorization: Bearer {token}</code> header unless marked as public.
            </p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-key', 'w-5 h-5 text-gray-400')
                    Authentication
                </div>
            </x-slot>

            <div class="space-y-4 mt-4">
                <div class="p-4 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">POST</span>
                        <code class="text-sm font-mono text-gray-800 dark:text-gray-200">/api/register</code>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Register a new user</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Request</p>
                            <pre class="p-3 rounded-lg bg-gray-900 dark:bg-gray-950 text-xs text-gray-300 font-mono overflow-x-auto">{!! e(json_encode(['name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'role' => 'candidate'], JSON_PRETTY_PRINT)) !!}</pre>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Response</p>
                            <pre class="p-3 rounded-lg bg-gray-900 dark:bg-gray-950 text-xs text-gray-300 font-mono overflow-x-auto">{!! e(json_encode(['status' => true, 'data' => ['user' => '...', 'token' => 'sanctum_token']], JSON_PRETTY_PRINT)) !!}</pre>
                        </div>
                    </div>
                </div>

                <div class="p-4 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">POST</span>
                        <code class="text-sm font-mono text-gray-800 dark:text-gray-200">/api/login</code>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Login and receive a token</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Request</p>
                            <pre class="p-3 rounded-lg bg-gray-900 dark:bg-gray-950 text-xs text-gray-300 font-mono overflow-x-auto">{!! e(json_encode(['email' => 'john@example.com', 'password' => 'password'], JSON_PRETTY_PRINT)) !!}</pre>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Response</p>
                            <pre class="p-3 rounded-lg bg-gray-900 dark:bg-gray-950 text-xs text-gray-300 font-mono overflow-x-auto">{!! e(json_encode(['status' => true, 'data' => ['user' => '...', 'token' => 'sanctum_token']], JSON_PRETTY_PRINT)) !!}</pre>
                        </div>
                    </div>
                </div>

                <div class="p-4 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400">POST</span>
                        <code class="text-sm font-mono text-gray-800 dark:text-gray-200">/api/logout</code>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Revoke current token (auth required)</span>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Response</p>
                        <pre class="p-3 rounded-lg bg-gray-900 dark:bg-gray-950 text-xs text-gray-300 font-mono overflow-x-auto">{!! e(json_encode(['status' => true, 'message' => 'Logged out successfully'], JSON_PRETTY_PRINT)) !!}</pre>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-user', 'w-5 h-5 text-gray-400')
                    Candidate APIs
                </div>
            </x-slot>

            <div class="space-y-4 mt-4">
                @php
                    $candidateEndpoints = [
                        ['method' => 'GET', 'url' => '/api/user', 'desc' => 'Get authenticated user profile', 'req' => null, 'res' => ['user' => ['id' => 1, 'name' => '...', 'email' => '...', 'role' => 'candidate', 'profile' => '...']]],
                        ['method' => 'POST', 'url' => '/api/candidate/profile-update', 'desc' => 'Update candidate profile details', 'req' => ['headline' => 'Senior Developer', 'skills' => ['PHP', 'Laravel'], 'city' => 'Dhaka'], 'res' => ['status' => true, 'message' => 'Profile updated']],
                        ['method' => 'GET', 'url' => '/api/candidate/recommended-jobs', 'desc' => 'Get AI-powered job recommendations', 'req' => null, 'res' => ['status' => true, 'data' => ['jobs' => ['...']]]],
                        ['method' => 'GET', 'url' => '/api/candidate/applied-jobs', 'desc' => 'List all jobs the candidate has applied to', 'req' => null, 'res' => ['status' => true, 'data' => ['applications' => ['...']]]],
                        ['method' => 'POST', 'url' => '/api/candidate/toggle-save/{jobId}', 'desc' => 'Save or unsave a job posting', 'req' => null, 'res' => ['status' => true, 'saved' => true]],
                        ['method' => 'POST', 'url' => '/api/jobs/{id}/apply', 'desc' => 'Apply to a specific job', 'req' => ['cover_letter' => 'I am interested...', 'expected_salary' => 50000], 'res' => ['status' => true, 'message' => 'Application submitted']],
                        ['method' => 'GET', 'url' => '/api/candidate/cv/profile', 'desc' => 'Get CV profile data', 'req' => null, 'res' => ['status' => true, 'data' => ['profile' => '...']]],
                        ['method' => 'POST', 'url' => '/api/candidate/cv/profile/update', 'desc' => 'Update CV profile data', 'req' => ['summary' => 'Experienced developer...', 'experience' => ['...']], 'res' => ['status' => true, 'message' => 'CV profile updated']],
                        ['method' => 'GET', 'url' => '/api/candidate/wallet', 'desc' => 'Get wallet balance and transaction history', 'req' => null, 'res' => ['status' => true, 'data' => ['balance' => 5000, 'transactions' => ['...']]]],
                    ];
                @endphp

                @foreach($candidateEndpoints as $ep)
                    @include('filament.pages._partials._api-endpoint', ['ep' => $ep])
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-building-office-2', 'w-5 h-5 text-gray-400')
                    Employer APIs
                </div>
            </x-slot>

            <div class="space-y-4 mt-4">
                @php
                    $employerEndpoints = [
                        ['method' => 'GET', 'url' => '/api/employer/dashboard', 'desc' => 'Get employer dashboard analytics', 'req' => null, 'res' => ['status' => true, 'data' => ['total_jobs' => 12, 'active_jobs' => 8, 'total_applicants' => 156]]],
                        ['method' => 'GET', 'url' => '/api/employer/profile', 'desc' => 'Get employer/company profile', 'req' => null, 'res' => ['status' => true, 'data' => ['company' => ['name' => '...', 'logo' => '...']]]],
                        ['method' => 'POST', 'url' => '/api/employer/jobs', 'desc' => 'Create a new job posting', 'req' => ['title' => 'Laravel Developer', 'description' => '...', 'salary_min' => 40000, 'salary_max' => 80000, 'location' => 'Dhaka', 'job_type' => 'Full-time'], 'res' => ['status' => true, 'message' => 'Job created', 'data' => ['job' => '...']]],
                        ['method' => 'PUT', 'url' => '/api/employer/jobs/{id}', 'desc' => 'Update an existing job posting', 'req' => ['title' => 'Updated Title'], 'res' => ['status' => true, 'message' => 'Job updated']],
                        ['method' => 'DELETE', 'url' => '/api/employer/jobs/{id}', 'desc' => 'Delete a job posting', 'req' => null, 'res' => ['status' => true, 'message' => 'Job deleted']],
                        ['method' => 'GET', 'url' => '/api/employer/applicants', 'desc' => 'List all applicants for employer jobs', 'req' => null, 'res' => ['status' => true, 'data' => ['applicants' => ['...']]]],
                        ['method' => 'POST', 'url' => '/api/employer/applicants/{id}/status', 'desc' => 'Update applicant status (shortlist/reject)', 'req' => ['status' => 'shortlisted'], 'res' => ['status' => true, 'message' => 'Status updated']],
                        ['method' => 'GET', 'url' => '/api/employer/promotions', 'desc' => 'List all ad promotions', 'req' => null, 'res' => ['status' => true, 'data' => ['promotions' => ['...']]]],
                        ['method' => 'POST', 'url' => '/api/employer/promotions', 'desc' => 'Create a new promotion campaign', 'req' => ['job_id' => 1, 'daily_budget' => 50, 'start_date' => '2024-01-01', 'end_date' => '2024-01-30'], 'res' => ['status' => true, 'message' => 'Promotion created']],
                    ];
                @endphp

                @foreach($employerEndpoints as $ep)
                    @include('filament.pages._partials._api-endpoint', ['ep' => $ep])
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-briefcase', 'w-5 h-5 text-gray-400')
                    Jobs APIs
                </div>
            </x-slot>

            <div class="space-y-4 mt-4">
                @php
                    $jobEndpoints = [
                        ['method' => 'GET', 'url' => '/api/jobs', 'desc' => 'List all active jobs with filters', 'req' => null, 'res' => ['status' => true, 'data' => ['jobs' => ['...'], 'meta' => ['total' => 500]]]],
                        ['method' => 'GET', 'url' => '/api/jobs/{id}', 'desc' => 'Get detailed information for a specific job', 'req' => null, 'res' => ['status' => true, 'data' => ['job' => ['id' => 1, 'title' => '...', 'company' => '...']]]],
                        ['method' => 'GET', 'url' => '/api/jobs/remote', 'desc' => 'List all remote job postings', 'req' => null, 'res' => ['status' => true, 'data' => ['jobs' => ['...']]]],
                        ['method' => 'GET', 'url' => '/api/search', 'desc' => 'Full-text search across jobs, companies, and candidates', 'req' => null, 'res' => ['status' => true, 'data' => ['jobs' => ['...'], 'companies' => ['...']]]],
                        ['method' => 'GET', 'url' => '/api/search/suggestions', 'desc' => 'Get search autocomplete suggestions', 'req' => null, 'res' => ['status' => true, 'data' => ['suggestions' => ['laravel developer', 'remote jobs']]]],
                    ];
                @endphp

                @foreach($jobEndpoints as $ep)
                    @include('filament.pages._partials._api-endpoint', ['ep' => $ep])
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-banknotes', 'w-5 h-5 text-gray-400')
                    Wallet APIs
                </div>
            </x-slot>

            <div class="space-y-4 mt-4">
                @php
                    $walletEndpoints = [
                        ['method' => 'GET', 'url' => '/api/employer/wallet', 'desc' => 'Get wallet balance and recent transactions', 'req' => null, 'res' => ['status' => true, 'data' => ['balance' => 10000, 'currency' => 'BDT', 'transactions' => ['...']]]],
                        ['method' => 'POST', 'url' => '/api/employer/deposit', 'desc' => 'Initiate a wallet deposit via payment gateway', 'req' => ['amount' => 500, 'gateway' => 'onipay'], 'res' => ['status' => true, 'data' => ['payment_url' => 'https://...']]],
                        ['method' => 'POST', 'url' => '/api/candidate/withdraw', 'desc' => 'Request a withdrawal from wallet', 'req' => ['amount' => 2000, 'gateway' => 'bkash', 'account' => '+8801712345678'], 'res' => ['status' => true, 'message' => 'Withdrawal request submitted']],
                    ];
                @endphp

                @foreach($walletEndpoints as $ep)
                    @include('filament.pages._partials._api-endpoint', ['ep' => $ep])
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-bell', 'w-5 h-5 text-gray-400')
                    Notifications APIs
                </div>
            </x-slot>

            <div class="space-y-4 mt-4">
                @php
                    $notifEndpoints = [
                        ['method' => 'GET', 'url' => '/api/notifications', 'desc' => 'List all notifications for the authenticated user', 'req' => null, 'res' => ['status' => true, 'data' => ['notifications' => ['...']]]],
                        ['method' => 'POST', 'url' => '/api/notifications/{id}/read', 'desc' => 'Mark a specific notification as read', 'req' => null, 'res' => ['status' => true, 'message' => 'Notification marked as read']],
                        ['method' => 'POST', 'url' => '/api/notifications/read-all', 'desc' => 'Mark all notifications as read', 'req' => null, 'res' => ['status' => true, 'message' => 'All notifications marked as read']],
                        ['method' => 'POST', 'url' => '/api/notifications/bulk-read', 'desc' => 'Mark multiple notifications as read', 'req' => ['ids' => [1, 2, 3]], 'res' => ['status' => true, 'message' => 'Selected notifications marked as read']],
                    ];
                @endphp

                @foreach($notifEndpoints as $ep)
                    @include('filament.pages._partials._api-endpoint', ['ep' => $ep])
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-chat-bubble-left-right', 'w-5 h-5 text-gray-400')
                    Messaging APIs
                </div>
            </x-slot>

            <div class="space-y-4 mt-4">
                @php
                    $msgEndpoints = [
                        ['method' => 'GET', 'url' => '/api/messages/inbox', 'desc' => 'Get all conversations for the user', 'req' => null, 'res' => ['status' => true, 'data' => ['conversations' => ['...']]]],
                        ['method' => 'GET', 'url' => '/api/messages/unread-count', 'desc' => 'Get count of unread messages', 'req' => null, 'res' => ['status' => true, 'data' => ['count' => 5]]],
                        ['method' => 'GET', 'url' => '/api/messages/direct/{targetUserId}', 'desc' => 'Fetch direct messages with a user', 'req' => null, 'res' => ['status' => true, 'data' => ['messages' => ['...']]]],
                        ['method' => 'POST', 'url' => '/api/messages/direct/{targetUserId}', 'desc' => 'Send a direct message to a user', 'req' => ['message' => 'Hello!'], 'res' => ['status' => true, 'data' => ['message' => '...']]],
                        ['method' => 'GET', 'url' => '/api/messages/conversation/{uuid}', 'desc' => 'Fetch messages by conversation UUID', 'req' => null, 'res' => ['status' => true, 'data' => ['messages' => ['...']]]],
                        ['method' => 'POST', 'url' => '/api/messages/conversation/{uuid}', 'desc' => 'Send a message in a conversation by UUID', 'req' => ['message' => 'Hello!'], 'res' => ['status' => true, 'data' => ['message' => '...']]],
                    ];
                @endphp

                @foreach($msgEndpoints as $ep)
                    @include('filament.pages._partials._api-endpoint', ['ep' => $ep])
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-building-office', 'w-5 h-5 text-gray-400')
                    Company APIs
                </div>
            </x-slot>

            <div class="space-y-4 mt-4">
                @php
                    $companyEndpoints = [
                        ['method' => 'GET', 'url' => '/api/companies', 'desc' => 'List all companies', 'req' => null, 'res' => ['status' => true, 'data' => ['companies' => ['...']]]],
                        ['method' => 'GET', 'url' => '/api/companies/{slug}', 'desc' => 'Get a company public profile by slug', 'req' => null, 'res' => ['status' => true, 'data' => ['company' => ['name' => '...', 'logo' => '...', 'rating' => 4.5]]]],
                        ['method' => 'POST', 'url' => '/api/candidate/companies/{id}/follow', 'desc' => 'Follow or unfollow a company', 'req' => null, 'res' => ['status' => true, 'following' => true]],
                        ['method' => 'POST', 'url' => '/api/candidate/companies/{id}/reviews', 'desc' => 'Submit a review for a company', 'req' => ['rating' => 5, 'title' => 'Great company!', 'review' => '...'], 'res' => ['status' => true, 'message' => 'Review submitted']],
                        ['method' => 'GET', 'url' => '/api/companies/{companyId}/reviews', 'desc' => 'List reviews for a company', 'req' => null, 'res' => ['status' => true, 'data' => ['reviews' => ['...']]]],
                    ];
                @endphp

                @foreach($companyEndpoints as $ep)
                    @include('filament.pages._partials._api-endpoint', ['ep' => $ep])
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
