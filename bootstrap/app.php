<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        // 域名解析状态检查定时任务
        // 每天检查一次未解析的域名
        $schedule->command('domain:check-resolution')
            ->daily()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/domain-check.log'));

        // 像素 Access Token 状态检查定时任务
        // 每小时检查一次状态异常的像素（status = 0）
        $schedule->command('pixel:check-status --limit=50')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/pixel-check.log'));

        // 每天凌晨 2 点检查所有像素（包括状态正常的）
        $schedule->command('pixel:check-status --all --limit=100')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/pixel-check-all.log'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
