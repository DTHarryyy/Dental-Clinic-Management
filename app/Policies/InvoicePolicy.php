<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::BillingView);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission(Permission::BillingView);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::BillingManage);
    }

    public function manage(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission(Permission::BillingManage);
    }
}
