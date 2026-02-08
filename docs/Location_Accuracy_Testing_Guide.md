# 🧪 GPS & Location Accuracy Testing Guide

Panduan lengkap untuk melakukan testing akurasi pengambilan lokasi pada sistem absensi AbsenKi.

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Testing Tools](#testing-tools)
3. [Unit Testing](#unit-testing)
4. [Feature Testing](#feature-testing)
5. [Manual Testing](#manual-testing)
6. [Performance Testing](#performance-testing)
7. [Real-World Testing](#real-world-testing)
8. [Troubleshooting](#troubleshooting)

---

## Overview

Sistem AbsenKi menggunakan geolocation API untuk mendapatkan koordinat GPS staff dan memvalidasi apakah staff berada di dalam area kantor (geofence). Akurasi GPS sangat penting untuk mencegah:

-   ❌ False positives (staff di luar area tapi terdeteksi di dalam)
-   ❌ False negatives (staff di dalam area tapi terdeteksi di luar)
-   ❌ GPS drift (koordinat "lompat-lompat")

### GPS Accuracy Levels

| Accuracy  | Method            | Typical Value | Use Case        |
| --------- | ----------------- | ------------- | --------------- |
| Excellent | GPS (clear sky)   | ±5-10m        | Outdoor absensi |
| Good      | GPS (partial sky) | ±10-20m       | Semi-outdoor    |
| Fair      | GPS + WiFi        | ±20-50m       | Indoor office   |
| Poor      | WiFi only         | ±50-200m      | Deep indoor     |
| Very Poor | Cell tower        | ±200-1000m    | Basement        |

---

## Testing Tools

### 1. GPS Accuracy Test Tool (Web-based)

**Location:** `public/gps-test-tool.html`

**Features:**

-   ✅ Single GPS reading
-   ✅ Continuous monitoring (every 5s)
-   ✅ Multiple readings (10x with averaging)
-   ✅ Visual map display
-   ✅ Statistical analysis
-   ✅ Real-time accuracy monitoring

**How to Use:**

```bash
# Access via browser
http://localhost/gps-test-tool.html

# Or in production
https://your-domain.com/gps-test-tool.html
```

**Steps:**

1. Open tool in browser (Chrome/Firefox recommended)
2. Allow location permission when prompted
3. Click "Get Single Reading" for one-time test
4. Or click "Start Continuous" for ongoing monitoring
5. Review statistics and recommendations

### 2. PHPUnit Tests

**Location:**

-   `tests/Unit/GeofenceLocationTest.php` - Algorithm testing
-   `tests/Feature/GeofenceValidationTest.php` - Integration testing

**How to Run:**

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Unit/GeofenceLocationTest.php
php artisan test tests/Feature/GeofenceValidationTest.php

# Run with coverage
php artisan test --coverage

# Run specific test method
php artisan test --filter=test_point_inside_simple_square
```

### 3. Browser DevTools

**Chrome DevTools - Sensors Tab:**

1. Open DevTools (F12)
2. Click "..." → More tools → Sensors
3. Under "Location", select or enter custom coordinates
4. Test different locations without physically moving

**Firefox:**

1. Type `about:config` in address bar
2. Search for `geo.enabled`
3. Use browser extensions like "Location Guard"

---

## Unit Testing

### Test Point-in-Polygon Algorithm

File: `tests/Unit/GeofenceLocationTest.php`

**Test Scenarios:**

#### 1. Basic Geometry Tests

```php
// Test center point (should be inside)
$polygon = [[0,0], [10,0], [10,10], [0,10], [0,0]];
$point = [5, 5];
assertTrue(pointInPolygon($point, $polygon));

// Test outside point
$point = [20, 20];
assertFalse(pointInPolygon($point, $polygon));
```

#### 2. Edge Cases

```php
// Point on boundary
$point = [0, 5]; // On left edge
// Result may vary - ensure it returns boolean

// Point very close to edge (GPS drift simulation)
$point = [0.1, 5]; // Just inside
assertTrue(pointInPolygon($point, $polygon));
```

#### 3. Real Coordinates

```php
// Jakarta office example
$polygon = [
    [106.8446, -6.2078], // NW corner
    [106.8466, -6.2078], // NE corner
    [106.8466, -6.2098], // SE corner
    [106.8446, -6.2098], // SW corner
    [106.8446, -6.2078]  // Close polygon
];

$insidePoint = [106.8456, -6.2088]; // Office center
assertTrue(pointInPolygon($insidePoint, $polygon));
```

#### 4. GPS Accuracy Simulation

```php
// Simulate GPS drift ±10 meters
$centerLat = -6.2088;
$centerLng = 106.8456;

for ($i = 0; $i < 10; $i++) {
    $driftLat = rand(-10, 10) / 111000; // ±10m
    $driftLng = rand(-10, 10) / 111000;

    $testLat = $centerLat + $driftLat;
    $testLng = $centerLng + $driftLng;

    // Should still be inside with 10m drift
    assertTrue(pointInPolygon([$testLng, $testLat], $polygon));
}
```

**Run Unit Tests:**

```bash
php artisan test tests/Unit/GeofenceLocationTest.php

# Output:
# ✓ test_point_inside_simple_square
# ✓ test_point_outside_simple_square
# ✓ test_point_on_edge
# ✓ test_real_office_coordinates_jakarta
# ✓ test_gps_accuracy_near_boundary
# ✓ test_complex_irregular_polygon
# ✓ test_multiple_points_accuracy
# ✓ test_haversine_distance_calculation
# ✓ test_gps_accuracy_scenarios
```

---

## Feature Testing

### Test Complete Geofence Validation Flow

File: `tests/Feature/GeofenceValidationTest.php`

**Test Scenarios:**

#### 1. Inside Geofence

```php
public function test_staff_can_check_location_inside_geofence()
{
    $this->actingAs($staffUser);

    Livewire::test(Absen::class)
        ->call('checkLocation', -6.2088, 106.8456)
        ->assertSet('geoStatus', 'inside')
        ->assertSee('di dalam area kantor');
}
```

#### 2. Outside Geofence

```php
public function test_staff_location_outside_geofence_is_detected()
{
    $this->actingAs($staffUser);

    // Surabaya coordinates (far from Jakarta)
    Livewire::test(Absen::class)
        ->call('checkLocation', -7.2575, 112.7521)
        ->assertSet('geoStatus', 'outside')
        ->assertSee('di luar area kantor');
}
```

#### 3. Near Boundary

```php
public function test_location_near_boundary_with_good_accuracy()
{
    $testCases = [
        [-6.2079, 106.8456, 'Just inside top edge', 'inside'],
        [-6.2097, 106.8456, 'Just inside bottom edge', 'inside'],
        // More test cases...
    ];

    foreach ($testCases as [$lat, $lng, $desc, $expected]) {
        Livewire::test(Absen::class)
            ->call('checkLocation', $lat, $lng)
            ->assertSet('geoStatus', $expected);
    }
}
```

#### 4. GPS Accuracy Scenarios

```php
public function test_realistic_gps_accuracy_scenarios()
{
    $scenarios = [
        ['accuracy' => 5, 'location' => [-6.2088, 106.8456], 'expected' => 'inside'],
        ['accuracy' => 15, 'location' => [-6.2088, 106.8456], 'expected' => 'inside'],
        ['accuracy' => 50, 'location' => [-6.2200, 106.8456], 'expected' => 'outside'],
    ];

    foreach ($scenarios as $scenario) {
        Livewire::test(Absen::class)
            ->call('checkLocation', $scenario['location'][0], $scenario['location'][1])
            ->assertSet('geoStatus', $scenario['expected']);
    }
}
```

**Run Feature Tests:**

```bash
php artisan test tests/Feature/GeofenceValidationTest.php

# Output:
# ✓ test_staff_can_check_location_inside_geofence
# ✓ test_staff_location_outside_geofence_is_detected
# ✓ test_location_near_boundary_with_good_accuracy
# ✓ test_location_with_poor_gps_accuracy
# ✓ test_no_active_geofence
# ✓ test_multiple_geofences_only_active_used
# ✓ test_realistic_gps_accuracy_scenarios
# ✓ test_edge_cases_and_error_handling
```

---

## Manual Testing

### Browser-based Testing

#### 1. Desktop Testing

**Prerequisites:**

-   Modern browser (Chrome/Firefox)
-   HTTPS connection (or localhost)
-   Location permission granted

**Steps:**

1. **Login as staff:**

    ```
    Email: staff@demo.test
    Password: password
    ```

2. **Navigate to absen page:**

    ```
    http://localhost/staff/absen
    ```

3. **Click "Cek Lokasi Saya"**

4. **Observe results:**

    - Latitude & Longitude displayed
    - Accuracy value (±Xm)
    - Status: "Di dalam area" or "Di luar area"

5. **Check browser console:**
    ```javascript
    // Expected logs:
    📍 Attempt 1: WiFi positioning (fast mode)...
    ✅ Location obtained (attempt 1): ±50 meters
    🗺️ Updating location map...
    ```

#### 2. Mobile Testing

**Android:**

1. Enable Location Services:

    ```
    Settings → Location → Mode → High Accuracy
    ```

2. Open browser (Chrome recommended)

3. Grant location permission

4. Test in different scenarios:
    - ✅ Outdoor (clear sky)
    - ✅ Indoor (near window)
    - ✅ Indoor (deep inside)
    - ✅ Moving vs stationary

**iOS:**

1. Enable Location Services:

    ```
    Settings → Privacy → Location Services → ON
    Settings → Safari → Location → Allow
    ```

2. Open Safari

3. Test similar scenarios as Android

#### 3. GPS Accuracy Test Scenarios

| Scenario  | Location           | Expected Accuracy | Expected Result |
| --------- | ------------------ | ----------------- | --------------- |
| Best case | Outdoor, clear sky | ±5-10m            | Inside geofence |
| Good      | Near window        | ±10-20m           | Inside geofence |
| Fair      | Indoor office      | ±20-50m           | Inside geofence |
| Poor      | Deep indoor        | ±50-200m          | May vary        |
| Worst     | Basement           | ±200m+            | Unreliable      |

---

## Performance Testing

### 1. Response Time

Test how long it takes to get GPS location:

```javascript
const startTime = Date.now();

navigator.geolocation.getCurrentPosition(
    (position) => {
        const duration = Date.now() - startTime;
        console.log(`GPS fix took ${duration}ms`);
    },
    errorCallback,
    { enableHighAccuracy: true, timeout: 10000 }
);

// Expected results:
// Fast (WiFi): 1-3 seconds
// GPS: 5-15 seconds
```

### 2. Accuracy Consistency

Test multiple readings for consistency:

```javascript
const readings = [];

for (let i = 0; i < 10; i++) {
    // Get reading
    const position = await getPosition();
    readings.push({
        lat: position.coords.latitude,
        lng: position.coords.longitude,
        accuracy: position.coords.accuracy,
    });

    await sleep(2000); // Wait 2s between readings
}

// Calculate statistics
const avgAccuracy =
    readings.reduce((sum, r) => sum + r.accuracy, 0) / readings.length;
const variance = calculateVariance(readings);

console.log(`Average accuracy: ±${avgAccuracy}m`);
console.log(`Variance: ${variance}`);
```

### 3. Load Testing

Run feature test 100 times to measure performance:

```bash
php artisan test tests/Feature/GeofenceValidationTest.php::test_geofence_validation_performance

# Expected output:
# ✓ 100 location checks completed in 8.5 seconds
# ✓ Average time per check: 85ms
```

---

## Real-World Testing

### Field Testing Checklist

#### Preparation

-   [ ] Setup geofence with appropriate radius
-   [ ] Ensure HTTPS or localhost
-   [ ] Grant location permissions
-   [ ] Charge device (GPS drains battery)

#### Test Locations

1. **Inside Office (Center)**

    - Stand at office center
    - Expected: "Di dalam area" with good accuracy
    - Take 5 readings
    - Record average accuracy

2. **Inside Office (Near Boundary)**

    - Stand ~5-10m from geofence edge
    - Expected: Still "Di dalam area"
    - Check for false negatives

3. **Outside Office (Just Outside)**

    - Stand ~10-20m outside boundary
    - Expected: "Di luar area"
    - Check for false positives

4. **Outside Office (Far Away)**
    - Stand 500m+ away
    - Expected: "Di luar area" consistently
    - Should never show inside

#### Different Times of Day

Test at different times as satellite positions change:

-   [ ] Morning (6-9 AM)
-   [ ] Midday (12-2 PM)
-   [ ] Evening (5-7 PM)
-   [ ] Night (if 24h operation)

#### Different Weather Conditions

-   [ ] Clear sky (best GPS)
-   [ ] Cloudy (may affect signal)
-   [ ] Heavy rain (signal degradation)

#### Different Devices

Test on multiple devices to ensure consistency:

-   [ ] Android (flagship phone)
-   [ ] Android (budget phone)
-   [ ] iPhone (latest)
-   [ ] iPhone (older model)
-   [ ] Tablet
-   [ ] ❌ Laptop (not recommended for absensi)

### Recording Test Results

Create a test log:

```
Date: 2024-01-15
Time: 10:30 AM
Location: Office Center
Device: Samsung Galaxy S21
Weather: Clear

Test Results:
- Reading 1: ±8m (inside) ✅
- Reading 2: ±6m (inside) ✅
- Reading 3: ±12m (inside) ✅
- Reading 4: ±7m (inside) ✅
- Reading 5: ±9m (inside) ✅

Average: ±8.4m
Result: PASS
Notes: Excellent GPS accuracy, outdoor location
```

---

## Troubleshooting

### Common Issues

#### Issue 1: "Lokasi lari-lari" (GPS jumping)

**Symptoms:**

-   Coordinates jump around significantly
-   Multiple readings show 100m+ difference

**Diagnosis:**

```javascript
// Check GPS options
console.log(navigator.geolocation);

// Expected settings:
{
    enableHighAccuracy: true,
    timeout: 10000,
    maximumAge: 0
}
```

**Solutions:**

-   ✅ Ensure `enableHighAccuracy: true`
-   ✅ Set `maximumAge: 0` (no caching)
-   ✅ Increase timeout to 10-15 seconds
-   ✅ Move to more open area
-   ✅ Restart device GPS

#### Issue 2: Always showing "Di luar area"

**Diagnosis:**

```php
// Check geofence in database
SELECT * FROM geofences WHERE is_active = 1;

// Check polygon coordinates
// Ensure coordinates are [lng, lat] not [lat, lng]
```

**Solutions:**

-   ✅ Verify geofence is active
-   ✅ Check polygon covers office location
-   ✅ Increase geofence radius if too small
-   ✅ Verify coordinate order (lng, lat)

#### Issue 3: Slow GPS fix

**Symptoms:**

-   Takes 30+ seconds to get location
-   Frequent timeouts

**Solutions:**

-   ✅ Enable A-GPS (assisted GPS) on device
-   ✅ Ensure internet connection available
-   ✅ Clear GPS cache (device settings)
-   ✅ Update device firmware
-   ✅ Test outdoors first

#### Issue 4: Poor accuracy indoors

**Expected Behavior:**
Indoor GPS is inherently less accurate (±30-100m)

**Solutions:**

-   ✅ Increase geofence radius (100m+)
-   ✅ Use WiFi positioning fallback
-   ✅ Implement multiple reading averaging
-   ✅ Consider beacon-based indoor positioning

### Debug Mode

Enable debug logging in browser console:

```javascript
// In absen.blade.php
navigator.geolocation.getCurrentPosition(
    (position) => {
        console.log("📍 GPS Debug Info:");
        console.log("Latitude:", position.coords.latitude);
        console.log("Longitude:", position.coords.longitude);
        console.log("Accuracy:", position.coords.accuracy);
        console.log("Altitude:", position.coords.altitude);
        console.log("Speed:", position.coords.speed);
        console.log("Heading:", position.coords.heading);
        console.log("Timestamp:", new Date(position.timestamp));
    },
    errorCallback,
    geoOptions
);
```

### Performance Monitoring

Monitor GPS performance over time:

```sql
-- Add GPS accuracy column to attendances table
ALTER TABLE attendances
ADD COLUMN gps_accuracy DECIMAL(8,2) NULL COMMENT 'GPS accuracy in meters';

-- Query average accuracy per day
SELECT
    DATE(created_at) as date,
    AVG(gps_accuracy) as avg_accuracy,
    MIN(gps_accuracy) as best_accuracy,
    MAX(gps_accuracy) as worst_accuracy,
    COUNT(*) as total_readings
FROM attendances
WHERE gps_accuracy IS NOT NULL
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

---

## Best Practices & Recommendations

### For Developers

1. **Always test on real devices**

    - Emulators don't accurately simulate GPS behavior
    - Test on both Android and iOS

2. **Use appropriate timeouts**

    - 10 seconds for outdoor
    - 15 seconds for indoor

3. **Handle errors gracefully**

    - Provide clear user feedback
    - Log errors for debugging

4. **Monitor accuracy values**
    - Store accuracy in database
    - Alert on poor accuracy trends

### For End Users (Staff)

1. **Enable GPS on device**

    - Android: Settings → Location → High Accuracy
    - iOS: Settings → Privacy → Location Services

2. **Grant location permission**

    - Allow "Always" or "While Using App"

3. **Use in appropriate location**

    - Outdoor: Best accuracy
    - Near window: Good accuracy
    - Deep indoor: Poor accuracy

4. **Wait for GPS fix**
    - Don't move during location check
    - Wait 5-10 seconds for accurate reading

### For Admins

1. **Set appropriate geofence radius**

    - Outdoor office: 50-80m
    - Indoor office: 80-150m
    - Mixed environment: 100-150m

2. **Monitor GPS performance**

    - Check average accuracy daily
    - Adjust geofence if needed

3. **Collect user feedback**
    - Are false positives/negatives occurring?
    - Is GPS taking too long?

---

## Summary Checklist

### Before Deployment

-   [ ] Run all unit tests
-   [ ] Run all feature tests
-   [ ] Test with GPS Test Tool
-   [ ] Test on real devices (Android + iOS)
-   [ ] Test in different locations (indoor/outdoor)
-   [ ] Set appropriate geofence radius
-   [ ] Configure GPS timeout (10-15s)
-   [ ] Enable error logging
-   [ ] Document expected accuracy

### After Deployment

-   [ ] Monitor GPS accuracy metrics
-   [ ] Collect user feedback
-   [ ] Analyze false positives/negatives
-   [ ] Adjust geofence radius if needed
-   [ ] Update documentation
-   [ ] Regular testing (monthly)

---

## Support & Resources

### Documentation

-   [GPS_Location_Guide.md](GPS_Location_Guide.md) - Troubleshooting GPS issues
-   [Story_004.md](Story_004.md) - Original feature specification
-   [Story_004_Implementation.md](Story_004_Implementation.md) - Implementation details

### Tools

-   GPS Test Tool: `/gps-test-tool.html`
-   Browser DevTools: Chrome Sensors tab
-   PHPUnit Tests: `tests/Unit/` and `tests/Feature/`

### External Resources

-   [MDN Geolocation API](https://developer.mozilla.org/en-US/docs/Web/API/Geolocation_API)
-   [W3C Geolocation Specification](https://www.w3.org/TR/geolocation/)
-   [Turf.js Documentation](https://turfjs.org/)

---

## Conclusion

Testing akurasi lokasi adalah proses yang berkelanjutan. Gunakan kombinasi dari:

1. **Automated tests** - Unit & feature tests untuk regression
2. **Manual testing** - GPS Test Tool untuk real-time monitoring
3. **Field testing** - Real-world testing dengan berbagai kondisi
4. **Performance monitoring** - Track metrics over time

Dengan testing yang comprehensive, sistem absensi akan lebih reliable dan akurat! 🎯
