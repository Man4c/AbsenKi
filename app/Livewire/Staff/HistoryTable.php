<?php

namespace App\Livewire\Staff;

use App\Models\Attendance;
use App\Models\Leave;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Illuminate\Contracts\View\View;

class HistoryTable extends Component
{
    public function placeholder(): View
    {
        return view('livewire.placeholders.history-table-skeleton');
    }

    public function render(): View
    {
        // Get last 30 attendance records
        $attendances = Attendance::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->take(30)
            ->get()
            ->map(function ($attendance) {
                return [
                    'type' => 'attendance',
                    'date' => $attendance->created_at,
                    'data' => $attendance,
                ];
            });

        // Get all leaves (approved)
        $leaves = Leave::where('user_id', Auth::id())
            ->where('status', 'approved')
            ->orderBy('start_date', 'desc')
            ->take(30)
            ->get()
            ->map(function ($leave) {
                return [
                    'type' => 'leave',
                    'date' => $leave->start_date,
                    'data' => $leave,
                ];
            });

        // Merge and sort by date
        $records = $attendances->concat($leaves)
            ->sortByDesc('date')
            ->take(30)
            ->values();

        return view('livewire.staff.history-table', [
            'records' => $records
        ]);
    }
}
