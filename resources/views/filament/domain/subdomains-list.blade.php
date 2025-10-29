<div class="space-y-4">
    @if($subdomains->count() > 0)
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700 table-fixed">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th scope="col" class="w-2/5 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            子域名
                        </th>
                        <th scope="col" class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            解析状态
                        </th>
                        <th scope="col" class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            使用状态
                        </th>
                        <th scope="col" class="w-1/4 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            创建时间
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($subdomains as $subdomain)
                        <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                <div class="flex items-center space-x-2">
                                    <span>{{ $subdomain->domain }}</span>
                                    <button 
                                        onclick="event.stopPropagation(); navigator.clipboard.writeText('{{ $subdomain->domain }}').then(() => { this.title = '已复制!'; setTimeout(() => { this.title = '复制域名'; }, 2000); })"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                                        title="复制域名"
                                        type="button"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($subdomain->status)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                        解析成功
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                        未解析
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($subdomain->checkUsage())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                        已使用
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100">
                                        未使用
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $subdomain->created_at->format('Y-m-d H:i:s') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="text-sm text-gray-500 dark:text-gray-400 text-center">
            共 {{ $subdomains->count() }} 个子域名
        </div>
    @else
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            暂无子域名
        </div>
    @endif
</div>

