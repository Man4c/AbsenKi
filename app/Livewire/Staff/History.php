<?php

namespace App\Livewire\Staff;

use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Illuminate\Contracts\View\View;

class History extends Component
{
    public function render(): View
    {
        // Hapus query $attendances disini.

        // Count total attendance this month (TETAP DISINI untuk Header)
        $totalThisMonth = Attendance::where('user_id', Auth::id())
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        return view('livewire.staff.history', [
            'totalThisMonth' => $totalThisMonth
        ]);
    }
}
