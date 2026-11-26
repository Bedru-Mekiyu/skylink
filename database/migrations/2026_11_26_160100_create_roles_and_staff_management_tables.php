<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Roles table
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('display_name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 2. Role User pivot table
        if (!Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'role_id']);
            });
        }

        // 3. Admin Audit Logs table
        if (!Schema::hasTable('admin_audit_logs')) {
            Schema::create('admin_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action'); // e.g. 'catalog.duplicate', 'pricing.update', 'staff.role_change'
                $table->string('target_type')->nullable(); // e.g. 'Product', 'ItemOption', 'User'
                $table->unsignedBigInteger('target_id')->nullable();
                $table->json('changes')->nullable(); // before and after payload
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // Seed default 4 roles
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Administrator',
                'description' => 'Full administrative access to all system functions, settings, staff, and finance.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'content_editor',
                'display_name' => 'Content Editor',
                'description' => 'Manage marketing content, blog articles, videos, and catalog details (names, descriptions, highlights).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'sales',
                'display_name' => 'Sales Representative',
                'description' => 'Manage incoming inquiries, quote requests, and view customer demand pipelines.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'finance',
                'display_name' => 'Finance Officer',
                'description' => 'View orders, payments, verify Chapa transactions, and export reconciliation reports.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($roles as $r) {
            DB::table('roles')->updateOrInsert(['name' => $r['name']], $r);
        }

        // Assign existing admin users the super_admin role
        $superAdminRole = DB::table('roles')->where('name', 'super_admin')->first();
        if ($superAdminRole && Schema::hasTable('users') && Schema::hasColumn('users', 'is_admin')) {
            $admins = DB::table('users')->where('is_admin', true)->get();
            foreach ($admins as $admin) {
                DB::table('role_user')->updateOrInsert(
                    ['user_id' => $admin->id, 'role_id' => $superAdminRole->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
