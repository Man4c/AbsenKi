<?php

namespace App\Livewire\Admin\Reports;

use App\Models\Attendance;
use Livewire\Component;
use Livewire\Attributes\Reactive; // PENTING: Untuk sinkronisasi filter
use Livewire\WithPagination;
use Illuminate\Contracts\View\View;

class ReportTable extends Component
{
    use WithPagination;

    // Terima data filter dari Parent
    #[Reactive] public string $staffId = 'all';
    #[Reactive] public string $startDate = '';
    #[Reactive] public string $endDate = '';
    #[Reactive] public string $type = 'all';
    #[Reactive] public string $locationType = 'all';
    #[Reactive] public string $evidenceFilter = 'all';
    #[Reactive] public string $statusFilter = 'all';

    // Placeholder Skeleton
    public function placeholder(): View
    {
        return view('livewire.placeholders.report-table-skeleton');
    }

    public function updated(string $property): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        // Copy Query Logic kamu ke sini
        $query = Attendance::with(['user', 'createdByAdmin'])
            ->whereBetween('created_at', [
                $this->startDate . ' 00:00:00',
                $this->endDate . ' 23:59:59'
            ]);

        if ($this->staffId !== 'all') $query->where('user_id', $this->staffId);
        if ($this->type !== 'all') $query->where('type', $this->type);

        if ($this->locationType === 'office') {
            $query->where('is_offsite', false);
        } elseif ($this->locationType === 'offsite') {
            $query->where('is_offsite', true);
        }

        if ($this->evidenceFilter === 'with_evidence') {
            $query->whereNotNull('evidence_path');
        } elseif ($this->evidenceFilter === 'without_evidence') {
            $query->whereNull('evidence_path');
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status_flag', $this->statusFilter);
        }

        $records = $query->orderBy('created_at', 'desc')->paginate(20);

        // Return view yang HANYA berisi tabel
        return view('livewire.admin.reports.report-table', [
            'records' => $records
        ]);
    }
}
