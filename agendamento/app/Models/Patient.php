<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'health_insurance',
        'health_insurance_number',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    /**
     * Get the user associated with the patient.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the appointments for the patient.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the medical records for the patient.
     */
    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    /**
     * Get the health profile for the patient.
     */
    public function healthProfile()
    {
        return $this->hasOne(HealthProfile::class);
    }

    /**
     * Get the documents for the patient.
     */
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get the payments for the patient.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the feedbacks submitted by the patient.
     */
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    /**
     * Get the patient's full name.
     */
    public function getFullNameAttribute()
    {
        return $this->user->name;
    }
}