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
        Schema::table('attendances', function (Blueprint $table) {
            // evidence_path already exists from previous migration
            // Adding metadata columns
            $table->string('evidence_mime', 100)->nullable()->after('evidence_path');
            $table->unsignedInteger('evidence_size')->nullable()->after('evidence_mime');
            $table->text('evidence_note')->nullable()->after('evidence_size');
            $table->timestamp('evidence_uploaded_at')->nullable()->after('evidence_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'evidence_mime',
                'evidence_size',
                'evidence_note',
                'evidence_uploaded_at',
            ]);
        });
    }
};
