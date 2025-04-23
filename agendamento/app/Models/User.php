<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'cpf',
        'birth_date',
        'gender',
        'photo',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birth_date' => 'date',
        'last_login_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    
    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Roles::class, 'user_roles');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Role Checks
     */
    
    public function hasRole($roles)
    {
        if (is_string($roles)) {
            return $this->roles()->where('name', $roles)->exists();
        }

        return $roles->intersect($this->roles)->isNotEmpty();
    }

    public function isDoctor()
    {
        return $this->hasRole('doctor') || $this->doctor()->exists();
    }

    public function isPatient()
    {
        return $this->hasRole('patient') || $this->patient()->exists();
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    /**
     * Helper Methods
     */
    
    public function getPhotoUrlAttribute()
    {
        return $this->photo ? asset('storage/'.$this->photo) : asset('images/default-avatar.png');
    }
}