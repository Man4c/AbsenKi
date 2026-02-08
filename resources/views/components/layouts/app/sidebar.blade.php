<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky stashable
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 !z-[1000]">
        <div class="flex items-center justify-between w-full">
            <a href="{{ auth()->user()->role === 'admin' ? route('admin.dashboard') : route('staff.absen') }}"
                class="flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />
        </div>

        <flux:navlist variant="outline">
            <flux:navlist.group :heading="__('Platform')" class="grid">
                @if (auth()->user()->role === 'admin')
                    <flux:navlist.item icon="home" :href="route('admin.dashboard')"
                        :current="request()->routeIs('admin.dashboard')" wire:navigate>{{ __('Dashboard') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="map-pin" :href="route('admin.geofence')"
                        :current="request()->routeIs('admin.geofence')" wire:navigate>{{ __('Geofence') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="user-group" :href="route('admin.faces')"
                        :current="request()->routeIs('admin.faces*')" wire:navigate>{{ __('Faces') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="calendar" :href="route('admin.izin-cuti')"
                        :current="request()->routeIs('admin.izin-cuti')" wire:navigate>{{ __('Izin & Cuti') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="document-text" :href="route('admin.laporan')"
                        :current="request()->routeIs('admin.laporan')" wire:navigate>{{ __('Laporan') }}
                    </flux:navlist.item>
                @endif
            </flux:navlist.group>

            @if (auth()->user()->role === 'admin')
                <flux:navlist.group :heading="__('Pengaturan')" class="grid">
                    <flux:navlist.item icon="calendar" :href="route('admin.settings.work-schedule')"
                        :current="request()->routeIs('admin.settings.work-schedule')" wire:navigate>
                        {{ __('Jadwal Kerja') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="calendar-days" :href="route('admin.settings.holidays')"
                        :current="request()->routeIs('admin.settings.holidays')" wire:navigate>{{ __('Hari Libur') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            @else
                <flux:navlist.item icon="clipboard-document-check" :href="route('staff.absen')"
                    :current="request()->routeIs('staff.absen')" wire:navigate>{{ __('Absen') }}
                </flux:navlist.item>
                <flux:navlist.item icon="clock" :href="route('staff.riwayat')"
                    :current="request()->routeIs('staff.riwayat')" wire:navigate>{{ __('Riwayat') }}
                </flux:navlist.item>
            @endif
        </flux:navlist>

        <flux:spacer />

        {{-- <flux:navlist variant="outline">
            <flux:navlist.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit"
                target="_blank">
                {{ __('Repository') }}
            </flux:navlist.item>

            <flux:navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire"
                target="_blank">
                {{ __('Documentation') }}
            </flux:navlist.item>
        </flux:navlist> --}}

        <!-- Desktop User Menu -->
        <flux:dropdown class="hidden lg:block" position="bottom" align="start">
            <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()"
                icon:trailing="chevrons-up-down" data-test="sidebar-menu-button" />

            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full"
                        data-test="logout-button">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full"
                        data-test="logout-button">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @fluxScripts
</body>

</html>
