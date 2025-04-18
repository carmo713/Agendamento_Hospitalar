<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'specialty_id',
        'start_time',
        'end_time',
        'status',
        'reason',
        'notes',
        'cancellation_reason',
        'canceled_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get the patient associated with the appointment.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the doctor associated with the appointment.
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the specialty associated with the appointment.
     */
    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }

    /**
     * Get the medical record associated with the appointment.
     */
    public function medicalRecord()
    {
        return $this->hasOne(MedicalRecord::class);
    }

    /**
     * Get the user who canceled the appointment.
     */
    public function canceledBy()
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }

    /**
     * Get the payment associated with the appointment.
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Get the feedback for the appointment.
     */
    public function feedback()
    {
        return $this->hasOne(Feedback::class);
    }

    /**
     * Get the room assigned to the appointment.
     */
    public function room()
    {
        return $this->hasOne(AppointmentRoom::class);
    }

    /**
     * Get the messages associated with the appointment.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Scope a query to only include upcoming appointments.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>', now())
                    ->where('status', '!=', 'canceled');
    }

    /**
     * Scope a query to only include past appointments.
     */
    public function scopePast($query)
    {
        return $query->where('start_time', '<', now());
    }

    /**
     * Check if appointment is cancellable.
     */
    public function isCancellable()
    {
        return in_array($this->status, ['scheduled', 'confirmed']) && 
               now()->diffInHours($this->start_time) > 24;
    }
}