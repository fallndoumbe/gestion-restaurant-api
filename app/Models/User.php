<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone',
    ];

    protected $hidden = ['password'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function servedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'server_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Vérifie si l'utilisateur est un manager (admin)
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Vérifie si l'utilisateur est un serveur
     */
    public function isServer(): bool
    {
        return $this->role === 'server';
    }

    /**
     * Vérifie si l'utilisateur est un client
     */
    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    /**
     * Alias pour isManager() - certains codes peuvent utiliser isAdmin()
     */
    public function isAdmin(): bool
    {
        return $this->isManager();
    }

    /**
     * Vérifie si l'utilisateur est du personnel (manager ou serveur)
     */
    public function isStaff(): bool
    {
        return $this->isManager() || $this->isServer();
    }
}
