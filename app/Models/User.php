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
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function servedOrders()
    {
        return $this->hasMany(Order::class, 'server_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    
    public function isManager()
    {
        return $this->role === 'manager';
    }

    public function isServer()
    {
        return $this->role === 'server';
    }

    public function isClient()
    {
        return $this->role === 'client';
    }
}