# Story_011 — Fitur Izin & Sakit (Leave Management)

## Tujuan

Staf dapat mengajukan permohonan Izin atau Sakit untuk rentang tanggal tertentu. Admin melakukan review dan memberikan keputusan (approve/reject). Data izin/sakit yang disetujui akan terintegrasi dengan Laporan Kehadiran tanpa dihitung sebagai keterlambatan atau ketidakhadiran.

---

## Alur Utama

### 1. Pengajuan (Staff)

-   Staff mengakses menu **"Izin & Sakit"**
-   Mengisi form pengajuan:
    -   Jenis: Izin / Sakit
    -   Tanggal Mulai & Tanggal Selesai
    -   Alasan (wajib, min 10 karakter)
    -   Lampiran bukti (opsional: surat dokter/undangan)
-   Validasi client-side: cek overlap dengan pengajuan approved
-   Submit → status: **pending**
-   Notifikasi real-time ke admin

### 2. Review & Keputusan (Admin)

-   Admin melihat daftar pengajuan (filter: pending/approved/rejected)
-   Membuka detail pengajuan (lihat bukti lampiran jika ada)
-   Memberikan keputusan:
    -   **Approve**: wajib isi catatan persetujuan
    -   **Reject**: wajib isi alasan penolakan
-   Sistem otomatis generate `leave_days` untuk rentang yang disetujui
-   Notifikasi real-time ke staff

### 3. Tracking Status (Staff)

-   Melihat riwayat pengajuan dengan status real-time
-   Aksi yang tersedia:
    -   **Pending**: Batalkan (dengan konfirmasi)
    -   **Approved/Rejected**: Lihat detail keputusan
-   Notifikasi badge untuk update status

### 4. Integrasi Laporan

-   Hari yang tercakup leave approved ditampilkan sebagai **IZIN/SAKIT**
-   Tidak diperhitungkan dalam:
    -   Keterlambatan
    -   Alpha/tidak hadir
    -   Pelanggaran geofence/face recognition
-   Summary report otomatis menghitung: Hadir, Izin, Sakit, Alpha

---

## Peran & Hak Akses

| Aksi                    | Staff  | Admin  |
| ----------------------- | ------ | ------ |
| Ajukan Leave            | ✅     | ✅     |
| Lihat Leave Sendiri     | ✅     | ✅     |
| Lihat Leave Semua Staff | ❌     | ✅     |
| Batalkan (Pending)      | ✅ Own | ✅ All |
| Approve/Reject          | ❌     | ✅     |
| Download Attachment     | ❌     | ✅     |

---

## Skema Database

### Tabel: `leave_requests`

```sql
id                 BIGINT UNSIGNED PRIMARY KEY
user_id            BIGINT UNSIGNED (FK → users.id, onDelete: RESTRICT)
type               ENUM('izin', 'sakit') NOT NULL
start_date         DATE NOT NULL
end_date           DATE NOT NULL
reason             TEXT NOT NULL
attachment_path    VARCHAR(255) NULL
status             ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending'
decision_note      TEXT NULL
decided_by         BIGINT UNSIGNED NULL (FK → users.id, onDelete: SET NULL)
decided_at         TIMESTAMP NULL
submitted_at       TIMESTAMP NULL (waktu submit, bisa beda dengan created_at)
created_at         TIMESTAMP
updated_at         TIMESTAMP
deleted_at         TIMESTAMP NULL (soft delete)

INDEX(user_id, status)
INDEX(start_date, end_date)
INDEX(status, submitted_at)
```

### Tabel: `leave_days` (Generated saat Approve)

```sql
id                 BIGINT UNSIGNED PRIMARY KEY
leave_request_id   BIGINT UNSIGNED (FK → leave_requests.id, onDelete: CASCADE)
date               DATE NOT NULL
type               ENUM('izin', 'sakit') NOT NULL
is_weekend         BOOLEAN DEFAULT FALSE
is_holiday         BOOLEAN DEFAULT FALSE
created_at         TIMESTAMP

UNIQUE(leave_request_id, date)
INDEX(date, type)
```

---

## Aturan Bisnis (Business Rules)

### 1. Validasi Rentang Tanggal

-   `start_date` ≤ `end_date` (wajib)
-   Maksimal rentang: **30 hari** dalam satu pengajuan
-   Tidak boleh tanggal masa depan > 90 hari
-   **Retroactive leave**: maksimal 7 hari ke belakang dari hari ini
    -   Jika > 7 hari: warning + butuh approval khusus

### 2. Cek Overlap

-   Tidak boleh overlap dengan leave lain yang berstatus **approved**
-   Boleh overlap dengan **pending** (warning + konfirmasi)
-   Cek overlap dilakukan:
    -   Client-side: saat input tanggal (AJAX check)
    -   Server-side: sebelum save (final validation)

### 3. Konflik dengan Absensi Existing

**Skenario A**: Leave diajukan SEBELUM hari-H

-   Normal flow, tidak ada konflik

**Skenario B**: Leave diajukan SETELAH sudah absen

```
Jika sudah ada clock-in/clock-out pada hari tersebut:
  - Izinkan approve (realitas: staff memang hadir lalu sakit/izin mendadak)
  - Tandai sebagai "Late Leave Request"
  - Badge khusus di laporan: "Hadir Sebagian + Izin/Sakit"
  - Hitung sebagai valid (tidak kena penalty telat)
  - Admin wajib review manual + isi decision_note
```

### 4. Weekend & Hari Libur

-   **Auto-skip**: Weekend & hari libur nasional tidak dihitung sebagai hari leave
-   Field `is_weekend` dan `is_holiday` di `leave_days` untuk tracking
-   Total hari effective = total calendar days - (weekend + holidays)

### 5. Leave Balance/Quota (Future Enhancement)

**Phase 1 (Story_011)**: Unlimited leave dengan approval
**Phase 2 (Story_012 - optional)**:

-   Tabel `leave_balances` (user_id, year, izin_quota, sakit_quota, used, remaining)
-   Reset tahunan
-   Warning saat quota hampir habis

### 6. File Attachment

-   **Format**: JPG, PNG, PDF
-   **Size**: Max 5 MB
-   **Storage**: `storage/app/leaves/{year}/{userId}/{leaveRequestId}_filename.ext`
-   **Naming**: `{timestamp}_{original_name}`
-   **Auto-cleanup**:
    -   Rejected: hapus file setelah 30 hari
    -   Cancelled: hapus file immediately
    -   Approved: simpan permanen (sampai end of retention period)
-   **Security**:
    -   Middleware: hanya admin & owner yang bisa download
    -   URL signed untuk prevent direct access

### 7. Status Transitions

```
Created → Pending (setelah submit)
Pending → Approved (by admin)
Pending → Rejected (by admin)
Pending → Cancelled (by staff/admin)
Cancelled/Rejected → [FINAL, tidak bisa reopen]
```

### 8. Notifikasi (MANDATORY)

| Event            | Penerima           | Channel        | Priority |
| ---------------- | ------------------ | -------------- | -------- |
| Leave Submitted  | Admin (all)        | In-app + Badge | High     |
| Leave Approved   | Staff (owner)      | In-app + Email | High     |
| Leave Rejected   | Staff (owner)      | In-app + Email | High     |
| Leave Cancelled  | Admin (decided_by) | In-app         | Medium   |
| Reminder Pending | Admin              | Daily digest   | Low      |

### 9. Dashboard Integration

**Widget "Kehadiran Hari Ini"**:

```
Total Staff: 50
✅ Hadir: 35
📅 Izin: 8
🏥 Sakit: 5
❌ Alpha: 2
```

**Status Compliance**: (Hadir + Izin + Sakit) / Total Staff

---

## UI/UX Design

### A. Staff Interface

#### 1. Menu: "Izin & Sakit" (`/staff/leaves`)

**Tab: Ajukan Baru**

```
┌─────────────────────────────────────────┐
│ 📝 Ajukan Izin/Sakit                     │
├─────────────────────────────────────────┤
│ Jenis *                                  │
│ ⭕ Izin    ⭕ Sakit                       │
│                                          │
│ Tanggal Mulai *                          │
│ [📅 DD/MM/YYYY]                          │
│                                          │
│ Tanggal Selesai *                        │
│ [📅 DD/MM/YYYY]                          │
│ ℹ️ Total: 3 hari kerja (5 hari kalender) │
│                                          │
│ Alasan *                                 │
│ [Textarea, min 10 karakter]              │
│                                          │
│ Lampiran Bukti (opsional)                │
│ [📎 Upload File] Max 5MB (JPG/PNG/PDF)  │
│                                          │
│ [Batal] [Ajukan] ← disabled jika overlap │
└─────────────────────────────────────────┘
```

**Tab: Riwayat**

```
┌────────────────────────────────────────────────────────────────┐
│ Filter: [Semua Status ▼] [Bulan Ini ▼]         [🔍 Cari...]   │
├──────┬────────────┬────────┬──────────┬──────────┬────────────┤
│ Jenis│ Tanggal    │ Durasi │ Status   │ Keputusan│ Aksi       │
├──────┼────────────┼────────┼──────────┼──────────┼────────────┤
│ 🏥 S │ 01-05 Des  │ 5 hari │ 🟢 Disetujui │ Dr.Admin │ [Detail] │
│ 📅 I │ 10-12 Des  │ 3 hari │ 🟡 Pending   │ -        │ [Batal]  │
│ 📅 I │ 15-15 Nov  │ 1 hari │ 🔴 Ditolak   │ Alasan..│ [Detail] │
└──────┴────────────┴────────┴──────────┴──────────┴────────────┘
```

#### 2. Modal: Detail Pengajuan

```
┌─────────────────────────────────────────┐
│ 📋 Detail Izin/Sakit           [✕]      │
├─────────────────────────────────────────┤
│ Jenis: 🏥 Sakit                          │
│ Periode: 01 Des 2025 - 05 Des 2025      │
│ Total Hari: 5 hari kalender (3 kerja)   │
│                                          │
│ Alasan:                                  │
│ "Demam tinggi dan batuk, perlu istirahat│
│  sesuai anjuran dokter."                 │
│                                          │
│ Lampiran: [📄 surat_dokter.pdf] [Download]│
│                                          │
│ Status: 🟢 Disetujui                     │
│ Oleh: Dr. Admin (03 Des 2025, 14:30)    │
│ Catatan Keputusan:                       │
│ "Approved. Get well soon!"               │
│                                          │
│ [Tutup]                                  │
└─────────────────────────────────────────┘
```

### B. Admin Interface

#### 1. Menu: "Manajemen Izin & Sakit" (`/admin/leaves`)

```
┌────────────────────────────────────────────────────────────────┐
│ 🔔 8 Pengajuan Pending | Filter: [Status ▼] [Jenis ▼] [Staff ▼]│
├────────┬──────────────┬────────────┬────────┬─────────┬────────┤
│ Staff  │ Jenis        │ Tanggal    │ Durasi │ Status  │ Aksi   │
├────────┼──────────────┼────────────┼────────┼─────────┼────────┤
│ Budi   │ 🏥 Sakit     │ 11-13 Des  │ 3 hari │ 🟡 Pending│[Review]│
│ Ani    │ 📅 Izin      │ 15-15 Des  │ 1 hari │ 🟡 Pending│[Review]│
│ Eko    │ 🏥 Sakit     │ 08-10 Des  │ 3 hari │ 🟢 Approved│[Lihat] │
│ Siti   │ 📅 Izin      │ 05-05 Des  │ 1 hari │ 🔴 Rejected│[Lihat] │
└────────┴──────────────┴────────────┴────────┴─────────┴────────┘
```

#### 2. Modal: Review Pengajuan

```
┌─────────────────────────────────────────┐
│ 🔍 Review Pengajuan - Budi Santoso [✕]  │
├─────────────────────────────────────────┤
│ Jenis: 🏥 Sakit                          │
│ Periode: 11 Des - 13 Des 2025 (3 hari)  │
│ Diajukan: 10 Des 2025, 08:15             │
│                                          │
│ Alasan:                                  │
│ "Sakit flu dan demam tinggi."            │
│                                          │
│ Lampiran: [📄 surat_dokter.pdf] [Lihat]  │
│                                          │
│ ⚠️ Warning:                              │
│ • Staff sudah clock-in pada 11 Des 07:30│
│ • Ini adalah late leave request          │
│                                          │
│ Keputusan *                              │
│ ⭕ Setujui    ⭕ Tolak                    │
│                                          │
│ Catatan Keputusan * (min 5 karakter)     │
│ [Textarea]                               │
│                                          │
│ [Batal] [Simpan Keputusan]               │
└─────────────────────────────────────────┘
```

### C. Integrasi Laporan

#### 1. Filter Tambahan

```
Filter: [Semua Staff ▼] [Des 2025 ▼] [Jenis Kehadiran: Semua ▼]
                                       └─ Semua
                                          Hadir
                                          Izin ← NEW
                                          Sakit ← NEW
                                          Alpha
```

#### 2. Tampilan Row dengan Leave

```
┌──────────────────────────────────────────────────────────────────┐
│ Budi Santoso - 11 Desember 2025                                  │
├─────────┬────────┬──────────┬───────────┬─────────┬─────────────┤
│ Jenis   │ Masuk  │ Keluar   │ Lokasi    │ Face    │ Keterangan  │
├─────────┼────────┼──────────┼───────────┼─────────┼─────────────┤
│ 🏥 SAKIT│ —      │ —        │ —         │ —       │ Disetujui   │
│         │        │          │           │         │ oleh Admin  │
│         │        │          │           │         │ [Lihat]     │
└─────────┴────────┴──────────┴───────────┴─────────┴─────────────┘

Note: Badge "Clock-in 07:30" ditampilkan jika ada late request
```

#### 3. Export CSV

```csv
Nama,Tanggal,Jenis,Jam_Masuk,Jam_Keluar,Status_Lokasi,Face_Match,Keterangan
Budi Santoso,11/12/2025,SAKIT,—,—,—,—,Disetujui oleh Admin (Dr.Admin)
Ani Wijaya,11/12/2025,HADIR,08:00,17:00,VALID,98.5%,—
```

#### 4. Summary Report

```
┌─────────────────────────────────────────┐
│ 📊 Ringkasan Kehadiran - Desember 2025  │
├─────────────────────────────────────────┤
│ Total Hari Kerja: 22 hari                │
│ Total Staff: 50 orang                    │
│                                          │
│ ✅ Hadir: 880 (80%)                      │
│ 📅 Izin: 110 (10%)          ← NEW        │
│ 🏥 Sakit: 55 (5%)           ← NEW        │
│ ❌ Alpha: 55 (5%)                        │
│                                          │
│ Compliance Rate: 95%                     │
│ (Hadir + Izin + Sakit)                   │
└─────────────────────────────────────────┘
```

---

## Technical Implementation

### 1. Model Relationships

```php
// app/Models/LeaveRequest.php
class LeaveRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'type', 'start_date', 'end_date',
        'reason', 'attachment_path', 'status',
        'decision_note', 'decided_by', 'decided_at', 'submitted_at'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'decided_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function decidedBy() {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function leaveDays() {
        return $this->hasMany(LeaveDay::class);
    }

    // Scope untuk filter
    public function scopePending($query) {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query) {
        return $query->where('status', 'approved');
    }

    // Helper methods
    public function getTotalDaysAttribute() {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getWorkingDaysAttribute() {
        // Exclude weekends & holidays
        return $this->leaveDays()
            ->where('is_weekend', false)
            ->where('is_holiday', false)
            ->count();
    }

    public function hasOverlapWith($startDate, $endDate) {
        return self::where('user_id', $this->user_id)
            ->where('status', 'approved')
            ->where('id', '!=', $this->id)
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function($q2) use ($startDate, $endDate) {
                      $q2->where('start_date', '<=', $startDate)
                         ->where('end_date', '>=', $endDate);
                  });
            })
            ->exists();
    }
}
```

### 2. Service Layer

```php
// app/Services/LeaveService.php
class LeaveService
{
    public function submitLeave(array $data, User $user) {
        // Validation
        $this->validateLeaveRequest($data, $user);

        DB::beginTransaction();
        try {
            $leave = LeaveRequest::create([
                'user_id' => $user->id,
                'type' => $data['type'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'reason' => $data['reason'],
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            // Handle file upload
            if (isset($data['attachment'])) {
                $path = $this->storeAttachment($data['attachment'], $user->id, $leave->id);
                $leave->update(['attachment_path' => $path]);
            }

            // Send notification to admins
            $this->notifyAdmins($leave);

            DB::commit();
            return $leave;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function approveLeave(LeaveRequest $leave, User $admin, string $note) {
        DB::beginTransaction();
        try {
            // Generate leave_days
            $this->generateLeaveDays($leave);

            $leave->update([
                'status' => 'approved',
                'decided_by' => $admin->id,
                'decided_at' => now(),
                'decision_note' => $note,
            ]);

            $this->notifyUser($leave, 'approved');

            DB::commit();
            return $leave;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function generateLeaveDays(LeaveRequest $leave) {
        $period = CarbonPeriod::create($leave->start_date, $leave->end_date);

        foreach ($period as $date) {
            LeaveDay::create([
                'leave_request_id' => $leave->id,
                'date' => $date,
                'type' => $leave->type,
                'is_weekend' => $date->isWeekend(),
                'is_holiday' => $this->isHoliday($date),
            ]);
        }
    }

    private function isHoliday(Carbon $date): bool {
        // Check against holidays table or external API
        return false; // TODO: implement
    }

    private function validateLeaveRequest(array $data, User $user) {
        // Check retroactive limit (7 days)
        if (Carbon::parse($data['start_date'])->lt(now()->subDays(7))) {
            throw new \Exception('Tidak dapat mengajukan izin lebih dari 7 hari ke belakang');
        }

        // Check overlap
        $tempLeave = new LeaveRequest([
            'user_id' => $user->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ]);

        if ($tempLeave->hasOverlapWith($data['start_date'], $data['end_date'])) {
            throw new \Exception('Rentang tanggal bertabrakan dengan izin yang sudah disetujui');
        }
    }
}
```

### 3. Livewire Components

**Staff: Leave Request Form**

```php
// app/Livewire/Staff/LeaveRequestForm.php
class LeaveRequestForm extends Component
{
    public $type = 'izin';
    public $start_date;
    public $end_date;
    public $reason;
    public $attachment;

    public $total_days = 0;
    public $working_days = 0;
    public $has_overlap = false;

    protected $rules = [
        'type' => 'required|in:izin,sakit',
        'start_date' => 'required|date|after_or_equal:today',
        'end_date' => 'required|date|after_or_equal:start_date',
        'reason' => 'required|min:10',
        'attachment' => 'nullable|file|mimes:jpg,png,pdf|max:5120',
    ];

    public function updated($field) {
        if (in_array($field, ['start_date', 'end_date'])) {
            $this->checkOverlap();
            $this->calculateDays();
        }
    }

    public function checkOverlap() {
        // AJAX call to check overlap
        if ($this->start_date && $this->end_date) {
            $this->has_overlap = app(LeaveService::class)
                ->checkOverlap(auth()->id(), $this->start_date, $this->end_date);
        }
    }

    public function submit() {
        $this->validate();

        try {
            app(LeaveService::class)->submitLeave([
                'type' => $this->type,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'reason' => $this->reason,
                'attachment' => $this->attachment,
            ], auth()->user());

            session()->flash('success', 'Pengajuan berhasil dikirim');
            return redirect()->route('staff.leaves.index');

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
}
```

**Admin: Leave Review**

```php
// app/Livewire/Admin/LeaveReview.php
class LeaveReview extends Component
{
    public LeaveRequest $leave;
    public $decision = '';
    public $decision_note = '';

    protected $rules = [
        'decision' => 'required|in:approve,reject',
        'decision_note' => 'required|min:5',
    ];

    public function mount(LeaveRequest $leave) {
        $this->leave = $leave;
        $this->checkConflicts();
    }

    public function checkConflicts() {
        // Check if staff already has attendance on those dates
        $conflicts = Attendance::where('user_id', $this->leave->user_id)
            ->whereBetween('date', [$this->leave->start_date, $this->leave->end_date])
            ->exists();

        if ($conflicts) {
            session()->flash('warning', 'Staff sudah memiliki absensi pada tanggal tersebut. Mohon review manual.');
        }
    }

    public function submit() {
        $this->validate();

        try {
            if ($this->decision === 'approve') {
                app(LeaveService::class)->approveLeave(
                    $this->leave,
                    auth()->user(),
                    $this->decision_note
                );
            } else {
                app(LeaveService::class)->rejectLeave(
                    $this->leave,
                    auth()->user(),
                    $this->decision_note
                );
            }

            session()->flash('success', 'Keputusan berhasil disimpan');
            return redirect()->route('admin.leaves.index');

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }
}
```

### 4. Report Integration

```php
// Modify: app/Livewire/Admin/LaporanAbsensi.php
class LaporanAbsensi extends Component
{
    public $filter_attendance_type = 'all'; // all, hadir, izin, sakit, alpha

    public function getAttendanceDataProperty() {
        $query = Attendance::with('user', 'location')
            ->whereBetween('date', [$this->start_date, $this->end_date]);

        if ($this->filter_attendance_type !== 'all') {
            if ($this->filter_attendance_type === 'izin' || $this->filter_attendance_type === 'sakit') {
                // Get dates covered by approved leaves
                $leaveDates = LeaveDay::whereHas('leaveRequest', function($q) {
                    $q->where('status', 'approved')
                      ->where('type', $this->filter_attendance_type);
                })
                ->pluck('date');

                // Exclude from attendance
                $query->whereNotIn('date', $leaveDates);
            } else {
                $query->where('status', $this->filter_attendance_type);
            }
        }

        return $query->get();
    }

    public function getLeaveDataProperty() {
        // Get all approved leaves in date range
        return LeaveDay::with('leaveRequest.user', 'leaveRequest.decidedBy')
            ->whereHas('leaveRequest', function($q) {
                $q->where('status', 'approved');
            })
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->get()
            ->groupBy('date');
    }

    public function exportCSV() {
        // Include leave data in export
        return Excel::download(new AttendanceExport([
            'attendance' => $this->attendance_data,
            'leaves' => $this->leave_data,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ]), 'laporan_kehadiran.csv');
    }
}
```

---

## Keamanan & Validasi

### 1. Authorization (Policy)

```php
// app/Policies/LeaveRequestPolicy.php
class LeaveRequestPolicy
{
    public function view(User $user, LeaveRequest $leave) {
        return $user->isAdmin() || $user->id === $leave->user_id;
    }

    public function create(User $user) {
        return true; // Semua user bisa ajukan
    }

    public function cancel(User $user, LeaveRequest $leave) {
        return ($user->id === $leave->user_id && $leave->status === 'pending')
            || $user->isAdmin();
    }

    public function decide(User $user, LeaveRequest $leave) {
        return $user->isAdmin() && $leave->status === 'pending';
    }

    public function downloadAttachment(User $user, LeaveRequest $leave) {
        return $user->isAdmin() || $user->id === $leave->user_id;
    }
}
```

### 2. File Upload Security

```php
// app/Http/Controllers/LeaveAttachmentController.php
class LeaveAttachmentController extends Controller
{
    public function download(LeaveRequest $leave) {
        $this->authorize('downloadAttachment', $leave);

        if (!$leave->attachment_path || !Storage::exists($leave->attachment_path)) {
            abort(404, 'File tidak ditemukan');
        }

        // Generate signed URL (expire in 1 hour)
        return Storage::download($leave->attachment_path);
    }

    private function storeAttachment($file, $userId, $leaveId) {
        $year = date('Y');
        $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $extension = $file->getClientOriginalExtension();

        $path = "leaves/{$year}/{$userId}/{$leaveId}_{$filename}.{$extension}";

        return $file->storeAs('leaves', $path, 'private');
    }
}
```

### 3. Input Sanitization

```php
// app/Http/Requests/StoreLeaveRequest.php
class StoreLeaveRequest extends FormRequest
{
    public function rules() {
        return [
            'type' => 'required|in:izin,sakit',
            'start_date' => [
                'required',
                'date',
                'after_or_equal:' . now()->subDays(7)->toDateString(),
                'before_or_equal:' . now()->addDays(90)->toDateString(),
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                function ($attribute, $value, $fail) {
                    $start = Carbon::parse($this->start_date);
                    $end = Carbon::parse($value);
                    if ($start->diffInDays($end) > 30) {
                        $fail('Maksimal rentang 30 hari dalam satu pengajuan.');
                    }
                },
            ],
            'reason' => 'required|string|min:10|max:1000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }

    public function messages() {
        return [
            'start_date.after_or_equal' => 'Tidak dapat mengajukan izin lebih dari 7 hari ke belakang.',
            'start_date.before_or_equal' => 'Tidak dapat mengajukan izin lebih dari 90 hari ke depan.',
            'reason.min' => 'Alasan minimal 10 karakter.',
            'attachment.max' => 'Ukuran file maksimal 5 MB.',
        ];
    }
}
```

---

## Testing Strategy

### 1. Unit Tests

```php
// tests/Unit/LeaveRequestTest.php
class LeaveRequestTest extends TestCase
{
    /** @test */
    public function it_calculates_total_days_correctly() {
        $leave = LeaveRequest::factory()->create([
            'start_date' => '2025-12-10',
            'end_date' => '2025-12-12',
        ]);

        $this->assertEquals(3, $leave->total_days);
    }

    /** @test */
    public function it_detects_overlap_with_approved_leaves() {
        $user = User::factory()->create();

        LeaveRequest::factory()->create([
            'user_id' => $user->id,
            'start_date' => '2025-12-10',
            'end_date' => '2025-12-15',
            'status' => 'approved',
        ]);

        $newLeave = new LeaveRequest([
            'user_id' => $user->id,
            'start_date' => '2025-12-12',
            'end_date' => '2025-12-14',
        ]);

        $this->assertTrue($newLeave->hasOverlapWith('2025-12-12', '2025-12-14'));
    }
}
```

### 2. Feature Tests

```php
// tests/Feature/LeaveManagementTest.php
class LeaveManagementTest extends TestCase
{
    /** @test */
    public function staff_can_submit_leave_request() {
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)
            ->post('/staff/leaves', [
                'type' => 'izin',
                'start_date' => now()->addDays(1)->toDateString(),
                'end_date' => now()->addDays(3)->toDateString(),
                'reason' => 'Acara keluarga yang penting',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $staff->id,
            'type' => 'izin',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function admin_can_approve_leave_request() {
        $admin = User::factory()->admin()->create();
        $leave = LeaveRequest::factory()->pending()->create();

        $response = $this->actingAs($admin)
            ->post("/admin/leaves/{$leave->id}/approve", [
                'decision_note' => 'Approved, enjoy your leave',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'status' => 'approved',
            'decided_by' => $admin->id,
        ]);

        // Check leave_days generated
        $this->assertDatabaseCount('leave_days', 3); // assuming 3-day leave
    }

    /** @test */
    public function cannot_submit_overlapping_leave() {
        $staff = User::factory()->create();

        LeaveRequest::factory()->create([
            'user_id' => $staff->id,
            'start_date' => '2025-12-10',
            'end_date' => '2025-12-15',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($staff)
            ->post('/staff/leaves', [
                'type' => 'izin',
                'start_date' => '2025-12-12',
                'end_date' => '2025-12-14',
                'reason' => 'Trying to overlap',
            ]);

        $response->assertSessionHasErrors();
    }
}
```

### 3. Browser Tests (Dusk)

```php
// tests/Browser/LeaveFlowTest.php
class LeaveFlowTest extends DuskTestCase
{
    /** @test */
    public function complete_leave_approval_flow() {
        $staff = User::factory()->staff()->create();
        $admin = User::factory()->admin()->create();

        // Staff submits leave
        $this->browse(function (Browser $browser) use ($staff) {
            $browser->loginAs($staff)
                ->visit('/staff/leaves')
                ->click('@submit-leave-tab')
                ->radio('type', 'izin')
                ->type('start_date', now()->addDays(5)->format('Y-m-d'))
                ->type('end_date', now()->addDays(7)->format('Y-m-d'))
                ->type('reason', 'Family vacation trip to Bali')
                ->press('Ajukan')
                ->assertSee('Pengajuan berhasil dikirim');
        });

        // Admin approves
        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/leaves')
                ->assertSee('1 Pengajuan Pending')
                ->click('@review-leave-1')
                ->radio('decision', 'approve')
                ->type('decision_note', 'Approved, enjoy Bali!')
                ->press('Simpan Keputusan')
                ->assertSee('Keputusan berhasil disimpan');
        });

        // Staff sees approved status
        $this->browse(function (Browser $browser) use ($staff) {
            $browser->loginAs($staff)
                ->visit('/staff/leaves')
                ->assertSee('Disetujui')
                ->assertSee('Approved, enjoy Bali!');
        });
    }
}
```

---

## Performance Optimization

### 1. Database Indexing

```sql
-- Already covered in schema, additional composite indexes:
CREATE INDEX idx_leave_user_status_date ON leave_requests(user_id, status, start_date, end_date);
CREATE INDEX idx_leave_days_date_type ON leave_days(date, type);
CREATE INDEX idx_leave_status_submitted ON leave_requests(status, submitted_at);
```

### 2. Eager Loading

```php
// In queries, always eager load relationships
LeaveRequest::with(['user', 'decidedBy', 'leaveDays'])
    ->pending()
    ->latest('submitted_at')
    ->get();
```

### 3. Caching Strategy

```php
// Cache approved leaves for report generation
Cache::remember("approved_leaves_{$userId}_{$month}", 3600, function() use ($userId, $month) {
    return LeaveDay::whereHas('leaveRequest', function($q) use ($userId) {
        $q->where('user_id', $userId)
          ->where('status', 'approved');
    })
    ->whereYear('date', $month->year)
    ->whereMonth('date', $month->month)
    ->pluck('date')
    ->toArray();
});
```

### 4. Queue for Notifications

```php
// Dispatch notification jobs instead of synchronous
dispatch(new SendLeaveApprovalNotification($leave, $staff));
```

---

## Acceptance Criteria (Testing Checklist)

### Functional Requirements

-   [ ] **FR-01**: Staff dapat mengajukan izin/sakit dengan rentang tanggal
-   [ ] **FR-02**: Staff dapat upload lampiran bukti (JPG/PNG/PDF, max 5MB)
-   [ ] **FR-03**: Sistem validasi overlap dengan leave approved
-   [ ] **FR-04**: Sistem validasi rentang tanggal (max 30 hari, max 7 hari retroactive)
-   [ ] **FR-05**: Admin menerima notifikasi real-time saat ada pengajuan baru
-   [ ] **FR-06**: Admin dapat approve/reject dengan catatan wajib
-   [ ] **FR-07**: Sistem generate `leave_days` otomatis saat approve
-   [ ] **FR-08**: Sistem skip weekend & holiday saat generate leave_days
-   [ ] **FR-09**: Staff menerima notifikasi real-time saat keputusan dibuat
-   [ ] **FR-10**: Staff dapat cancel pengajuan pending
-   [ ] **FR-11**: Staff dapat melihat riwayat pengajuan dengan status
-   [ ] **FR-12**: Laporan menampilkan IZIN/SAKIT pada hari yang tercakup leave
-   [ ] **FR-13**: Filter laporan by jenis kehadiran (Hadir/Izin/Sakit/Alpha)
-   [ ] **FR-14**: Export CSV/PDF include data leave dengan format konsisten
-   [ ] **FR-15**: Dashboard summary count: Hadir, Izin, Sakit, Alpha
-   [ ] **FR-16**: Compliance rate calculation: (Hadir+Izin+Sakit)/Total

### Security Requirements

-   [ ] **SR-01**: Staff hanya bisa lihat leave milik sendiri
-   [ ] **SR-02**: Admin bisa lihat semua leave
-   [ ] **SR-03**: Only admin dapat approve/reject
-   [ ] **SR-04**: File attachment hanya bisa diunduh oleh owner & admin
-   [ ] **SR-05**: File tersimpan di private storage (tidak direct access)
-   [ ] **SR-06**: Input sanitization untuk prevent XSS/SQL Injection
-   [ ] **SR-07**: CSRF protection untuk semua form
-   [ ] **SR-08**: Authorization check di semua endpoint

### Performance Requirements

-   [ ] **PR-01**: Leave submission response time < 2 detik
-   [ ] **PR-02**: Approval process response time < 2 detik
-   [ ] **PR-03**: Report generation with leave data < 5 detik (1000 rows)
-   [ ] **PR-04**: Notification delivery < 1 detik (in-app)
-   [ ] **PR-05**: File upload handling untuk file 5MB < 3 detik

### Usability Requirements

-   [ ] **UR-01**: Form validation dengan error message yang jelas
-   [ ] **UR-02**: Real-time overlap check saat input tanggal (AJAX)
-   [ ] **UR-03**: Real-time calculation total hari & hari kerja
-   [ ] **UR-04**: Confirmation dialog sebelum cancel/approve/reject
-   [ ] **UR-05**: Loading indicator untuk proses async
-   [ ] **UR-06**: Success/error toast notification setelah action
-   [ ] **UR-07**: Badge count untuk pending leaves (admin sidebar)
-   [ ] **UR-08**: Status color coding: 🟡 Pending, 🟢 Approved, 🔴 Rejected

### Edge Cases

-   [ ] **EC-01**: Handle late leave request (staff sudah clock-in)
-   [ ] **EC-02**: Handle file upload failure (rollback transaction)
-   [ ] **EC-03**: Handle concurrent approval (2 admin approve bersamaan)
-   [ ] **EC-04**: Handle user deletion (set decided_by to NULL, keep record)
-   [ ] **EC-05**: Handle attachment file corruption
-   [ ] **EC-06**: Handle long weekend (hitung hari kerja dengan benar)
-   [ ] **EC-07**: Handle year-end leave (cross-year date range)

---

## Migration Plan

### Phase 1: Database & Models (Day 1-2)

```bash
php artisan make:migration create_leave_requests_table
php artisan make:migration create_leave_days_table
php artisan make:model LeaveRequest -f
php artisan make:model LeaveDay
```

### Phase 2: Core Services & Logic (Day 3-4)

-   Buat `LeaveService` dengan methods: submit, approve, reject, cancel
-   Buat `LeaveValidationService` untuk check overlap, retroactive, etc
-   Buat Policy: `LeaveRequestPolicy`
-   Unit tests untuk service layer

### Phase 3: Staff Interface (Day 5-6)

-   Livewire: `LeaveRequestForm`
-   Livewire: `LeaveHistory`
-   View: form pengajuan
-   View: riwayat pengajuan
-   Feature tests untuk staff flow

### Phase 4: Admin Interface (Day 7-8)

-   Livewire: `LeaveManagement`
-   Livewire: `LeaveReview`
-   View: daftar pengajuan
-   View: modal review
-   Feature tests untuk admin flow

### Phase 5: Report Integration (Day 9-10)

-   Modify: `LaporanAbsensi` Livewire
-   Tambah filter jenis kehadiran
-   Update query untuk include leave data
-   Update export CSV/PDF
-   Update dashboard summary

### Phase 6: Notifications & Polish (Day 11-12)

-   Setup notification channels (database, mail)
-   Implement real-time notifications (Livewire events)
-   Browser tests (Dusk)
-   UAT dengan sample data

### Phase 7: Production Deployment (Day 13-14)

-   Code review
-   Performance testing
-   Backup database
-   Run migrations
-   Monitor for issues
-   Hotfix jika ada bug critical

---

## Future Enhancements (Story_012+)

### v2.0: Leave Balance & Quota

-   [ ] Tabel `leave_balances` (jatah tahunan)
-   [ ] Auto-reset setiap tahun
-   [ ] Dashboard widget: sisa quota
-   [ ] Warning saat quota < 3 hari

### v2.1: Approval Workflow

-   [ ] Multi-level approval (Staff → Supervisor → HR → Approved)
-   [ ] Delegation: assign approver saat cuti
-   [ ] Auto-approval rules (sakit < 2 hari + ada surat)

### v2.2: Leave Types Expansion

-   [ ] Cuti tahunan (annual leave)
-   [ ] Cuti bersama (collective leave)
-   [ ] Cuti melahirkan (maternity leave)
-   [ ] Custom leave types per company

### v2.3: Analytics & Reporting

-   [ ] Leave trend analysis (per bulan/quarter)
-   [ ] Most common leave reasons (word cloud)
-   [ ] Leave utilization rate per department
-   [ ] Export advanced reports (Excel with charts)

### v2.4: Integration

-   [ ] Google Calendar sync (add approved leaves)
-   [ ] Slack/Teams notification integration
-   [ ] Email reminder: pending approvals (daily digest)
-   [ ] API for external HR systems

---

## Glossary

| Term               | Definition                                                    |
| ------------------ | ------------------------------------------------------------- |
| Leave              | Izin atau Sakit yang diajukan staff                           |
| Leave Request      | Pengajuan izin/sakit (status: pending/approved/rejected)      |
| Leave Day          | Hari-hari yang tercakup dalam leave approved                  |
| Retroactive Leave  | Izin yang diajukan untuk hari yang sudah lewat                |
| Late Leave Request | Izin diajukan setelah staff sudah clock-in pada hari tersebut |
| Overlap            | Dua leave yang tanggalnya bertabrakan                         |
| Working Days       | Hari kerja (exclude weekend & holiday)                        |
| Compliance Rate    | Persentase kehadiran valid (hadir + izin + sakit)             |
| Decision Note      | Catatan admin saat approve/reject                             |

---

**Last Updated**: 2025-12-11  
**Version**: 2.0 (Enhanced)  
**Author**: GitHub Copilot  
**Status**: Ready for Implementation 🚀
