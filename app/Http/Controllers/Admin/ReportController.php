<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function exportPdf(Request $request): Response
    {
        // Get filter parameters from request
        $staffId = $request->get('staffId', 'all');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');
        $type = $request->get('type', 'all');
        $locationType = $request->get('locationType', 'all');

        // Build query
        $query = Attendance::with(['user', 'createdByAdmin'])
            ->whereBetween('created_at', [
                (is_string($startDate) ? $startDate : date('Y-m-d')) . ' 00:00:00',
                (is_string($endDate) ? $endDate : date('Y-m-d')) . ' 23:59:59'
            ]);

        // Filter by staff
        if ($staffId !== 'all') {
            $query->where('user_id', $staffId);
        }

        // Filter by type
        if ($type !== 'all') {
            $query->where('type', $type);
        }

        // Filter by location type
        if ($locationType === 'office') {
            $query->where('is_offsite', false);
        } elseif ($locationType === 'offsite') {
            $query->where('is_offsite', true);
        }

        $records = $query->orderBy('created_at', 'desc')->get();

        // Helper function to clean UTF-8
        $cleanUtf8 = static function (?string $value): string {
            if ($value === null || $value === '') {
                return '';
            }

            // Remove invalid UTF-8 characters
            $encoded = (string) mb_convert_encoding($value, 'UTF-8', 'UTF-8');

            // Remove any remaining control characters except newlines and tabs
            $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $encoded);
            return is_string($cleaned) ? $cleaned : '';
        };

        // Clean all text data
        foreach ($records as $record) {
            if ($record->device_info) {
                $cleaned = $cleanUtf8($record->device_info);
                $record->device_info = $cleaned;
            }
            /** @var User|null $user */
            $user = $record->user;
            if ($user) {
                $cleanedName = $cleanUtf8($user->name);
                $cleanedEmail = $cleanUtf8($user->email);
                $user->name = $cleanedName;
                $user->email = $cleanedEmail;
            }
        }

        // Get filter info for PDF header
        $filterInfo = [];

        if ($staffId !== 'all') {
            $staff = User::find($staffId);
            /** @var User|null $staff */
            if ($staff instanceof User) {
                $cleanedStaffName = $cleanUtf8($staff->name);
                $filterInfo[] = 'Staff: ' . ($cleanedStaffName !== '' ? $cleanedStaffName : $staff->name);
            }
        } else {
            $filterInfo[] = 'Staff: Semua';
        }

        $startTimestamp = strtotime(is_string($startDate) ? $startDate : '');
        $endTimestamp = strtotime(is_string($endDate) ? $endDate : '');

        $filterInfo[] = 'Periode: ' .
            date('d M Y', $startTimestamp !== false ? $startTimestamp : time()) . ' - ' .
            date('d M Y', $endTimestamp !== false ? $endTimestamp : time());

        if ($type !== 'all') {
            $filterInfo[] = 'Jenis: ' . ($type === 'in' ? 'Masuk' : 'Keluar');
        }

        if ($locationType !== 'all') {
            $filterInfo[] = 'Lokasi: ' . ($locationType === 'office' ? 'Di Kantor' : 'Offsite');
        }

        // Use simple separator instead of bullet
        $filterInfoString = $cleanUtf8(implode(' | ', $filterInfo));

        $pdf = Pdf::loadView('livewire.admin.reports.pdf', [
            'records' => $records,
            'filterInfo' => $filterInfoString,
            'printDate' => now()->format('d M Y H:i')
        ]);

        return $pdf->download('laporan_absensi_' . now()->format('Y-m-d_His') . '.pdf');
    }
}
