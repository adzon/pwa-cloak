@php
    /**
     * 通用应用信息显示组件
     * @var \App\Models\Application $record 应用记录
     * @var bool $compact 是否使用紧凑模式（用于下拉选项）
     */
    $compact = $compact ?? false;
@endphp

@if($compact)
    {{-- 紧凑模式（用于下拉选项） --}}
    <div class="flex items-center gap-2">
        @if(!empty($record->icon))
            <div class="flex-shrink-0">
                <img
                    src="{{ Storage::disk('do')->url($record->icon) }}"
                    alt="{{ $record->name }}"
                    class="h-6 w-6 rounded-md object-cover shadow-sm"
                />
            </div>
        @endif

        <div class="flex min-w-0 flex-col">
            <div class="truncate text-xs font-medium text-gray-950 dark:text-white">
                {{ $record->name }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                ID: {{ $record->id }}
            </div>
        </div>
    </div>
@else
    {{-- 完整模式（用于表格列） --}}
    <div class="fi-ta-text-item">
        <div class="flex items-start gap-3">
            {{-- APP 图标 --}}
            @if(!empty($record->icon))
                <div class="flex-shrink-0">
                    <div class="group relative">
                        <img
                            src="{{ Storage::disk('do')->url($record->icon) }}"
                            alt="{{ $record->name }}"
                            class="h-16 w-16 rounded-xl object-cover shadow-md ring-2 ring-gray-950/5 transition-all group-hover:ring-primary-600 dark:ring-white/10 dark:group-hover:ring-primary-500"
                        />
                        <div class="absolute inset-0 rounded-xl bg-gradient-to-t from-black/20 to-transparent opacity-0 transition-opacity group-hover:opacity-100"></div>
                    </div>
                </div>
            @else
                <div class="flex-shrink-0">
                    <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-gray-100 shadow-md dark:bg-gray-800">
                        <svg class="h-8 w-8 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            @endif

            {{-- APP 信息 --}}
            <div class="flex-1 min-w-0">
                {{-- 应用名称 --}}
                <div class="mb-2 flex items-center gap-2">
                    <h4 class="truncate text-sm font-medium text-gray-950 dark:text-white">
                        {{ $record->name }}
                    </h4>
                </div>

                {{-- APP ID --}}
                <div class="mb-2 flex items-center gap-2">
                    <span class="fi-badge fi-badge-xs inline-flex items-center justify-center gap-x-1 rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset bg-primary-50 text-primary-600 ring-primary-600/10 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/20">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                        </svg>
                        APP ID: {{ $record->id }}
                    </span>
                </div>

                {{-- 备注信息 --}}
                @if ($record->remark)
                    <div class="mt-2">
                        <div class="flex items-start gap-1.5 rounded-lg border border-warning-600/20 bg-warning-50 p-2 dark:border-warning-400/20 dark:bg-warning-400/10">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                            </svg>
                            <div class="flex-1 min-w-0">
                                <p class="break-words text-xs text-warning-700 dark:text-warning-300">
                                    {{ $record->remark }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
