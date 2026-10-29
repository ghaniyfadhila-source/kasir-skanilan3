<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('kasir');
    }

    public function view(User $user, Transaction $transaction): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('kasir') && $user->device?->id === $transaction->device_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('kasir') || $user->hasRole('admin');
    }

    public function update(User $user, Transaction $transaction): bool
    {
        // Transactions are the kasir's monitoring record: cancelling happens
        // through the POS, editing belongs to admin only.
        return $user->hasRole('admin');
    }

    public function cancel(User $user, Transaction $transaction): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('kasir') && $user->device?->id === $transaction->device_id);
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $user->hasRole('admin');
    }
}
