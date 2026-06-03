<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('device_commands', function (Blueprint $table) {
            $table->id();
            $table->string('device_sn');
            $table->string('command'); // e.g., REBOOT, CHECK, CLEAR LOG
            $table->string('status')->default('pending'); // pending, sent, completed, error
            $table->text('response_payload')->nullable();
            $table->timestamps();

            $table->index(['device_sn', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_commands');
    }
};
