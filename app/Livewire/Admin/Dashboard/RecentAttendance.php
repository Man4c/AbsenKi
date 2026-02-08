<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Attendance;
use Livewire\Component;
use Illuminate\Contracts\View\View;

class RecentAttendance extends Component
{
    public function placeholder(): View
    {
        return view('livewire.placeholders.recent-attendance-skeleton');
    }

    public function render(): View
    {
        // Get 5 latest attendance records
        $recentRecords = Attendance::with('user')
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('livewire.admin.dashboard.recent-attendance', [
            'recentRecords' => $recentRecords
        ]);
    }
}
