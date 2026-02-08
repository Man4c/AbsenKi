<x-layouts.auth>
    <div class="flex flex-col gap-6">

        {{-- LOGO DESA (Agar senada dengan halaman Login) --}}
        <div class="flex justify-center mb-2">
            <a href="/" class="text-2xl font-extrabold leading-none text-black select-none">
                Desa Teromu<span class="text-blue-600">.</span>
            </a>
        </div>

        <x-auth-header title="Pendaftaran Staff Baru" description="Isi data diri Anda untuk membuat akun absensi." />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <flux:input name="name" label="Nama Lengkap" type="text" required autofocus autocomplete="name"
                placeholder="Nama sesuai KTP" />

            <flux:input name="email" label="Alamat Email" type="email" required autocomplete="email"
                placeholder="email@desateromu.go.id" />

            <flux:input name="password" label="Kata Sandi" type="password" required autocomplete="new-password"
                placeholder="Minimal 8 karakter" viewable />

            <flux:input name="password_confirmation" label="Ulangi Kata Sandi" type="password" required
                autocomplete="new-password" placeholder="Masukkan ulang kata sandi" viewable />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    Buat Akun
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 text-center text-sm text-zinc-600 rtl:space-x-reverse dark:text-zinc-400">
            <span>Sudah punya akun?</span>
            <flux:link :href="route('login')" wire:navigate>Masuk disini</flux:link>
        </div>
    </div>
</x-layouts.auth>
