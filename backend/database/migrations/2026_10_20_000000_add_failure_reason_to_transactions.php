<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // The console abandons a transaction after one rejected tap, so the
            // POS needs to stop polling and show the same reason the LCD shows.
            $table->string('failure_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('failure_reason');
        });
    }
};
