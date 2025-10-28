<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OtherPixelResource\Enum\PixelTypeEnum;
use App\Filament\Resources\PixelResource\Enum\ChannelEnum;
use App\Filament\Resources\PixelResource\Enum\PixelStatusEnum;
use App\Filament\Resources\PromotionResource\Enum\TemplateEnum;
use App\Filament\Resources\PromotionResource\Pages;
use App\Filament\Resources\PromotionResource\RelationManagers;
use App\Models\Domain;
use App\Models\OtherPixel;
use App\Models\Pixel;
use App\Models\Promotion;
use App\Models\Application;
use App\Models\Region;
use Faker\Provider\Text;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Str;

class PromotionResource extends Resource
{
    protected static ?string $model = Promotion::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationGroup = '推广';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = '推广链接';
    protected static ?string $slug = 'promotion';
    protected static ?string $pluralModelLabel = '推广链接';
    protected static ?string $modelLabel = '推广链接';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('APP基本信息')->schema([
                    Select::make('app_id')
                        ->label('推广APP')
                        ->placeholder('请选择推广APP')
                        ->required()
                        ->options(function () {
                            return Auth::user()->applications->mapWithKeys(function ($application) {
                                // 渲染紧凑模式的 app-info
                                $view = view('filament.tables.columns.app-info', [
                                    'record' => $application,
                                    'compact' => true
                                ])->render();

                                return [$application->id => $view];
                            })->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->allowHtml()
                        ->getOptionLabelUsing(function ($value) {
                            $application = Auth::user()->applications->find($value);
                            if (!$application) {
                                return '';
                            }
                            return $application->name;
                        }),
                    TextInput::make('promotion_name')
                        ->label('代理名称')
                        ->placeholder('请输入')
                        ->maxLength(20)
                        ->required(),
                    TextInput::make('link_address')
                        ->label('H5链接')
                        ->placeholder('请输入')
                        ->maxLength(1000),
                    Group::make([
                        TextInput::make('ios_link_address')
                            ->label('iOS跳转链接')
                            ->placeholder('请输入')
                            ->maxLength(1000)
                            ->columnSpan(2)
                            ->extraAttributes(fn($get) => $get('use_h5_link_for_ios') ? ['style' => 'display: none;'] : []),
                        Checkbox::make('use_h5_link_for_ios')
                            ->label('H5链接')
                            ->reactive()
                            ->inline()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                if ($state) {
                                    $set('ios_link_address', $get('link_address'));
                                }
                            })
                            ->columnSpan(1),
                    ])
                        ->columns(3),
                ]),
                Section::make('广告设置')->schema([
                    Select::make('channel')
                        ->label('投放平台')
                        ->placeholder('请选择投放平台')
                        ->options(array_merge([0 => '无'], ChannelEnum::CHANNEL_LIST))
                        ->default(0)
                        ->required()
                        ->reactive(),
                    Select::make('pixel_id')
                        ->label('请选择需要使用的广告像素')
                        ->options(function (callable $get) {
                            $channel = $get('channel');
                            if (!$channel) {
                                return [];
                            }
                            // 根据选择的 channel 和当前用户 ID 获取对应的像素
                            return Pixel::query()
                                ->where('channel', $channel)
                                ->where('user_id', Auth::id())
                                ->where('status', PixelStatusEnum::API_REPORTABLE)
                                ->pluck('pixel_name', 'id')
                                ->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->visible(fn($get) => !empty($get('channel')))
                        ->required(),
                ]),


                Section::make('落地页域名设置')
                    ->schema(function ($get, $record) {
                        $isEditing = $record !== null;
                        $platformBound = $record && $record->platformDomains()->exists();
                        $hostingBound = $record && $record->hostingDomains()->exists();

                        if ($platformBound) {
                            return [
                                Tabs::make('domain_tabs')
                                    ->tabs([
                                        Tab::make('平台域名')
                                            ->schema([
                                                Select::make('platform_domain_id')
                                                    ->label('平台域名')
                                                    ->options(fn() => $record
                                                        ? $record->platformDomains()->pluck('domain', 'id')
                                                        : collect()
                                                    )
                                                    ->multiple()
                                                    ->default($record?->platformDomains()->pluck('id')->toArray() ?? [])
                                                    ->disabled(),
                                            ]),
                                    ]),
                            ];
                        }
                        if ($hostingBound) {
                            return [
                                Tabs::make('domain_tabs')
                                    ->tabs([
                                        Tab::make('托管域名')
                                            ->schema([
                                                Select::make('hosting_domain_id')
                                                    ->label('托管域名')
                                                    ->options(fn() => $record
                                                        ? $record->hostingDomains()->pluck('domain', 'id')
                                                        : collect()
                                                    )
                                                    ->multiple()
                                                    ->default($record?->hostingDomains()->pluck('id')->toArray() ?? [])
                                                    ->disabled(),
                                            ]),
                                    ]),
                            ];
                        }

                        return [
                            Tabs::make('domain_tabs')
                                ->tabs([
                                    Tab::make('平台域名')
                                        ->schema([
                                            Select::make('platform_domain_id')
                                                ->label('请选择落地页使用的域名（一个包可使用多个域名）')
                                                ->options(fn() => !$isEditing
                                                    ? Domain::availablePlatform()->pluck('domain', 'id')
                                                    : collect()
                                                )
                                                ->multiple()
                                                ->searchable()
                                                ->preload()
                                                ->required(fn($get) => empty($get('hosting_domain_id')))
                                                ->reactive(),
                                        ]),

                                    Tab::make('托管域名')
                                        ->schema([
                                            Select::make('hosting_domain_id')
                                                ->label('请选择落地页使用的域名（一个包可使用多个域名）')
                                                ->options(fn() => !$isEditing
                                                    ? Domain::availableHosting(Auth::id())->pluck('domain', 'id')
                                                    : collect()
                                                )
                                                ->multiple()
                                                ->searchable()
                                                ->preload()
                                                ->required(fn($get) => empty($get('platform_domain_id')))
                                                ->reactive(),
                                        ]),
                                ]),
                        ];
                    }),

                Section::make('第三方归因平台')->schema([
                    Select::make('attribution_platform')
                        ->label('请选择第三方归因平台')
                        ->options(function () {
                            return array_merge(
                                [0 => '暂不选择'],
                                PixelTypeEnum::PIXEL_LIST
                            );
                        })
                        ->default(0)
                        ->required()
                        ->reactive(),

                    Select::make('other_pixel_id')
                        ->label('请选择应用')
                        ->options(function (callable $get) {
                            $channel = $get('attribution_platform');
                            return OtherPixel::query()
                                ->where('user_id', Auth::id())
                                ->where('channel', $channel)
                                ->pluck('app_name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->hidden(fn($get) => !$get('attribution_platform') || $get('attribution_platform') == 0)
                        ->required(fn($get) => $get('attribution_platform') && $get('attribution_platform') != 0),
                ]),
                Section::make('')
                    ->schema([
                        Group::make([
                            Placeholder::make('label')
                                ->label('开启广告防封')
                                ->extraAttributes(['class' => 'text-lg font-medium self-center']),
                            Toggle::make('is_open_cloak')
                                ->label('')
                                ->inline()
                                ->default(true)
                                ->reactive(),
                        ])->columns(2)
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'flex items-center gap-2 p-0']),
                        Placeholder::make('advice_notice')
                            ->label('')
                            ->content('建议开启！开启此功能大幅提升广告存活率！审核模版仅展示给黑名单及非投放地区')
                            ->extraAttributes(['class' => 'text-danger-500 text-sm font-bold mb-4']),

                        // 审核模版选择（仅在开启广告防封时显示）
                        Radio::make('template_id')
                            ->label('审核模版')
                            ->default(TemplateEnum::GAME_ID)
                            ->options(TemplateEnum::TEMPLATE_LIST)
                            ->visible(fn($get) => $get('is_open_cloak'))
                            ->required(fn($get) => $get('is_open_cloak'))
                            ->columns(3),

                        // 投放地区多选（仅在开启广告防封时显示）
                        Select::make('region_ids')
                            ->label('投放地区')
                            ->multiple()
                            ->options(function () {
                                return Region::pluck('name', 'code');
                            })
                            ->visible(fn($get) => $get('is_open_cloak'))
                            ->required(fn($get) => $get('is_open_cloak'))
                            ->preload()
                            ->saveRelationshipsUsing(function (Model $record, $state) {
                                if (is_array($state)) {
                                    $record->region_ids = implode(',', $state);
                                } else {
                                    $record->region_ids = $state;
                                }
                                $record->save();
                            })
                            ->loadStateFromRelationshipsUsing(function (Model $record): array {
                                // 从数据库加载时将逗号分隔的字符串转换回数组
                                if (empty($record->region_ids)) {
                                    return [];
                                }
                                return explode(',', $record->region_ids);
                            })
                            ->dehydrateStateUsing(function ($state) {
                                // 在保存前将数组转换为字符串
                                if (is_array($state)) {
                                    return implode(',', $state);
                                }
                                return $state;
                            }),

                        Hidden::make('hast_result')
                            ->default(fn() => Str::random(32)),

                        Placeholder::make('display_hast_result')
                            ->label('页面混淆指纹')
                            ->content(fn($get) => 'Hash地址: ' . self::maskHastResult($get('hast_result') ?: Str::random(32)))
                            ->visible(fn($get) => $get('is_open_cloak'))
                            ->extraAttributes(['class' => 'filament-forms-section-header']),

                        Actions::make([
                            Action::make('generate_new_fingerprint')
                                ->label('生成新混淆指纹')
                                ->action(function ($set) {
                                    $newHash = Str::random(32);
                                    $set('hastResult', $newHash);
                                })
                                ->visible(fn($get) => $get('is_open_cloak'))
                                ->icon('heroicon-o-arrow-path'),
                        ])->visible(fn($get) => $get('is_open_cloak')),

                        Placeholder::make('fingerprint_tips')
                            ->label('')
                            ->content('同一App的不同链路，内容完全隔离，防止关联误封')
                            ->visible(fn($get) => $get('is_open_cloak'))
                            ->extraAttributes(['class' => 'text-sm text-gray-500 mt-2']),

                        Placeholder::make('fingerprint_tips_2')
                            ->label('')
                            ->content('链接路径已混淆，防止关联误封')
                            ->visible(fn($get) => $get('is_open_cloak'))
                            ->extraAttributes(['class' => 'text-sm text-gray-500']),
                    ])
                    ->compact(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ViewColumn::make('promotion_info')
                    ->label('链接信息')
                    ->viewData(fn($record) => [
                        'record' => $record,
                    ])
                    ->view('filament.tables.columns.promotion-info')
                    ->width('40%'),
                ViewColumn::make('app-info')
                    ->label('推广APP')
                    ->viewData(fn($record) => ['record' => $record->application])
                    ->view('filament.tables.columns.app-info')
                    ->width('30%'),
                ViewColumn::make('pixel_info')
                    ->label('广告')
                    ->viewData(fn($record) => ['record' => $record])
                    ->view('filament.tables.columns.pixel-info')
                    ->width('30%'),
            ])
            ->filters([
                // 推广APP筛选
                Filter::make('app_id')
                    ->form([
                        Select::make('app_id')
                            ->label('推广APP')
                            ->options(function () {
                                return Application::pluck('name', 'id');
                            })
                            ->placeholder('请选择应用')
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['app_id'] ?? null,
                            fn(Builder $query, $appId): Builder => $query->where('app_id', $appId)
                        );
                    })
                    ->indicateUsing(function (array $data): array {
                        if ($data['app_id'] ?? null) {
                            $application = Application::find($data['app_id']);
                            if ($application) {
                                return ['应用: ' . $application->name];
                            }
                        }
                        return [];
                    })
                    ->columnSpan(1),

                // 代理名称筛选
                Filter::make('promotion_name')
                    ->form([
                        TextInput::make('promotion_name')
                            ->label('代理名称')
                            ->placeholder('请输入代理名称'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['promotion_name'] ?? null,
                            fn(Builder $query, $name): Builder => $query->where('promotion_name', 'like', "%{$name}%")
                        );
                    })
                    ->indicateUsing(function (array $data): array {
                        return ($data['promotion_name'] ?? null) ? ['代理名称: ' . $data['promotion_name']] : [];
                    })
                    ->columnSpan(1),

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
            ->filtersFormColumns(5)
            ->filtersFormWidth('full')
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\EditAction::make('修改'),
                // 添加隐藏操作
                Tables\Actions\Action::make('toggleVisibility')
                    ->label(fn($record) => $record->is_delete ? '显示' : '隐藏')
                    ->icon(fn($record) => $record->is_delete ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                    ->color(fn($record) => $record->is_delete ? 'success' : 'warning')
                    ->requiresConfirmation(fn($record) => !$record->is_delete) // 仅隐藏时需要确认
                    ->modalHeading(fn($record) => $record->is_delete ? '' : '确认隐藏该推广链接？')
                    ->modalDescription(fn($record) => $record->is_delete ? '' : '注意!隐藏后该链接将暂时不能使用，开启后才能重新使用')
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
            ->bulkActions([
                //
            ])
            ->recordUrl(null);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromotions::route('/'),
            'create' => Pages\CreatePromotion::route('/create'),
            'edit' => Pages\EditPromotion::route('/{record}/edit'),
        ];
    }

    protected static function maskHastResult(?string $hastResult): string
    {
        if (empty($hastResult)) {
            return Str::random(32);
        }

        return substr_replace($hastResult, '***', 4, strlen($hastResult) - 7);
    }
}
