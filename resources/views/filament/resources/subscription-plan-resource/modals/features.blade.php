@php
    $featureGroups = [];
    if ($employerFeatures->isNotEmpty()) {
        $featureGroups[] = ['title' => 'Employer Features', 'icon' => 'heroicon-o-building-office-2', 'features' => $employerFeatures];
    }
    if ($candidateFeatures->isNotEmpty()) {
        $featureGroups[] = ['title' => 'Candidate Features', 'icon' => 'heroicon-o-user', 'features' => $candidateFeatures];
    }
    if ($generalFeatures->isNotEmpty()) {
        $featureGroups[] = ['title' => 'General Features', 'icon' => 'heroicon-o-cog-6-tooth', 'features' => $generalFeatures];
    }
@endphp

<div class="space-y-4">
    @forelse ($featureGroups as $group)
        <div class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center gap-2 border-b border-gray-200 px-4 py-3 dark:border-white/10">
                <x-heroicon-o-list-bullet class="h-5 w-5 text-gray-500" />
                <span class="font-semibold text-gray-950 dark:text-white">{{ $group['title'] }}</span>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($group['features'] as $fv)
                    <div class="flex items-center justify-between px-4 py-2.5">
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-gray-950 dark:text-white">{{ $fv->feature->name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $fv->feature->description }}</div>
                        </div>
                        <div class="ml-4 flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                                @if ($fv->feature->type === 'boolean')
                                    {{ $fv->value === 'true' ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20' : 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20' }}
                                @else
                                    bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-400 dark:ring-blue-500/20
                                @endif
                            ">
                                {{ $fv->feature->type === 'boolean' ? ($fv->value === 'true' ? 'Enabled' : 'Disabled') : $fv->value }}
                            </span>
                            <span class="text-xs text-gray-400">{{ $fv->feature->type }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
            No features configured for this plan.
        </div>
    @endforelse
</div>
