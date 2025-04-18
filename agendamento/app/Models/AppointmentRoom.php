<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentRoom extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'appointment_id',
        'room_id',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'appointment_rooms';

    /**
     * Get the appointment associated with the room.
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the room associated with the appointment.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
