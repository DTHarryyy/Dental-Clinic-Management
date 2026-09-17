<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id', 'amount', 'method', 'status', 'reference', 'proof_path',
        'paid_at', 'received_by', 'submitted_by', 'verified_at', 'verified_by',
        'notes', 'rejection_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
        'status' => PaymentStatus::class,
    ];

    // method is intentionally NOT enum-cast: historical rows contain the
    // literal 'Legacy payment' (backfilled by
    // 2026_08_11_000001_create_payments_table.php), which is not a
    // PaymentMethod case and would throw on hydration. Use methodEnum().
    public function methodEnum(): ?PaymentMethod
    {
        return PaymentMethod::tryFrom($this->method);
    }

    public function methodLabel(): string
    {
        return $this->methodEnum()?->label() ?? $this->method;
    }

    // Column is qualified with the table name because these scopes are also used
    // after joining tables (e.g. ClinicReportService joins patients, which also
    // has a `status` column) — an unqualified where() is ambiguous there.
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('payments.status', PaymentStatus::Verified->value);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('payments.status', PaymentStatus::Pending->value);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    public function isVerified(): bool
    {
        return $this->status === PaymentStatus::Verified;
    }

    public function hasProof(): bool
    {
        return filled($this->proof_path);
    }
}
