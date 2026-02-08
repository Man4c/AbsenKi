<?php

namespace App\Livewire\Admin\Attendance;

use App\Models\Attendance;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewEvidence extends Component
{
    public bool $isOpen = false;
    public ?Attendance $attendance = null;

    /** @var array<string, string> */
    protected $listeners = ['openEvidenceModal' => 'openModal'];

    public function openModal(int $attendanceId): void
    {
        $this->attendance = Attendance::with('user')->find($attendanceId);

        if (!$this->attendance || !$this->attendance->has_evidence) {
            session()->flash('error', 'Bukti tidak ditemukan.');
            return;
        }

        $this->isOpen = true;
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
        $this->attendance = null;
    }

    public function downloadEvidence(): ?StreamedResponse
    {
        if (!$this->attendance || !$this->attendance->evidence_path) {
            return null;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        return $disk->download(
            $this->attendance->evidence_path,
            basename($this->attendance->evidence_path)
        );
    }

    public function render(): View
    {
        return view('livewire.admin.attendance.view-evidence');
    }
}
