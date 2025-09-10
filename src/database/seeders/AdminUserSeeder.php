<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'admin@example.com';

        // 既に居たら更新せず取得、居なければ作成
        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => '管理者',
                'password' => Hash::make('password'), // ←必要なら変えてOK
            ]
        );

        // 管理者フラグ/ロールがある場合だけ設定
        if (Schema::hasColumn('users', 'is_admin')) {
            $admin->is_admin = true;
            $admin->save();
        } elseif (Schema::hasColumn('users', 'role')) {
            $admin->role = 'admin';
            $admin->save();
        }
    }
}
