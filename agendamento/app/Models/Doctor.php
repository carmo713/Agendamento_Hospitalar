<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'crm',
        'crm_state',
        'bio',
        'consultation_duration',
    ];

    /**
     * Get the user associated with the doctor.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the specialties for the doctor.
     */
    public function specialties()
    {
        return $this->belongsToMany(Specialty::class, 'doctor_specialties');
    }

    /**
     * Get the schedules for the doctor.
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Get the appointments for the doctor.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the medical records created by the doctor.
     */
    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    /**
     * Get the feedback for the doctor.
     */
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    /**
     * Get the doctor's full name.
     */
    public function getFullNameAttribute()
    {
        return $this->user->name;
    }

    /**
     * Get the doctor's average rating.
     */
    public function getAverageRatingAttribute()
    {
        return $this->feedbacks()->avg('rating') ?: 0;
    }
}