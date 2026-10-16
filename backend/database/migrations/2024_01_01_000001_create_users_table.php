<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            // Kasir's device, as devices.id. The foreign key itself cannot be
            // declared here: this migration runs before create_devices_table,
            // and SQLite tolerates the forward reference while PostgreSQL
            // rejects it with `relation "devices" does not exist`. The device
            // side already points back through devices.kasir_id.
            $table->foreignId('device_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
