<?php

namespace App\Console\Commands;

use App\Events\TransactionCancelled;
use App\Models\Transaction;
use Illuminate\Console\Command;

class CancelExpiredTransactions extends Command
{
    protected $signature = 'transactions:cancel-expired';

    protected $description = 'Cancel expired pending transactions';

    public function handle(): int
    {
        $expiredTransactions = Transaction::where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expiredTransactions as $transaction) {
            $transaction->markCancelled();
            broadcast(new TransactionCancelled($transaction))->toOthers();
            $count++;
        }

        $this->info("Cancelled {$count} expired transaction(s).");

        return Command::SUCCESS;
    }
}
