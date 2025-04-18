<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamRequest extends Model
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
        'request_date',
        'exam_type',
        'instructions',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_date' => 'date',
    ];

    /**
     * Get the medical record associated with the exam request.
     */
    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    /**
     * Get the exam results for this request.
     */
    public function results()
    {
        return $this->hasMany(ExamResult::class);
    }

    /**
     * Check if the exam has results.
     */
    public function hasResults()
    {
        return $this->results()->exists();
    }
}