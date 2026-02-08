<?php

namespace App\Livewire\Admin\Settings;

use App\Models\WorkSchedule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Contracts\View\View;

#[Layout('components.layouts.app', ['title' => 'Jadwal Kerja'])]
class WorkScheduleSettings extends Component
{
    /** @var array<int, mixed> */
    public array $schedules = [];
    public ?int $editingDay = null;

    // Form fields
    public ?int $dayOfWeek = null;
    public string $inTime = '';
    public string $outTime = '';
    public int $graceLateMinutes = 0;
    public int $graceEarlyMinutes = 0;
    public ?string $lockInStart = null;
    public ?string $lockInEnd = null;
    public ?string $lockOutStart = null;
    public ?string $lockOutEnd = null;
    public bool $isActive = true;

    /** @var array<string, string|array<int, string>> */
    protected array $rules = [
        'inTime' => 'required|date_format:H:i',
        'outTime' => 'required|date_format:H:i|after:inTime',
        'graceLateMinutes' => 'required|integer|min:0|max:60',
        'graceEarlyMinutes' => 'required|integer|min:0|max:60',
        'lockInStart' => 'nullable|date_format:H:i',
        'lockInEnd' => 'nullable|date_format:H:i|after:lockInStart',
        'lockOutStart' => 'nullable|date_format:H:i',
        'lockOutEnd' => 'nullable|date_format:H:i|after:lockOutStart',
        'isActive' => 'required|boolean',
    ];

    /** @var array<string, string> */
    protected array $messages = [
        'inTime.required' => 'Jam masuk harus diisi',
        'outTime.required' => 'Jam pulang harus diisi',
        'outTime.after' => 'Jam pulang harus setelah jam masuk',
        'lockInEnd.after' => 'Batas akhir masuk harus setelah batas awal',
        'lockOutEnd.after' => 'Batas akhir pulang harus setelah batas awal',
    ];

    public function mount(): void
    {
        $this->loadSchedules();
    }

    public function loadSchedules(): void
    {
        /** @var array<int, array<string, mixed>> $schedules */
        $schedules = WorkSchedule::orderBy('day_of_week')->get()->values()->toArray();
        $this->schedules = $schedules;
    }

    public function editDay(int $dayOfWeek): void
    {
        $schedule = WorkSchedule::where('day_of_week', $dayOfWeek)->first();

        if ($schedule) {
            $this->editingDay = $dayOfWeek;
            $this->dayOfWeek = $schedule->day_of_week;
            $this->inTime = substr($schedule->in_time, 0, 5); // HH:MM
            $this->outTime = substr($schedule->out_time, 0, 5);
            $this->graceLateMinutes = $schedule->grace_late_minutes;
            $this->graceEarlyMinutes = $schedule->grace_early_minutes;
            $this->lockInStart = $schedule->lock_in_start ? substr($schedule->lock_in_start, 0, 5) : null;
            $this->lockInEnd = $schedule->lock_in_end ? substr($schedule->lock_in_end, 0, 5) : null;
            $this->lockOutStart = $schedule->lock_out_start ? substr($schedule->lock_out_start, 0, 5) : null;
            $this->lockOutEnd = $schedule->lock_out_end ? substr($schedule->lock_out_end, 0, 5) : null;
            $this->isActive = $schedule->is_active;
        }
    }

    public function save(): void
    {
        $this->validate();

        WorkSchedule::updateOrCreate(
            ['day_of_week' => $this->dayOfWeek],
            [
                'in_time' => $this->inTime . ':00',
                'out_time' => $this->outTime . ':00',
                'grace_late_minutes' => $this->graceLateMinutes,
                'grace_early_minutes' => $this->graceEarlyMinutes,
                'lock_in_start' => $this->lockInStart ? $this->lockInStart . ':00' : null,
                'lock_in_end' => $this->lockInEnd ? $this->lockInEnd . ':00' : null,
                'lock_out_start' => $this->lockOutStart ? $this->lockOutStart . ':00' : null,
                'lock_out_end' => $this->lockOutEnd ? $this->lockOutEnd . ':00' : null,
                'is_active' => $this->isActive,
            ]
        );

        session()->flash('message', 'Jadwal berhasil diperbarui.');
        $this->loadSchedules();
        $this->cancelEdit();
    }

    public function toggleActive(int $dayOfWeek): void
    {
        $schedule = WorkSchedule::where('day_of_week', $dayOfWeek)->first();

        if ($schedule) {
            $schedule->update(['is_active' => !$schedule->is_active]);
            $this->loadSchedules();
            session()->flash('message', 'Status hari kerja berhasil diubah.');
        }
    }

    public function cancelEdit(): void
    {
        $this->editingDay = null;
        $this->reset([
            'dayOfWeek',
            'inTime',
            'outTime',
            'graceLateMinutes',
            'graceEarlyMinutes',
            'lockInStart',
            'lockInEnd',
            'lockOutStart',
            'lockOutEnd',
            'isActive'
        ]);
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.settings.work-schedule-settings');
    }
}
