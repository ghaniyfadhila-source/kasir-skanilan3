<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * transactions.rfid_card_id must be nullable: the kasir creates the pending
 * transaction before a card is tapped, and the card is only attached later by
 * the tap flow. Some existing databases were created while the column was NOT
 * NULL, which made POST /api/transactions fail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('rfid_card_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('rfid_card_id')->nullable(false)->change();
        });
    }
};
