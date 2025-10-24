<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->default(0)->comment('用户ID');
            $table->unsignedBigInteger('language_id')->default(0)->comment('语言ID');
            $table->string('nickname')->nullable()->comment('昵称');
            $table->string('content', 512)->nullable()->comment('评论内容');
            $table->timestamps();
        });

        Schema::create('locale_application_comment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('locale_application_id');
            $table->unsignedBigInteger('comment_id');
            $table->timestamps();
            $table->unique(['locale_application_id', 'comment_id'], 'la_comment_unique');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
