<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // devices.kasir_id becomes the single source of truth. Backfill it from
        // the legacy users.device_id before that column is dropped.
        DB::table('users')
            ->whereNotNull('device_id')
            ->select('id', 'device_id')
            ->orderBy('id')
            ->each(function ($user) {
                DB::table('devices')
                    ->where('id', $user->device_id)
                    ->whereNull('kasir_id')
                    ->update(['kasir_id' => $user->id]);
            });

        Schema::table('devices', function (Blueprint $table) {
            // A kasir operates exactly one console. NULLs stay allowed so a
            // freshly registered console can wait for an admin to assign it.
            $table->unique('kasir_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('device_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('device_id')->nullable();
        });

        DB::table('devices')
            ->whereNotNull('kasir_id')
            ->select('id', 'kasir_id')
            ->orderBy('id')
            ->each(function ($device) {
                DB::table('users')
                    ->where('id', $device->kasir_id)
                    ->whereNull('device_id')
                    ->update(['device_id' => $device->id]);
            });

        Schema::table('devices', function (Blueprint $table) {
            $table->dropUnique(['kasir_id']);
        });
    }
};
