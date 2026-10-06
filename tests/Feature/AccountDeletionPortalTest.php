<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountDeletionPortalTest extends TestCase
{
    public function test_can_view_login_page(): void
    {
        $response = $this->get('/delete-account');

        $response->assertStatus(200);
        $response->assertSee('User Login');
        $response->assertSee('Mobile App Portal');
    }

    public function test_can_login_and_redirect_to_portal(): void
    {
        $user = User::where('user_type', 'customer')->whereNull('deleted_at')->first();
        if (!$user) {
            $user = User::create([
                'name' => 'Test Customer',
                'email' => 'customer_test_' . uniqid() . '@example.com',
                'password' => Hash::make('12345678'),
                'user_type' => 'customer',
                'status' => 'active',
            ]);
        }

        $response = $this->post('/delete-account/login', [
            'email' => $user->email,
            'password' => '12345678',
        ]);

        $response->assertRedirect('/delete-account/portal');
        $response->assertSessionHas('mobile_delete_portal_user_id', $user->id);
    }

    public function test_can_view_portal_when_authenticated(): void
    {
        $user = User::where('user_type', 'customer')->whereNull('deleted_at')->first();
        if (!$user) {
            $user = User::create([
                'name' => 'Test Customer',
                'email' => 'customer_test_' . uniqid() . '@example.com',
                'password' => Hash::make('12345678'),
                'user_type' => 'customer',
                'status' => 'active',
            ]);
        }

        $response = $this->withSession(['mobile_delete_portal_user_id' => $user->id])
            ->get('/delete-account/portal');

        $response->assertStatus(200);
        $response->assertSee($user->email);
        $response->assertSee('Delete Account &amp; Purge All Data', false);
        $response->assertSee('Account Details');
    }

    public function test_invalid_password_fails_login(): void
    {
        $response = $this->post('/delete-account/login', [
            'email' => 'customer@gmail.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_can_logout(): void
    {
        $response = $this->withSession(['mobile_delete_portal_user_id' => 2])
            ->post('/delete-account/logout');

        $response->assertRedirect('/delete-account');
        $response->assertSessionMissing('mobile_delete_portal_user_id');
    }

    public function test_can_confirm_account_deletion(): void
    {
        $originalEmail = 'temp_delete_' . time() . '@example.com';
        $tempUser = User::create([
            'name' => 'Temp To Delete',
            'email' => $originalEmail,
            'password' => 'password123',
            'user_type' => 'customer',
            'status' => 'active',
        ]);

        $response = $this->withSession(['mobile_delete_portal_user_id' => $tempUser->id])
            ->post('/delete-account/confirm', [
                'password' => 'password123',
            ]);

        $response->assertStatus(200);
        $response->assertSee('Account Purged Successfully');
        $response->assertSessionMissing('mobile_delete_portal_user_id');

        // Verify soft-deleted
        $this->assertSoftDeleted('users', ['id' => $tempUser->id]);

        // Verify email replacement and credentials
        $trashed = User::withTrashed()->find($tempUser->id);
        $this->assertEquals("delete_user_{$tempUser->id}@gmail.com", $trashed->email);
        $this->assertEquals($originalEmail, $trashed->original_email);
        $this->assertEquals('inactive', $trashed->status);
        $this->assertTrue(Hash::check('12345678', $trashed->password));
    }

    public function test_admin_can_restore_trashed_user(): void
    {
        $admin = User::where('user_type', 'admin')->first();
        $originalEmail = 'test_restore_' . time() . '@example.com';
        $tempUser = User::create([
            'name' => 'To Be Restored',
            'email' => $originalEmail,
            'password' => 'secret123',
            'user_type' => 'rider',
            'status' => 'active',
        ]);

        // Delete user
        \App\Services\UserDeletionService::deleteUser($tempUser);
        $this->assertSoftDeleted('users', ['id' => $tempUser->id]);

        // Admin restores user
        $response = $this->actingAs($admin)
            ->post("/admin/trash-users/restore/{$tempUser->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $restored = User::find($tempUser->id);
        $this->assertNotNull($restored);
        $this->assertEquals($originalEmail, $restored->email);
        $this->assertEquals('active', $restored->status);
    }

    public function test_admin_can_permanently_delete_user(): void
    {
        $admin = User::where('user_type', 'admin')->first();
        $tempUser = User::create([
            'name' => 'To Be Permanently Deleted',
            'email' => 'permanent_del_' . time() . '@example.com',
            'password' => 'secret123',
            'user_type' => 'customer',
            'status' => 'active',
        ]);

        \App\Services\UserDeletionService::deleteUser($tempUser);

        // Admin permanent force delete with correct admin password
        $response = $this->actingAs($admin)
            ->post("/admin/trash-users/force-delete/{$tempUser->id}", [
                'password' => '12345678',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // User should not exist at all in database
        $this->assertNull(User::withTrashed()->find($tempUser->id));
    }

    public function test_admin_can_view_trash_users_page(): void
    {
        $admin = User::where('user_type', 'admin')->first();

        $response = $this->actingAs($admin)
            ->get('/admin/trash-users');

        $response->assertStatus(200);
        $response->assertSee('Trash Users Management');
    }

    public function test_admin_can_fetch_trash_users_datatable_ajax(): void
    {
        $admin = User::where('user_type', 'admin')->first();

        $response = $this->actingAs($admin)
            ->get('/admin/trash-users', ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }
}

