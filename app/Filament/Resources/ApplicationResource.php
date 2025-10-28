<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationResource\Enum\CategoryEnum;
use App\Filament\Resources\ApplicationResource\Pages;
use App\Filament\Resources\ApplicationResource\RelationManagers;
use App\Models\Application;
use App\Models\Language;
use Arr;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Components\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\ColorPicker;
use Illuminate\Support\Facades\Auth;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationGroup = '推广';
    protected static ?string $navigationLabel = '应用管理';
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'appManage';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id())
            ->with(['languages', 'localeApplications.comments']);
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('基本信息')
                    ->description('配置应用的基础信息和品牌标识')
                    ->icon('heroicon-o-information-circle')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('APP 名称')
                                ->placeholder('请输入应用名称，如：抖音、微信')
                                ->required()
                                ->maxLength(40)
                                ->prefixIcon('heroicon-o-device-phone-mobile'),

                            TextInput::make('remark')
                                ->label('备注')
                                ->placeholder('选填，用于内部标记识别')
                                ->maxLength(200)
                                ->nullable()
                                ->prefixIcon('heroicon-o-chat-bubble-left-ellipsis'),
                        ]),

                        Grid::make(2)->schema([
                            Toggle::make('google_icon_enabled')
                                ->label('开启 Google 图标')
                                ->helperText('开启后会在应用商店页面显示 Google Play 图标')
                                ->inline(),

                            Toggle::make('official_verified')
                                ->label('官方认证')
                                ->helperText('显示官方认证标识，提升用户信任度')
                                ->inline(),
                        ]),

                        Grid::make(3)->schema([
                            FileUpload::make('icon')
                                ->label('请上传APP图标')
                                ->helperText('建议上传正方形图标，尺寸 512x512 或 1024x1024')
                                ->image()
                                ->disk('do')
                                ->directory('pwa-cloak/applications/icons')
                                ->required()
                                ->maxSize(2048)
                                ->imageCropAspectRatio('1:1')
                                ->imageResizeTargetWidth(512)
                                ->imageResizeTargetHeight(512)
                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                ->rules(['dimensions:ratio=1'])
                                ->validationMessages([
                                    'dimensions' => '图标必须是正方形尺寸（宽高相等）',
                                ])
                                ->imagePreviewHeight('180')
                                ->preserveFilenames()
                                ->columnSpan(1),

                            ColorPicker::make('background_color')
                                ->label('启动页背景色')
                                ->helperText('建议使用浅色，如 #FFFFFF')
                                ->default('#FFFFFF')
                                ->columnSpan(1),

                            ColorPicker::make('theme_color')
                                ->label('启动页主题色')
                                ->helperText('应用主题色，如 #1877F2')
                                ->default('#000000')
                                ->columnSpan(1),
                        ]),

                        Radio::make('category')
                            ->label('底部菜单激活')
                            ->helperText('选择应用在应用商店底部菜单栏中的分类位置')
                            ->default(CategoryEnum::CATEGORY_GAMES)
                            ->options(CategoryEnum::CATEGORY_LIST)
                            ->columns(5)
                            ->columnSpanFull(),

                        Grid::make(2)->schema([
                            Select::make('display_mode')
                                ->label('显示模式')
                                ->options([
                                    'standalone' => '应用模式（推荐）',
                                    'fullscreen' => '全屏模式'
                                ])
                                ->required()
                                ->default('standalone')
                                ->placeholder('请选择显示模式')
                                ->helperText('应用模式会保留状态栏，全屏模式会隐藏所有浏览器UI')
                                ->prefixIcon('heroicon-o-computer-desktop'),

                            Select::make('orientation')
                                ->label('屏幕方向')
                                ->options([
                                    'natural' => '跟随系统（推荐）',
                                    'portrait' => '竖屏',
                                    'landscape' => '横屏',
                                ])
                                ->required()
                                ->default('natural')
                                ->placeholder('请选择屏幕方向')
                                ->helperText('控制应用启动时的屏幕方向')
                                ->prefixIcon('heroicon-o-device-tablet'),
                        ]),
                    ]),

                Section::make('商店信息')
                    ->description('配置应用在不同语言环境下的展示内容')
                    ->icon('heroicon-o-globe-alt')
                    ->collapsible()
                    ->schema([
                        Select::make('languages')
                            ->label('支持语言')
                            ->helperText('选择应用需要支持的语言版本（可多选）')
                            ->multiple()
                            ->relationship('languages', 'name', fn($query) => $query->orderBy('id'))
                            ->preload()
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->columnSpanFull()
                            ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                // 初始化每个语言的嵌套数据结构
                                $localeApplications = $get('localeApplications') ?: [];
                                if (is_array($state)) {
                                    foreach ($state as $languageId) {
                                        if (!isset($localeApplications[$languageId])) {
                                            $localeApplications[$languageId] = [
                                                'name' => '',
                                                'manufacturer' => '',
                                                'icon' => null,
                                                'downloads' => null,
                                                'age_limit' => null,
                                                'comment_count' => null,
                                                'introduction' => '',
                                                'images' => [],
                                                'label' => [],
                                                'reviews' => [],
                                            ];
                                        }
                                    }
                                }
                                $set('localeApplications', $localeApplications);
                            }),

                        Tabs::make('本地化语言应用信息')
                            ->columnSpanFull()
                            ->tabs(function ($get) {
                                $languageIds = $get('languages') ?: [];
                                // 按照 ID 排序
                                sort($languageIds);
                                $tabs = [];
                                foreach ($languageIds as $languageId) {
                                    $language = Language::find($languageId);

                                    if (!$language) continue;

                                    $tabs[] = Tab::make($language->name)
                                        ->icon('heroicon-o-flag')
                                        ->schema([
                                            Grid::make(2)->schema([
                                                TextInput::make("localeApplications.{$languageId}.name")
                                                    ->label('APP 名称')
                                                    ->placeholder('输入该语言下的应用名称')
                                                    ->required()
                                                    ->maxLength(40)
                                                    ->prefixIcon('heroicon-o-device-phone-mobile'),

                                                TextInput::make("localeApplications.{$languageId}.manufacturer")
                                                    ->label('应用厂商')
                                                    ->placeholder('输入开发商/厂商名称')
                                                    ->required()
                                                    ->maxLength(40)
                                                    ->prefixIcon('heroicon-o-building-office-2'),
                                            ]),

                                            FileUpload::make("localeApplications.{$languageId}.icon")
                                                ->label('本地化图标')
                                                ->helperText('上传针对该语言版本的图标（可选），建议尺寸 512x512 或 1024x1024')
                                                ->image()
                                                ->disk('do')
                                                ->directory('pwa-cloak/applications/locale_icons')
                                                ->required()
                                                ->maxSize(2048)
                                                ->imageCropAspectRatio('1:1')
                                                ->imageResizeTargetWidth(512)
                                                ->imageResizeTargetHeight(512)
                                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                                ->rules(['dimensions:ratio=1'])
                                                ->validationMessages([
                                                    'dimensions' => '图标必须是正方形尺寸（宽高相等）',
                                                ])
                                                ->imagePreviewHeight('180')
                                                ->preserveFilenames(),

                                            Grid::make(3)->schema([
                                                Select::make("localeApplications.{$languageId}.downloads")
                                                    ->label('下载数')
                                                    ->options([
                                                        '10 K+' => '10 K+',
                                                        '20 K+' => '20 K+',
                                                        '50 K+' => '50 K+',
                                                        '100 K+' => '100 K+',
                                                        '1 mi+' => '1 mi+',
                                                        '2 mi+' => '2 mi+',
                                                        '5 mi+' => '5 mi+',
                                                        '10 mi+' => '10 mi+',
                                                    ])
                                                    ->required()
                                                    ->placeholder('选择下载量级别')
                                                    ->prefixIcon('heroicon-o-arrow-down-tray'),

                                                Select::make("localeApplications.{$languageId}.age_limit")
                                                    ->label('适用年龄')
                                                    ->options([
                                                        3 => '3岁以上',
                                                        7 => '7岁以上',
                                                        12 => '12岁以上',
                                                        18 => '18岁以上',
                                                    ])
                                                    ->placeholder('暂不设置')
                                                    ->prefixIcon('heroicon-o-user-group'),

                                                TextInput::make("localeApplications.{$languageId}.comment_count")
                                                    ->label('评论数')
                                                    ->placeholder('输入评论数量')
                                                    ->numeric()
                                                    ->required()
                                                    ->prefixIcon('heroicon-o-chat-bubble-left-right'),
                                            ]),

                                            Textarea::make("localeApplications.{$languageId}.introduction")
                                                ->label('应用简介')
                                                ->placeholder('输入应用的详细介绍，最多1000字')
                                                ->rows(4)
                                                ->maxLength(1000)
                                                ->required(),

                                            FileUpload::make("localeApplications.{$languageId}.images")
                                                ->label('应用截图')
                                                ->helperText('上传应用商店展示图片，最多5张，每张最大512KB')
                                                ->image()
                                                ->directory('pwa-cloak/applications/locale_images')
                                                ->multiple()
                                                ->disk('do')
                                                ->maxFiles(5)
                                                ->maxSize(512)
                                                ->required()
                                                ->imagePreviewHeight('120')
                                                ->panelLayout('grid')
                                                ->columnSpanFull(),

                                            TagsInput::make("localeApplications.{$languageId}.label")
                                                ->label('添加 APP 标签')
                                                ->placeholder('输入标签后按 Enter 或 Tab 或逗号添加')
                                                ->helperText('最多可添加 6 个标签，每个标签不超过 20 字符')
                                                ->suggestions(['热门', '推荐', '新品', '限时', '免费', '精品', '人气', '畅销'])
                                                ->splitKeys(['Enter', 'Tab', ','])
                                                ->required()
                                                ->rules([
                                                    'max:6',
                                                    function () {
                                                        return function (string $attribute, $value, $fail) {
                                                            if (is_array($value)) {
                                                                if (count($value) > 6) {
                                                                    $fail('最多只能添加 6 个标签');
                                                                }
                                                                foreach ($value as $tag) {
                                                                    if (mb_strlen($tag) > 20) {
                                                                        $fail('每个标签不能超过 20 个字符');
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                        };
                                                    }
                                                ])
                                                ->columnSpanFull(),


                                    Repeater::make("localeApplications.{$languageId}.reviews")
                                                ->label('APP评论库')
                                                ->schema([
                                                    TextInput::make('nickname')
                                                        ->label('用户昵称')
                                                        ->placeholder('输入评论者昵称')
                                                        ->required()
                                                        ->prefixIcon('heroicon-o-user'),

                                                    Textarea::make('content')
                                                        ->label('评论内容')
                                                        ->placeholder('输入用户评论的内容')
                                                        ->rows(2)
                                                        ->required(),

                                                    Select::make('language_id')
                                                        ->label('语言')
                                                        ->options(Language::pluck('name', 'id'))
                                                        ->default($languageId)
                                                        ->preload()
                                                        ->searchable()
                                                        ->required(),
                                                ])
                                                ->columns(3)
                                                ->addActionLabel('➕ 添加评论')
                                                ->reorderableWithButtons()
                                                ->collapsible()
                                                ->required()
                                                ->itemLabel(fn(array $state): ?string => $state['nickname'] ?? '新评论')
                                                ->maxItems(6)
                                                ->columnSpanFull(),

                                        ]);
                                }

                                return $tabs;
                            }),
                    ]),

                Section::make('其他设置')
                    ->description('配置应用的高级功能和附加选项')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsible()
                    ->schema([
                        // 3行2列布局
                        Grid::make(2)->schema([
                            // 第1行 - 左列
                            Section::make('APK 设置')
                                ->description('上传原生 Android 应用包')
                                ->icon('heroicon-o-archive-box-arrow-down')
                                ->compact()
                                ->columnSpan(1)
                                ->schema([
                                    Toggle::make('apk_upload_enabled')
                                        ->label('启用 APK 上传')
                                        ->helperText('开启后可上传 Android 安装包')
                                        ->inline()
                                        ->reactive(),

                                    FileUpload::make('apk')
                                        ->label('APK 文件')
                                        ->helperText('上传 Android 安装包（.apk 格式），最大100MB')
                                        ->disk('do')
                                        ->acceptedFileTypes([
                                            'application/vnd.android.package-archive',
                                            'application/zip',
                                            'application/octet-stream',
                                        ])
                                        ->directory('applications/apks')
                                        ->maxSize(102400)
                                        ->rules([
                                            function () {
                                                return function (string $attribute, $value, $fail) {
                                                    if ($value) {
                                                        $extension = strtolower(pathinfo($value->getClientOriginalName(), PATHINFO_EXTENSION));
                                                        if ($extension !== 'apk') {
                                                            $fail('文件必须是 .apk 格式');
                                                        }
                                                    }
                                                };
                                            }
                                        ])
                                        ->visible(fn($get) => $get('apk_upload_enabled'))
                                        ->reactive(),
                                ])
                                ->extraAttributes(['class' => 'border-0 shadow-none p-0']),

                            // 第1行 - 右列
                            Section::make('展示设置')
                                ->description('控制页面元素显示')
                                ->icon('heroicon-o-eye')
                                ->compact()
                                ->columnSpan(1)
                                ->schema([
                                    Toggle::make('ercode_show')
                                        ->label('开启二维码显示')
                                        ->helperText('在应用页面展示二维码')
                                        ->inline()
                                        ->default(true),
                                ])
                                ->extraAttributes(['class' => 'border-0 shadow-none p-0']),

                            // 第2行 - 左列
                            Section::make('包模式')
                                ->description('选择应用分发方式')
                                ->icon('heroicon-o-cube')
                                ->compact()
                                ->columnSpan(1)
                                ->schema([
                                    Radio::make('package_priority')
                                        ->label('分发模式')
                                        ->options(function ($get) {
                                            $options = ['PWA' => 'PWA（渐进式应用）'];
                                            if ($get('apk_upload_enabled') && $get('apk')) {
                                                $options['W2A'] = 'W2A（仅 APK）';
                                            }
                                            return $options;
                                        })
                                        ->default('PWA')
                                        ->reactive()
                                        ->columns(1),
                                ])
                                ->extraAttributes(['class' => 'border-0 shadow-none p-0']),

                            // 第2行 - 右列
                            Section::make('兼容性')
                                ->description('平台兼容设置')
                                ->icon('heroicon-o-device-phone-mobile')
                                ->compact()
                                ->columnSpan(1)
                                ->schema([
                                    Toggle::make('ios_guide')
                                        ->label('iOS 兼容')
                                        ->helperText('开启 iOS 设备兼容模式')
                                        ->inline()
                                        ->visible(fn($get) => !$get('package_priority') || $get('package_priority') !== 'W2A')
                                        ->default(true),

                                    Toggle::make('w2a_auto_down')
                                        ->label('W2A 自动下载')
                                        ->helperText('自动下载 APK 安装包')
                                        ->inline()
                                        ->visible(fn($get) => $get('package_priority') === 'W2A'),
                                ])
                                ->extraAttributes(['class' => 'border-0 shadow-none p-0']),

                            // 第3行 - 左列
                            Section::make('嵌入设置')
                                ->description('网页嵌入选项')
                                ->icon('heroicon-o-window')
                                ->compact()
                                ->columnSpan(1)
                                ->schema([
                                    Toggle::make('is_iframe')
                                        ->label('允许 IFrame 嵌入')
                                        ->helperText('允许在其他网页中嵌入显示')
                                        ->inline(),
                                ])
                                ->extraAttributes(['class' => 'border-0 shadow-none p-0']),

                            // 第3行 - 右列
                            Section::make('投诉设置')
                                ->description('用户反馈与投诉管理')
                                ->icon('heroicon-o-flag')
                                ->compact()
                                ->columnSpan(1)
                                ->schema([
                                    Toggle::make('complaint')
                                        ->label('开启投诉入口')
                                        ->helperText('有利于降低广告投诉、提升审核通过率')
                                        ->inline()
                                        ->reactive(),

                                    CheckboxList::make('complaint_config')
                                        ->label('可投诉状态')
                                        ->helperText('选择用户可投诉的应用状态')
                                        ->options([
                                            1 => '已安装',
                                            2 => '已启动',
                                            3 => '未启动',
                                            4 => '已卸载',
                                        ])
                                        ->columns(2)
                                        ->visible(fn($get) => $get('complaint'))
                                ])
                                ->extraAttributes(['class' => 'border-0 shadow-none p-0']),
                        ]),
                    ])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 整合的APP信息列（包含icon、name、appid、remark）
                ViewColumn::make('app_info')
                    ->label('APP信息')
                    ->viewData(fn($record) => ['record' => $record])
                    ->view('filament.tables.columns.app-info'),

                // 包文件列（apk_upload_enabled=1 显示蓝色已上传）
                TextColumn::make('apk_upload_enabled')
                    ->label('包文件')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? '已上传' : '未上传')
                    ->color(fn($state) => $state ? 'success' : 'warning'),

                // 模式展示列（package_priority）
                TextColumn::make('package_priority')->label('模式'),

                // iframe列（is_iframe 是/否）
                TextColumn::make('is_iframe')
                    ->label('iframe')
                    ->formatStateUsing(fn($state) => $state ? '是' : '否'),

                // 展示谷歌图标列（google_show 是/否）
                TextColumn::make('google_show')
                    ->label('展示谷歌图标')
                    ->formatStateUsing(fn($state) => $state ? '是' : '否'),
            ])
            ->filters([
                // APP名称筛选
                Filter::make('name')
                    ->form([
                        TextInput::make('name')
                            ->label('APP名称')
                            ->inlineLabel()
                            ->placeholder('请输入APP名称'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['name'],
                            fn(Builder $query, $name): Builder => $query->where('name', 'like', "%{$name}%")
                        );
                    }),

                // APPID筛选
                Filter::make('id')
                    ->form([
                        TextInput::make('id')
                            ->label('APPID')
                            ->inlineLabel()
                            ->placeholder('请输入APPID')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['id'],
                            fn(Builder $query, $id): Builder => $query->where('id', $id)
                        );
                    }),

                // 状态筛选（是否上传安装包）
                Filter::make('apk_upload_enabled')
                    ->form([
                        Select::make('apk_upload_enabled')
                            ->label('状态')
                            ->inlineLabel()
                            ->options([
                                '1' => '已上传',
                                '0' => '未上传',
                            ])
                            ->placeholder('请选择状态'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['apk_upload_enabled'],
                            fn(Builder $query, $status): Builder => $query->where('apk_upload_enabled', $status)
                        );
                    }),
                Filter::make('is_delete')
                    ->label('显示隐藏')
                    ->form([
                        Forms\Components\Toggle::make('is_delete')
                            ->label('显示全部(含隐藏)')
                            ->inline()
                            ->default(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['is_delete']) || !$data['is_delete']) {
                            // 如果开关未启用，只显示未删除的项目
                            return $query->where('is_delete', false);
                        }
                        // 如果开关启用，显示所有项目（包括已删除的）
                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['is_delete'] ?? false) {
                            $indicators[] = '包含隐藏项目';
                        }
                        return $indicators;
                    }),

            ])
            ->filtersFormWidth('full')
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make(),
                // 添加隐藏操作
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
            ->bulkActions([
                //
            ])
            ->defaultSort('id', 'desc');
    }


    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'create' => Pages\CreateApplication::route('/create'),
            'edit' => Pages\EditApplication::route('/{record}/edit'),
        ];
    }


}
