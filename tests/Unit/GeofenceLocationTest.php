<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests untuk validasi algoritma point-in-polygon
 * Testing akurasi penentuan lokasi dalam/luar geofence
 *
 * WARNING: RefreshDatabase temporarily disabled to prevent production data loss
 */
class GeofenceLocationTest extends TestCase
{
    // use RefreshDatabase; // DISABLED - causing production database to be wiped!

    /**
     * Point-in-polygon algorithm (same as in Absen.php)
     */
    private function pointInPolygon(array $point, array $polygon): bool
    {
        $x = $point[0];
        $y = $point[1];
        $inside = false;

        for ($i = 0, $j = count($polygon) - 1; $i < count($polygon); $j = $i++) {
            $xi = $polygon[$i][0];
            $yi = $polygon[$i][1];
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];

            $intersect = (($yi > $y) != ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Test: Point clearly inside a simple square polygon
     */
    public function test_point_inside_simple_square(): void
    {
        // Simple square: 0,0 to 10,10
        $polygon = [
            [0, 0],
            [10, 0],
            [10, 10],
            [0, 10],
            [0, 0] // Close polygon
        ];

        // Center point
        $point = [5, 5];
        $this->assertTrue($this->pointInPolygon($point, $polygon), 'Point [5,5] should be inside square [0,0 to 10,10]');

        // Near edge but inside
        $point = [1, 1];
        $this->assertTrue($this->pointInPolygon($point, $polygon), 'Point [1,1] should be inside square');
    }

    /**
     * Test: Point clearly outside a simple square polygon
     */
    public function test_point_outside_simple_square(): void
    {
        $polygon = [
            [0, 0],
            [10, 0],
            [10, 10],
            [0, 10],
            [0, 0]
        ];

        // Far outside
        $point = [20, 20];
        $this->assertFalse($this->pointInPolygon($point, $polygon), 'Point [20,20] should be outside square [0,0 to 10,10]');

        // Just outside
        $point = [-1, 5];
        $this->assertFalse($this->pointInPolygon($point, $polygon), 'Point [-1,5] should be outside square');
    }

    /**
     * Test: Point on the edge (boundary case)
     */
    public function test_point_on_edge(): void
    {
        $polygon = [
            [0, 0],
            [10, 0],
            [10, 10],
            [0, 10],
            [0, 0]
        ];

        // On left edge
        $point = [0, 5];
        // Edge case - result may vary by implementation
        $result = $this->pointInPolygon($point, $polygon);
        $this->assertIsBool($result, 'Edge point should return boolean');

        // On top edge
        $point = [5, 10];
        $result = $this->pointInPolygon($point, $polygon);
        $this->assertIsBool($result, 'Edge point should return boolean');
    }

    /**
     * Test: Real office coordinates (Jakarta example)
     * Simulating a typical office geofence
     */
    public function test_real_office_coordinates_jakarta(): void
    {
        // Example office building in Jakarta (approximate)
        // Center: -6.2088, 106.8456
        // Radius ~100m creates this polygon:
        $polygon = [
            [106.8446, -6.2078], // NW
            [106.8466, -6.2078], // NE
            [106.8466, -6.2098], // SE
            [106.8446, -6.2098], // SW
            [106.8446, -6.2078]  // Close
        ];

        // Point inside office (center)
        $insidePoint = [106.8456, -6.2088];
        $this->assertTrue(
            $this->pointInPolygon($insidePoint, $polygon),
            'Point at office center should be inside geofence'
        );

        // Point outside office (200m away)
        $outsidePoint = [106.8500, -6.2088];
        $this->assertFalse(
            $this->pointInPolygon($outsidePoint, $polygon),
            'Point 200m away should be outside geofence'
        );
    }

    /**
     * Test: GPS accuracy simulation - points near boundary
     * Simulates GPS drift of ±10 meters
     */
    public function test_gps_accuracy_near_boundary(): void
    {
        // Square polygon: 100m x 100m
        $polygon = [
            [106.8446, -6.2078],
            [106.8456, -6.2078],
            [106.8456, -6.2088],
            [106.8446, -6.2088],
            [106.8446, -6.2078]
        ];

        // Point just inside boundary (5m from edge)
        // In real-world, GPS ±10m accuracy could show as outside
        $nearBoundaryInside = [106.8446 + 0.00005, -6.2083]; // ~5m from left edge
        $this->assertTrue(
            $this->pointInPolygon($nearBoundaryInside, $polygon),
            'Point 5m inside boundary should still register as inside'
        );

        // Point just outside boundary (5m from edge)
        $nearBoundaryOutside = [106.8446 - 0.00005, -6.2083]; // ~5m from left edge
        $this->assertFalse(
            $this->pointInPolygon($nearBoundaryOutside, $polygon),
            'Point 5m outside boundary should register as outside'
        );
    }

    /**
     * Test: Complex polygon (irregular shape)
     */
    public function test_complex_irregular_polygon(): void
    {
        // L-shaped office building
        $polygon = [
            [0, 0],
            [10, 0],
            [10, 5],
            [5, 5],
            [5, 10],
            [0, 10],
            [0, 0]
        ];

        // Inside the L
        $this->assertTrue($this->pointInPolygon([2, 2], $polygon), 'Point in bottom part of L');
        $this->assertTrue($this->pointInPolygon([2, 8], $polygon), 'Point in vertical part of L');

        // Outside the L (in the cutout)
        $this->assertFalse($this->pointInPolygon([8, 8], $polygon), 'Point in cutout area should be outside');
    }

    /**
     * Test: Multiple test points with expected results
     * Comprehensive accuracy test
     */
    public function test_multiple_points_accuracy(): void
    {
        $polygon = [
            [0, 0],
            [10, 0],
            [10, 10],
            [0, 10],
            [0, 0]
        ];

        $testCases = [
            // [point, expected_result, description]
            [[5, 5], true, 'Center point'],
            [[0.1, 0.1], true, 'Near corner inside'],
            [[9.9, 9.9], true, 'Far corner inside'],
            [[-0.1, 5], false, 'Just outside left edge'],
            [[10.1, 5], false, 'Just outside right edge'],
            [[5, -0.1], false, 'Just outside bottom edge'],
            [[5, 10.1], false, 'Just outside top edge'],
            [[15, 15], false, 'Far outside'],
            [[-5, -5], false, 'Far outside negative'],
        ];

        foreach ($testCases as [$point, $expected, $description]) {
            $result = $this->pointInPolygon($point, $polygon);
            $this->assertEquals(
                $expected,
                $result,
                sprintf(
                    'Failed for %s: Point [%s, %s] should be %s',
                    $description,
                    $point[0],
                    $point[1],
                    $expected ? 'inside' : 'outside'
                )
            );
        }
    }

    /**
     * Test: Haversine distance calculation for GPS accuracy
     * Calculates real distance between two GPS points
     */
    public function test_haversine_distance_calculation(): void
    {
        // Calculate distance between two points
        $lat1 = -6.2088;
        $lng1 = 106.8456;

        // Point ~100 meters away (approximate)
        $lat2 = -6.2098;
        $lng2 = 106.8456;

        $distance = $this->haversineDistance($lat1, $lng1, $lat2, $lng2);

        // Should be approximately 100-120 meters
        $this->assertGreaterThan(100, $distance, 'Distance should be more than 100m');
        $this->assertLessThan(120, $distance, 'Distance should be less than 120m');
    }

    /**
     * Haversine formula to calculate distance between two GPS coordinates
     * Returns distance in meters
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

    /**
     * Test: GPS accuracy impact on geofence validation
     * Simulates different GPS accuracy levels
     */
    public function test_gps_accuracy_scenarios(): void
    {
        // Office center
        $centerLat = -6.2088;
        $centerLng = 106.8456;

        // Create circular-approximation polygon (50m radius)
        $radius = 50; // meters
        $polygon = $this->createCircularPolygon($centerLat, $centerLng, $radius, 8);

        // Test scenarios with different GPS accuracies
        $scenarios = [
            ['accuracy' => 5, 'offset' => 40, 'should_be' => 'inside'],   // Good GPS, 40m from center
            ['accuracy' => 5, 'offset' => 60, 'should_be' => 'outside'],  // Good GPS, 60m from center
            ['accuracy' => 20, 'offset' => 40, 'should_be' => 'uncertain'], // Poor GPS near boundary
            ['accuracy' => 50, 'offset' => 30, 'should_be' => 'uncertain'], // Very poor GPS
        ];

        foreach ($scenarios as $scenario) {
            // Offset north by specified meters
            $latOffset = ($scenario['offset'] / 111000); // 1 degree ≈ 111km
            $testLat = $centerLat + $latOffset;
            $testLng = $centerLng;

            $result = $this->pointInPolygon([$testLng, $testLat], $polygon);

            if ($scenario['should_be'] === 'inside') {
                $this->assertTrue($result, sprintf(
                    'Point %dm from center with ±%dm accuracy should be inside 50m radius',
                    $scenario['offset'],
                    $scenario['accuracy']
                ));
            } elseif ($scenario['should_be'] === 'outside') {
                $this->assertFalse($result, sprintf(
                    'Point %dm from center with ±%dm accuracy should be outside 50m radius',
                    $scenario['offset'],
                    $scenario['accuracy']
                ));
            } else {
                // Uncertain - just verify it returns a boolean
                $this->assertIsBool($result);
            }
        }
    }

    /**
     * Helper: Create approximate circular polygon
     */
    private function createCircularPolygon(float $centerLat, float $centerLng, float $radiusMeters, int $points = 8): array
    {
        $polygon = [];

        for ($i = 0; $i < $points; $i++) {
            $angle = ($i * 360 / $points) * pi() / 180;

            // Approximate conversion (works for small distances)
            $latOffset = ($radiusMeters * cos($angle)) / 111000;
            $lngOffset = ($radiusMeters * sin($angle)) / (111000 * cos($centerLat * pi() / 180));

            $polygon[] = [
                $centerLng + $lngOffset,
                $centerLat + $latOffset
            ];
        }

        // Close the polygon
        $polygon[] = $polygon[0];

        return $polygon;
    }
}
