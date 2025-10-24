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
        Schema::create('other_pixels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->default(0)->comment('用户ID');
            $table->unsignedBigInteger('channel')->default(1)->comment('Channel');
            $table->string('app_name')->nullable()->comment('App Name');
            $table->string('app_code')->nullable()->comment('App Code');
            $table->string('api_code')->nullable()->comment('Api Code');
            $table->string('access_code')->nullable()->comment('Access Code');
            $table->string('install_code')->nullable()->comment('Install Code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribution_apps');
    }
};
