<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'clinic_id',
        'name',
        'description',
    ];

    /**
     * Get the clinic associated with the room.
     */
    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Get the appointments associated with the room.
     */
    public function appointments()
    {
        return $this->hasMany(AppointmentRoom::class);
    }

    /**
     * Check if the room is available at a specific time.
     */
    public function isAvailable($startTime, $endTime)
    {
        return !$this->appointments()
                    ->whereHas('appointment', function ($query) use ($startTime, $endTime) {
                        $query->where(function ($q) use ($startTime, $endTime) {
                            $q->whereBetween('start_time', [$startTime, $endTime])
                              ->orWhereBetween('end_time', [$startTime, $endTime])
                              ->orWhere(function ($q) use ($startTime, $endTime) {
                                $q->where('start_time', '<=', $startTime)
                                  ->where('end_time', '>=', $endTime);
                              });
                        })->whereNotIn('status', ['canceled', 'no_show']);
                    })->exists();
    }
}
