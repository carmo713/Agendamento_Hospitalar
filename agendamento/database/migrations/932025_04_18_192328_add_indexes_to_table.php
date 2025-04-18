<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['doctor_id', 'start_time'], 'idx_appointments_doctor_date');
            $table->index(['patient_id', 'start_time'], 'idx_appointments_patient_date');
            $table->index('status', 'idx_appointments_status');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->index(['doctor_id', 'day_of_week'], 'idx_schedules_doctor_day');
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->index('patient_id', 'idx_medical_records_patient');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'read_at'], 'idx_notifications_user_read');
        });

        // Corrigido: índice simples para mensagens (MySQL não suporta LEAST/GREATEST em índices)
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['sender_id', 'receiver_id', 'created_at'], 'idx_messages_conversation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_doctor_date');
            $table->dropIndex('idx_appointments_patient_date');
            $table->dropIndex('idx_appointments_status');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('idx_schedules_doctor_day');
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropIndex('idx_medical_records_patient');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_user_read');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('idx_messages_conversation');
        });
    }
};