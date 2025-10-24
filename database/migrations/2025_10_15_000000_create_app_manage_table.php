<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->default(0)->comment('用户ID');
            $table->string('name')->nullable()->comment('名称');
            $table->string('remark')->nullable()->comment('备注');
            $table->boolean('google_show')->default(false)->comment('是否开启谷歌图标');
            $table->boolean('official_verified')->default(false)->comment('是否官方认证');
            $table->string('icon')->nullable()->comment('图标');
            $table->string('background_color')->nullable()->comment('背景色');
            $table->string('theme_color')->nullable()->comment('主题色');
            $table->string('category')->nullable()->comment('底部菜单激活');
            $table->string('display_mode')->default('standalone')->comment('显示模式');
            $table->string('orientation')->default('natural')->comment('PWA启动页横竖屏');
            $table->boolean('apk_upload_enabled')->default(false)->comment('是否上传APK');
            $table->string('apk')->nullable()->comment('APK');
            $table->boolean('ercode_show')->default(true)->comment('是否开启二维码显示');
            $table->string('package_priority')->nullable()->comment('包优先级');
            $table->boolean('ios_guide')->default(false)->comment('是否开启IOS兼容');
            $table->unsignedTinyInteger('choose')->default(1)->comment('包模式选择 1-pwa优先 2-仅w2a');
            $table->boolean('w2a_auto_down')->default(false)->comment('W2A是否自动下载APK');
            $table->boolean('is_iframe')->default(true)->comment('是否Iframe嵌入');
            $table->boolean('complaint')->default(false)->comment('投诉入口');
            $table->string('complaint_config')->nullable()->comment('投诉配置 1-已安装 2-已启动 3-未启动 4-已卸载，逗号分割');
            $table->boolean('is_delete')->default(0)->comment('是否隐藏');
            $table->timestamps();
        });

        Schema::create('locale_application', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('language_id')->comment('语言ID');
            $table->unsignedBigInteger('app_id')->comment('应用ID');
            $table->string('name', 64)->nullable()->comment('名称');
            $table->string('manufacturer', 64)->nullable()->comment('应用厂商');
            $table->string('icon', 255)->nullable()->comment('图标');
            $table->string('downloads', 255)->nullable()->comment('下载数');
            $table->unsignedTinyInteger('age_limit')->nullable()->comment('适用年龄');
            $table->unsignedBigInteger('comment_count')->nullable()->comment('评论数');
            $table->string('introduction', 1024)->nullable()->comment('简介');
            $table->text('images')->nullable()->comment('详情图片，json');
            $table->string('label', 1024)->nullable()->comment('标签，逗号分割');
            $table->timestamps();
            $table->unique(['language_id', 'app_id']);
        });

        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('名称');
            $table->string('en_name')->comment('英文名称');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态');
            $table->timestamps();
        });

        DB::table('languages')->insert([
            ['id' => '1','name' => '英语', 'en_name' => 'English'],
            ['id' => '2','name' => '葡萄牙语', 'en_name' => 'Portuguese'],
            ['id' => '3','name' => '菲律宾语', 'en_name' => 'Filipino'],
            ['id' => '4','name' => '越南语', 'en_name' => 'Vietnamese'],
            ['id' => '5','name' => '印尼语', 'en_name' => 'Indonesian'],
            ['id' => '6','name' => '泰语', 'en_name' => 'Thai'],
            ['id' => '7','name' => '日语', 'en_name' => 'Japanese'],
            ['id' => '8','name' => '韩语', 'en_name' => 'Korean'],
            ['id' => '9','name' => '孟加拉语', 'en_name' => 'Bengali'],
            ['id' => '10','name' => '阿拉伯语', 'en_name' => 'Arabic'],
            ['id' => '11','name' => '德语', 'en_name' => 'German'],
            ['id' => '12','name' => '法语', 'en_name' => 'French'],
            ['id' => '13','name' => '西班牙语', 'en_name' => 'Spanish'],
            ['id' => '14','name' => '缅甸语', 'en_name' => 'Burmese'],
            ['id' => '15','name' => '俄语', 'en_name' => 'Russian'],
            ['id' => '16','name' => '高棉语', 'en_name' => 'Cambodian'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('locale_application');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('languages');
    }
};
