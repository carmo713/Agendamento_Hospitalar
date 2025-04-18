<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'exam_request_id',
        'result_date',
        'file_path',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'result_date' => 'date',
    ];

    /**
     * Get the exam request associated with the result.
     */
    public function examRequest()
    {
        return $this->belongsTo(ExamRequest::class);
    }

    /**
     * Get the full URL for the exam result file.
     */
    public function getUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }
}