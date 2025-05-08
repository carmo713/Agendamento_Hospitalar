<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Schedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'doctor_id',
        'day_of_week',
        'start_time',
        'end_time',
        'recurrence',
        'start_date',
        'end_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'day_of_week' => 'integer',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the doctor that owns the schedule.
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the exceptions for this schedule.
     */
    public function exceptions()
    {
        return $this->hasMany(ScheduleException::class);
    }

    /**
     * Get the appointments for this schedule.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Check if the schedule is active on a specific date.
     *
     * @param \Carbon\Carbon|string $date
     * @return bool
     */
    public function isActiveOn($date)
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        
        // Verificar se a data está dentro do período de validade do horário
        if ($date->lt($this->start_date)) {
            return false;
        }
        
        if ($this->end_date && $date->gt($this->end_date)) {
            return false;
        }
        
        // Verificar se o dia da semana corresponde
        if ($date->dayOfWeek !== $this->day_of_week) {
            return false;
        }
        
        // Verificar recorrência
        if ($this->recurrence === 'weekly') {
            return true;
        } elseif ($this->recurrence === 'biweekly') {
            $weeksDiff = $date->diffInWeeks($this->start_date);
            return $weeksDiff % 2 === 0;
        } elseif ($this->recurrence === 'monthly') {
            // Verifica se é o mesmo dia do mês
            // Por exemplo, se start_date é 15/04, então será ativo em 15/05, 15/06, etc.
            return $date->day === Carbon::parse($this->start_date)->day;
        }
        
        return false;
    }

    /**
     * Check if the schedule has any exceptions on a specific date.
     *
     * @param \Carbon\Carbon|string $date
     * @return \App\Models\ScheduleException|null
     */
    public function getExceptionForDate($date)
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        
        return $this->exceptions()
            ->where('exception_date', $date->format('Y-m-d'))
            ->first();
    }

    /**
     * Get all dates for this schedule within a date range.
     *
     * @param \Carbon\Carbon|string $startDate
     * @param \Carbon\Carbon|string $endDate
     * @return array
     */
    public function getDatesInRange($startDate, $endDate)
    {
        $startDate = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $endDate = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
        
        // Garantir que não busque datas anteriores ao início da programação
        $startDate = $startDate->lt($this->start_date) ? $this->start_date : $startDate;
        
        // Garantir que não busque datas após o fim da programação
        if ($this->end_date && $endDate->gt($this->end_date)) {
            $endDate = $this->end_date;
        }
        
        // Array para armazenar as datas válidas
        $validDates = [];
        
        // Criar um período de datas
        $period = CarbonPeriod::create($startDate, $endDate);
        
        // Iterar sobre cada data
        foreach ($period as $date) {
            if ($this->isActiveOn($date)) {
                $validDates[] = $date;
            }
        }
        
        return $validDates;
    }

    /**
     * Get the day of the week as a text.
     */
    public function getDayNameAttribute()
    {
        $days = [
            0 => 'Domingo',
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
        ];

        return $days[$this->day_of_week] ?? 'Desconhecido';
    }

    /**
     * Get the recurrence as a text.
     */
    public function getRecurrenceNameAttribute()
    {
        $recurrences = [
            'weekly' => 'Semanal',
            'biweekly' => 'Quinzenal',
            'monthly' => 'Mensal',
        ];

        return $recurrences[$this->recurrence] ?? 'Desconhecido';
    }
}