<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SetAdminPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_sets_the_password_of_an_admin_only(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'email' => 'owner@example.test']);
        $this->artisan('admin:set-password', ['email' => 'owner@example.test'])
            ->expectsQuestion('New password', 'Zx9!kLm2#Qw8vNb')->expectsQuestion('Type it again', 'Zx9!kLm2#Qw8vNb')
            ->assertSuccessful();
        $this->assertTrue(Hash::check('Zx9!kLm2#Qw8vNb', $admin->fresh()->password));
    }

    public function test_refuses_a_normal_user_and_a_weak_or_mismatched_password(): void
    {
        User::factory()->create(['is_admin' => false, 'email' => 'visitor@example.test']);
        $this->artisan('admin:set-password', ['email' => 'visitor@example.test'])->assertFailed();
        $admin = User::factory()->create(['is_admin' => true, 'email' => 'owner@example.test']);
        $old = $admin->password;
        $this->artisan('admin:set-password', ['email' => 'owner@example.test'])
            ->expectsQuestion('New password', 'Zx9!kLm2#Qw8vNb')->expectsQuestion('Type it again', 'different')->assertFailed();
        $this->assertSame($old, $admin->fresh()->password);
    }
}
