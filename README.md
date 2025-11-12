# PWA Cloak 应用管理系统

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
</p>

## 📋 项目简介

PWA Cloak 是一个基于 Laravel 12 和 Filament 3 构建的现代化应用管理系统，主要用于管理 PWA（渐进式 Web 应用）的配置、域名、广告像素、推广链接等核心业务。系统提供了完整的权限管理、多语言支持、自动化域名配置和像素验证等功能。

## ✨ 核心功能

### 1. 应用管理 (Application)
- 应用信息配置（名称、图标、主题色、背景色等）
- PWA 配置（显示模式、方向、APK 上传等）
- 多语言支持（LocaleApplication）
- 应用评论管理
- Google Play 商店显示控制
- 官方认证标记

### 2. 域名管理 (Domain)
- **AWS Route53 集成**：自动创建 Hosted Zone
- **三步向导式域名配置**：
  - 第一步：输入域名，自动创建 AWS Hosted Zone
  - 第二步：显示 DNS 名称服务器，引导用户配置
  - 第三步：自动检测 DNS 解析状态
- 支持平台域名和托管域名两种类型
- 域名状态实时检测
- 域名与推广链接关联

### 3. 像素管理 (Pixel)
- 支持三大广告平台：
  - **Facebook Pixel**
  - **TikTok Pixel**
  - **Google Conversion Tracking**
- **自动验证系统**：
  - 定时检查 Access Token 有效性
  - 每天凌晨 2 点全量检查
  - 每 6 小时检查异常像素
  - 手动测试工具
- 像素状态实时更新
- 测试事件代码配置

### 4. 推广链接管理 (Promotion)
- 推广链接创建和配置
- 关联应用、像素、域名
- 地区定向配置
- Cloak 功能开关
- 模板选择
- 支持 iOS 和 Android 双链接

### 5. 评论管理 (Comment)
- 评论库管理
- 多语言评论支持
- 评论与应用关联
- 评论审核功能

### 6. 权限系统 (RBAC)
- **两种用户角色**：
  - `super_admin`：超级管理员，可查看和管理所有数据
  - `panel_user`：普通用户，只能管理自己创建的数据
- 基于角色的数据访问控制
- 统一的权限 Trait (`HasUserAccess`)
- 自动数据过滤和权限检查

### 7. 其他功能
- 归因平台管理 (OtherPixel)
- 语言和地区管理
- 用户管理
- 完整的日志系统

## 🛠️ 技术栈

### 后端
- **Laravel 12** - PHP Web 框架
- **PHP 8.2+** - 编程语言
- **MySQL 8** - 数据库
- **Filament 3** - 管理后台框架
- **Filament Shield** - 权限管理插件
- **AWS SDK for PHP** - AWS Route53 集成

### 前端
- **Vite 7** - 构建工具
- **Tailwind CSS 4** - CSS 框架
- **Livewire** - 动态组件
- **Alpine.js** - 轻量级 JavaScript 框架

### 基础设施
- **Docker & Docker Compose** - 容器化部署
- **Nginx** - Web 服务器
- **PHP-FPM** - PHP 进程管理器

## 📁 项目结构

```
pwa-cloak/
├── app/
│   ├── Console/Commands/          # Artisan 命令
│   ├── Filament/                   # Filament 资源
│   │   ├── Resources/              # 资源管理
│   │   ├── Forms/                  # 表单组件
│   │   ├── Traits/                 # 共享 Trait
│   │   └── Widgets/                # 仪表板组件
│   ├── Helper/                     # 辅助函数
│   │   └── functions.php           # 全局函数
│   ├── Http/                       # HTTP 层
│   │   └── Controllers/            # 控制器
│   ├── Livewire/                   # Livewire 组件
│   ├── Models/                     # Eloquent 模型
│   ├── Observers/                  # 模型观察者
│   ├── Providers/                  # 服务提供者
│   └── Services/                   # 业务服务
├── config/                         # 配置文件
├── database/                       # 数据库
│   ├── migrations/                 # 数据库迁移
│   └── seeders/                    # 数据填充
├── docs/                           # 文档目录
├── nginx/                          # Nginx 配置
├── public/                         # 公共资源
├── resources/                      # 视图和资源
│   ├── views/                      # Blade 模板
│   └── js/                         # JavaScript 文件
├── routes/                         # 路由定义
│   ├── web.php                     # Web 路由
│   └── console.php                 # 定时任务路由
├── scripts/                        # 脚本文件
├── storage/                        # 存储目录
├── tests/                          # 测试文件
├── docker-compose.yml              # Docker Compose 配置
├── Dockerfile                      # Docker 镜像配置
├── nginx.conf                      # Nginx 配置
├── composer.json                   # PHP 依赖
├── package.json                    # Node.js 依赖
└── vite.config.js                  # Vite 配置
```

## 🚀 快速开始

### 环境要求

- PHP 8.2 或更高版本
- Composer
- Node.js 18+ 和 npm
- MySQL 8.0+
- Docker 和 Docker Compose（可选）

### 安装步骤

#### 1. 克隆项目

```bash
git clone <repository-url>
cd pwa-cloak
```

#### 2. 安装 PHP 依赖

```bash
composer install
```

#### 3. 安装前端依赖

```bash
npm install
```

#### 4. 配置环境变量

```bash
cp .env.example .env
php artisan key:generate
```

编辑 `.env` 文件，配置数据库和 AWS 凭证：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pwa-cloak
DB_USERNAME=your_username
DB_PASSWORD=your_password

AWS_ACCESS_KEY_ID=your_aws_key
AWS_SECRET_ACCESS_KEY=your_aws_secret
AWS_DEFAULT_REGION=us-east-1
```

#### 5. 运行数据库迁移

```bash
php artisan migrate
```

#### 6. 构建前端资源

```bash
npm run build
```

#### 7. 配置定时任务

编辑 `crontab.txt`，修改项目路径，然后安装：

```bash
crontab crontab.txt
```

或使用自动安装脚本：

```bash
bash scripts/install-crontab.sh
```

### 使用 Docker 部署

#### 启动服务

```bash
docker-compose up -d
```

#### 进入容器执行命令

```bash
docker-compose exec php php artisan migrate
docker-compose exec php php artisan key:generate
```

#### 访问应用

- Web 应用：http://localhost:8003
- 管理后台：http://localhost:8003/admin

## 📖 使用指南

### 域名管理

#### 创建新域名

1. 进入 **域名管理** → **新建域名**
2. **第一步**：输入域名（如 `example.com`）
   - 系统自动调用 AWS Route53 API 创建 Hosted Zone
   - 获取 DNS 名称服务器列表
3. **第二步**：配置 DNS
   - 复制显示的 DNS 名称服务器地址
   - 前往域名注册商处更新 NS 记录
4. **第三步**：验证域名
   - 系统自动检测 DNS 解析状态
   - 至少一条解析成功 → 显示"保存"按钮
   - 全部解析失败 → 显示"先保存，等下再检查"按钮

#### 手动检查域名状态

```bash
php artisan domain:check-status
```

### 像素管理

#### 创建像素配置

1. 进入 **像素管理** → **新建像素**
2. 选择广告平台（Facebook/TikTok/Google）
3. 输入像素代码和 Access Token
4. 配置测试事件代码（可选）

#### 测试像素验证

```bash
# Facebook 像素
php artisan pixel:test-validation 1 "123456789012345" "your-access-token"

# TikTok 像素
php artisan pixel:test-validation 2 "C4A123456789" "your-access-token"

# Google 转化
php artisan pixel:test-validation 3 "AW-9876543210" "your-access-token"
```

#### 批量检查像素状态

```bash
# 检查所有异常像素（默认）
php artisan pixel:check-status

# 检查所有像素
php artisan pixel:check-status --all

# 只检查 Facebook 像素
php artisan pixel:check-status --channel=1

# 查看详细输出
php artisan pixel:check-status -v
```

### 应用管理

1. 进入 **应用管理** → **新建应用**
2. 配置应用基本信息（名称、图标、主题色等）
3. 配置 PWA 设置（显示模式、方向等）
4. 添加多语言版本
5. 关联评论和推广链接

### 推广链接管理

1. 进入 **推广链接** → **新建推广链接**
2. 选择关联的应用
3. 配置广告渠道和像素
4. 选择目标地区
5. 绑定域名
6. 配置 Cloak 功能

## 🔧 定时任务

系统使用 Laravel 的调度器管理定时任务。在 Laravel 11+ 中，定时任务配置在 `bootstrap/app.php` 文件的 `withSchedule()` 方法中。

### 定时任务配置位置

**Laravel 11+（当前版本）**：`bootstrap/app.php`

```php
->withSchedule(function (Schedule $schedule): void {
    // 域名解析状态检查定时任务
    $schedule->command('domain:check-resolution')
        ->daily()
        ->withoutOverlapping()
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/domain-check.log'));

    // 像素验证任务 - 每小时检查异常像素
    $schedule->command('pixel:check-status --limit=50')
        ->hourly()
        ->withoutOverlapping()
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/pixel-check.log'));

    // 像素验证任务 - 每天凌晨2点全量检查
    $schedule->command('pixel:check-status --all --limit=100')
        ->dailyAt('02:00')
        ->withoutOverlapping()
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/pixel-check-all.log'));
})
```

#### 像素验证任务

- **全量检查**：每天凌晨 2:00 执行（检查所有像素）
- **异常检查**：每小时执行一次（只检查状态异常的像素）

#### 域名检查任务

- **DNS 解析检查**：每天执行一次（检查未解析的域名）

### 配置服务器 Crontab

确保服务器 crontab 已配置，每分钟执行一次 Laravel 调度器：

```bash
* * * * * cd /path/to/pwa-cloak && php artisan schedule:run >> /dev/null 2>&1
```

**注意**：请将 `/path/to/pwa-cloak` 替换为你的实际项目路径。

### 查看定时任务列表

```bash
php artisan schedule:list
```

### 手动测试定时任务

```bash
# 执行所有到期的定时任务
php artisan schedule:run

# 测试定时任务（不实际执行）
php artisan schedule:test
```

## 🔐 权限系统

### 用户角色

- **super_admin**：超级管理员
  - 可查看和管理所有用户的数据
  - 拥有所有权限
  
- **panel_user**：普通用户
  - 只能查看和管理自己创建的数据
  - 数据自动过滤

### 应用权限控制

所有包含 `user_id` 字段的模块都已应用权限控制：

- ✅ ApplicationResource - 应用管理
- ✅ CommentResource - 评论库
- ✅ DomainResource - 域名管理
- ✅ PixelResource - 像素配置
- ✅ OtherPixelResource - 归因平台
- ✅ PromotionResource - 推广链接

### 在新模块中应用权限

```php
use App\Filament\Traits\HasUserAccess;

class YourResource extends Resource
{
    use HasUserAccess;
    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        return applyUserDataScope($query);
    }
}
```

详细说明请参考：`docs/权限系统说明.md`

## 🧪 开发指南

### 开发环境启动

使用 Composer 脚本快速启动开发环境：

```bash
composer run dev
```

这将同时启动：
- Laravel 开发服务器
- 队列监听器
- Pail 日志查看器
- Vite 开发服务器

### 代码规范

项目使用 Laravel Pint 进行代码格式化：

```bash
./vendor/bin/pint
```

### 运行测试

```bash
composer run test
```

或使用 PHPUnit：

```bash
php artisan test
```

### 生成 IDE 辅助文件

```bash
php artisan ide-helper:generate
php artisan ide-helper:models
php artisan ide-helper:meta
```

## 📚 相关文档

- [权限系统说明](docs/权限系统说明.md)
- [像素验证系统](docs/pixel-validation-system.md)
- [域名管理向导实现](DOMAIN_WIZARD_IMPLEMENTATION.md)
- [像素验证快速入门](PIXEL_VALIDATION_README.md)

## 🔍 核心服务说明

### AwsRoute53Service

AWS Route53 域名管理服务：

- `createHostedZone()` - 创建 Hosted Zone
- `resolveNsRecord()` - 解析 NS 记录

支持 Mock 模式，便于本地开发测试。

### PixelValidationService

像素验证服务：

- `validateFacebookPixel()` - 验证 Facebook 像素
- `validateTikTokPixel()` - 验证 TikTok 像素
- `validateGoogleConversion()` - 验证 Google 转化跟踪
- `validatePixel()` - 自动选择验证方法
- `validatePixelsBatch()` - 批量验证

## 🐛 故障排查

### 像素验证失败

1. 检查 Access Token 是否有效
2. 查看日志：`storage/logs/pixel-check.log`
3. 使用测试命令手动验证

### 域名 DNS 解析失败

1. 确认已在域名注册商处更新 NS 记录
2. 等待 DNS 传播（通常需要几分钟到几小时）
3. 使用"重新检测"功能重试

### 权限问题

1. 确认用户角色配置正确
2. 检查 `user_id` 字段是否正确关联
3. 查看 `app/Helper/functions.php` 中的权限函数


<p align="center">Made with ❤️ using Laravel & Filament</p>
