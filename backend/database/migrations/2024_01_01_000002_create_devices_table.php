<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique()->comment('Unique identifier from ESP8266 (MAC/UUID)');
            $table->string('name')->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->text('api_token')->nullable()->comment('Device API token for authentication');
            $table->string('firmware_version')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('metadata')->nullable()->comment('RSSI, heap, uptime, etc.');
            $table->timestamp('last_seen_at')->nullable();
            $table->foreignId('kasir_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};