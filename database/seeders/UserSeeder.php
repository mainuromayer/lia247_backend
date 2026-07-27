<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin User
        User::create([
            'avatar' => 'https://static.vecteezy.com/system/resources/previews/048/926/084/non_2x/silver-membership-icon-default-avatar-profile-icon-membership-icon-social-media-user-image-illustration-vector.jpg',
            'name' => 'Admin',
            'user_type' => 'admin',
            'phone' => '+12025550123',
            'email' => 'admin@admin.com',
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
            'is_admin' => true,
            'status' => 'active',
        ]);

        // Customer
        User::create([
            'avatar' => 'https://static.vecteezy.com/system/resources/previews/048/926/084/non_2x/silver-membership-icon-default-avatar-profile-icon-membership-icon-social-media-user-image-illustration-vector.jpg',
            'name' => 'Customer',
            'user_type' => 'customer',
            'phone' => '+12025550124',
            'email' => 'customer@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
            'status' => 'active',
        ]);

        // Vendor
        User::create([
            'avatar' => 'https://static.vecteezy.com/system/resources/previews/048/926/084/non_2x/silver-membership-icon-default-avatar-profile-icon-membership-icon-social-media-user-image-illustration-vector.jpg',
            'name' => 'Vendor',
            'user_type' => 'vendor',
            'phone' => '+12025550125',
            'email' => 'vendor@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
            'status' => 'active',
        ]);

        // Rider
        User::create([
            'avatar' => 'https://static.vecteezy.com/system/resources/previews/048/926/084/non_2x/silver-membership-icon-default-avatar-profile-icon-membership-icon-social-media-user-image-illustration-vector.jpg',
            'name' => 'Rider',
            'user_type' => 'rider',
            'phone' => '+12025550126',
            'email' => 'rider@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
            'status' => 'active',
        ]);

        // Delivery Man
        User::create([
            'avatar' => 'https://static.vecteezy.com/system/resources/previews/048/926/084/non_2x/silver-membership-icon-default-avatar-profile-icon-membership-icon-social-media-user-image-illustration-vector.jpg',
            'name' => 'Delivery Driver',
            'user_type' => 'delivery_man',
            'phone' => '+12025550127',
            'email' => 'delivery_man@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
            'status' => 'active',
        ]);
    }
}





