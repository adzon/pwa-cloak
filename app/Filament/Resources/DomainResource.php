<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DomainResource\Pages;
use App\Models\Domain;
use App\Services\AwsRoute53Service;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Support\Exceptions\Halt;
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
                            TextInput::make('domain')
                                ->label('域名')
                                ->placeholder('请输入需要配置的域名(不带协议)，建议输入二级域名')
                                ->required()
                                ->maxLength(255)
                                ->lazy()
                                ->rules([
                                    'regex:/^(?!:\/\/)([a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9]\.)+[a-zA-Z]{2,}$/',
                                ])
                                ->validationMessages([
                                    'regex' => '请输入有效的域名格式，不包含协议（如http://或https://）',
                                ])
                                ->dehydrateStateUsing(function ($state) {
                                    // 数据提交时自动添加 www 前缀
                                    if ($state && !str_starts_with(strtolower($state), 'www.')) {
                                        return 'www.' . $state;
                                    }
                                    return $state;
                                }),

                            // 隐藏字段用于保存 AWS Route53 返回的数据
                            Forms\Components\Hidden::make('hosting_id'),
                            Forms\Components\Hidden::make('hosting_name_servers'),
                        ])
                        ->visible(fn($livewire) => $livewire instanceof \App\Filament\Resources\DomainResource\Pages\CreateDomain)
                        ->afterValidation(function ($state, callable $set, callable $get) {
                            // 获取域名（已通过 dehydrateStateUsing 添加了 www 前缀）
                            $domain = $state['domain'];
                            if (!str_starts_with(strtolower($domain), 'www.')) {
                                $domain = 'www.' . $domain;
                            }

                            // 1. 检查域名唯一性
                            if (Domain::where('domain', $domain)->exists()) {
                                Notification::make()
                                    ->title('域名已存在')
                                    ->body('该域名已存在，请选择其他域名或编辑现有域名记录。')
                                    ->danger()
                                    ->send();
                                throw new Halt();
                            }

                            // 2. 创建 Hosted Zone
                            $service = app(AwsRoute53Service::class);
                            $response = $service->createHostedZone($domain);

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
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->whereNull('pid'))
            ->columns([
                // 域名（支持展开查看子域名）
                TextColumn::make('domain')
                    ->label('域名')
                    ->copyable()
                    ->copyMessage('已复制')
                    ->description(function (Domain $record) {
                        $childrenCount = $record->children()->count();
                        if ($childrenCount > 0) {
                            return "📁 {$childrenCount} 个子域名";
                        }
                        return null;
                    })
                    ->weight('medium')
                    ->size(TextColumn\TextColumnSize::Medium),

                // 解析状态
                TextColumn::make('status')
                    ->label('解析状态')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? '解析成功' : '未解析')
                    ->color(fn($state) => $state === true ? 'success' : 'warning')
                    ->icon(fn($state) => $state === true ? 'heroicon-o-check-circle' : 'heroicon-o-clock')
                    ->alignCenter(),

                // 使用状态 (基于 promotion_id 判断)
                TextColumn::make('usage')
                    ->label('使用状态')
                    ->getStateUsing(fn(Domain $record) => $record->checkUsage() ? '已使用' : '未使用')
                    ->badge()
                    ->color(fn(Domain $record) => $record->checkUsage() ? 'success' : 'gray')
                    ->icon(fn(Domain $record) => $record->checkUsage() ? 'heroicon-o-check-badge' : 'heroicon-o-minus-circle')
                    ->alignCenter(),

                // 创建时间
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->size(TextColumn\TextColumnSize::Small),
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
                // 查看子域名（优先级最高）
                Tables\Actions\Action::make('viewSubdomains')
                    ->label('子域名')
                    ->icon('heroicon-o-rectangle-stack')
                    ->color('info')
                    ->tooltip(fn($record) => "查看 {$record->children()->count()} 个子域名")
                    ->visible(fn($record) => $record->children()->count() > 0)
                    ->modalContent(fn(Domain $record) => view('filament.domain.subdomains-list', [
                        'subdomains' => $record->children
                    ]))
                    ->modalHeading(fn($record) => $record->domain . ' 的子域名')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('关闭')
                    ->modalWidth('2xl'),

                // 生成子域名按钮（仅对解析成功且无父域名的域名显示）
                Tables\Actions\Action::make('generateSubdomain')
                    ->label('生成子域名')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === true && is_null($record->pid))
                    ->form(function (Domain $record) {
                        // 获取二级域名用于示例展示
                        $baseDomain = $record->domain;
                        if (str_starts_with(strtolower($baseDomain), 'www.')) {
                            $baseDomain = substr($baseDomain, 4);
                        }

                        return [
                            TagsInput::make('subdomains')
                                ->label('请输入子域名前缀')
                                ->placeholder('输入子域名前缀后按 Enter 或 Tab 添加')
                                ->splitKeys(['Enter', 'Tab', ','])
                                ->required()
                                ->rules([
                                    function () {
                                        return function (string $attribute, $value, $fail) {
                                            if (is_array($value)) {
                                                foreach ($value as $subdomain) {
                                                    // 验证子域名格式：只允许字母、数字和连字符，不能以连字符开头或结尾
                                                    if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/', $subdomain)) {
                                                        $fail("子域名前缀 '{$subdomain}' 格式无效。只允许字母、数字和连字符，且不能以连字符开头或结尾。");
                                                        break;
                                                    }
                                                }
                                            }
                                        };
                                    }
                                ])
                        ];
                    })
                    ->action(function (Domain $record, array $data) {
                        $subdomains = $data['subdomains'] ?? [];
                        $created = 0;
                        $errors = [];

                        // 获取父域名的二级域名（去掉 www. 前缀）
                        $baseDomain = $record->domain;
                        if (str_starts_with(strtolower($baseDomain), 'www.')) {
                            $baseDomain = substr($baseDomain, 4); // 去掉 "www."
                        }

                        foreach ($subdomains as $prefix) {
                            // 子域名 = 前缀 + 二级域名
                            $fullDomain = $prefix . '.' . $baseDomain;

                            // 检查是否已存在
                            if (Domain::where('domain', $fullDomain)->exists()) {
                                $errors[] = "子域名 {$fullDomain} 已存在";
                                continue;
                            }

                            // 创建子域名记录
                            Domain::create([
                                'user_id' => $record->user_id,
                                'pid' => $record->id,
                                'domain' => $fullDomain,
                                'hosting_id' => $record->hosting_id,
                                'hosting_name_servers' => $record->hosting_name_servers,
                                'status' => 0, // 子域名初始化为未解析状态
                                'promotion_id' => 0,
                                'is_save' => $record->is_save,
                                'is_delete' => false,
                            ]);

                            $created++;
                        }

                        if ($created > 0) {
                            Notification::make()
                                ->title('生成成功')
                                ->body("成功生成 {$created} 个子域名" . (count($errors) > 0 ? '，' . count($errors) . ' 个失败' : ''))
                                ->success()
                                ->send();
                        }

                        if (count($errors) > 0) {
                            Notification::make()
                                ->title('部分失败')
                                ->body(implode("\n", $errors))
                                ->warning()
                                ->send();
                        }
                    })
                    ->modalHeading('生成子域名')
                    ->modalSubmitActionLabel('生成子域名')
                    ->modalWidth('md'),

                // 编辑
                Tables\Actions\EditAction::make()
                    ->label('编辑'),

                // 隐藏/显示
                Tables\Actions\Action::make('toggleVisibility')
                    ->label(fn($record) => $record->is_delete ? '显示' : '隐藏')
                    ->icon(fn($record) => $record->is_delete ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                    ->color(fn($record) => $record->is_delete ? 'success' : 'warning')
                    ->requiresConfirmation(fn($record) => !$record->is_delete) // 仅隐藏时需要确认
                    ->modalHeading(fn($record) => $record->is_delete ? '' : '确认隐藏该APP？')
                    ->modalDescription(fn($record) => $record->is_delete ? '' : '注意！隐藏后的APP将无法新建推广链接，但是不影响已创建的推广链接正常使用。')
                    ->modalSubmitActionLabel(fn($record) => $record->is_delete ? '' : '隐藏')
                    ->action(function ($record) {
                        $record->is_delete = !$record->is_delete;
                        $record->save();
                        \Filament\Notifications\Notification::make()
                            ->title('操作成功')
                            ->body($record->is_delete ? '项目已隐藏' : '项目已显示')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->recordUrl(null)
            ->paginated([10, 25, 50, 100]);
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

    /**
     * @param callable $get
     * @param callable $set
     * @param $record
     * @return void
     */
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
