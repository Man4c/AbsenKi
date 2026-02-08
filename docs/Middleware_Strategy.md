# Middleware Strategy - Role-Based Access Control

## Konsistensi Implementasi ✅

Project AbsenKi menggunakan **middleware role-based** yang konsisten di seluruh aplikasi.

---

## Strategi yang Dipilih: Custom Middleware `role:{role}`

### Kenapa Pilih Ini?

1. **Sederhana dan Eksplisit** - Jelas terlihat di route definition
2. **Mudah Dipahami** - Cocok untuk dokumentasi skripsi
3. **Konsisten** - Satu pattern untuk semua role-based access
4. **Tidak Ada Campuran** - Tidak menggunakan Gate/Policy/can: yang bisa bikin bingung

---

## Implementasi

### 1. Middleware Registration

**File:** `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\EnsureUserHasRole::class,
    ]);
})
```

### 2. Middleware Class

**File:** `app/Http/Middleware/EnsureUserHasRole.php`

```php
public function handle(Request $request, Closure $next, string $role): Response
{
    if (! $request->user()) {
        abort(403, 'Unauthorized access.');
    }

    if ($request->user()->role !== $role) {
        abort(403, 'You do not have permission to access this page.');
    }

    return $next($request);
}
```

**Cara Kerja:**

-   Check apakah user sudah authenticated
-   Validasi `$request->user()->role` sama dengan parameter `$role`
-   Jika tidak match → HTTP 403 Forbidden

### 3. Route Protection

**File:** `routes/web.php`

**Admin Routes:**

```php
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::get('/geofence', \App\Livewire\Admin\Geofence\Index::class)->name('geofence');
});
```

**Staff Routes:**

```php
Route::middleware(['auth', 'role:staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::view('/absen', 'staff.absen')->name('absen');
});
```

**Public API (Authenticated Only):**

```php
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/geofence/active', function () {
        // Accessible by both admin and staff
    });
});
```

---

## Konsistensi Check ✅

### ✅ Yang BENAR (Digunakan di Project):

-   `middleware(['auth', 'role:admin'])`
-   `middleware(['auth', 'role:staff'])`
-   `middleware(['auth'])` untuk endpoint yang boleh diakses semua authenticated users

### ❌ Yang TIDAK Digunakan (Untuk Konsistensi):

-   ~~`Gate::define('isAdmin', ...)`~~
-   ~~`$this->authorize('isAdmin')`~~
-   ~~`middleware(['can:isAdmin'])`~~
-   ~~`@can('isAdmin')` di Blade~~
-   ~~`if (auth()->user()->role === 'admin')` di controller~~

**Catatan:** Conditional rendering di Blade (`@if (auth()->user()->role === 'admin')`) masih OK untuk UI purposes, tapi **route protection harus pakai middleware**.

---

## Verification Checklist

| Component       | File             | Middleware Used | Status |
| --------------- | ---------------- | --------------- | ------ |
| Admin Dashboard | `routes/web.php` | `role:admin`    | ✅     |
| Admin Geofence  | `routes/web.php` | `role:admin`    | ✅     |
| Staff Absen     | `routes/web.php` | `role:staff`    | ✅     |
| API Geofence    | `routes/web.php` | `auth` only     | ✅     |
| Settings Pages  | `routes/web.php` | `auth` only     | ✅     |

**Kesimpulan:** Semua route protection konsisten menggunakan middleware `role:{role}`.

---

## Testing Role-Based Access

### Test 1: Admin Access

**Login:** `admin@demo.test` / `password`

✅ **Should Access:**

-   `/admin/dashboard`
-   `/admin/geofence`
-   `/api/geofence/active`
-   `/settings/*`

❌ **Should Deny (403):**

-   `/staff/absen`

### Test 2: Staff Access

**Login:** `staff@demo.test` / `password`

✅ **Should Access:**

-   `/staff/absen`
-   `/api/geofence/active`
-   `/settings/*`

❌ **Should Deny (403):**

-   `/admin/dashboard`
-   `/admin/geofence`

### Test 3: Unauthenticated

❌ **Should Redirect to Login:**

-   `/admin/*`
-   `/staff/*`
-   `/api/*`
-   `/settings/*`

---

## Untuk Laporan Skripsi

Ketika menulis laporan, Anda bisa dengan yakin menyatakan:

> **"Sistem menggunakan Role-Based Access Control (RBAC) dengan custom middleware `role:{role}` yang didaftarkan di `bootstrap/app.php`. Middleware ini memvalidasi role user pada setiap request ke route yang dilindungi."**

**Keuntungan Pendekatan Ini:**

1. **Konsisten** - Satu pattern untuk semua proteksi
2. **Mudah Diaudit** - Tinggal lihat di `routes/web.php`
3. **Type-Safe** - Parameter role sebagai string yang eksplisit
4. **Scalable** - Mudah tambahkan role baru (misal: `supervisor`, `manager`)

---

## Future-Proof: Jika Perlu Role Tambahan

Jika suatu saat perlu role baru (misal: `supervisor`):

**1. Update Migration/Enum:**

```php
// Jika pakai enum
$table->enum('role', ['admin', 'staff', 'supervisor'])->default('staff');
```

**2. Tambah Route Group:**

```php
Route::middleware(['auth', 'role:supervisor'])->prefix('supervisor')->name('supervisor.')->group(function () {
    Route::view('/dashboard', 'supervisor.dashboard')->name('dashboard');
});
```

**3. Update Redirect Logic:**

```php
// FortifyServiceProvider.php
return match($user->role) {
    'admin' => redirect('/admin/dashboard'),
    'staff' => redirect('/staff/absen'),
    'supervisor' => redirect('/supervisor/dashboard'),
    default => redirect('/staff/absen'),
};
```

**Tidak Perlu:**

-   ❌ Update middleware class
-   ❌ Define Gate baru
-   ❌ Buat policy baru

---

## Summary

✅ **Middleware Strategy:** Custom `role:{role}` middleware  
✅ **Implementation:** Konsisten di semua routes  
✅ **No Mixed Patterns:** Tidak ada Gate/Policy/can:  
✅ **Well-Documented:** Mudah dijelaskan di skripsi  
✅ **Tested:** Admin dan Staff access verified

**Status:** KONSISTEN & PRODUCTION-READY 🎉
