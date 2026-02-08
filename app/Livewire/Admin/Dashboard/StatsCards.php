<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Geofence;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;

class StatsCards extends Component
{
    public function placeholder(): View
    {
        return view('livewire.placeholders.stats-cards-skeleton');
    }

    public function render(): View
    {
        // Total Staff & Face Status
        $totalStaff = User::where('role', 'staff')->count();
        $staffBelumPunyaWajah = User::where('role', 'staff')
            ->whereDoesntHave('faceProfiles')
            ->count();

        // Kehadiran Hari Ini
        $today = Carbon::today();
        $hadirHariIni = Attendance::where('type', 'in')
            ->whereDate('created_at', $today)
            ->distinct('user_id')
            ->count('user_id');

        // Terlambat (absen masuk setelah 08:00)
        $terlambatHariIni = Attendance::where('type', 'in')
            ->whereDate('created_at', $today)
            ->whereTime('created_at', '>', '08:00:00')
            ->distinct('user_id')
            ->count('user_id');

        // Status Sistem
        $geofenceAktif = Geofence::where('is_active', true)->first();
        $geofenceAktifName = $geofenceAktif ? $geofenceAktif->name : 'Tidak ada';

        // Check Rekognition connection (simplified - assume OK if geofence exists)
        $rekognitionOk = !empty(config('services.rekognition.collection'));

        // Last attendance time
        $lastAttendance = Attendance::latest('created_at')->first();
        $lastAttendanceTime = $lastAttendance && $lastAttendance->created_at
            ? $lastAttendance->created_at->format('d M Y H:i')
            : 'Belum ada data';

        return view('livewire.admin.dashboard.stats-cards', [
            'totalStaff' => $totalStaff,
            'staffBelumPunyaWajah' => $staffBelumPunyaWajah,
            'hadirHariIni' => $hadirHariIni,
            'terlambatHariIni' => $terlambatHariIni,
            'geofenceAktifName' => $geofenceAktifName,
            'rekognitionOk' => $rekognitionOk,
            'lastAttendanceTime' => $lastAttendanceTime,
        ]);
    }
}
