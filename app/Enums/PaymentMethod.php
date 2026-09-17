<?php

namespace App\Enums;

/**
 * The single source of truth for how a payment can be made.
 *
 * Backing values are the display strings that were already stored in
 * payments.method before this enum existed, so no data migration is needed.
 * Note that historical rows also contain 'Legacy payment' (backfilled by
 * 2026_08_11_000001_create_payments_table.php) and, before this case was
 * removed, 'PhilHealth' — neither is a case here, which is why
 * Payment::method is not cast to this enum.
 */
enum PaymentMethod: string
{
    case Cash = 'Cash';
    case GCash = 'GCash';
    case Maya = 'Maya';
    case Card = 'Credit/Debit Card';
    case BankTransfer = 'Bank Transfer';
    case Other = 'Other';

    public function label(): string
    {
        return $this->value;
    }

    public function icon(): string
    {
        return match ($this) {
            self::Cash => 'fa-money-bill-wave',
            self::GCash, self::Maya => 'fa-mobile-screen-button',
            self::Card => 'fa-credit-card',
            self::BankTransfer => 'fa-building-columns',
            self::Other => 'fa-ellipsis',
        };
    }

    /**
     * A transaction number is the audit trail for these, so it is mandatory.
     * Cash has none, and "Other" is settled/arranged at the counter.
     */
    public function requiresReference(): bool
    {
        return match ($this) {
            self::GCash, self::Maya, self::Card, self::BankTransfer => true,
            self::Cash, self::Other => false,
        };
    }

    /** The patient sends money elsewhere, so they must be shown where. */
    public function isRemoteTransfer(): bool
    {
        return match ($this) {
            self::GCash, self::Maya, self::BankTransfer => true,
            default => false,
        };
    }

    /** Only money the patient moves themselves can be self-declared online. */
    public function isPatientSubmittable(): bool
    {
        return $this->isRemoteTransfer();
    }

    /** Wallets get a receiving number plus a scannable QR; banks get account details. */
    public function isEWallet(): bool
    {
        return match ($this) {
            self::GCash, self::Maya => true,
            default => false,
        };
    }

    public function helpText(): string
    {
        return match ($this) {
            self::Cash => 'Please settle this balance in cash at the clinic front desk. Our staff will record it and email your receipt.',
            self::Card => 'Card payments are processed in person on the clinic terminal.',
            self::GCash, self::Maya => 'Send the amount to the clinic account below, then enter the reference number and attach your confirmation screenshot.',
            self::BankTransfer => 'Transfer the amount to the clinic bank account below, then enter the transaction reference and attach your deposit slip.',
            self::Other => 'Describe the arrangement in the notes field. Staff will confirm before this is applied.',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return array<int, self> */
    public static function patientSubmittable(): array
    {
        return array_values(array_filter(self::cases(), fn (self $method) => $method->isPatientSubmittable()));
    }

    /** @return array<int, self> Shown to patients as instructions only - no online form. */
    public static function inPersonOnly(): array
    {
        return array_values(array_filter(self::cases(), fn (self $method) => ! $method->isPatientSubmittable()));
    }
}
