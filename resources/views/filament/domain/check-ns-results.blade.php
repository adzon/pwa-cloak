@php
    $state = $getState() ?? [];

    // 判断是否是创建场景
    $isCreatePage = $this instanceof \App\Filament\Resources\DomainResource\Pages\CreateDomain;
    
    // 优先从 Livewire 组件属性获取数据（创建场景）
    if ($isCreatePage && isset($this->nsResults) && !empty($this->nsResults)) {
        $results = $this->nsResults;
    } else {
        $results = $state['nsResults'] ?? [];
    }
    
    // 获取其他数据
    $nameServers = $state['hosting_name_servers'] ?? [];
    $status = $state['status'] ?? 0;
    $domain = $state['domain'] ?? '';
    
    // 统计结果
    $totalServers = count($nameServers);
    $successCount = collect($results)->filter(fn($v) => $v === 1)->count();
    $failCount = collect($results)->filter(fn($v) => $v === 0)->count();
    $pendingCount = $totalServers - $successCount - $failCount;
@endphp

<div 
    @if($isCreatePage && empty($results))
        wire:poll.1s
    @endif
    class="space-y-4"
>
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
            DNS 解析检测结果
        </h3>
        @if (!empty($nameServers))
            <div class="text-sm text-gray-600 dark:text-gray-400">
                共 {{ $totalServers }} 个服务器
                @if ($successCount > 0)
                    <span class="text-green-600 dark:text-green-400 font-medium">{{ $successCount }} 个成功</span>
                @endif
                @if ($failCount > 0)
                    <span class="text-red-600 dark:text-red-400 font-medium">{{ $failCount }} 个失败</span>
                @endif
            </div>
        @endif
    </div>

    @if (empty($nameServers))
        <div class="p-6 text-center text-gray-600 dark:text-gray-400 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <p class="mt-2">未获取到 DNS 服务器信息</p>
            <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">请返回上一步重新操作</p>
        </div>
    @elseif($isCreatePage && empty($results))
        <!-- 创建场景且还没有检测结果，显示检测中状态 -->
        <div class="relative overflow-hidden rounded-lg border border-blue-200 dark:border-blue-700 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20">
            <div class="px-6 py-8">
                <div class="flex items-center justify-center space-x-4">
                    <!-- 小号旋转图标 -->
                    <div class="flex-shrink-0">
                        <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    
                    <!-- 文字内容 -->
                    <div class="flex-1 text-left">
                        <h4 class="text-lg font-semibold text-blue-900 dark:text-blue-100">
                            正在检测 DNS 解析
                        </h4>
                        <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                            正在验证 {{ $totalServers }} 个 DNS 服务器，请稍候...
                        </p>
                    </div>
                </div>
                
                <!-- 进度条动画 -->
                <div class="mt-6">
                    <div class="h-1.5 w-full bg-blue-200 dark:bg-blue-800 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500 dark:bg-blue-400 rounded-full animate-pulse" style="width: 60%;"></div>
                    </div>
                    <p class="mt-2 text-xs text-center text-blue-600 dark:text-blue-400">
                        预计需要 2-3 秒
                    </p>
                </div>
            </div>
        </div>
    @else
        <!-- DNS 服务器列表 -->
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            序号
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            DNS 服务器
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            解析状态
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($nameServers as $index => $ns)
                        @php
                            $statusItem = $results[$ns] ?? null;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $index + 1 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <code class="text-sm font-mono text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">
                                        {{ $ns }}
                                    </code>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($statusItem === 1)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold text-green-800 bg-green-100 dark:text-green-100 dark:bg-green-800">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        解析成功
                                    </span>
                                @elseif ($statusItem === 0)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold text-red-800 bg-red-100 dark:text-red-100 dark:bg-red-800">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        解析失败
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold text-gray-800 bg-gray-100 dark:text-gray-100 dark:bg-gray-700">
                                        <svg class="animate-spin w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        检测中
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- 整体状态提示 --}}
        <div class="rounded-lg overflow-hidden">
            @if ($successCount > 0)
                <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800 dark:text-green-200">
                                域名 DNS 解析成功
                            </p>
                            <p class="mt-1 text-sm text-green-700 dark:text-green-300">
                                已有 {{ $successCount }} 个 DNS 服务器解析成功，您可以保存并继续使用该域名。
                            </p>
                        </div>
                    </div>
                </div>
            @elseif ($failCount === $totalServers)
                <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                域名 DNS 解析失败
                            </p>
                            <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                                所有 DNS 服务器解析失败，请确认已在域名注册商处更新 DNS 配置。您可以先保存域名，稍后重新检测。
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                DNS 解析进行中
                            </p>
                            <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                                正在检测 DNS 解析状态，请稍候。如果长时间未通过，请检查域名注册商的 DNS 配置。
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
