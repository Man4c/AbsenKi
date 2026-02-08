<?php

namespace Database\Seeders;

use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;

class WorkScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schedules = [
            // Sunday (0) - Inactive
            [
                'day_of_week' => 0,
                'in_time' => '07:30:00',
                'out_time' => '16:00:00',
                'grace_late_minutes' => 0,
                'grace_early_minutes' => 0,
                'is_active' => false,
            ],
            // Monday (1) - Active
            [
                'day_of_week' => 1,
                'in_time' => '07:30:00',
                'out_time' => '16:00:00',
                'grace_late_minutes' => 0,
                'grace_early_minutes' => 0,
                'is_active' => true,
            ],
            // Tuesday (2) - Active
            [
                'day_of_week' => 2,
                'in_time' => '07:30:00',
                'out_time' => '16:00:00',
                'grace_late_minutes' => 0,
                'grace_early_minutes' => 0,
                'is_active' => true,
            ],
            // Wednesday (3) - Active
            [
                'day_of_week' => 3,
                'in_time' => '07:30:00',
                'out_time' => '16:00:00',
                'grace_late_minutes' => 0,
                'grace_early_minutes' => 0,
                'is_active' => true,
            ],
            // Thursday (4) - Active
            [
                'day_of_week' => 4,
                'in_time' => '07:30:00',
                'out_time' => '16:00:00',
                'grace_late_minutes' => 0,
                'grace_early_minutes' => 0,
                'is_active' => true,
            ],
            // Friday (5) - Active
            [
                'day_of_week' => 5,
                'in_time' => '07:30:00',
                'out_time' => '16:00:00',
                'grace_late_minutes' => 0,
                'grace_early_minutes' => 0,
                'is_active' => true,
            ],
            // Saturday (6) - Inactive
            [
                'day_of_week' => 6,
                'in_time' => '07:30:00',
                'out_time' => '16:00:00',
                'grace_late_minutes' => 0,
                'grace_early_minutes' => 0,
                'is_active' => false,
            ],
        ];

        foreach ($schedules as $schedule) {
            WorkSchedule::updateOrCreate(
                ['day_of_week' => $schedule['day_of_week']],
                $schedule
            );
        }

        $this->command->info('Work schedules seeded successfully!');
    }
}
