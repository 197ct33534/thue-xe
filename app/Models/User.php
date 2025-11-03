<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'role', 'status'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => 'string',
        'status' => 'string',
    ];

    // Role constants
    const ROLE_ADMIN = 'admin';
    const ROLE_CUSTOMER = 'customer';

    // Status
    const STATUS_ACTIVE = 'active';
    const STATUS_BANNED = 'banned';

    // Scopes
    public function scopeAdmin($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    public function scopeCustomer($query)
    {
        return $query->where('role', self::ROLE_CUSTOMER);
    }

    // Helpers
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}