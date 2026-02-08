<?php

namespace App\Livewire\Admin\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CreateOffsite extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;

    // Form fields
    public ?int $userId = null;
    public string $attendanceDate = '';
    public string $attendanceTime = '';
    public string $type = 'in'; // in, out, hadir
    public string $location = '';
    public string $coordinates = '';
    public string $reason = '';
    public TemporaryUploadedFile|null $evidence = null;
    public string $evidenceNote = '';

    /** @var array<string, string> */
    protected $listeners = ['openModal' => 'openModal'];

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'userId' => 'required|exists:users,id',
            'attendanceDate' => 'required|date|before_or_equal:today',
            'attendanceTime' => 'required|date_format:H:i',
            'type' => 'required|in:in,out,hadir',
            'location' => 'required|string|max:255',
            'coordinates' => [
                'nullable',
                'string',
                'regex:/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/'
            ],
            'reason' => 'nullable|string|max:1000',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
            'evidenceNote' => 'nullable|string|max:500',
        ];
    }

    /** @var array<string, string> */
    protected array $messages = [
        'userId.required' => 'Staff harus dipilih.',
        'userId.exists' => 'Staff tidak ditemukan.',
        'attendanceDate.required' => 'Tanggal absensi harus diisi.',
        'attendanceDate.date' => 'Format tanggal tidak valid.',
        'attendanceDate.before_or_equal' => 'Tanggal tidak boleh di masa depan.',
        'attendanceTime.required' => 'Jam absensi harus diisi.',
        'attendanceTime.date_format' => 'Format jam harus HH:MM.',
        'type.required' => 'Jenis absensi harus dipilih.',
        'type.in' => 'Jenis absensi tidak valid.',
        'location.required' => 'Lokasi/alamat harus diisi.',
        'location.max' => 'Lokasi maksimal 255 karakter.',
        'coordinates.regex' => 'Format koordinat harus: lat,lng (contoh: -6.200000,106.816666)',
        'reason.max' => 'Alasan maksimal 1000 karakter.',
        'evidence.file' => 'Bukti harus berupa file.',
        'evidence.mimes' => 'Bukti harus berformat JPG, PNG, atau PDF.',
        'evidence.max' => 'Ukuran file bukti maksimal 5MB.',
        'evidenceNote.max' => 'Catatan bukti maksimal 500 karakter.',
    ];

    public function mount(): void
    {
        $this->attendanceDate = now()->format('Y-m-d');
        $this->attendanceTime = now()->format('H:i');
    }

    public function openModal(): void
    {
        $this->isOpen = true;
        $this->resetForm();
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function resetForm(): void
    {
        $this->userId = null;
        $this->attendanceDate = now()->format('Y-m-d');
        $this->attendanceTime = now()->format('H:i');
        $this->type = 'in';
        $this->location = '';
        $this->coordinates = '';
        $this->reason = '';
        $this->evidence = null;
        $this->evidenceNote = '';
    }

    public function save(): void
    {
        // Validate
        $validated = $this->validate();

        // Verify user is staff
        $user = User::find($this->userId);
        if (!$user || $user->role !== 'staff') {
            $this->addError('userId', 'User yang dipilih harus memiliki role staff.');
            return;
        }

        // Check for duplicate entry (anti-duplikasi)
        // Jika type = 'in' atau 'hadir', cek apakah sudah ada entry 'in' di hari yang sama
        if (in_array($this->type, ['in', 'hadir'])) {
            $existingEntry = Attendance::where('user_id', $this->userId)
                ->where('type', 'in')
                ->whereDate('created_at', $this->attendanceDate)
                ->exists();

            if ($existingEntry) {
                $this->addError('attendanceDate', 'Staff sudah memiliki entri Masuk di tanggal ini.');
                return;
            }
        }

        // Parse coordinates if provided
        $lat = null;
        $lng = null;
        if ($this->coordinates) {
            [$lat, $lng] = explode(',', $this->coordinates);
            $lat = trim($lat);
            $lng = trim($lng);
        }

        // Handle evidence upload
        $evidencePath = null;
        $evidenceMime = null;
        $evidenceSize = null;
        $evidenceUploadedAt = null;

        if ($this->evidence) {
            $dateFolder = now()->format('Ymd');
            $evidenceUploadedAt = now();

            $fileName = time() . '_' . $this->evidence->getClientOriginalName();

            // Store in public disk for easy access
            $evidencePath = $this->evidence->storeAs(
                'evidence/' . $this->userId . '/' . $dateFolder,
                $fileName,
                'public'
            );

            $evidenceMime = $this->evidence->getMimeType();
            $evidenceSize = $this->evidence->getSize();
        }

        // Create datetime
        $attendanceDateTime = $this->attendanceDate . ' ' . $this->attendanceTime . ':00';

        // Determine final type (if 'hadir', convert to 'in')
        $finalType = $this->type === 'hadir' ? 'in' : $this->type;

        // Create attendance record
        Attendance::create([
            'user_id' => $this->userId,
            'type' => $finalType,
            'lat' => $lat ?? 0,
            'lng' => $lng ?? 0,
            'geo_ok' => false, // Offsite entries don't need geofence validation
            'face_score' => null, // No face check for offsite
            'status' => 'success',
            'device_info' => 'Offsite entry by admin',
            'is_offsite' => true,
            'offsite_location_text' => $this->location,
            'offsite_coords' => $this->coordinates,
            'offsite_reason' => $this->reason,
            'evidence_path' => $evidencePath,
            'evidence_mime' => $evidenceMime,
            'evidence_size' => $evidenceSize,
            'evidence_note' => $this->evidenceNote,
            'evidence_uploaded_at' => $evidenceUploadedAt,
            'created_by_admin_id' => Auth::id(),
            'created_at' => $attendanceDateTime,
            'updated_at' => now(),
        ]);

        // Success notification
        session()->flash('message', 'Entri offsite berhasil ditambahkan.');

        // Close modal and emit event to refresh parent
        $this->closeModal();
        $this->dispatch('offsite-created');
    }

    public function render(): View
    {
        $staffList = User::where('role', 'staff')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('livewire.admin.attendance.create-offsite', [
            'staffList' => $staffList
        ]);
    }
}
