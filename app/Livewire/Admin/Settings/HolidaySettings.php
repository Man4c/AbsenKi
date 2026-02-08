<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Holiday;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Contracts\View\View;

#[Layout('components.layouts.app', ['title' => 'Hari Libur'])]
class HolidaySettings extends Component
{
    use WithPagination;

    public bool $isOpen = false;
    public ?int $editingId = null;

    // Form fields
    public string $date = '';
    public string $title = '';
    public ?string $description = '';
    public bool $isActive = true;

    /** @var array<string, string|array<int, string>> */
    protected array $rules = [
        'date' => 'required|date',
        'title' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
        'isActive' => 'required|boolean',
    ];

    /** @var array<string, string> */
    protected array $messages = [
        'date.required' => 'Tanggal harus diisi',
        'date.date' => 'Format tanggal tidak valid',
        'title.required' => 'Nama libur harus diisi',
        'title.max' => 'Nama libur maksimal 255 karakter',
    ];

    public function openModal(): void
    {
        $this->isOpen = true;
        $this->resetForm();
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->date = '';
        $this->title = '';
        $this->description = '';
        $this->isActive = true;
    }

    public function edit(int $id): void
    {
        $holiday = Holiday::findOrFail($id);

        $this->editingId = $holiday->id;
        $this->date = $holiday->date->format('Y-m-d');
        $this->title = $holiday->title;
        $this->description = $holiday->description;
        $this->isActive = $holiday->is_active;

        $this->isOpen = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            // Update existing
            $holiday = Holiday::findOrFail($this->editingId);
            $holiday->update([
                'date' => $this->date,
                'title' => $this->title,
                'description' => $this->description,
                'is_active' => $this->isActive,
            ]);
            $message = 'Hari libur berhasil diperbarui.';
        } else {
            // Create new
            Holiday::create([
                'date' => $this->date,
                'title' => $this->title,
                'description' => $this->description,
                'is_active' => $this->isActive,
            ]);
            $message = 'Hari libur berhasil ditambahkan.';
        }

        session()->flash('message', $message);
        $this->closeModal();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        Holiday::findOrFail($id)->delete();
        session()->flash('message', 'Hari libur berhasil dihapus.');
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->update(['is_active' => !$holiday->is_active]);
        session()->flash('message', 'Status hari libur berhasil diubah.');
    }

    public function render(): View
    {
        $holidays = Holiday::orderBy('date', 'desc')->paginate(10);

        return view('livewire.admin.settings.holiday-settings', [
            'holidays' => $holidays
        ]);
    }
}
