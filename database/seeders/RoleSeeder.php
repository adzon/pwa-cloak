<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * 初始化系统角色
     */
    public function run(): void
    {
        // 创建超级管理员角色
        Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web'],
            ['name' => 'super_admin', 'guard_name' => 'web']
        );

        // 创建普通用户角色
        Role::firstOrCreate(
            ['name' => 'panel_user', 'guard_name' => 'web'],
            ['name' => 'panel_user', 'guard_name' => 'web']
        );

        $this->command->info('角色初始化完成：super_admin, panel_user');
    }
}

