<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'agent_code',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];

    // -----------------------------------------------------------------
    // Helpers de rôle
    // -----------------------------------------------------------------

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['manager', 'admin'], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
