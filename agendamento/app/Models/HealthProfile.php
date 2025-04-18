<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_id',
        'blood_type',
        'height',
        'weight',
        'allergies',
        'chronic_diseases',
        'current_medications',
        'family_history',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
    ];

    /**
     * Get the patient associated with the health profile.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Calculate BMI (Body Mass Index).
     */
    public function getBmiAttribute()
    {
        if ($this->height && $this->weight) {
            $heightInMeters = $this->height / 100;
            return round($this->weight / ($heightInMeters * $heightInMeters), 2);
        }
        return null;
    }

    /**
     * Get BMI classification.
     */
    public function getBmiClassificationAttribute()
    {
        $bmi = $this->getBmiAttribute();
        if ($bmi === null) {
            return null;
        }

        if ($bmi < 18.5) {
            return 'Abaixo do peso';
        } elseif ($bmi < 25) {
            return 'Peso normal';
        } elseif ($bmi < 30) {
            return 'Sobrepeso';
        } elseif ($bmi < 35) {
            return 'Obesidade Grau I';
        } elseif ($bmi < 40) {
            return 'Obesidade Grau II';
        } else {
            return 'Obesidade Grau III';
        }
    }
}