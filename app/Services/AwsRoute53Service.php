<?php

namespace App\Services;

use Aws\Route53\Route53Client;
use Illuminate\Support\Facades\Log;

class AwsRoute53Service
{
    protected ?Route53Client $client = null;

    public function __construct()
    {
        // 如果开启了 mock 或处于本地/测试环境，不实例化 AWS 客户端
        if (! $this->isMock()) {
            $this->client = new Route53Client([
                'version' => 'latest',
                'region' => config('services.aws.region'),
                'credentials' => [
                    'key' => config('services.aws.access_key'),
                    'secret' => config('services.aws.secret_key'),
                ],
            ]);
        }
    }

    /**
     * 判断是否启用 Mock
     */
    protected function isMock(): bool
    {
        return app()->environment('local', 'testing') || config('services.aws.mock', false);
    }

    /**
     * 创建 HostedZone（支持 Mock）
     */
    public function createHostedZone(string $domain): array
    {
        if ($this->isMock()) {
            return [
                'code' => 0,
                'msg' => '操作成功（mock）',
                'data' => [
                    'domain' => $domain,
                    'hosting_id' => '/hostedzone/Z0MOCK' . strtoupper(substr(md5($domain), 0, 10)),
                    'created_at' => now()->timestamp,
                    'hosting_name_servers' => [
                        'ns-1113.awsdns-11.org',
                        'ns-468.awsdns-58.com',
                        'ns-1766.awsdns-28.co.uk',
                        'ns-740.awsdns-28.net',
                    ],
                    'id' => rand(1000, 2000),
                ],
            ];
        }

        try {
            $result = $this->client->createHostedZone([
                'Name' => $domain,
                'CallerReference' => (string) str()->uuid(),
            ]);

            return [
                'code' => 0,
                'msg' => '操作成功',
                'data' => [
                    'domain' => $domain,
                    'hosting_id' => $result['HostedZone']['Id'] ?? null,
                    'hosting_name_servers' => $result['DelegationSet']['NameServers'] ?? [],
                    'created_at' => now()->timestamp,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('创建 HostedZone 失败：' . $e->getMessage());
            return [
                'code' => 1,
                'msg' => '创建 HostedZone 失败：' . $e->getMessage(),
            ];
        }
    }

    public function resolveNsRecord(string $domain, string $expectedNs): bool
    {
        try {
            $records = dns_get_record($domain, DNS_NS);
            if (!$records) return false;

            $actualNs = collect($records)
                ->pluck('target')
                ->map(fn($ns) => strtolower(trim($ns, '.')))
                ->toArray();

            $target = strtolower(trim($expectedNs, '.'));

            return in_array($target, $actualNs);
        } catch (\Throwable $e) {
            Log::warning("DNS检测失败: {$domain} - {$expectedNs} - {$e->getMessage()}");
            return false;
        }
    }
}
