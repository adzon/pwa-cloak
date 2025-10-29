<?php

namespace App\Console\Commands;

use App\Models\Pixel;
use App\Services\PixelValidationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckPixelStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pixel:check-status 
                            {--all : 检查所有像素，包括状态正常的}
                            {--limit=20 : 每次检查的像素数量}
                            {--channel= : 只检查指定渠道的像素（1=Facebook, 2=TikTok, 3=Google）}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自动检查像素 Access Token 状态并更新数据库';

    protected PixelValidationService $validationService;

    /**
     * Execute the console command.
     */
    public function handle(PixelValidationService $validationService)
    {
        $this->validationService = $validationService;

        $checkAll = $this->option('all');
        $limit = (int) $this->option('limit');
        $channel = $this->option('channel');

        // 构建查询
        $query = Pixel::query()
            ->where('is_delete', false)
            ->whereNotNull('access_token')
            ->whereNotNull('pixel_code');

        // 筛选渠道
        if ($channel) {
            $query->where('channel', (int) $channel);
        }

        // 筛选状态
        if (!$checkAll) {
            // 默认只检查状态异常的像素（status = 0）
            $query->where('status', 0);
        }

        $pixels = $query->limit($limit)->get();

        if ($pixels->isEmpty()) {
            $this->info('没有需要检查的像素');
            return 0;
        }

        $this->info("开始检查 {$pixels->count()} 个像素的 Access Token 状态...");
        $this->newLine();

        $successCount = 0;
        $failCount = 0;
        $unchangedCount = 0;

        $progressBar = $this->output->createProgressBar($pixels->count());
        $progressBar->start();

        foreach ($pixels as $pixel) {
            $progressBar->advance();
            
            $this->newLine();
            $this->line("检查像素: {$pixel->pixel_name} ({$pixel->pixel_code})");
            $this->line("  渠道: " . $this->getChannelName($pixel->channel));

            // 验证像素
            $result = $this->validationService->validatePixel(
                $pixel->channel,
                $pixel->pixel_code,
                $pixel->access_token
            );

            $oldStatus = $pixel->status;
            $newStatus = $result['valid'] ? 1 : 0;

            // 更新状态
            $pixel->update(['status' => $newStatus]);

            // 准备日志数据
            $logData = [
                'pixel_id' => $pixel->id,
                'pixel_code' => $pixel->pixel_code,
                'channel' => $pixel->channel,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'message' => $result['message']
            ];

            // 添加额外的诊断信息
            if (isset($result['error_code'])) {
                $logData['error_code'] = $result['error_code'];
            }
            if (isset($result['http_status'])) {
                $logData['http_status'] = $result['http_status'];
            }
            if (isset($result['pixel_name'])) {
                $logData['pixel_name'] = $result['pixel_name'];
            }

            // 记录日志
            if ($result['valid']) {
                Log::info('像素状态检查 - 验证成功', $logData);
            } else {
                Log::warning('像素状态检查 - 验证失败', $logData);
            }

            // 输出结果
            if ($result['valid']) {
                if ($oldStatus == 0) {
                    $this->info("  ✅ 验证成功（状态已更新: 0 → 1）");
                    if (isset($result['pixel_name'])) {
                        $this->line("     像素名称: {$result['pixel_name']}");
                    }
                    $successCount++;
                } else {
                    $this->comment("  ✓  验证成功（状态未变）");
                    if (isset($result['pixel_name'])) {
                        $this->line("     像素名称: {$result['pixel_name']}");
                    }
                    $unchangedCount++;
                }
            } else {
                if ($oldStatus == 1) {
                    $this->error("  ❌ 验证失败（状态已更新: 1 → 0）");
                    $this->warn("     原因: {$result['message']}");
                    
                    // 显示错误诊断信息
                    if (isset($result['error_code'])) {
                        $this->warn("     错误码: {$result['error_code']}");
                    }
                    if (isset($result['http_status'])) {
                        $this->warn("     HTTP状态: {$result['http_status']}");
                    }
                    
                    $failCount++;
                } else {
                    $this->comment("  ✗  验证失败（状态未变）");
                    $this->warn("     原因: {$result['message']}");
                    
                    // 显示错误诊断信息
                    if (isset($result['error_code'])) {
                        $this->warn("     错误码: {$result['error_code']}");
                    }
                    if (isset($result['http_status'])) {
                        $this->warn("     HTTP状态: {$result['http_status']}");
                    }
                    
                    $unchangedCount++;
                }
            }

            // 显示详细信息
            if ($this->output->isVerbose() && !empty($result['data'])) {
                $this->line("  详细信息:");
                foreach ($result['data'] as $key => $value) {
                    $this->line("    {$key}: " . json_encode($value, JSON_UNESCAPED_UNICODE));
                }
            }

            // 避免请求过快
            usleep(300000); // 0.3秒
        }

        $progressBar->finish();
        $this->newLine(2);

        // 输出统计信息
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('检查完成！统计信息：');
        $this->line("  总计检查: {$pixels->count()} 个像素");
        $this->line("  新验证成功: {$successCount} 个");
        $this->line("  新验证失败: {$failCount} 个");
        $this->line("  状态未变: {$unchangedCount} 个");
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        return 0;
    }

    /**
     * 获取渠道名称
     *
     * @param int $channel
     * @return string
     */
    protected function getChannelName(int $channel): string
    {
        return match ($channel) {
            1 => 'Facebook',
            2 => 'TikTok',
            3 => 'Google',
            default => '未知渠道'
        };
    }
}

