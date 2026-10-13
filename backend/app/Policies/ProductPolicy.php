<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('kasir') || $user->hasRole('admin');
    }

    public function view(User $user, Product $product): bool
    {
        if ($user->hasRole('admin')) return true;
        return $user->hasRole('kasir') && $user->id === $product->kasir_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('kasir') || $user->hasRole('admin');
    }

    public function update(User $user, Product $product): bool
    {
        if ($user->hasRole('admin')) return true;
        return $user->hasRole('kasir') && $user->id === $product->kasir_id;
    }

    public function delete(User $user, Product $product): bool
    {
        if ($user->hasRole('admin')) return true;
        return $user->hasRole('kasir') && $user->id === $product->kasir_id;
    }
}