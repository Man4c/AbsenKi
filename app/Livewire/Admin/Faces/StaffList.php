<?php

namespace App\Livewire\Admin\Faces;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Contracts\View\View;

class StaffList extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $staff = User::where('role', 'staff')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->withCount('faceProfiles')
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.admin.faces.staff-list', [
            'staff' => $staff
        ]);
    }
}
