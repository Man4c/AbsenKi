<?php

namespace App\Livewire\Admin\Reports;

use App\Models\Attendance;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Index extends Component
{
    use WithPagination;

    // Filter properties
    public string $staffId = 'all';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $type = 'all'; // all | in | out
    public string $locationType = 'all'; // all | office | offsite
    public string $evidenceFilter = 'all'; // all | with_evidence | without_evidence
    public string $statusFilter = 'all'; // all | on_time | late | normal_leave | early_leave

    /** @var array<string, string> */
    protected $listeners = ['offsite-created' => 'refreshData'];

    public function mount(): void
    {
        // Default: current month (from start of month to today)
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function applyFilter(): void
    {
        // Reset pagination when applying filter
        $this->resetPage();
    }

    public function refreshData(): void
    {
        // Refresh data after offsite entry created
        $this->resetPage();
    }

    /**
     * @return Builder<Attendance>
     */
    public function getRecordsQuery(): Builder
    {
        $query = Attendance::with(['user', 'createdByAdmin'])
            ->whereBetween('created_at', [
                $this->startDate . ' 00:00:00',
                $this->endDate . ' 23:59:59'
            ]);

        // Filter by staff
        if ($this->staffId !== 'all') {
            $query->where('user_id', $this->staffId);
        }

        // Filter by type
        if ($this->type !== 'all') {
            $query->where('type', $this->type);
        }

        // Filter by location type
        if ($this->locationType === 'office') {
            $query->where('is_offsite', false);
        } elseif ($this->locationType === 'offsite') {
            $query->where('is_offsite', true);
        }

        // Filter by evidence
        if ($this->evidenceFilter === 'with_evidence') {
            $query->whereNotNull('evidence_path');
        } elseif ($this->evidenceFilter === 'without_evidence') {
            $query->whereNull('evidence_path');
        }

        // Filter by status
        if ($this->statusFilter !== 'all') {
            $query->where('status_flag', $this->statusFilter);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function exportCsv(): StreamedResponse
    {
        $records = $this->getRecordsQuery()->get();

        $filename = 'laporan_absensi_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');

            if ($file === false) {
                return;
            }

            // Header CSV
            fputcsv($file, [
                'Nama Staff',
                'Email Staff',
                'Waktu',
                'Jenis Absen',
                'Status',
                'Jenis Lokasi',
                'Di Dalam Area',
                'Face Score (%)',
                'Quality Blur (Laplacian)',
                'Quality Brightness (HSV)',
                'Latitude',
                'Longitude',
                'Device Info',
                'Lokasi Offsite',
                'Koordinat Offsite',
                'Alasan Offsite',
                'Bukti URL',
                'Catatan Bukti',
                'Dibuat Oleh Admin'
            ]);

            // Data rows
            foreach ($records as $record) {
                fputcsv($file, [
                    $record->user ? $record->user->name : 'N/A',
                    $record->user ? $record->user->email : 'N/A',
                    $record->created_at ? $record->created_at->format('d M Y H:i') : 'N/A',
                    $record->type === 'in' ? 'Masuk' : 'Keluar',
                    $record->status_label ?? '-',
                    $record->is_offsite ? 'Offsite' : 'Di Kantor',
                    $record->geo_ok ? 'Ya' : 'Tidak',
                    $record->face_score ? number_format((float) $record->face_score, 1) : '-',
                    $record->quality_blur_var ? number_format((float) $record->quality_blur_var, 2) : '-',
                    $record->quality_brightness ? number_format((float) $record->quality_brightness, 2) : '-',
                    $record->lat,
                    $record->lng,
                    $record->device_info ?? '-',
                    $record->offsite_location_text ?? '-',
                    $record->offsite_coords ?? '-',
                    $record->offsite_reason ?? '-',
                    $record->evidence_url ?? '-',
                    $record->evidence_note ?? '-',
                    $record->createdByAdmin ? $record->createdByAdmin->name : '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(): void
    {
        // Build query parameters
        $params = [
            'staffId' => $this->staffId,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'type' => $this->type,
            'locationType' => $this->locationType,
        ];

        // Redirect to export route using Livewire redirect method
        $this->redirect(route('admin.laporan.export-pdf', $params), navigate: false);
    }

    public function render(): View
    {
        $records = $this->getRecordsQuery()->paginate(20);

        $staffList = User::where('role', 'staff')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('livewire.admin.reports.index', [
            'records' => $records,
            'staffList' => $staffList
        ]);
    }
}
