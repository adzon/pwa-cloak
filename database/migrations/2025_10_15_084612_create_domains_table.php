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
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->default(0)->comment('用户id');
            $table->unsignedBigInteger('pid')->nullable()->comment('父级id');
            $table->unsignedBigInteger('promotion_id')->default(0)->comment('推广连接id');
            $table->string('domain')->comment('域名');
            $table->string('hosting_id')->comment('主机id');
            $table->text('hosting_name_servers')->comment('主机服务器名称');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态');
            $table->unsignedBigInteger('is_save')->default(0)->comment('使用状态');
            $table->boolean('is_delete')->default(0)->comment('是否隐藏');
            $table->timestamps();
            $table->index('user_id');
            $table->index('domain');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain');
    }
};
