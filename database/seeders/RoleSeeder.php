<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles matching user_type ENUM
        Role::create(['name' => 'admin']);  // Full access: manage VIPs, Sparks, gifts
        Role::create(['name' => 'customer']);    // Limited: view own Sparks/gifts, set reminders
        Role::create(['name' => 'vendor']);   // Basic: set Sparks, send gifts/notifications
        Role::create(['name' => 'rider']);   // Basic: set Sparks, send gifts/notifications
        Role::create(['name' => 'delivery_man']);   // Basic: set Sparks, send gifts/notifications

        // Assign all permissions to admin role (if permissions seeded elsewhere)
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole && Permission::count() > 0) {
            $adminRole->syncPermissions(Permission::all());
        }

        // Assign roles to users (after UserSeeder)
        $adminUser = User::where('email', 'admin@admin.com')->first();
        if ($adminUser) {
            $adminUser->assignRole('admin');
        }

        $customer = User::where('email', 'customer@customer.com')->first();
        if ($customer) {
            $customer->assignRole('customer');
        }

        $vendor = User::where('email', 'vendor@vendor.com')->first();
        if ($vendor) {
            $vendor->assignRole('vendor');
        }

        $rider = User::where('email', 'rider@rider.com')->first();
        if ($rider) {
            $rider->assignRole('rider');
        }

        $deliveryMan = User::where('email', 'delivery_man@delivery_man.com')->first();
        if ($deliveryMan) {
            $deliveryMan->assignRole('delivery_man');
        }
    }
}