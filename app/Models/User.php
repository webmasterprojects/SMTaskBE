<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'role_id', 'status', 'phone_number', 'permissions', 'preferences',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'permissions'       => 'array',
        'preferences'       => 'array',
        'password'          => 'hashed',
    ];

    private static array $permissionAliases = [
        'time.start'  => 'task.time.start',
        'time.delete' => 'task.time.end',
        'time.view'   => 'report.time.view',
    ];

    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'admin') return true;
        $perms = $this->permissions ?? [];
        if (in_array($permission, $perms)) return true;
        $alias = self::$permissionAliases[$permission] ?? null;
        return $alias && in_array($alias, $perms);
    }

    public function technician(): HasOne
    {
        return $this->hasOne(Technician::class);
    }

    public function linkedRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
