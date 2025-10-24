<?php

namespace App\Filament\Resources\DomainResource\Pages;

use App\Filament\Resources\DomainResource;
use App\Services\AwsRoute53Service;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

class CreateDomain extends CreateRecord
{
    protected static string $resource = DomainResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        // 根据 nsResults 判断 status
        $nsResults = $data['nsResults'] ?? [];
        $anyPassed = collect($nsResults)->contains(1);
        $data['status'] = $anyPassed ? 1 : 0;

        // 清理临时数据，不需要存入数据库
        unset($data['nsResults']);

        return $data;
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('创建成功')
            ->body('您的域名已成功创建。')
            ->success()
            ->send();
    }

    // 保存检测结果
    public array $nsResults = [];

    // 跟踪3个步骤的完成状态
    public bool $step1Complete = false;  // 第一步：输入域名
    public bool $step2Complete = false;  // 第二步：托管域名
    public bool $step3Complete = false;  // 第三步：进入验证步骤（无论成功失败）
    public bool $dnsSuccess = false;     // DNS验证是否成功

    // DNS检测状态
    public bool $isCheckingDns = false;  // 是否正在检测DNS
    public bool $autoCheckDns = false;   // 是否需要自动检测DNS（进入第三步时）

    public function updated($name, $value): void
    {
        // 监听表单中domain字段的变化 - 表示第1步完成
        if ($name === 'data.domain' && !empty($value)) {
            $this->step1Complete = true;
            \Illuminate\Support\Facades\Log::info('Step 1 complete: domain entered');
        }

        // 检查hosting_name_servers是否已被填充（无论通过什么方式）
        // 这会在form->fill()后被触发
        if ($name === 'data.hosting_name_servers' && !empty($value)) {
            $this->step2Complete = true;
            \Illuminate\Support\Facades\Log::info('Step 2 complete: hosting_name_servers available');
        }

        // 也检查form的整体状态变化
        if ($name === 'data' && is_array($value)) {
            // 如果form中已有hosting_name_servers，则标记第2步完成
            if (!empty($value['hosting_name_servers']) && !$this->step2Complete) {
                $this->step2Complete = true;
                \Illuminate\Support\Facades\Log::info('Step 2 complete: detected from form data');
            }

            // 如果 nsResults 已经存在，说明DNS检测已完成
            if (isset($value['nsResults']) && !empty($value['nsResults'])) {
                $this->step3Complete = true;
                $anyPassed = collect($value['nsResults'])->contains(1);
                $this->dnsSuccess = $anyPassed;
                \Illuminate\Support\Facades\Log::info('Step 3 complete: DNS check results available', [
                    'dnsSuccess' => $anyPassed,
                    'nsResults' => $value['nsResults']
                ]);
            }

            // 如果第二步和第三步都已标记完成，但还没有检测结果，则自动触发检测
            if ($this->step2Complete && $this->step3Complete && !$this->isCheckingDns) {
                $hasResults = !empty($value['nsResults'] ?? []) || !empty($this->nsResults);
                if (!$hasResults) {
                    \Illuminate\Support\Facades\Log::info('Auto-triggering DNS check after step completion');
                    $this->checkNsRecords();
                }
            }
        }
    }

    #[On('checkDns')]
    public function onCheckDns(): void
    {
        $this->checkNsRecords();
    }

    // 监听DNS检测需要触发的事件
    #[On('dns-check-needed')]
    public function onDnsCheckNeeded(): void
    {
        \Illuminate\Support\Facades\Log::info('dns-check-needed event received, starting DNS check');
        $this->checkNsRecords();
    }

    // 进入第三步时自动触发DNS检测
    #[On('initStep3')]
    public function onInitStep3(): void
    {
        \Illuminate\Support\Facades\Log::info('initStep3 event received', [
            'isCheckingDns' => $this->isCheckingDns,
            'hasResults' => !empty($this->form->getState()['nsResults'] ?? []),
        ]);

        // 如果没有检测结果且不在检测中，则开始检测
        if (!$this->isCheckingDns) {
            $formState = $this->form->getState();
            $hasResults = !empty($formState['nsResults'] ?? []);

            if (!$hasResults) {
                \Illuminate\Support\Facades\Log::info('Step 3 initialized, starting auto DNS check');
                $this->checkNsRecords();
            }
        }
    }

    public function checkNsRecords($get = null, $set = null, $record = null): void
    {
        // 防止重复检测
        if ($this->isCheckingDns) {
            \Illuminate\Support\Facades\Log::warning('DNS check already in progress');
            return;
        }

        $this->isCheckingDns = true;

        try {
            // 获取表单实例
            $form = $this->form;

            // 使用Filament的表单getter和setter
            $get ??= fn($key) => $form->getState()[$key] ?? null;
            $set ??= function ($key, $val) use ($form) {
                $formData = $form->getState();
                $formData[$key] = $val;
                $form->fill($formData);
            };

            // 显示开始检测的通知
            Notification::make()
                ->title('正在检测 DNS 解析')
                ->body('正在验证域名解析配置，请稍候...')
                ->info()
                ->send();

            // 执行DNS检测（会调用$set来更新nsResults）
            DomainResource::checkNsRecords($get, $set, $record ?? $this->record);

            // 重新获取最新的表单状态
            $formState = $this->form->getState();
            $nsResults = $formState['nsResults'] ?? [];

            // 同时保存到 Livewire 组件属性中，确保视图能访问
            $this->nsResults = $nsResults;

            // 检查DNS验证是否成功
            $anyPassed = collect($nsResults)->contains(1);  // 检查数字1

            // 更新DNS成功标志和步骤完成状态
            $this->dnsSuccess = $anyPassed;
            $this->step3Complete = true;

            \Illuminate\Support\Facades\Log::info('DNS check completed', [
                'step3Complete' => true,
                'dnsSuccess' => $anyPassed,
                'nsResults' => $nsResults,
                'formState' => $formState,
            ]);
        } finally {
            $this->isCheckingDns = false;
        }
    }

    // 检查所有步骤是否完成
    public function isAllStepsComplete(): bool
    {
        return $this->step1Complete && $this->step2Complete && $this->step3Complete;
    }

    protected function getFormActions(): array
    {
        $actions = parent::getFormActions();

        // 移除 "创建并创建另一个" 按钮
        $actions = array_filter($actions, function ($action) {
            return $action->getName() !== 'createAnother';
        });

        // 获取所有步骤是否完成
        $isComplete = $this->isAllStepsComplete();

        // 检查是否正在检测DNS或DNS检测未完成
        $formState = $this->form->getState();
        $hasResults = !empty($formState['nsResults'] ?? []);

        // 根据DNS验证结果确定按钮文案
        $buttonLabel = $this->dnsSuccess ? '保存' : '先保存，等下再检查';

        // 只修改主要的 Create 按钮
        foreach ($actions as $action) {
            $actionName = $action->getName() ?? '';

            // 只修改主要创建按钮（精确匹配 'create'）
            if ($actionName === 'create') {
                // 按钮启用条件：
                // 1. 所有步骤完成
                // 2. 不在检测中
                // 3. 已有检测结果
                $shouldEnable = $isComplete && !$this->isCheckingDns && $hasResults;

                $action
                    ->disabled(!$shouldEnable)
                    ->label($buttonLabel);

                // 根据不同状态添加提示
                if (!$isComplete) {
                    $action->tooltip('请完成全部3个步骤');
                } elseif ($this->isCheckingDns) {
                    $action->tooltip('正在检测 DNS 解析，请稍候...');
                } elseif (!$hasResults) {
                    $action->tooltip('等待 DNS 检测完成...');
                }
            }
        }

        return $actions;
    }

    public function create(bool $another = false): void
    {
        // 检查所有步骤是否完成（已访问完所有步骤即可）
        if (!$this->isAllStepsComplete()) {
            Notification::make()
                ->title('无法保存')
                ->body('请先完成所有3个步骤：输入域名 → 托管域名 → 验证域名')
                ->danger()
                ->send();
            return;
        }

        parent::create($another);
    }
}
