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
            // Offsite attendance flag
            $table->boolean('is_offsite')->default(false)->after('status');

            // Offsite location details
            $table->string('offsite_location_text', 255)->nullable()->after('is_offsite');
            $table->string('offsite_coords', 64)->nullable()->after('offsite_location_text');
            $table->text('offsite_reason')->nullable()->after('offsite_coords');

            // Evidence file path
            $table->string('evidence_path', 255)->nullable()->after('offsite_reason');

            // Admin who created this offsite entry
            $table->unsignedBigInteger('created_by_admin_id')->nullable()->after('evidence_path');

            // Foreign key constraint
            $table->foreign('created_by_admin_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes for performance
            $table->index('is_offsite');
            $table->index('created_by_admin_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['created_by_admin_id']);

            // Drop indexes
            $table->dropIndex(['is_offsite']);
            $table->dropIndex(['created_by_admin_id']);

            // Drop columns
            $table->dropColumn([
                'is_offsite',
                'offsite_location_text',
                'offsite_coords',
                'offsite_reason',
                'evidence_path',
                'created_by_admin_id'
            ]);
        });
    }
};
