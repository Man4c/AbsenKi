<?php

namespace App\Livewire\Admin\Geofence;

use App\Models\Geofence;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Illuminate\Contracts\View\View;

class Index extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|json')]
    public string $polygon_geojson = '';

    public bool $is_active = false;

    public ?int $editingId = null;

    public function mount(): void
    {
        // Load active geofence if exists with caching
        // Use longer TTL for production (5 minutes)
        $activeGeofence = cache()->remember('geofence:active', now()->addMinutes(5), function () {
            return Geofence::where('is_active', true)->first();
        });

        if ($activeGeofence) {
            $this->editingId = $activeGeofence->id;
            $this->name = $activeGeofence->name;
            $encoded = json_encode($activeGeofence->polygon_geojson, JSON_PRETTY_PRINT);
            $this->polygon_geojson = $encoded !== false ? $encoded : '';
            $this->is_active = $activeGeofence->is_active;
        } else {
            // Set default template GeoJSON if no active geofence
            $encoded = json_encode([
                'type' => 'Polygon',
                'coordinates' => [
                    [
                        [119.4821, -5.1236],
                        [119.4831, -5.1236],
                        [119.4831, -5.1246],
                        [119.4821, -5.1246],
                        [119.4821, -5.1236]
                    ]
                ]
            ], JSON_PRETTY_PRINT);
            $this->polygon_geojson = $encoded !== false ? $encoded : '';
        }
    }

    public function save(): void
    {
        $this->validate();

        // Validate JSON format
        $decoded = json_decode($this->polygon_geojson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->addError('polygon_geojson', 'Format JSON tidak valid');
            return;
        }

        // If setting this as active, deactivate all others
        if ($this->is_active) {
            Geofence::query()->update(['is_active' => false]);
        }

        if ($this->editingId) {
            // Update existing
            $geofence = Geofence::find($this->editingId);
            if ($geofence) {
                $geofence->update([
                    'name' => $this->name,
                    'polygon_geojson' => $decoded,
                    'is_active' => $this->is_active,
                ]);
            }
            session()->flash('saved', 'Geofence berhasil diperbarui!');
        } else {
            // Create new
            Geofence::create([
                'name' => $this->name,
                'polygon_geojson' => $decoded,
                'is_active' => $this->is_active,
            ]);
            session()->flash('saved', 'Geofence berhasil dibuat!');
        }

        // Clear cache with proper key pattern
        cache()->forget('geofence:active');
        cache()->forget('geofence:list');

        // Reload
        $this->mount();
    }

    public function toggleActive(): void
    {
        if ($this->editingId) {
            // If activating, deactivate all others first
            if (!$this->is_active) {
                Geofence::query()->update(['is_active' => false]);
            }

            $geofence = Geofence::find($this->editingId);
            if ($geofence) {
                $geofence->update(['is_active' => !$this->is_active]);
                $this->is_active = !$this->is_active;
            }

            cache()->forget('geofence:active');
            cache()->forget('geofence:list');

            session()->flash('saved', 'Status geofence berhasil diperbarui!');
        }
    }

    public function activate(int $id): void
    {
        // Deactivate all geofences
        Geofence::query()->update(['is_active' => false]);

        // Activate selected geofence
        $geofence = Geofence::find($id);
        if ($geofence) {
            $geofence->update(['is_active' => true]);
        }

        cache()->forget('geofence:active');
        cache()->forget('geofence:list');

        if ($geofence) {
            session()->flash('saved', "Geofence '{$geofence->name}' berhasil diaktifkan!");
        }
    }

    public function delete(int $id): void
    {
        $geofence = Geofence::find($id);
        if (!$geofence) {
            return;
        }
        $name = $geofence->name;
        $geofence->delete();

        cache()->forget('geofence:active');
        cache()->forget('geofence:list');

        session()->flash('saved', "Geofence '{$name}' berhasil dihapus!");

        // Reset form if deleting current editing geofence
        if ($this->editingId == $id) {
            $this->reset(['name', 'polygon_geojson', 'is_active', 'editingId']);
        }
    }

    public function render(): View
    {
        // Cache list for 5 minutes in production
        $geofences = cache()->remember('geofence:list', now()->addMinutes(5), function () {
            return Geofence::orderBy('created_at', 'desc')->get();
        });

        return view('livewire.admin.geofence.index', [
            'geofences' => $geofences
        ]);
    }
}
