<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessRestrictionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure roles exist
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'sub_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'vendor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'rider', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'delivery_man', 'guard_name' => 'web']);
    }

    public function test_admin_user_can_access_admin_dashboard(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_test_access@example.com'],
            [
                'name' => 'Admin Tester',
                'password' => '12345678',
                'user_type' => 'admin',
                'is_admin' => true,
                'status' => 'active',
            ]
        );
        $admin->syncRoles(['admin']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertStatus(200);
    }

    public function test_sub_admin_user_can_access_admin_dashboard(): void
    {
        $subAdmin = User::firstOrCreate(
            ['email' => 'sub_admin_test_access@example.com'],
            [
                'name' => 'Sub Admin Tester',
                'password' => '12345678',
                'user_type' => 'sub_admin',
                'is_admin' => false,
                'status' => 'active',
            ]
        );
        $subAdmin->syncRoles(['sub_admin']);

        $response = $this->actingAs($subAdmin)->get('/admin/dashboard');
        $response->assertStatus(200);
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $customer = User::firstOrCreate(
            ['email' => 'customer_test_blocked@example.com'],
            [
                'name' => 'Customer Tester',
                'password' => '12345678',
                'user_type' => 'customer',
                'is_admin' => false,
                'status' => 'active',
            ]
        );
        $customer->syncRoles(['customer']);

        $response = $this->actingAs($customer)->get('/admin/dashboard');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_rider_cannot_access_admin_dashboard(): void
    {
        $rider = User::firstOrCreate(
            ['email' => 'rider_test_blocked@example.com'],
            [
                'name' => 'Rider Tester',
                'password' => '12345678',
                'user_type' => 'rider',
                'is_admin' => false,
                'status' => 'active',
            ]
        );
        $rider->syncRoles(['rider']);

        $response = $this->actingAs($rider)->get('/admin/dashboard');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_vendor_cannot_access_admin_dashboard(): void
    {
        $vendor = User::firstOrCreate(
            ['email' => 'vendor_test_blocked@example.com'],
            [
                'name' => 'Vendor Tester',
                'password' => '12345678',
                'user_type' => 'vendor',
                'is_admin' => false,
                'status' => 'active',
            ]
        );
        $vendor->syncRoles(['vendor']);

        $response = $this->actingAs($vendor)->get('/admin/dashboard');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_delivery_man_cannot_access_admin_dashboard(): void
    {
        $deliveryMan = User::firstOrCreate(
            ['email' => 'delivery_test_blocked@example.com'],
            [
                'name' => 'Delivery Tester',
                'password' => '12345678',
                'user_type' => 'delivery_man',
                'is_admin' => false,
                'status' => 'active',
            ]
        );
        $deliveryMan->syncRoles(['delivery_man']);

        $response = $this->actingAs($deliveryMan)->get('/admin/dashboard');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_rider_cannot_login_via_admin_login(): void
    {
        $rider = User::firstOrCreate(
            ['email' => 'rider_login_blocked@example.com'],
            [
                'name' => 'Rider Login Blocked',
                'password' => '12345678',
                'user_type' => 'rider',
                'is_admin' => false,
                'status' => 'active',
            ]
        );
        $rider->syncRoles(['rider']);

        $response = $this->post('/admin/login', [
            'email' => $rider->email,
            'password' => '12345678',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
