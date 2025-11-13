<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 给 applications 表添加 app_type 字段
        Schema::table('applications', function (Blueprint $table) {
            $table->unsignedTinyInteger('app_type')->default(1)->after('is_delete')->comment('应用类型');
        });

        // 给 locale_application 表添加安装键相关字段
        Schema::table('locale_application', function (Blueprint $table) {
            $table->boolean('install_button')->default(true)->after('label')->comment('安装键设置');
            $table->string('install_button_text')->default('')->after('install_button')->comment('安装键文案');
            $table->string('install_button_color', 20)->default('#952929')->after('install_button_text')->comment('安装键颜色');
            $table->unsignedTinyInteger('install_button_position')->default(3)->after('install_button_color')->comment('安装键位置');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('app_type');
        });

        Schema::table('locale_application', function (Blueprint $table) {
            $table->dropColumn([
                'install_button',
                'install_button_text',
                'install_button_color',
                'install_button_position',
            ]);
        });
    }
};
