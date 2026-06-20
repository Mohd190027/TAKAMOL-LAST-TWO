<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Create roles ────────────────────────────────────────────────
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'مدير النظام — صلاحيات كاملة']
        );

        $userRole = Role::firstOrCreate(
            ['name' => 'user'],
            ['description' => 'مستخدم عادي — عرض فقط']
        );

        // ── 2. Create admin user ───────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'admin@tkamel.sa'],
            [
                'role_id' => $adminRole->id,
                'full_name' => 'مدير النظام',
                'password_hash' => Hash::make('Admin@1234'),
            ]
        );

        // ── 3. Create regular user ─────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'user@tkamel.sa'],
            [
                'role_id' => $userRole->id,
                'full_name' => 'مستخدم تجريبي',
                'password_hash' => Hash::make('User@1234'),
            ]
        );
    }
}
