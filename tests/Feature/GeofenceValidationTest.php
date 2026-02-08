<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Geofence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Staff\Absen;

/**
 * Feature tests untuk validasi geofence dengan koordinat real
 * Testing integrasi lengkap sistem lokasi dengan database
 *
 * WARNING: RefreshDatabase temporarily disabled to prevent production data loss
 * TODO: Fix phpunit.xml environment variables not being applied correctly
 */
class GeofenceValidationTest extends TestCase
{
    // use RefreshDatabase; // DISABLED - causing production database to be wiped!

    private User $staffUser;
    private Geofence $activeGeofence;

    protected function setUp(): void
    {
        parent::setUp();

        // Create staff user
        $this->staffUser = User::factory()->create([
            'name' => 'Test Staff',
            'email' => 'staff@test.com',
            'role' => 'staff',
        ]);

        // Create active geofence (Jakarta office example)
        $this->activeGeofence = Geofence::create([
            'name' => 'Kantor Jakarta',
            'is_active' => true,
            'polygon_geojson' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [106.8446, -6.2078], // NW
                    [106.8466, -6.2078], // NE
                    [106.8466, -6.2098], // SE
                    [106.8446, -6.2098], // SW
                    [106.8446, -6.2078]  // Close polygon
                ]]
            ]
        ]);
    }

    /**
     * Test: Staff can check location inside geofence
     */
    public function test_staff_can_check_location_inside_geofence(): void
    {
        $this->actingAs($this->staffUser);

        // Coordinates inside the geofence (center)
        $lat = -6.2088;
        $lng = 106.8456;

        Livewire::test(Absen::class)
            ->call('checkLocation', $lat, $lng)
            ->assertSet('geoStatus', 'inside')
            ->assertSet('lat', $lat)
            ->assertSet('lng', $lng)
            ->assertSee('di dalam area kantor');
    }

    /**
     * Test: Staff location outside geofence is detected
     */
    public function test_staff_location_outside_geofence_is_detected(): void
    {
        $this->actingAs($this->staffUser);

        // Coordinates far outside (different city)
        $lat = -7.2575; // Surabaya
        $lng = 112.7521;

        Livewire::test(Absen::class)
            ->call('checkLocation', $lat, $lng)
            ->assertSet('geoStatus', 'outside')
            ->assertSee('di luar area kantor');
    }

    /**
     * Test: Location check near boundary with good GPS accuracy
     */
    public function test_location_near_boundary_with_good_accuracy(): void
    {
        $this->actingAs($this->staffUser);

        // Point just inside the boundary (±5 meters from edge)
        // With good GPS accuracy (±5-10m), should be reliable
        $testCases = [
            // [lat, lng, description, expected_status]
            [-6.2079, 106.8456, 'Just inside top edge', 'inside'],
            [-6.2097, 106.8456, 'Just inside bottom edge', 'inside'],
            [-6.2088, 106.8447, 'Just inside left edge', 'inside'],
            [-6.2088, 106.8465, 'Just inside right edge', 'inside'],
        ];

        foreach ($testCases as [$lat, $lng, $description, $expectedStatus]) {
            Livewire::test(Absen::class)
                ->call('checkLocation', $lat, $lng)
                ->assertSet(
                    'geoStatus',
                    $expectedStatus,
                    sprintf('Failed for %s at [%s, %s]', $description, $lat, $lng)
                );
        }
    }

    /**
     * Test: Location with poor GPS accuracy simulation
     */
    public function test_location_with_poor_gps_accuracy(): void
    {
        $this->actingAs($this->staffUser);

        // Simulate multiple readings with GPS drift (±20-50m)
        $centerLat = -6.2088;
        $centerLng = 106.8456;

        // Take 5 readings with simulated GPS drift
        $readings = [];
        for ($i = 0; $i < 5; $i++) {
            // Add random offset (±20m ≈ ±0.0002 degrees)
            $driftLat = (rand(-20, 20) / 111000);
            $driftLng = (rand(-20, 20) / 111000);

            $readings[] = [
                'lat' => $centerLat + $driftLat,
                'lng' => $centerLng + $driftLng,
            ];
        }

        // All readings should still be inside (center point with 20m drift)
        foreach ($readings as $index => $reading) {
            Livewire::test(Absen::class)
                ->call('checkLocation', $reading['lat'], $reading['lng'])
                ->assertSet(
                    'geoStatus',
                    'inside',
                    sprintf('Reading %d with GPS drift should still be inside', $index + 1)
                );
        }
    }

    /**
     * Test: No active geofence scenario
     */
    public function test_no_active_geofence(): void
    {
        $this->actingAs($this->staffUser);

        // Deactivate geofence
        $this->activeGeofence->update(['is_active' => false]);

        Livewire::test(Absen::class)
            ->call('checkLocation', -6.2088, 106.8456)
            ->assertSet('geoStatus', 'outside')
            ->assertSee('Tidak ada geofence aktif');
    }

    /**
     * Test: Multiple geofences (only active one is used)
     */
    public function test_multiple_geofences_only_active_used(): void
    {
        $this->actingAs($this->staffUser);

        // Create inactive geofence at different location
        Geofence::create([
            'name' => 'Kantor Bandung (Inactive)',
            'is_active' => false,
            'polygon_geojson' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [107.6, -6.9],
                    [107.62, -6.9],
                    [107.62, -6.92],
                    [107.6, -6.92],
                    [107.6, -6.9]
                ]]
            ]
        ]);

        // Test with Jakarta coordinates (active geofence)
        Livewire::test(Absen::class)
            ->call('checkLocation', -6.2088, 106.8456)
            ->assertSet('geoStatus', 'inside');

        // Test with Bandung coordinates (inactive geofence)
        Livewire::test(Absen::class)
            ->call('checkLocation', -6.91, 107.61)
            ->assertSet('geoStatus', 'outside'); // Should be outside because Bandung geofence is inactive
    }

    /**
     * Test: Realistic GPS accuracy scenarios
     */
    public function test_realistic_gps_accuracy_scenarios(): void
    {
        $this->actingAs($this->staffUser);

        $scenarios = [
            [
                'name' => 'Excellent GPS (outdoor, clear sky)',
                'accuracy' => 5, // ±5 meters
                'location' => [-6.2088, 106.8456], // center
                'expected' => 'inside',
                'description' => 'Staff with excellent GPS accuracy at office center'
            ],
            [
                'name' => 'Good GPS (outdoor)',
                'accuracy' => 15, // ±15 meters
                'location' => [-6.2088, 106.8456],
                'expected' => 'inside',
                'description' => 'Staff with good GPS accuracy at office center'
            ],
            [
                'name' => 'Fair GPS (near window)',
                'accuracy' => 30, // ±30 meters
                'location' => [-6.2088, 106.8456],
                'expected' => 'inside',
                'description' => 'Staff with fair GPS accuracy at office center'
            ],
            [
                'name' => 'Poor GPS but far from office',
                'accuracy' => 50, // ±50 meters
                'location' => [-6.2200, 106.8456], // ~1.2km away
                'expected' => 'outside',
                'description' => 'Staff far from office, even with poor GPS should be outside'
            ],
        ];

        foreach ($scenarios as $scenario) {
            Livewire::test(Absen::class)
                ->call('checkLocation', $scenario['location'][0], $scenario['location'][1])
                ->assertSet(
                    'geoStatus',
                    $scenario['expected'],
                    sprintf(
                        '%s: %s (accuracy: ±%dm)',
                        $scenario['name'],
                        $scenario['description'],
                        $scenario['accuracy']
                    )
                );
        }
    }

    /**
     * Test: Edge cases and error handling
     */
    public function test_edge_cases_and_error_handling(): void
    {
        $this->actingAs($this->staffUser);

        // Test with invalid coordinates
        $invalidCases = [
            [999, 999, 'Invalid coordinates (out of range)'],
            [0, 0, 'Null island (valid but unlikely)'],
            [-90, 180, 'Valid but extreme coordinates'],
        ];

        foreach ($invalidCases as [$lat, $lng, $description]) {
            Livewire::test(Absen::class)
                ->call('checkLocation', $lat, $lng)
                ->assertSet('lat', $lat)
                ->assertSet('lng', $lng)
                // Should return outside for any coordinates not in polygon
                ->assertSet('geoStatus', 'outside', $description);
        }
    }

    /**
     * Test: Real-world office location (multiple cities)
     */
    public function test_real_world_office_locations(): void
    {
        $this->actingAs($this->staffUser);

        // Test various Indonesian cities coordinates
        $locations = [
            ['lat' => -6.2088, 'lng' => 106.8456, 'city' => 'Jakarta', 'inside_active' => true],
            ['lat' => -7.2575, 'lng' => 112.7521, 'city' => 'Surabaya', 'inside_active' => false],
            ['lat' => -6.9175, 'lng' => 107.6191, 'city' => 'Bandung', 'inside_active' => false],
            ['lat' => -8.6705, 'lng' => 115.2126, 'city' => 'Bali', 'inside_active' => false],
            ['lat' => 3.5952, 'lng' => 98.6722, 'city' => 'Medan', 'inside_active' => false],
        ];

        foreach ($locations as $location) {
            $expectedStatus = $location['inside_active'] ? 'inside' : 'outside';

            Livewire::test(Absen::class)
                ->call('checkLocation', $location['lat'], $location['lng'])
                ->assertSet(
                    'geoStatus',
                    $expectedStatus,
                    sprintf(
                        'Testing location: %s should be %s',
                        $location['city'],
                        $expectedStatus
                    )
                );
        }
    }

    /**
     * Test: API endpoint for active geofence
     */
    public function test_active_geofence_api_endpoint(): void
    {
        // API endpoint requires authentication
        $this->actingAs($this->staffUser);

        $response = $this->getJson('/api/geofence/active');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'active',
                'name',
                'polygon'
            ])
            ->assertJson([
                'active' => true,
                'name' => 'Kantor Jakarta'
            ]);

        $data = $response->json();
        $this->assertEquals('Polygon', $data['polygon']['type']);
        $this->assertIsArray($data['polygon']['coordinates']);
    }

    /**
     * Test: Geofence validation performance
     */
    public function test_geofence_validation_performance(): void
    {
        $this->actingAs($this->staffUser);

        // Measure time for 100 location checks
        $startTime = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            // Random points around office
            $lat = -6.2088 + (rand(-100, 100) / 10000);
            $lng = 106.8456 + (rand(-100, 100) / 10000);

            Livewire::test(Absen::class)
                ->call('checkLocation', $lat, $lng);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Should complete 100 checks in under 10 seconds
        $this->assertLessThan(
            10,
            $duration,
            sprintf('100 location checks took %.2f seconds (should be under 10s)', $duration)
        );

        // Average time per check should be under 100ms
        $avgTime = ($duration / 100) * 1000; // ms
        $this->assertLessThan(
            100,
            $avgTime,
            sprintf('Average time per check: %.2fms (should be under 100ms)', $avgTime)
        );
    }

    /**
     * Test: Distance calculation accuracy
     */
    public function test_distance_calculation_accuracy(): void
    {
        // Test haversine distance calculation
        $distances = [
            // [lat1, lng1, lat2, lng2, expected_meters, tolerance]
            [-6.2088, 106.8456, -6.2088, 106.8466, 111, 15], // ~111m east (0.001 degree lng at this latitude)
            [-6.2088, 106.8456, -6.2098, 106.8456, 111, 15], // ~111m south (0.001 degree lat)
            [-6.2088, 106.8456, -6.2088, 106.8456, 0, 1],    // Same point
        ];

        foreach ($distances as [$lat1, $lng1, $lat2, $lng2, $expected, $tolerance]) {
            $calculated = $this->haversineDistance($lat1, $lng1, $lat2, $lng2);

            $this->assertEqualsWithDelta(
                $expected,
                $calculated,
                $tolerance,
                sprintf(
                    'Distance from [%s,%s] to [%s,%s] should be approximately %dm (got %.2fm)',
                    $lat1,
                    $lng1,
                    $lat2,
                    $lng2,
                    $expected,
                    $calculated
                )
            );
        }
    }

    /**
     * Helper: Calculate haversine distance
     */
    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
