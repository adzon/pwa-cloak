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
        Schema::create('pixels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->default(0)->comment('用户ID');
            $table->string('pixel_code')->nullable()->comment('像素ID');
            $table->string('pixel_name')->nullable()->comment('像素名称');
            $table->unsignedBigInteger('channel')->default(0)->comment('渠道ID');
            $table->string('test_event_code')->nullable()->comment('测试事件ID');
            $table->string('access_token')->nullable()->comment('access_token');
            $table->boolean('is_delete')->default(0)->comment('是否隐藏');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pixel');
    }
};
