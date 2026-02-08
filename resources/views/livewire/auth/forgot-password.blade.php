<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header title="Lupa Kata Sandi"
            description="Masukkan email Anda, kami akan mengirimkan link untuk mereset kata sandi." />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input name="email" label="Alamat Email" type="email" required autofocus
                placeholder="email@desateromu.go.id" />

            <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                Kirim Link Reset
            </flux:button>
        </form>

        <div class="space-x-1 text-center text-sm text-zinc-400 rtl:space-x-reverse">
            <span>Atau, kembali ke</span>
            <flux:link :href="route('login')" wire:navigate>Halaman Login</flux:link>
        </div>
    </div>
</x-layouts.auth>
