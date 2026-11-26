<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'is_active',
    ];

    /**
     * The roles that belong to the user.
     */
    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Check if user has a specific role (or one of multiple roles)
     */
    public function hasRole(string|array $role): bool
    {
        // If user is super_admin or legacy is_admin without role rows, grant full access
        $roles = is_array($role) ? $role : [$role];

        // Eager-loaded in-memory check
        $userRoleNames = $this->roles->pluck('name')->toArray();

        // If no explicit role in pivot but is_admin is true, default to super_admin
        if (empty($userRoleNames) && (bool) $this->is_admin) {
            $userRoleNames = ['super_admin'];
        }

        if (in_array('super_admin', $userRoleNames, true)) {
            return true;
        }

        foreach ($roles as $r) {
            if (in_array($r, $userRoleNames, true)) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function getPrimaryRoleAttribute(): string
    {
        $role = $this->roles->first();
        if ($role) {
            return $role->display_name;
        }
        return $this->is_admin ? 'Super Administrator' : 'Staff Member';
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
