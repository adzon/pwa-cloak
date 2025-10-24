@php
    /** @var \App\Models\Promotion $record */
@endphp

<div class="fi-ta-text-item">
    <div class="space-y-3">
        {{-- 像素信息 --}}
        @if($record->pixel)
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-500 shadow-sm dark:bg-primary-600">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="mb-1 flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">
                            {{ $record->pixel->pixel_name }}
                        </span>
                    </div>

                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        像素 ID: {{ $record->pixel->id }}
                    </div>
                </div>
            </div>
        @else
            <div class="flex items-center gap-2 rounded-lg border border-dashed border-gray-950/10 bg-white p-2.5 dark:border-white/10 dark:bg-white/5">
                <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">未配置像素</span>
            </div>
        @endif

        {{-- 配置信息卡片 --}}
        <div class="grid grid-cols-1 gap-2">
            {{-- 广告防封 --}}
            <div class="flex items-center justify-between rounded-lg border border-gray-950/10 bg-white p-2.5 shadow-sm dark:border-white/10 dark:bg-white/5">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">广告防封</span>
                </div>

                @if($record->is_open_cloak)
                    <span class="fi-badge fi-badge-xs inline-flex items-center justify-center gap-x-1 rounded-full px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset bg-success-50 text-success-700 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/20">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        已开启
                    </span>
                @else
                    <span class="fi-badge fi-badge-xs inline-flex items-center justify-center gap-x-1 rounded-full px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset bg-gray-50 text-gray-600 ring-gray-600/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        已关闭
                    </span>
                @endif
            </div>

            {{-- 审核模版 --}}
            @if($record->template_name)
                <div class="flex items-center gap-2 rounded-lg border border-info-600/20 bg-info-50 p-2.5 shadow-sm dark:border-info-400/20 dark:bg-info-400/10">
                    <svg class="h-4 w-4 flex-shrink-0 text-info-500 dark:text-info-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <div class="flex-1 min-w-0">
                        <div class="mb-0.5 text-xs text-gray-500 dark:text-gray-400">审核模版</div>
                        <div class="truncate text-xs font-medium text-info-700 dark:text-info-300">
                            {{ $record->template_name }}
                        </div>
                    </div>
                </div>
            @endif

            {{-- 地区信息 --}}
            @if($record->region_names)
                <div class="flex items-center gap-2 rounded-lg border border-success-600/20 bg-success-50 p-2.5 shadow-sm dark:border-success-400/20 dark:bg-success-400/10">
                    <svg class="h-4 w-4 flex-shrink-0 text-success-500 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1 min-w-0">
                        <div class="mb-0.5 text-xs text-gray-500 dark:text-gray-400">投放地区</div>
                        <div class="break-words text-xs font-medium text-success-700 dark:text-success-300">
                            {{ $record->region_names }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
