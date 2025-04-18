<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'zip_code',
        'phone',
        'email',
    ];

    /**
     * Get the rooms for the clinic.
     */
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
    public function getFullAddressAttribute()
    {
        return "{$this->address}, {$this->city} - {$this->state}, {$this->zip_code}";
    }
}