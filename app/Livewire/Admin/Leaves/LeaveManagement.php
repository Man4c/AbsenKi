<?php

namespace App\Livewire\Admin\Leaves;

use App\Models\Leave;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class LeaveManagement extends Component
{
    use WithPagination, WithFileUploads;

    public ?int $leaveId = null;
    public ?int $user_id = null;
    public string $type = 'izin';
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?string $reason = null;
    public TemporaryUploadedFile|null $evidence = null;

    public string $filterType = '';
    public string $filterUserId = '';
    public string $filterMonth = '';

    public bool $isModalOpen = false;
    public bool $isEditing = false;

    /** @var array<string, string> */
    protected array $rules = [
        'user_id' => 'required|exists:users,id',
        'type' => 'required|in:izin,cuti,sakit',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'reason' => 'nullable|string|max:500',
        'evidence' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
    ];

    /** @var array<string, string> */
    protected array $messages = [
        'user_id.required' => 'Silakan pilih staff',
        'user_id.exists' => 'Staff tidak ditemukan',
        'type.required' => 'Silakan pilih jenis',
        'type.in' => 'Jenis tidak valid',
        'start_date.required' => 'Tanggal mulai harus diisi',
        'start_date.date' => 'Format tanggal mulai tidak valid',
        'end_date.required' => 'Tanggal selesai harus diisi',
        'end_date.date' => 'Format tanggal selesai tidak valid',
        'end_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai',
        'reason.max' => 'Keterangan maksimal 500 karakter',
        'evidence.file' => 'File bukti tidak valid',
        'evidence.mimes' => 'Format file harus: JPG, PNG, atau PDF',
        'evidence.max' => 'Ukuran file maksimal 5MB',
    ];

    public function openModal(): void
    {
        $this->resetFields();
        $this->isModalOpen = true;
        $this->isEditing = false;
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->resetFields();
        $this->resetValidation();
    }

    public function resetFields(): void
    {
        $this->leaveId = null;
        $this->user_id = null;
        $this->type = 'izin';
        $this->start_date = null;
        $this->end_date = null;
        $this->reason = null;
        $this->evidence = null;
    }

    public function save(): void
    {
        $this->validate();

        // Cek duplikasi periode untuk staff yang sama
        $exists = Leave::where('user_id', $this->user_id)
            ->where(function ($query) {
                $query->whereBetween('start_date', [$this->start_date, $this->end_date])
                    ->orWhereBetween('end_date', [$this->start_date, $this->end_date])
                    ->orWhere(function ($q) {
                        $q->where('start_date', '<=', $this->start_date)
                            ->where('end_date', '>=', $this->end_date);
                    });
            });

        if ($this->isEditing) {
            $exists = $exists->where('id', '!=', $this->leaveId);
        }

        if ($exists->exists()) {
            session()->flash('error', 'Periode izin/cuti ini bertabrakan dengan data yang sudah ada untuk staff tersebut.');
            return;
        }

        // Handle file upload
        $evidencePath = null;
        if ($this->evidence) {
            $evidencePath = $this->evidence->store('leave-evidence', 'public');
        }

        if ($this->isEditing) {
            $leave = Leave::findOrFail($this->leaveId);

            $updateData = [
                'user_id' => $this->user_id,
                'type' => $this->type,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'reason' => $this->reason,
            ];

            // Only update evidence_path if new file is uploaded
            if ($evidencePath) {
                // Delete old evidence if exists
                if ($leave->evidence_path && Storage::disk('public')->exists($leave->evidence_path)) {
                    Storage::disk('public')->delete($leave->evidence_path);
                }
                $updateData['evidence_path'] = $evidencePath;
            }

            $leave->update($updateData);
            session()->flash('message', 'Data izin/cuti berhasil diperbarui.');
        } else {
            Leave::create([
                'user_id' => $this->user_id,
                'type' => $this->type,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'reason' => $this->reason,
                'status' => 'approved',
                'evidence_path' => $evidencePath,
            ]);
            session()->flash('message', 'Data izin/cuti berhasil ditambahkan.');
        }

        $this->closeModal();
    }

    public function edit(int $id): void
    {
        $leave = Leave::findOrFail($id);

        $this->leaveId = $leave->id;
        $this->user_id = $leave->user_id;
        $this->type = $leave->type;
        $this->start_date = $leave->start_date->format('Y-m-d');
        $this->end_date = $leave->end_date->format('Y-m-d');
        $this->reason = $leave->reason;
        // Note: existing evidence_path will be shown in view, but not loaded into $this->evidence

        $this->isModalOpen = true;
        $this->isEditing = true;
    }

    public function delete(int $id): void
    {
        Leave::destroy($id);
        session()->flash('message', 'Data izin/cuti berhasil dihapus.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $query = Leave::with('user');

        // Filter by type
        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        // Filter by user
        if ($this->filterUserId) {
            $query->where('user_id', $this->filterUserId);
        }

        // Filter by month
        if ($this->filterMonth) {
            $timestamp = strtotime($this->filterMonth);
            if ($timestamp !== false) {
                $query->whereMonth('start_date', (int) date('m', $timestamp))
                    ->whereYear('start_date', (int) date('Y', $timestamp));
            }
        }

        $leaves = $query->orderBy('start_date', 'desc')->paginate(10);
        $staff = User::where('role', 'staff')->orderBy('name')->get();

        return view('livewire.admin.leave.leave-management', [
            'leaves' => $leaves,
            'staff' => $staff,
        ]);
    }
}
