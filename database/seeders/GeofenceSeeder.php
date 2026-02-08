<?php

namespace Database\Seeders;

use App\Models\Geofence;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GeofenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample geofence: Area sekitar kantor (contoh koordinat untuk testing)
        // Anda bisa generate polygon di https://geojson.io

        Geofence::create([
            'name' => 'Kantor Desa Teromu',
            'is_active' => true,
            'polygon_geojson' => [
                'type' => 'Polygon',
                'coordinates' => [
                    [
                        // Format: [longitude, latitude]
                        // Contoh polygon persegi panjang kecil untuk testing
                        // Lokasi ini hanya contoh, sesuaikan dengan lokasi kantor yang sebenarnya
                        [106.8270, -6.1754], // Southwest corner
                        [106.8280, -6.1754], // Southeast corner
                        [106.8280, -6.1744], // Northeast corner
                        [106.8270, -6.1744], // Northwest corner
                        [106.8270, -6.1754], // Close polygon (same as first point)
                    ]
                ]
            ]
        ]);

        // Uncomment untuk tambahkan geofence lain (non-aktif)
        /*
        Geofence::create([
            'name' => 'Kantor Cabang',
            'is_active' => false,
            'polygon_geojson' => [
                'type' => 'Polygon',
                'coordinates' => [
                    [
                        [106.9270, -6.2754],
                        [106.9280, -6.2754],
                        [106.9280, -6.2744],
                        [106.9270, -6.2744],
                        [106.9270, -6.2754],
                    ]
                ]
            ]
        ]);
        */
    }
}
