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
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
            $table->date('work_date')->nullable()->after('user_id');
            $table->dateTime('clock_in_at')->nullable()->after('work_date');
            $table->dateTime('clock_out_at')->nullable()->after('clock_in_at');
            $table->decimal('clock_in_latitude', 10, 7)->nullable()->after('clock_out_at');
            $table->decimal('clock_in_longitude', 10, 7)->nullable()->after('clock_in_latitude');
            $table->unsignedInteger('clock_in_distance_meters')->nullable()->after('clock_in_longitude');
            $table->boolean('clock_in_within_geofence')->default(false)->after('clock_in_distance_meters');
            $table->string('clock_in_photo_path')->nullable()->after('clock_in_within_geofence');
            $table->decimal('clock_out_latitude', 10, 7)->nullable()->after('clock_in_photo_path');
            $table->decimal('clock_out_longitude', 10, 7)->nullable()->after('clock_out_latitude');
            $table->unsignedInteger('clock_out_distance_meters')->nullable()->after('clock_out_longitude');
            $table->boolean('clock_out_within_geofence')->default(false)->after('clock_out_distance_meters');
            $table->string('clock_out_photo_path')->nullable()->after('clock_out_within_geofence');
            $table->dateTime('timesheet_start_at')->nullable()->after('clock_out_photo_path');
            $table->dateTime('timesheet_end_at')->nullable()->after('timesheet_start_at');
            $table->text('timesheet_notes')->nullable()->after('timesheet_end_at');
            $table->unique(['user_id', 'work_date']);
            $table->index(['user_id', 'work_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('attendances_user_id_work_date_unique');
            $table->dropIndex('attendances_user_id_work_date_index');
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'user_id',
                'work_date',
                'clock_in_at',
                'clock_out_at',
                'clock_in_latitude',
                'clock_in_longitude',
                'clock_in_distance_meters',
                'clock_in_within_geofence',
                'clock_in_photo_path',
                'clock_out_latitude',
                'clock_out_longitude',
                'clock_out_distance_meters',
                'clock_out_within_geofence',
                'clock_out_photo_path',
                'timesheet_start_at',
                'timesheet_end_at',
                'timesheet_notes',
            ]);
        });
    }
};
