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
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('day_of_week')->comment('0=Sunday, 1=Monday, ..., 6=Saturday');
            $table->time('in_time')->comment('Expected check-in time');
            $table->time('out_time')->comment('Expected check-out time');
            $table->unsignedInteger('grace_late_minutes')->default(0)->comment('Grace period for late arrival');
            $table->unsignedInteger('grace_early_minutes')->default(0)->comment('Grace period for early departure');
            $table->time('lock_in_start')->nullable()->comment('Earliest time allowed for check-in');
            $table->time('lock_in_end')->nullable()->comment('Latest time allowed for check-in');
            $table->time('lock_out_start')->nullable()->comment('Earliest time allowed for check-out');
            $table->time('lock_out_end')->nullable()->comment('Latest time allowed for check-out');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('day_of_week');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};
