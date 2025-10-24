@php
    $servers = $getState() ?? [];
    $servers = is_array($servers) ? $servers : [];
    $totalServers = count($servers);
@endphp

<div class="space-y-6">
    @if($totalServers > 0)
        <!-- 说明提示 -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-400 p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        配置说明
                    </p>
                    <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                        请前往您的域名注册商（如阿里云、腾讯云、GoDaddy等）管理后台，将以下 DNS 服务器配置到域名的 NS 记录中。配置完成后，点击"下一步"继续验证。
                    </p>
                </div>
            </div>
        </div>

        <!-- DNS 服务器列表 -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    DNS 服务器列表
                </h4>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    共 {{ $totalServers }} 个服务器
                </span>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                序号
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                DNS 服务器地址
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                操作
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($servers as $index => $ns)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $index + 1 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <code class="text-sm font-mono text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-800 px-3 py-1.5 rounded">
                                        {{ $ns }}
                                    </code>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <button
                                        x-data="{ copied: false }"
                                        @click="
                                            navigator.clipboard.writeText('{{ $ns }}');
                                            copied = true;
                                            setTimeout(() => copied = false, 2000);
                                        "
                                        type="button"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 dark:text-blue-100 dark:bg-blue-800 dark:hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                    >
                                        <svg x-show="!copied" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                        <svg x-show="copied" class="w-4 h-4 mr-1 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span x-show="!copied">复制</span>
                                        <span x-show="copied" class="text-green-600 dark:text-green-400">已复制</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 操作步骤 -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">配置步骤：</h5>
            <ol class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full text-xs font-semibold mr-2">1</span>
                    <span>登录您的域名注册商管理后台</span>
                </li>
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full text-xs font-semibold mr-2">2</span>
                    <span>找到域名的 DNS 管理或名称服务器设置</span>
                </li>
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full text-xs font-semibold mr-2">3</span>
                    <span>将上述 DNS 服务器地址逐一添加到 NS 记录中</span>
                </li>
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full text-xs font-semibold mr-2">4</span>
                    <span>保存设置后，等待 DNS 解析生效（通常需要 5-10 分钟）</span>
                </li>
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full text-xs font-semibold mr-2">5</span>
                    <span>点击"下一步"进行 DNS 解析验证</span>
                </li>
            </ol>
        </div>

        <!-- 温馨提示 -->
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        温馨提示
                    </p>
                    <ul class="mt-1 text-sm text-yellow-700 dark:text-yellow-300 list-disc list-inside space-y-1">
                        <li>DNS 解析生效时间因域名注册商而异，通常为 5-60 分钟</li>
                        <li>如果验证失败，请稍后再试或联系域名注册商确认配置</li>
                        <li>请确保替换掉原有的 NS 记录，而不是追加</li>
                    </ul>
                </div>
            </div>
        </div>
    @else
        <!-- 错误提示 -->
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800 dark:text-red-200">
                        获取 DNS 服务器失败
                    </p>
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                        未获取到 DNS 服务器信息，请返回上一步重新操作或联系技术支持。
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
