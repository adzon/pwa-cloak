<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DomainResource\Pages;
use App\Models\Domain;
use App\Services\AwsRoute53Service;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

class DomainResource extends Resource
{
    protected static ?string $model = Domain::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = '推广';
    protected static ?string $navigationLabel = '域名管理';
    protected static ?string $pluralModelLabel = '域名';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    // 第一步：输入域名
                    Step::make('输入域名')
                        ->schema([
                            Forms\Components\TextInput::make('domain')
                                ->label('域名')
                                ->placeholder('例如:example.com')
                                ->helperText('请输入需要配置的域名(不带协议)，建议输入二级域名')
                                ->required()
                                ->maxLength(255)
                                ->rules([
                                    'regex:/^(?!:\/\/)([a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9]\.)+[a-zA-Z]{2,}$/',
                                    // 使用 Rule 类来定义唯一性验证
                                    \Illuminate\Validation\Rule::unique('domains', 'domain')
                                ])
                                ->validationMessages([
                                    'regex' => '请输入有效的域名格式，不包含协议（如http://或https://）',
                                    'unique' => '该域名已存在，请选择其他域名或编辑现有域名记录。'
                                ]),

                            // 隐藏字段用于保存 AWS Route53 返回的数据
                            Forms\Components\Hidden::make('hosting_id'),
                            Forms\Components\Hidden::make('hosting_name_servers'),
                        ])
                        ->visible(fn($livewire) => $livewire instanceof \App\Filament\Resources\DomainResource\Pages\CreateDomain)
                        ->afterValidation(function ($state, callable $set, callable $get) {
                            // 检查是否已存在相同域名且已有 hosting_name_servers
                            $service = app(AwsRoute53Service::class);
                            $response = $service->createHostedZone($state['domain']);

                            if (isset($response['code']) && $response['code'] === 0) {
                                $data = $response['data'] ?? [];
                                $hostingId = $data['hosting_id'] ?? null;
                                $hostingNameServers = $data['hosting_name_servers'] ?? [];

                                $set('hosting_id', $hostingId);
                                $set('hosting_name_servers', $hostingNameServers);
                                $set('status', 0);

                                Notification::make()
                                    ->title('域名托管成功')
                                    ->body('已成功创建 Hosted Zone，请在下一步更新 DNS 服务器。')
                                    ->success()
                                    ->send();
                            } else {
                                $msg = $response['msg'] ?? '创建 Hosted Zone 失败';
                                Notification::make()
                                    ->title('创建失败')
                                    ->body($msg)
                                    ->danger()
                                    ->send();
                            }
                        }),

                    // 第二步：托管域名（DNS 修改提示）
                    Step::make('托管域名')
                        ->schema([
                            Section::make('托管域名（修改DNS信息）')
                                ->schema([
                                    View::make('filament.domain.dns-info')
                                        ->label('DNS 信息')
                                        ->statePath('hosting_name_servers')
                                ]),
                        ])
                        ->afterValidation(function ($state, callable $get, callable $set, $livewire) {
                            // 如果是 CreateDomain 页面，标记步骤状态并触发DNS检测
                            if ($livewire instanceof \App\Filament\Resources\DomainResource\Pages\CreateDomain) {
                                // 标记第二步和第三步都已完成
                                $livewire->step2Complete = true;
                                $livewire->step3Complete = true;

                                \Illuminate\Support\Facades\Log::info('Entering step 3, triggering DNS check', [
                                    'step1' => $livewire->step1Complete,
                                    'step2' => $livewire->step2Complete,
                                    'step3' => $livewire->step3Complete,
                                ]);

                                // 使用 Livewire 的 dispatch 在下一个周期触发检测
                                $livewire->dispatch('dns-check-needed');
                            }
                        })
                        ->visible(fn($livewire) => $livewire instanceof \App\Filament\Resources\DomainResource\Pages\CreateDomain
                        ),
                    // 第三步：验证域名
                    Step::make('验证域名')
                        ->schema([
                            // 隐藏字段用于保存检测结果
                            Forms\Components\Hidden::make('nsResults'),
                            Forms\Components\Hidden::make('status'),

                            Section::make('DNS 检测结果')
                                ->schema([
                                    View::make('filament.domain.check-ns-results')
                                        ->statePath(''),
                                ])
                                ->description('DNS 解析检测已完成，请查看下方结果。'),

                            Actions::make([
                                Actions\Action::make('verifyDns')
                                    ->label('重新检测')
                                    ->color('primary')
                                    ->icon('heroicon-o-arrow-path')
                                    ->action(function ($livewire, $get, $set) {
                                        self::checkNsRecords($get, $set);
                                        // 如果是 CreateDomain 页面，更新步骤状态
                                        if ($livewire instanceof \App\Filament\Resources\DomainResource\Pages\CreateDomain) {
                                            $livewire->checkNsRecords();
                                        }
                                    }),
                            ])->alignment('center'),
                        ]),
                ])
                    ->key('domain-wizard')
                    ->persistStepInQueryString()
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 域名
                TextColumn::make('domain')
                    ->label('域名')
                    ->copyable()
                    ->copyMessage('已复制'),

                // 解析状态
                TextColumn::make('status')
                    ->label('解析状态')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? '解析成功' : '未解析')
                    ->color(fn($state) => $state === true ? 'success' : 'warning'),

                // 使用状态 (基于 promotion_id 判断)
                TextColumn::make('usage')
                    ->label('使用状态')
                    ->getStateUsing(fn(Domain $record) => $record->checkUsage() ? '已使用' : '未使用')
                    ->badge()
                    ->color(fn(Domain $record) => $record->checkUsage() ? 'success' : 'gray'),

                // 创建时间
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->filters([
                // 创建时间范围筛选
                Filter::make('created_at')
                    ->form([
                        Forms\Components\Grid::make(5)
                            ->schema([
                                Forms\Components\DatePicker::make('created_from')
                                    ->label('开始时间')
                                    ->placeholder('选择开始日期')
                                    ->native(false)
                                    ->displayFormat('Y-m-d')
                                    ->columnSpan(2),
                                Forms\Components\Placeholder::make('separator')
                                    ->label(false)
                                    ->content(new \Illuminate\Support\HtmlString('<div class="flex items-center justify-center h-full pb-1 text-gray-500 dark:text-gray-400">—</div>'))
                                    ->columnSpan(1),
                                Forms\Components\DatePicker::make('created_until')
                                    ->label('结束时间')
                                    ->placeholder('选择结束日期')
                                    ->native(false)
                                    ->displayFormat('Y-m-d')
                                    ->columnSpan(2),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['created_from']) && isset($data['created_until'])) {
                            return $query
                                ->whereDate('created_at', '>=', $data['created_from'])
                                ->whereDate('created_at', '<=', $data['created_until']);
                        }
                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        if (isset($data['created_from']) && isset($data['created_until'])) {
                            $from = \Carbon\Carbon::parse($data['created_from'])->format('Y-m-d');
                            $until = \Carbon\Carbon::parse($data['created_until'])->format('Y-m-d');
                            return ["创建时间: {$from} ~ {$until}"];
                        }
                        return [];
                    })
                    ->columnSpan(2),

                // 域名筛选
                Filter::make('domain')
                    ->form([
                        Forms\Components\TextInput::make('domain')
                            ->label('域名')
                            ->placeholder('请输入域名'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['domain'] ?? null,
                            fn(Builder $query, $domain): Builder => $query->where('domain', 'like', "%{$domain}%")
                        );
                    })
                    ->columnSpan(1),

                // 解析状态筛选
                SelectFilter::make('status')
                    ->label('解析状态')
                    ->options([
                        1 => '解析成功',
                        0 => '未解析',
                    ])
                    ->columnSpan(1),

                // 使用状态筛选
                SelectFilter::make('usage_status')
                    ->label('使用状态')
                    ->options([
                        'used' => '已使用',
                        'unused' => '未使用',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        return match ($value) {
                            'used' => $query->where('promotion_id', '>', 0),
                            'unused' => $query->where('promotion_id', 0),
                            default => $query,
                        };
                    })
                    ->columnSpan(1),

                // 显示隐藏筛选
                Filter::make('is_delete')
                    ->form([
                        Forms\Components\Toggle::make('is_delete')
                            ->label('显示全部(含隐藏)')
                            ->inline()
                            ->default(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['is_delete']) || !$data['is_delete']) {
                            return $query->where('is_delete', false);
                        }
                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        return ($data['is_delete'] ?? false) ? ['包含隐藏项目'] : [];
                    })
                    ->columnSpan(1),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(6)
            ->filtersFormWidth('full')
            ->persistFiltersInSession()
            ->actions([
                // 编辑
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleVisibility')
                    ->label(fn($record) => $record->is_delete ? '显示' : '隐藏')
                    ->icon(fn($record) => $record->is_delete ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                    ->color(fn($record) => $record->is_delete ? 'success' : 'warning')
                    ->action(function ($record) {
                        $record->is_delete = !$record->is_delete;
                        $record->save();
                        \Filament\Notifications\Notification::make()
                            ->title('操作成功')
                            ->body($record->is_delete ? '项目已隐藏' : '项目已显示')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn($record) => $record->is_delete ? '确认显示该像素？' : '确认隐藏该像素？')
                    ->modalDescription('注意！隐藏后的像素将无法新建推广链接，但是不影响已创建的推广链接正常使用。')
                    ->modalSubmitActionLabel(fn($record) => $record->is_delete ? '显示' : '隐藏'),
            ])
            ->recordUrl(null);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDomains::route('/'),
            'create' => Pages\CreateDomain::route('/create'),
            'edit' => Pages\EditDomain::route('/{record}'),
        ];
    }

    public static function checkNsRecords(callable $get, callable $set, $record = null): void
    {
        $domain = $get('domain');
        $expectedNs = $get('hosting_name_servers') ?? [];

        if (!$domain || empty($expectedNs)) {
            Notification::make()->title('缺少信息')->body('请先完成前两步的 DNS 配置。')->danger()->send();
            return;
        }

        $service = app(AwsRoute53Service::class);
        $results = [];
        $total = count($expectedNs);
        $current = 0;

        foreach ($expectedNs as $ns) {
            $current++;

            // 显示检测进度
            Log::info("正在检测 DNS 记录 {$current}/{$total}: {$ns}");

            $ok = $service->resolveNsRecord($domain, $ns);
            $results[$ns] = $ok ? 1 : 0;

            // 每个检测之间间隔，确保有足够时间显示加载状态
            if ($current < $total) {
                usleep(500000); // 0.5秒，让加载状态更明显
            }
        }

        $set('nsResults', $results);
        $anyPassed = collect($results)->contains(1);  // 检查是否包含数字1，而不是布尔值true

        if ($anyPassed) {
            if ($record) {
                $record->update(['status' => 1]);
            } else {
                $set('status', 1);
            }

            Notification::make()
                ->title('DNS 解析成功')
                ->body('至少一个 DNS 记录已成功解析。')
                ->success()
                ->send();
        } else {
            if ($record) {
                $record->update(['status' => 0]);
            } else {
                $set('status', 0); // 确保设置为整数 0
            }

            Notification::make()
                ->title('DNS 解析失败')
                ->body('未检测到有效解析，请确认已在域名注册商处更新 NS 记录，或稍后重试。')
                ->warning() // 改为 warning 而不是 danger，因为可以稍后再检查
                ->send();
        }
    }
}
