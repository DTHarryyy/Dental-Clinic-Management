<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'dentist_id', 'full_name', 'contact_number', 'email',
        'requested_by_user_id',
        'appointment_date', 'appointment_time', 'preferred_date', 'preferred_time_window',
        'requested_start_at', 'requested_end_at', 'scheduling_mode', 'duration_minutes',
        'scheduled_start_at', 'scheduled_end_at', 'confirmed_at', 'priority_override_reason',
        'cancellation_reason', 'cancelled_at', 'rescheduled_appointment_id',
        'service', 'concern', 'status',
        'confirmation_email_sent_at', 'confirmation_email_message_id',
        'confirmation_email_error',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'preferred_date' => 'date',
        'requested_start_at' => 'datetime',
        'requested_end_at' => 'datetime',
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'confirmation_email_sent_at' => 'datetime',
    ];

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->roleEnum() === Role::Dentist
            ? $query->where($this->qualifyColumn('dentist_id'), $user->id)
            : $query;
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class)->select(Patient::BASIC_COLUMNS);
    }

    public function dentist()
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function dentalRecord()
    {
        return $this->hasOne(DentalRecord::class);
    }

    public function emailDeliveries()
    {
        return $this->morphMany(EmailDelivery::class, 'related');
    }

    public function serviceItems()
    {
        return $this->hasMany(AppointmentService::class)->orderBy('display_order');
    }

    public function rescheduledAppointment()
    {
        return $this->belongsTo(self::class, 'rescheduled_appointment_id');
    }

    public function changeRequests()
    {
        return $this->hasMany(AppointmentChangeRequest::class);
    }

    public function getServiceNamesAttribute(): string
    {
        // loadMissing (not relationLoaded) so this is always the real value, never a value
        // that silently depends on whether some earlier query remembered to eager-load.
        $items = $this->loadMissing('serviceItems')->serviceItems;

        return $items->isNotEmpty() ? $items->pluck('name_snapshot')->join(', ') : $this->service;
    }

    public function getTotalDurationMinutesAttribute(): int
    {
        if ($this->duration_minutes) {
            return (int) $this->duration_minutes;
        }

        $items = $this->loadMissing('serviceItems')->serviceItems;

        return $items->isNotEmpty() ? (int) $items->sum('duration_minutes_snapshot') : 30;
    }

    public function getEstimatedTotalAttribute(): float
    {
        return (float) $this->loadMissing('serviceItems')->serviceItems->sum('price_snapshot');
    }
}
