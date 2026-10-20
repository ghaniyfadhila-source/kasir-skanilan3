<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Devices registered by the hardware have no human label. Filament's
        // Select cannot render a null option label, so give them the device_id
        // as a fallback. Admins can rename them later.
        DB::table('devices')
            ->whereNull('name')
            ->update(['name' => DB::raw('device_id')]);
    }

    public function down(): void
    {
        // Backfill is intentionally irreversible: we cannot tell which names
        // were null before this migration.
    }
};
