<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'dashboard menu',
            'users menu',
            'customer list',
            'rider list',
            'delivery_man list',
            'vendor list',
            'delivery_options menu',
            'categories menu',
            'vouchers menu',
            'support_chat menu',
            'campaigns menu',
            'ads menu',
            'orders menu',
            'rides menu',
            'transport menu',
            'settings menu',
            'roles menu',
        ];

        foreach (array_unique($permissions) as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
