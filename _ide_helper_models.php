<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int $user_id 用户ID
 * @property string|null $name 名称
 * @property string|null $remark 备注
 * @property bool $google_show 是否开启谷歌图标
 * @property bool $official_verified 是否官方认证
 * @property string|null $icon 图标
 * @property string|null $background_color 背景色
 * @property string|null $theme_color 主题色
 * @property string|null $category 底部菜单激活
 * @property string $display_mode 显示模式
 * @property string $orientation PWA启动页横竖屏
 * @property bool $apk_upload_enabled 是否上传APK
 * @property string|null $apk APK
 * @property bool $ercode_show 是否开启二维码显示
 * @property string|null $package_priority 包优先级
 * @property bool $ios_guide 是否开启IOS兼容
 * @property bool $w2a_auto_down W2A是否自动下载APK
 * @property bool $is_iframe 是否Iframe嵌入
 * @property bool $complaint 投诉入口
 * @property string|null $complaint_config 投诉配置 1-已安装 2-已启动 3-未启动 4-已卸载，逗号分割
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Language> $languages
 * @property-read int|null $languages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LocaleApplication> $localeApplications
 * @property-read int|null $locale_applications_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereApk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereApkUploadEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereBackgroundColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereComplaint($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereComplaintConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereDisplayMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereErcodeShow($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereGoogleShow($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereIosGuide($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereIsIframe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereOfficialVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereOrientation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application wherePackagePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereThemeColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereW2aAutoDown($value)
 */
	class Application extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $language_id
 * @property string|null $nickname 昵称
 * @property string|null $content 评论内容
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Language|null $language
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LocaleApplication> $localeApplications
 * @property-read int|null $locale_applications_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereUserId($value)
 */
	class Comment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name 名称
 * @property string $en_name 英文名称
 * @property bool $status 状态
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Application> $applications
 * @property-read int|null $applications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comment> $comments
 * @property-read int|null $comments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LocaleApplication> $localeApplications
 * @property-read int|null $locale_applications_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language whereEnName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language whereUpdatedAt($value)
 */
	class Language extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $language_id
 * @property int $app_id
 * @property string|null $name 名称
 * @property string|null $manufacturer 应用厂商
 * @property string|null $icon 图标
 * @property string|null $downloads 下载数
 * @property int|null $age_limit 适用年龄
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comment> $comments 评论数
 * @property string|null $introduction 简介
 * @property string|null $images 详情图片，逗号分割
 * @property string|null $label 标签，逗号分割
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Application|null $application
 * @property-read int|null $comments_count
 * @property-read \App\Models\Language|null $language
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplication newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplication newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplication query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplication whereAgeLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplication whereAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplication whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplication whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplication whereDownloads($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplication whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplication whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplication whereImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplication whereIntroduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplication whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplication whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplication whereManufacturer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplication whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplication whereUpdatedAt($value)
 */
	class LocaleApplication extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $locale_application_id
 * @property int $comment_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Comment|null $comment
 * @property-read \App\Models\LocaleApplication|null $localeApplication
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplicationComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplicationComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplicationComment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplicationComment whereCommentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplicationComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplicationComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplicationComment whereLocaleApplicationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LocaleApplicationComment whereUpdatedAt($value)
 */
	class LocaleApplicationComment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

