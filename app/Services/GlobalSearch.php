<?php

namespace App\Services;

use App\Enums\Permission;
use App\Models\Appointment;
use App\Models\DentalRecord;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GlobalSearch
{
    public function search(string $term, User $user): array
    {
        $groups = [
            $this->group('patients', 'Patients', $this->patients($term)),
            $this->group('appointments', 'Appointments', $this->appointments($term, $user)),
        ];

        if ($user->hasPermission(Permission::RecordsView)) {
            $groups[] = $this->group('records', 'Dental Records', $this->records($term));
        }

        if ($user->hasPermission(Permission::BillingView)) {
            $groups[] = $this->group('billing', 'Billing', $this->invoices($term));
        }

        return [
            'query' => $term,
            'groups' => $groups,
            'total' => collect($groups)->sum(fn (array $group) => count($group['results'])),
        ];
    }

    private function patients(string $term): Collection
    {
        $numeric = $this->numericId($term);
        $results = Patient::query()
            ->select(['id', 'first_name', 'last_name', 'email', 'mobile', 'status'])
            ->where(function (Builder $query) use ($term, $numeric): void {
                $this->textSearch($query, ['first_name', 'last_name', 'email', 'mobile'], $term, "first_name || ' ' || last_name");
                if ($numeric !== null) {
                    $query->orWhere('id', $numeric);
                }
            })->limit(20)->get();

        return $this->rank($results, $term, fn (Patient $patient) => [$patient->name, $patient->email, $patient->mobile, (string) $patient->id])
            ->map(fn (Patient $patient) => [
                'id' => 'patient-'.$patient->id,
                'title' => $patient->name,
                'subtitle' => collect([$patient->mobile, $patient->email])->filter()->join(' · ') ?: 'No contact information',
                'meta' => 'ID #'.str_pad((string) $patient->id, 4, '0', STR_PAD_LEFT).' · '.ucfirst($patient->status),
                'url' => route('patients.index', ['view' => $patient->id]),
            ]);
    }

    private function appointments(string $term, User $user): Collection
    {
        $numeric = $this->numericId($term);
        $results = Appointment::query()->visibleTo($user)
            ->select(['id', 'full_name', 'email', 'contact_number', 'service', 'appointment_date', 'appointment_time', 'status'])
            ->where(function (Builder $query) use ($term, $numeric): void {
                $this->textSearch($query, ['full_name', 'email', 'contact_number', 'service'], $term);
                $query->orWhereHas('patient', fn (Builder $patient) => $this->textSearch($patient, ['first_name', 'last_name'], $term, "first_name || ' ' || last_name"));
                $query->orWhereHas('serviceItems', fn (Builder $service) => $this->textSearch($service, ['name_snapshot'], $term));
                if ($numeric !== null) {
                    $query->orWhere('id', $numeric);
                }
            })->limit(20)->get();

        return $this->rank(
            $results,
            $term,
            fn (Appointment $appointment) => [$appointment->full_name, $appointment->email, $appointment->contact_number, $appointment->service, (string) $appointment->id],
            fn (Appointment $appointment) => abs(now()->diffInDays($appointment->appointment_date, false))
        )
            ->map(fn (Appointment $appointment) => [
                'id' => 'appointment-'.$appointment->id,
                'title' => $appointment->full_name,
                'subtitle' => $appointment->service.' · '.$appointment->appointment_date->format('M j, Y').($appointment->appointment_time ? ' '.$appointment->appointment_time : ''),
                'meta' => ucfirst($appointment->status),
                'url' => route('appointments.index', ['appointment' => $appointment->id]),
            ]);
    }

    private function records(string $term): Collection
    {
        $numeric = $this->numericId($term);
        $results = DentalRecord::query()
            ->select(['dental_records.id', 'dental_records.procedure', 'dental_records.tooth_area', 'dental_records.treatment_date', 'patients.first_name', 'patients.last_name', 'users.name as dentist_name'])
            ->join('patients', 'patients.id', '=', 'dental_records.patient_id')
            ->leftJoin('users', 'users.id', '=', 'dental_records.dentist_id')
            ->where(function (Builder $query) use ($term, $numeric): void {
                $this->textSearch($query, ['dental_records.procedure', 'dental_records.tooth_area', 'patients.first_name', 'patients.last_name', 'users.name'], $term, "patients.first_name || ' ' || patients.last_name");
                if ($numeric !== null) {
                    $query->orWhere('dental_records.id', $numeric);
                }
            })->latest('dental_records.treatment_date')->limit(20)->get();

        return $this->rank($results, $term, fn (DentalRecord $record) => [trim($record->first_name.' '.$record->last_name), $record->procedure, $record->tooth_area, $record->dentist_name, (string) $record->id], fn (DentalRecord $record) => -$record->treatment_date->timestamp)
            ->map(fn (DentalRecord $record) => [
                'id' => 'record-'.$record->id,
                'title' => trim($record->first_name.' '.$record->last_name).' · '.$record->procedure,
                'subtitle' => $record->treatment_date->format('M j, Y').($record->dentist_name ? ' · '.$record->dentist_name : ''),
                'meta' => 'Record #'.$record->id,
                'url' => route('records.show', $record->id),
            ]);
    }

    private function invoices(string $term): Collection
    {
        $numeric = $this->numericId($term);
        $results = Invoice::query()
            ->select(['invoices.id', 'invoices.invoice_date', 'invoices.due_date', 'invoices.total', 'invoices.payment_status', 'patients.first_name', 'patients.last_name', 'patients.email'])
            ->join('patients', 'patients.id', '=', 'invoices.patient_id')
            ->where(function (Builder $query) use ($term, $numeric): void {
                $this->textSearch($query, ['patients.first_name', 'patients.last_name', 'patients.email'], $term, "patients.first_name || ' ' || patients.last_name");
                $query->orWhereHas('items', fn (Builder $item) => $this->textSearch($item, ['description'], $term));
                if ($numeric !== null) {
                    $query->orWhere('invoices.id', $numeric);
                }
            })->latest('invoices.invoice_date')->limit(20)->get();

        return $this->rank($results, $term, fn (Invoice $invoice) => [$invoice->invoice_number, trim($invoice->first_name.' '.$invoice->last_name), $invoice->email, (string) $invoice->id], fn (Invoice $invoice) => -$invoice->invoice_date->timestamp)
            ->map(fn (Invoice $invoice) => [
                'id' => 'invoice-'.$invoice->id,
                'title' => $invoice->invoice_number.' · '.trim($invoice->first_name.' '.$invoice->last_name),
                'subtitle' => '₱'.number_format((float) $invoice->total, 2).' · '.$invoice->invoice_date->format('M j, Y'),
                'meta' => $invoice->display_status,
                'url' => route('billing.index', ['view' => $invoice->id]),
            ]);
    }

    private function textSearch(Builder $query, array $columns, string $term, ?string $combined = null): void
    {
        $pattern = '%'.$this->escapeLike(mb_strtolower($term)).'%';
        $query->where(function (Builder $search) use ($columns, $combined, $pattern): void {
            foreach ($columns as $column) {
                $search->orWhereRaw("LOWER(COALESCE({$column}, '')) LIKE ? ESCAPE '\\'", [$pattern]);
            }
            if ($combined) {
                $search->orWhereRaw("LOWER({$combined}) LIKE ? ESCAPE '\\'", [$pattern]);
            }
        });
    }

    private function rank(Collection $results, string $term, callable $values, ?callable $tieBreaker = null): Collection
    {
        $needle = mb_strtolower($term);

        return $results->sortBy(function ($result) use ($needle, $values, $tieBreaker): array {
            $haystacks = collect($values($result))->filter()->map(fn ($value) => mb_strtolower((string) $value));
            $score = $haystacks->contains(fn ($value) => $value === $needle) ? 0 : ($haystacks->contains(fn ($value) => str_starts_with($value, $needle)) ? 1 : 2);

            return [$score, $tieBreaker ? $tieBreaker($result) : $haystacks->first()];
        })->take(5)->values();
    }

    private function numericId(string $term): ?int
    {
        $normalized = preg_replace('/^inv-/i', '', trim($term));

        return ctype_digit($normalized) ? (int) $normalized : null;
    }

    private function escapeLike(string $term): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
    }

    private function group(string $type, string $label, Collection $results): array
    {
        return ['type' => $type, 'label' => $label, 'results' => $results->all()];
    }
}
