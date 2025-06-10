<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id'); // Remove constrained()
            $table->unsignedBigInteger('vehicle_id'); // Remove constrained()
            $table->string('start_location');
            $table->string('end_location');
            $table->string('status')->default('Scheduled'); // Scheduled, InProgress, Completed, Cancelled
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
