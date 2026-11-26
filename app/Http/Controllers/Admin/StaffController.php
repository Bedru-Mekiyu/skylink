<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with('roles');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleFilter = $request->input('role')) {
            $query->whereHas('roles', function ($q) use ($roleFilter) {
                $q->where('name', $roleFilter);
            });
        }

        $staffMembers = $query->orderBy('name')->paginate(15);
        $roles = Role::all();

        return view('admin.staff.index', compact('staffMembers', 'roles'));
    }

    public function create(): View
    {
        $roles = Role::all();
        return view('admin.staff.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => true,
            'is_active' => true,
        ]);

        $user->roles()->sync([$validated['role_id']]);

        $role = Role::find($validated['role_id']);
        AdminAuditLog::record('staff.create', 'User', $user->id, [
            'created_user' => $user->email,
            'role' => $role?->name,
        ]);

        return redirect()->route('admin.staff.index')->with('success', "Staff account for '{$user->name}' created successfully.");
    }

    public function edit(User $staff): View
    {
        $roles = Role::all();
        $currentRoleId = $staff->roles->first()?->id;

        return view('admin.staff.edit', compact('staff', 'roles', 'currentRoleId'));
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($staff->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $beforeRole = $staff->roles->first()?->name;

        $staff->name = $validated['name'];
        $staff->email = $validated['email'];
        if (!empty($validated['password'])) {
            $staff->password = Hash::make($validated['password']);
        }
        $staff->save();

        $staff->roles()->sync([$validated['role_id']]);

        $afterRole = Role::find($validated['role_id'])?->name;

        AdminAuditLog::record('staff.update', 'User', $staff->id, [
            'user' => $staff->email,
            'before_role' => $beforeRole,
            'after_role' => $afterRole,
            'password_changed' => !empty($validated['password']),
        ]);

        return redirect()->route('admin.staff.index')->with('success', "Staff account '{$staff->name}' updated successfully.");
    }

    public function toggleActive(User $staff): RedirectResponse
    {
        if ($staff->id === Auth::id()) {
            return back()->with('error', 'You cannot deactivate your own staff account.');
        }

        // If deactivating, ensure at least one active super_admin remains
        if ($staff->is_active && $staff->hasRole('super_admin')) {
            $activeSuperAdminCount = User::where('is_active', true)
                ->whereHas('roles', fn($q) => $q->where('name', 'super_admin'))
                ->count();

            if ($activeSuperAdminCount <= 1) {
                return back()->with('error', 'Cannot deactivate the only active Super Administrator account.');
            }
        }

        $newStatus = ! $staff->is_active;
        $staff->is_active = $newStatus;
        $staff->save();

        $action = $newStatus ? 'staff.activated' : 'staff.deactivated';
        AdminAuditLog::record($action, 'User', $staff->id, [
            'user' => $staff->email,
            'is_active' => $newStatus,
        ]);

        $label = $newStatus ? 'reactivated' : 'deactivated';
        return redirect()->route('admin.staff.index')->with('success', "Staff account '{$staff->email}' has been {$label} successfully.");
    }

    public function destroy(User $staff): RedirectResponse
    {
        // Deactivate instead of delete, preserving staff audit trail and roles
        if ($staff->id === Auth::id()) {
            return back()->with('error', 'You cannot deactivate your own staff account.');
        }

        if ($staff->is_active && $staff->hasRole('super_admin')) {
            $activeSuperAdminCount = User::where('is_active', true)
                ->whereHas('roles', fn($q) => $q->where('name', 'super_admin'))
                ->count();

            if ($activeSuperAdminCount <= 1) {
                return back()->with('error', 'Cannot deactivate the only active Super Administrator account.');
            }
        }

        $staff->is_active = false;
        $staff->save();

        AdminAuditLog::record('staff.deactivated', 'User', $staff->id, [
            'user' => $staff->email,
            'is_active' => false,
        ]);

        return redirect()->route('admin.staff.index')->with('success', "Staff account '{$staff->email}' has been deactivated successfully.");
    }
}
