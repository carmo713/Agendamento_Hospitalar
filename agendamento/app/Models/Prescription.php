<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'medical_record_id',
        'code',
        'issue_date',
        'expiration_date',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'issue_date' => 'date',
        'expiration_date' => 'date',
    ];

    /**
     * Get the medical record associated with the prescription.
     */
    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    /**
     * Get the items for the prescription.
     */
    public function items()
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    /**
     * Check if the prescription is expired.
     */
    public function isExpired()
    {
        return now()->greaterThan($this->expiration_date);
    }
}