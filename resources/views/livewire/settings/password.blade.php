<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component {
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Ubah Password')" :subheading="__('Pastikan akun Anda menggunakan password yang aman')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <flux:input wire:model="current_password" label="Password Saat Ini" type="password" required
                autocomplete="current-password" viewable />
            <flux:input wire:model="password" label="Password Baru" type="password" required autocomplete="new-password"
                viewable />
            <flux:input wire:model="password_confirmation" label="Konfirmasi Password Baru" type="password" required
                autocomplete="new-password" viewable />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-password-button">
                        {{ __('Ubah Password') }}
                    </flux:button>
                </div>

                <x-action-message class="me-3" on="password-updated">
                    {{ __('Password berhasil diubah.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
