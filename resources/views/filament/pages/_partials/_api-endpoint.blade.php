@php
    $methodColors = [
        'GET' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'POST' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        'PUT' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        'PATCH' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        'DELETE' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
    ];
@endphp

<div class="p-4 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5">
    <div class="flex items-center gap-3 mb-3">
        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $methodColors[$ep['method']] ?? 'bg-gray-100 text-gray-700' }}">
            {{ $ep['method'] }}
        </span>
        <code class="text-sm font-mono text-gray-800 dark:text-gray-200">{{ $ep['url'] }}</code>
    </div>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">{{ $ep['desc'] }}</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @if($ep['req'])
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Request</p>
                <pre class="p-3 rounded-lg bg-gray-900 dark:bg-gray-950 text-xs text-gray-300 font-mono overflow-x-auto max-h-40">{{ json_encode($ep['req'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endif
        <div class="{{ !$ep['req'] ? 'md:col-span-2' : '' }}">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Response</p>
            <pre class="p-3 rounded-lg bg-gray-900 dark:bg-gray-950 text-xs text-gray-300 font-mono overflow-x-auto max-h-40">{{ json_encode($ep['res'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>
</div>
