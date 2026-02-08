<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Admin Routes - Using the main dashboard view
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::get('/geofence', \App\Livewire\Admin\Geofence\Index::class)->name('geofence');
    Route::get('/faces', \App\Livewire\Admin\Faces\StaffList::class)->name('faces');
    Route::get('/faces/{userId}', \App\Livewire\Admin\Faces\Manage::class)->name('faces.manage');
    Route::get('/laporan', \App\Livewire\Admin\Reports\Index::class)->name('laporan');
    Route::get('/laporan/export-pdf', [\App\Http\Controllers\Admin\ReportController::class, 'exportPdf'])->name('laporan.export-pdf');
    Route::get('/izin-cuti', \App\Livewire\Admin\Leaves\LeaveManagement::class)->name('izin-cuti');

    // Settings Routes
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/work-schedule', \App\Livewire\Admin\Settings\WorkScheduleSettings::class)->name('work-schedule');
        Route::get('/holidays', \App\Livewire\Admin\Settings\HolidaySettings::class)->name('holidays');
    });
});

// Staff Routes
Route::middleware(['auth', 'role:staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/absen', \App\Livewire\Staff\Absen::class)->name('absen');
    Route::get('/riwayat', \App\Livewire\Staff\History::class)->name('riwayat');
});

// API Routes (accessible by authenticated users)
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/geofence/active', function () {
        $geofence = \App\Models\Geofence::where('is_active', true)->first();

        if (!$geofence) {
            return response()->json([
                'active' => false,
                'message' => 'No active geofence found',
            ], 200);
        }

        return response()->json([
            'active' => true,
            'name' => $geofence->name,
            'polygon' => $geofence->polygon_geojson,
        ], 200);
    });
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    // Volt::route('settings/two-factor', 'settings.two-factor')
    //     ->middleware(
    //         when(
    //             Features::canManageTwoFactorAuthentication()
    //                 && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
    //             ['password.confirm'],
    //             [],
    //         ),
    //     )
    //     ->name('two-factor.show');
});
