<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Services\AwsRoute53Service;
use Illuminate\Console\Command;

class CheckDomainResolution extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domain:check-resolution 
                            {--all : 检查所有域名，包括已解析的}
                            {--limit=10 : 每次检查的域名数量}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自动检查域名 DNS 解析状态';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $checkAll = $this->option('all');
        $limit = (int) $this->option('limit');

        // 获取需要检查的域名
        $query = Domain::query()
            ->whereNotNull('hosting_name_servers')
            ->whereNull('pid'); // 只检查父域名，不检查子域名

        if (!$checkAll) {
            // 默认只检查未解析的域名
            $query->where('status', 0);
        }

        $domains = $query->limit($limit)->get();

        if ($domains->isEmpty()) {
            $this->info('没有需要检查的域名');
            return 0;
        }

        $this->info("开始检查 {$domains->count()} 个域名的解析状态...");
        $this->newLine();

        $service = app(AwsRoute53Service::class);
        $successCount = 0;
        $failCount = 0;
        $unchangedCount = 0;

        foreach ($domains as $domain) {
            $this->line("检查域名: {$domain->domain}");

            $expectedNs = $domain->hosting_name_servers;
            if (empty($expectedNs)) {
                $this->warn("  ⚠️  域名 {$domain->domain} 没有 Name Servers 配置，跳过");
                continue;
            }

            $results = [];
            foreach ($expectedNs as $ns) {
                $ok = $service->resolveNsRecord($domain->domain, $ns);
                $results[$ns] = $ok ? 1 : 0;
                
                // 避免请求过快
                usleep(200000); // 0.2秒
            }

            $anyPassed = collect($results)->contains(1);
            $oldStatus = $domain->status;

            if ($anyPassed) {
                $domain->update(['status' => 1]);
                
                if (!$oldStatus) {
                    $this->info("  ✅ 解析成功（状态已更新）");
                    $successCount++;
                } else {
                    $this->comment("  ✓  解析成功（状态未变）");
                    $unchangedCount++;
                }
            } else {
                $domain->update(['status' => 0]);
                
                if ($oldStatus) {
                    $this->error("  ❌ 解析失败（状态已更新）");
                    $failCount++;
                } else {
                    $this->comment("  ✗  解析失败（状态未变）");
                    $unchangedCount++;
                }
            }

            // 显示详细结果
            if ($this->output->isVerbose()) {
                foreach ($results as $ns => $result) {
                    $status = $result ? '✓' : '✗';
                    $this->line("    {$status} {$ns}");
                }
            }

            $this->newLine();
        }

        // 输出统计信息
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('检查完成！统计信息：');
        $this->line("  总计检查: {$domains->count()} 个域名");
        $this->line("  新解析成功: {$successCount} 个");
        $this->line("  新解析失败: {$failCount} 个");
        $this->line("  状态未变: {$unchangedCount} 个");
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        return 0;
    }
}

