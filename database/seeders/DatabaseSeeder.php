<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. 初始化角色
        $this->call([
            RoleSeeder::class,
        ]);

        // 2. 创建管理员用户（如果不存在）
        $admin = User::firstOrCreate(
            ['email' => 'admin@bz.com'],
            [
                'name' => 'admin',
                'email' => 'admin@bz.com',
                'password' => bcrypt('aa123456'),
            ]
        );

        // 3. 为管理员分配超级管理员角色
        if (!$admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
            $this->command->info('已为管理员分配 super_admin 角色');
        }

        // User::factory(10)->create();
    }
}
