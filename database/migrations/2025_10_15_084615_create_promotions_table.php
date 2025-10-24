<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->default(0)->comment('用户ID');
            $table->string('promotion_name')->nullable()->comment('代理名称');
            $table->unsignedBigInteger('app_id')->default(0)->comment('appID');
            $table->unsignedTinyInteger('channel')->default(0)->comment('渠道ID');
            $table->unsignedBigInteger('pixel_id')->default(0)->comment('像素ID');
            $table->unsignedBigInteger('other_pixel_id')->default(0)->comment('归因平台ID');
            $table->string('region_ids', 512)->nullable()->comment('投放地区id，逗号分割');
            $table->boolean('is_open_cloak')->default(1)->comment('是否开启广告防封');
            $table->unsignedBigInteger('template_id')->default(0)->comment('审核模板ID 1-游戏 2-短剧 3-商城');
            $table->string('hast_result')->nullable()->comment('页面混淆指纹');
            $table->string('link_address', 1024)->nullable()->comment('H5链接');
            $table->string('ios_link_address')->nullable()->comment('iosH5链接');
            $table->boolean('is_delete')->default(0)->comment('是否隐藏');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
