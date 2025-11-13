@php
    /**
     * @var \App\Models\Application $record
     */
@endphp

<div
    class="group relative flex flex-col rounded-2xl border border-gray-300/40 bg-white/90 p-6 shadow-sm
           transition-all duration-200 hover:-translate-y-1 hover:shadow-lg dark:border-gray-700/60
           dark:bg-gray-800/70 dark:hover:border-primary-600/50 dark:hover:bg-gray-800/90"
>
    {{-- 应用图标 --}}
    <div class="flex justify-center mb-4">
        @if(!empty($record->icon))
            <div class="relative overflow-hidden rounded-2xl">
                <img
                    src="{{ Storage::disk('do')->url($record->icon) }}"
                    alt="{{ $record->name }}"
                    class="h-28 w-28 rounded-2xl object-cover shadow-md ring-1 ring-gray-950/5
                           transition-transform duration-200 group-hover:scale-105 dark:ring-white/10"
                />
                <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-primary-500/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            </div>
        @else
            <div class="flex h-28 w-28 items-center justify-center rounded-2xl bg-gradient-to-br from-gray-100 to-gray-200 shadow-md dark:from-gray-700 dark:to-gray-800">
                <svg class="h-14 w-14 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif
    </div>

    {{-- 应用信息 --}}
    <div class="flex flex-col items-center text-center flex-1">
        <div class="flex items-center justify-center gap-2 mb-1">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white line-clamp-1">
                {{ $record->name }}
            </h3>
        </div>

        @if($record->remark)
            <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 mb-2">
                {{ $record->remark }}
            </p>
        @endif

        <div class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-3 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
            <x-heroicon-o-hashtag class="h-3 w-3" />
            {{ __('ID: ') . $record->id }}
        </div>
    </div>

    {{-- 操作按钮 --}}
    <div class="mt-5 grid grid-cols-2 gap-2">
        <a
            href="{{ \App\Filament\Resources\CustomAppResource::getUrl('edit', ['record' => $record]) }}"
            class="flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-gray-50 py-2.5 text-sm font-medium text-gray-700 transition-all duration-150 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-primary-900/30 dark:hover:text-primary-300"
        >
            <x-heroicon-o-pencil-square class="h-4 w-4" />
            编辑安装页
        </a>

        <a
            href="{{ \App\Filament\Resources\CustomAppResource::getUrl('create') }}?copy_from={{ $record->id }}"
            class="flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-gray-900 to-gray-800 py-2.5 text-sm font-medium text-white shadow-md transition-all duration-150 hover:from-gray-800 hover:to-gray-700 dark:from-gray-700 dark:to-gray-600 dark:hover:from-gray-600 dark:hover:to-gray-500"
        >
            <x-heroicon-o-document-duplicate class="h-4 w-4" />
            复制创建
        </a>
    </div>
</div>
