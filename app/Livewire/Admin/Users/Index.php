<?php

namespace App\Livewire\Admin\Users;

use Livewire\Component;
use Illuminate\Contracts\View\View;

class Index extends Component
{
    public function render(): View
    {
        return view('livewire.admin.users.index');
    }
}
