<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationResource\Enum\CategoryEnum;
use App\Filament\Resources\CustomAppResource\Enum\ButtonPositionEnum;
use App\Filament\Resources\CustomAppResource\Pages;
use App\Filament\Resources\CustomAppResource\RelationManagers;
use App\Filament\Traits\HasUserAccess;
use App\Models\Application;
use App\Models\Language;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\View;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class CustomAppResource extends Resource
{
    use HasUserAccess;

    protected const DEFAULT_LANGUAGE_ID = 1;

    protected static ?string $model = Application::class;
    protected static ?string $navigationGroup = '推广';
    protected static ?string $navigationLabel = '自定义应用';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'customApp';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('app_type', Application::APP_TYPE_CUSTOM);

        // 应用用户数据权限过滤
        return applyUserDataScope($query);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('app_type')
                    ->default(Application::APP_TYPE_CUSTOM)
                    ->dehydrated(),

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
                                    'standalone' => '应用模式',
                                    'fullscreen' => '全屏模式',
                                ])
                                ->required()
                                ->default('standalone')
                                ->placeholder('请选择显示模式')
                                ->reactive()
                                ->helperText(function (Get $get) {
                                    return $get('display_mode') === 'fullscreen'
                                        ? '全屏显示，隐藏地址栏、状态栏和导航栏，适合游戏类 App。'
                                        : '类原生应用模式，没有浏览器 UI（地址栏等），但仍保留操作系统的导航栏。最常用。';
                                })
                                ->prefixIcon('heroicon-o-computer-desktop'),

                            Select::make('orientation')
                                ->label('屏幕方向')
                                ->options([
                                    'natural' => '跟随系统',
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
                            ->default(fn(?Application $record) => $record && $record->exists
                                ? $record->languages()->pluck('id')->toArray()
                                : [self::DEFAULT_LANGUAGE_ID])
                            ->afterStateHydrated(function (callable $set, callable $get, $state, ?Application $record) {
                                if ($record && $record->exists) {
                                    return;
                                }

                                $currentLanguages = collect($state)->filter()->all();

                                if (empty($currentLanguages)) {
                                    $currentLanguages = [self::DEFAULT_LANGUAGE_ID];
                                    $set('languages', $currentLanguages);
                                }

                                $localeApplications = $get('localeApplications') ?: [];
                                foreach ($currentLanguages as $languageId) {
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
                                            'install_button' => true,
                                            'install_button_text' => '',
                                            'install_button_color' => '',
                                            'install_button_position' => ButtonPositionEnum::BOTTOM,
                                        ];
                                    }
                                }

                                $set('localeApplications', $localeApplications);
                            })
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
                                                'install_button' => true,
                                                'install_button_text' => '',
                                                'install_button_color' => '',
                                                'install_button_position' => ButtonPositionEnum::BOTTOM,
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
                                                ->label('请上传APP图标')
                                                ->helperText('只接受png,jpg,jpeg格式图片,建议尺寸512px * 512px 或者 1024px * 1024px')
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


                                            FileUpload::make("localeApplications.{$languageId}.images")
                                                ->label('自定义图片')
                                                ->helperText('支持jpg/jpeg/webp/png格式,且不能超过500KB,最多只能上传5张')
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

                                            Toggle::make("localeApplications.{$languageId}.install_button")
                                                ->label('启用安装键')
                                                ->inline()
                                                ->reactive()
                                                ->default(true)
                                                ->columnSpanFull(),

                                            TextInput::make("localeApplications.{$languageId}.install_button_text")
                                                ->label('安装键文案')
                                                ->placeholder('请输入安装键文案')
                                                ->maxLength(50)
                                                ->visible(fn(Get $get) => (bool) $get("localeApplications.{$languageId}.install_button"))
                                                ->columnSpanFull(),

                                            Grid::make(2)
                                                ->schema([
                                                    ColorPicker::make("localeApplications.{$languageId}.install_button_color")
                                                        ->label('安装键颜色'),
                                                    Select::make("localeApplications.{$languageId}.install_button_position")
                                                        ->label('安装键位置')
                                                        ->options(ButtonPositionEnum::SELECT)
                                                        ->default(ButtonPositionEnum::BOTTOM),
                                                ])
                                                ->columnSpanFull()
                                                ->visible(fn(Get $get) => (bool) $get("localeApplications.{$languageId}.install_button")),
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
                                        ->default(false),

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
                                        ->default(true)
                                        ->inline(),
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
                // 卡片视图列
                ViewColumn::make('card')
                    ->view('filament.tables.custom-app-card')
                    ->viewData(fn($record) => ['record' => $record]),
            ])
            ->contentGrid([
                'md' => 4,
                'xl' => 6,
                '2xl' => 8,
                'gap' => 12, // 控制卡片间距
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

            ])
            ->filtersFormWidth('full')
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->actions([]) // 卡片布局不需要表格操作，按钮已在卡片内
            ->bulkActions([]) // 卡片布局不需要批量操作
            ->defaultSort('id', 'desc')
            ->paginated([10, 20, 50]); // 优化分页选项，适合卡片布局
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
            'index' => Pages\ListCustomApps::route('/'),
            'create' => Pages\CreateCustomApp::route('/create'),
            'edit' => Pages\EditCustomApp::route('/{record}/edit'),
        ];
    }
}
