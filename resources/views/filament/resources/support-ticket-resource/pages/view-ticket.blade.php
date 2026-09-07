<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        <!-- Message Timeline (Left side, takes 3/4 cols) -->
        <div class="space-y-6 lg:col-span-3">
            
            <!-- Conversation Box -->
            <div class="space-y-6">
                @foreach ($this->getRecord()->messages as $msg)
                    @php
                        $sender = $msg->sender;
                        $isAdmin = $msg->is_admin_reply;
                        // Initials for avatar fallback
                        $initials = strtoupper(substr($sender->name, 0, 2));
                    @endphp
                    
                    <div class="flex items-start gap-4 p-4 rounded-xl border {{ $isAdmin ? 'bg-indigo-50/50 border-indigo-200 dark:bg-indigo-950/20 dark:border-indigo-900/50' : 'bg-white border-gray-200 dark:bg-gray-900 dark:border-gray-800' }} shadow-sm transition hover:shadow">
                        <!-- Avatar -->
                        <div class="flex-shrink-0">
                            @if ($sender->profile && $sender->profile->avatar)
                                <img src="{{ asset('storage/' . $sender->profile->avatar) }}" alt="{{ $sender->name }}" class="w-10 h-10 rounded-full object-cover ring-2 ring-primary-500/10" />
                            @else
                                <div class="w-10 h-10 rounded-full {{ $isAdmin ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }} flex items-center justify-center font-bold text-sm ring-2 ring-primary-500/10">
                                    {{ $initials }}
                                </div>
                            @endif
                        </div>
                        
                        <!-- Message Details -->
                        <div class="flex-1 space-y-2 overflow-hidden">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-gray-900 dark:text-white">{{ $sender->name }}</span>
                                    
                                    @if ($isAdmin)
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-200 ring-1 ring-indigo-600/10">
                                            {{ $msg->admin_role_label ?? 'Support Agent' }}
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200 ring-1 ring-gray-600/10">
                                            Client
                                        </span>
                                    @endif
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $msg->created_at->diffForHumans() }} ({{ $msg->created_at->format('M d, Y h:i A') }})
                                </span>
                            </div>
                            
                            <!-- Content -->
                            <div class="prose prose-sm max-w-none dark:prose-invert text-gray-700 dark:text-gray-300 leading-relaxed break-words">
                                {!! \Illuminate\Support\Str::markdown(strip_tags($msg->message)) !!}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Reply Box -->
            @if ($this->getRecord()->status !== 'closed')
                <div class="p-6 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm space-y-4">
                    <h3 class="text-md font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-chat-bubble-left-right class="w-5 h-5 text-primary-500" />
                        Reply to Support Ticket
                    </h3>
                    
                    <form wire:submit.prevent="sendReply" class="space-y-4">
                        <div class="space-y-1">
                            <textarea 
                                wire:model="replyMessage" 
                                id="replyMessage" 
                                rows="5" 
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500" 
                                placeholder="Write your response here... (Markdown supported)"
                                required
                            ></textarea>
                            @error('replyMessage')
                                <span class="text-xs text-danger-600">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="flex justify-end">
                            <button 
                                type="submit" 
                                class="inline-flex items-center justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-150 ease-in-out cursor-pointer"
                            >
                                <x-heroicon-m-paper-airplane class="w-4 h-4 mr-2" />
                                Submit Reply
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="p-4 rounded-xl bg-gray-50 border border-gray-200 dark:bg-gray-950/20 dark:border-gray-900 text-center text-gray-500 dark:text-gray-400 font-semibold flex items-center justify-center gap-2">
                    <x-heroicon-o-lock-closed class="w-5 h-5 text-danger-500" />
                    This support ticket is closed. You can no longer post replies.
                </div>
            @endif

        </div>
        
        <!-- Ticket Sidebar Details (Right side, takes 1/4 cols) -->
        <div class="space-y-6">
            <div class="p-6 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm space-y-4">
                <h3 class="text-md font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-800 pb-2">
                    Ticket Information
                </h3>
                
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Ticket ID</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $this->getRecord()->ticket_number }}</span>
                    </div>
                    
                    <div>
                        <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</span>
                        @php
                            $statusColor = $this->getRecord()->status_color;
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $statusColor === 'danger' ? 'bg-red-100 text-red-800 dark:bg-red-900/60 dark:text-red-200' : ($statusColor === 'warning' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/60 dark:text-yellow-200' : ($statusColor === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900/60 dark:text-green-200' : ($statusColor === 'info' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'))) }}">
                            {{ ucwords($this->getRecord()->status) }}
                        </span>
                    </div>
                    
                    <div>
                        <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Priority</span>
                        @php
                            $priorityColor = $this->getRecord()->priority_color;
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $priorityColor === 'danger' ? 'bg-red-100 text-red-800 dark:bg-red-900/60 dark:text-red-200' : ($priorityColor === 'warning' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/60 dark:text-yellow-200' : ($priorityColor === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900/60 dark:text-green-200' : ($priorityColor === 'info' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'))) }}">
                            {{ ucwords($this->getRecord()->priority) }}
                        </span>
                    </div>
                    
                    <div>
                        <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Category</span>
                        <span class="font-semibold text-gray-900 dark:text-white">
                            {{ ucwords(str_replace('_', ' ', $this->getRecord()->category)) }}
                        </span>
                    </div>
                    
                    <div>
                        <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Client</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $this->getRecord()->user->name }}</span>
                        <span class="block text-xs text-gray-500">{{ $this->getRecord()->user->email }}</span>
                    </div>
                    
                    <div>
                        <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Assigned Agent</span>
                        <span class="font-semibold text-gray-900 dark:text-white">
                            {{ $this->getRecord()->assignedAdmin?->name ?? 'Unassigned' }}
                        </span>
                    </div>
                    
                    <div>
                        <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Created Date</span>
                        <span class="font-semibold text-gray-900 dark:text-white">
                            {{ $this->getRecord()->created_at->format('M d, Y h:i A') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
