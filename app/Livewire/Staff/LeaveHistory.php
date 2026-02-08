<?php

namespace App\Livewire\Staff;

use App\Models\Leave;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class LeaveHistory extends Component
{
    use WithPagination;

    public function render(): \Illuminate\Contracts\View\View
    {
        $leaves = Leave::where('user_id', Auth::id())
            ->orderBy('start_date', 'desc')
            ->paginate(10);

        return view('livewire.staff.leave-history', [
            'leaves' => $leaves,
        ]);
    }
}
