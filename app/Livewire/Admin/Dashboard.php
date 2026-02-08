<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class Dashboard extends Component
{
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        /** @var view-string $view */
        $view = 'livewire.admin.dashboard';

        return view($view);
    }
}
