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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['in', 'out']); // in = check in, out = check out
            $table->decimal('lat', 10, 7); // Latitude
            $table->decimal('lng', 10, 7); // Longitude
            $table->boolean('geo_ok')->default(false); // Is location inside geofence?
            $table->decimal('face_score', 5, 2)->nullable(); // Face match confidence score (0-100)
            $table->enum('status', ['success', 'fail'])->default('success');
            $table->text('device_info')->nullable(); // User agent, browser info
            $table->timestamps();

            // Indexes for faster queries
            $table->index('user_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
