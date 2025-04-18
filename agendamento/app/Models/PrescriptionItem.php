<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescriptionItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'prescription_id',
        'medication',
        'dosage',
        'frequency',
        'period',
        'instructions',
    ];

    /**
     * Get the prescription associated with the item.
     */
    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }
}