<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 域名解析状态检查定时任务
// 每天检查一次未解析的域名
Schedule::command('domain:check-resolution')
    ->daily()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/domain-check.log'));

// 像素 Access Token 状态检查定时任务
// 每小时检查一次状态异常的像素（status = 0）
Schedule::command('pixel:check-status --limit=50')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/pixel-check.log'));

// 每天凌晨 2 点检查所有像素（包括状态正常的）
Schedule::command('pixel:check-status --all --limit=100')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/pixel-check-all.log'));
