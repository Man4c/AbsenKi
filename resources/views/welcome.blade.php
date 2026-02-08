<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="antialiased bg-white text-gray-900 font-sans">

    {{-- 1. NAVIGATION BAR (Fixed for Mobile) --}}
    <section class="w-full px-8 text-gray-700 bg-white">
        <div class="container flex items-center justify-between py-5 mx-auto max-w-7xl">

            {{-- LOGO --}}
            <div class="relative flex items-center">
                <a href="/" class="text-2xl font-extrabold leading-none text-black select-none">
                    AbsenKi<span class="text-blue-600">.</span>
                </a>
            </div>

            {{-- TOMBOL LOGIN --}}
            <div class="inline-flex items-center justify-end">
                @auth
                    {{-- Jika User SUDAH LOGIN, tampilkan tombol Dashboard --}}
                    <a href="{{ auth()->user()->role === 'admin' ? route('admin.dashboard') : route('staff.absen') }}"
                        class="inline-flex items-center justify-center px-5 py-2 text-base font-medium leading-6 text-white whitespace-no-wrap bg-blue-600 border border-blue-700 rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Dashboard
                    </a>
                @else
                    {{-- Jika User BELUM LOGIN, tampilkan tombol Login Staff --}}
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center justify-center px-5 py-2 text-base font-medium leading-6 text-white whitespace-no-wrap bg-blue-600 border border-blue-700 rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Login
                    </a>
                @endauth
            </div>
        </div>
    </section>

    <main>
        {{-- 2. HERO SECTION --}}
        <section class="px-2 py-20 bg-white md:px-0">
            <div class="container items-center max-w-6xl px-8 mx-auto xl:px-5">
                <div class="flex flex-wrap items-center sm:-mx-3">

                    {{-- Bagian Teks (Kiri) --}}
                    <div class="w-full md:w-1/2 md:px-3">
                        <div
                            class="w-full pb-6 space-y-6 sm:max-w-md lg:max-w-lg md:space-y-4 lg:space-y-8 xl:space-y-9 sm:pr-5 lg:pr-0 md:pb-0">
                            <h1
                                class="text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl md:text-4xl lg:text-5xl xl:text-6xl">
                                <span class="block xl:inline">Sistem Absensi Digital</span>
                                <span class="block text-blue-600 xl:inline">Desa Teromu</span>
                            </h1>
                            <p class="mx-auto text-base text-gray-500 sm:max-w-md lg:text-xl md:max-w-3xl">
                                Platform terintegrasi untuk pengelolaan kehadiran staff, pemantauan kinerja, dan
                                pelaporan administrasi desa yang transparan.
                            </p>
                            <div class="relative flex flex-col sm:flex-row sm:space-x-4">
                                @auth
                                    {{-- Tombol jika SUDAH LOGIN --}}
                                    <a href="{{ auth()->user()->role === 'admin' ? route('admin.dashboard') : route('staff.absen') }}"
                                        class="flex items-center justify-center w-full px-6 py-3 mb-3 text-lg text-white bg-blue-600 rounded-md sm:mb-0 hover:bg-blue-700 sm:w-auto">
                                        Buka Dashboard
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 ml-1" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                            <polyline points="12 5 19 12 12 19"></polyline>
                                        </svg>
                                    </a>
                                @else
                                    {{-- Tombol jika BELUM LOGIN --}}
                                    <a href="{{ route('login') }}"
                                        class="flex items-center justify-center w-full px-6 py-3 mb-3 text-lg text-white bg-blue-600 rounded-md sm:mb-0 hover:bg-blue-700 sm:w-auto">
                                        Masuk Sekarang
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 ml-1" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                            <polyline points="12 5 19 12 12 19"></polyline>
                                        </svg>
                                    </a>
                                @endauth
                            </div>
                        </div>
                    </div>

                    {{-- Bagian Gambar (Kanan) --}}
                    <div class="w-full md:w-1/2">
                        <div class="w-full h-auto overflow-hidden rounded-md shadow-xl sm:rounded-xl">
                            {{-- Ganti URL gambar ini dengan foto kantor desa nanti --}}
                            <img src="{{ asset('images/hero.jpg') }}" alt="Kantor Desa"
                                class="object-cover w-full h-full">
                        </div>
                    </div>

                </div>
            </div>
        </section>
    </main>

    {{-- 3. FOOTER (Clean Version) --}}
    <section class="bg-white border-t border-gray-100">
        <div class="max-w-screen-xl px-4 py-8 mx-auto overflow-hidden sm:px-6 lg:px-8">
            <p class="mt-8 text-base leading-6 text-center text-gray-400">
                &copy; {{ date('Y') }} Pemerintah Desa Teromu. Hak Cipta Dilindungi.
            </p>
        </div>
    </section>

    @fluxScripts
</body>

</html>
