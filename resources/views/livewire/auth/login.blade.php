<x-layouts.auth>
    <div class="flex flex-col gap-6">

        {{-- LOGO DESA (Ditambahkan agar senada dengan Landing Page) --}}
        <div class="flex justify-center mb-2">
            <a href="/" class="text-2xl font-extrabold leading-none text-black select-none">
                Desa Teromu<span class="text-blue-600">.</span>
            </a>
        </div>

        <x-auth-header title="Masuk ke Sistem" description="Silakan masukkan kredensial staff Anda." />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input name="email" label="Alamat Email" type="email" required autofocus autocomplete="email"
                placeholder="email@desateromu.go.id" />

            <div class="relative">
                <flux:input name="password" label="Kata Sandi" type="password" required autocomplete="current-password"
                    placeholder="Masukkan kata sandi" viewable />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        Lupa kata sandi?
                    </flux:link>
                @endif
            </div>

            <flux:checkbox name="remember" label="Ingat saya di perangkat ini" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    Masuk
                </flux:button>
            </div>
        </form>

        @if (Route::has('register'))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
                <span>Belum punya akun?</span>
                <flux:link :href="route('register')" wire:navigate>Daftar Staff</flux:link>
            </div>
        @endif
    </div>
</x-layouts.auth>
