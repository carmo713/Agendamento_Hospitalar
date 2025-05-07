<?php
// filepath: /home/carmo/Documentos/trabalhofinal_agendamentohospitalar/agendamento/app/Policies/AppointmentPolicy.php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppointmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the appointment.
     */
    public function view(User $user, Appointment $appointment)
    {
        // Patient can only view their own appointments
        if ($user->hasRole('patient')) {
            return $user->patient && $appointment->patient_id === $user->patient->id;
        }
        
        // Doctor can only view appointments where they are the doctor
        if ($user->hasRole('doctor')) {
            return $user->doctor && $appointment->doctor_id === $user->doctor->id;
        }
        
        // Admin can view all appointments
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the appointment.
     */
    public function update(User $user, Appointment $appointment)
    {
        // Patients can only update their own appointments if they are scheduled or confirmed
        if ($user->hasRole('patient')) {
            return $user->patient && 
                   $appointment->patient_id === $user->patient->id &&
                   in_array($appointment->status, ['scheduled', 'confirmed']);
        }
        
        // Admins and doctors have different update policies that would be defined elsewhere
        return $user->hasRole('admin');
    }
}