<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

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
        'role',
        'role_id',
        'is_active',
    ];

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
     * Appended attributes for role-name compatibility with middleware.
     *
     * @var list<string>
     */
    protected $appends = [
        'role',
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

    public function roleRelation(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * String role name for middleware / login redirects (admin, cashier, inventory).
     */
    public function getRoleAttribute(): ?string
    {
        if (array_key_exists('role', $this->attributes) && $this->attributes['role'] !== null) {
            return $this->attributes['role'];
        }

        if ($this->relationLoaded('roleRelation') && $this->roleRelation) {
            return $this->roleRelation->name;
        }

        if (Schema::hasColumn($this->getTable(), 'role_id') && $this->role_id) {
            return Role::query()->where('id', $this->role_id)->value('name');
        }

        return null;
    }

    public function setRoleAttribute($value): void
    {
        if (Schema::hasColumn($this->getTable(), 'role_id')) {
            $roleId = Role::query()->where('name', $value)->value('id');
            if ($roleId) {
                $this->attributes['role_id'] = $roleId;
            }

            return;
        }

        $this->attributes['role'] = $value;
    }
}
