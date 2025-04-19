<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    /**
     * Display the patient's profile settings.
     *
     * @return \Illuminate\View\View
     */
    public function profile()
    {
        $user = Auth::user();
        $patient = $user->patient;
        
        return view('patient.settings.profile', [
            'user' => $user,
            'patient' => $patient
        ]);
    }
    
    /**
     * Update the patient's profile information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'birth_date' => 'required|date|before_or_equal:today',
            'gender' => 'required|in:male,female,other',
            'health_insurance' => 'nullable|string|max:255',
            'health_insurance_number' => 'nullable|string|max:50',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'photo' => 'nullable|image|max:2048',
        ]);
        
        try {
            // Atualizar dados do usuário
            $user->name = $request->name;
            $user->phone = $request->phone;
            $user->birth_date = $request->birth_date;
            $user->gender = $request->gender;
            
            // Upload de foto, se fornecida
            if ($request->hasFile('photo')) {
                // Excluir foto anterior, se existir
                if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                    Storage::disk('public')->delete($user->photo);
                }
                
                $path = $request->file('photo')->store('profile_photos', 'public');
                $user->photo = $path;
            }
            
            $user->save();
            
            // Atualizar dados do paciente
            $patient = $user->patient;
            $patient->health_insurance = $request->health_insurance;
            $patient->health_insurance_number = $request->health_insurance_number;
            $patient->emergency_contact_name = $request->emergency_contact_name;
            $patient->emergency_contact_phone = $request->emergency_contact_phone;
            $patient->save();
            
            return back()->with('success', 'Perfil atualizado com sucesso!');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao atualizar o perfil. Por favor, tente novamente.')
                ->withInput();
        }
    }
    
    /**
     * Display the security settings page.
     *
     * @return \Illuminate\View\View
     */
    public function security()
    {
        return view('patient.settings.security');
    }
    
    /**
     * Update the patient's email address.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateEmail(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'current_password' => 'required|string',
        ]);
        
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'A senha atual está incorreta.')
                ->withInput();
        }
        
        try {
            $user->email = $request->email;
            $user->email_verified_at = null; // Requer nova verificação
            $user->save();
            
            $user->sendEmailVerificationNotification();
            
            return back()->with('success', 'Email atualizado com sucesso! Por favor, verifique seu email para confirmar o novo endereço.');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao atualizar o email. Por favor, tente novamente.')
                ->withInput();
        }
    }
    
    /**
     * Update the patient's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        $user = Auth::user();
        
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'A senha atual está incorreta.');
        }
        
        try {
            $user->password = Hash::make($request->password);
            $user->save();
            
            return back()->with('success', 'Senha atualizada com sucesso!');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao atualizar a senha. Por favor, tente novamente.');
        }
    }
    
    /**
     * Display the notification settings page.
     *
     * @return \Illuminate\View\View
     */
    public function notifications()
    {
        $user = Auth::user();
        $notificationSettings = $user->notification_settings ?? [
            'email_appointment_reminders' => true,
            'email_appointment_confirmations' => true,
            'email_appointment_cancellations' => true,
            'email_medical_updates' => true,
            'sms_appointment_reminders' => true,
            'browser_notifications' => false,
        ];
        
        return view('patient.settings.notifications', [
            'notificationSettings' => $notificationSettings
        ]);
    }
    
    /**
     * Update the patient's notification settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateNotifications(Request $request)
    {
        $settings = [
            'email_appointment_reminders' => $request->has('email_appointment_reminders'),
            'email_appointment_confirmations' => $request->has('email_appointment_confirmations'),
            'email_appointment_cancellations' => $request->has('email_appointment_cancellations'),
            'email_medical_updates' => $request->has('email_medical_updates'),
            'sms_appointment_reminders' => $request->has('sms_appointment_reminders'),
            'browser_notifications' => $request->has('browser_notifications'),
        ];
        
        try {
            $user = Auth::user();
            $user->notification_settings = $settings;
            $user->save();
            
            return back()->with('success', 'Preferências de notificação atualizadas com sucesso!');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao atualizar as preferências de notificação. Por favor, tente novamente.');
        }
    }
    
    /**
     * Display the privacy settings page.
     *
     * @return \Illuminate\View\View
     */
    public function privacy()
    {
        $user = Auth::user();
        $privacySettings = $user->privacy_settings ?? [
            'share_health_data_with_doctors' => true,
            'allow_research_use' => false,
            'default_feedback_anonymity' => false,
        ];
        
        return view('patient.settings.privacy', [
            'privacySettings' => $privacySettings
        ]);
    }
    
    /**
     * Update the patient's privacy settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePrivacy(Request $request)
    {
        $settings = [
            'share_health_data_with_doctors' => $request->has('share_health_data_with_doctors'),
            'allow_research_use' => $request->has('allow_research_use'),
            'default_feedback_anonymity' => $request->has('default_feedback_anonymity'),
        ];
        
        try {
            $user = Auth::user();
            $user->privacy_settings = $settings;
            $user->save();
            
            return back()->with('success', 'Configurações de privacidade atualizadas com sucesso!');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao atualizar as configurações de privacidade. Por favor, tente novamente.');
        }
    }
    
    /**
     * Display account deletion confirmation page.
     *
     * @return \Illuminate\View\View
     */
    public function deleteAccount()
    {
        return view('patient.settings.delete-account');
    }
    
    /**
     * Process account deletion request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyAccount(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);
        
        $user = Auth::user();
        
        if (!Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Senha incorreta. A exclusão da conta não foi realizada.');
        }
        
        try {
            // Verificar se existem consultas agendadas futuras
            $futureAppointments = $user->patient->appointments()
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->where('start_time', '>', now())
                ->exists();
                
            if ($futureAppointments) {
                return back()->with('error', 'Você possui consultas agendadas. Por favor, cancele todas as consultas futuras antes de excluir sua conta.');
            }
            
            // Excluir foto de perfil
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }
            
            // Aqui poderia ter lógica para anonimizar dados que precisam ser mantidos
            // ou para fazer um "soft delete" dos dados do paciente
            
            $user->delete(); // Assumindo que há cascades configurados no banco de dados
            
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('home')->with('info', 'Sua conta foi excluída com sucesso.');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Ocorreu um erro ao excluir sua conta. Por favor, contate o suporte.');
        }
    }
}