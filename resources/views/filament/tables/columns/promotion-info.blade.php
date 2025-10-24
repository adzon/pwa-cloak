<!-- resources/views/filament/tables/columns/promotion-info.blade.php -->
@php
    /** @var \App\Models\Promotion $record */
@endphp

<div class="fi-ta-text-item">
    {{-- 推广名称和基础信息 --}}
    <div class="flex items-start gap-3 mb-3">
        <div class="flex-shrink-0 mt-0.5">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-500 shadow-sm dark:bg-primary-600">
                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                </svg>
            </div>
        </div>
        
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
                <h4 class="text-sm font-medium text-gray-950 dark:text-white truncate">
                    {{ $record->promotion_name }}
                </h4>
                <span class="fi-badge fi-badge-xs inline-flex items-center justify-center gap-x-1 rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset bg-gray-50 text-gray-600 ring-gray-600/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                    #{{ $record->id }}
                </span>
            </div>
            
            <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    {{ $record->created_at->format('Y-m-d H:i') }}
                </span>
            </div>
        </div>
    </div>

    {{-- 推广链接区域 --}}
    <div class="space-y-2">
        <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400 mb-2">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            推广链接（广告访问和审核页）
        </div>
        
        @foreach($record->full_promotion_urls as $index => $url)
            <div class="group relative">
                <div class="flex items-center gap-2 rounded-lg border border-gray-950/10 bg-white p-2.5 shadow-sm transition hover:border-primary-600 dark:border-white/10 dark:bg-white/5 dark:hover:border-primary-500">
                    <div class="flex-shrink-0">
                        <div class="flex h-6 w-6 items-center justify-center rounded bg-primary-50 dark:bg-primary-500/10">
                            <span class="text-xs font-medium text-primary-600 dark:text-primary-400">{{ $index + 1 }}</span>
                        </div>
                    </div>
                    
                    <input
                        type="text"
                        readonly
                        class="flex-1 cursor-text border-0 bg-transparent px-0 py-0 text-xs font-mono text-gray-700 focus:ring-0 dark:text-gray-300"
                        value="{{ $url }}"
                        onclick="this.select()"
                    >
                    
                    <button
                        type="button"
                        class="fi-btn relative inline-flex items-center justify-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium outline-none transition bg-primary-50 text-primary-600 hover:bg-primary-100 focus-visible:bg-primary-100 dark:bg-primary-400/10 dark:text-primary-400 dark:hover:bg-primary-400/15 dark:focus-visible:bg-primary-400/15"
                        x-data="{}"
                        x-on:click="
                            navigator.clipboard.writeText('{{ $url }}');
                            $tooltip('已复制', { timeout: 1500 });
                        "
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        复制
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>
