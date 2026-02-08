<div class="grid gap-4 md:grid-cols-3 animate-pulse">
    {{-- Loop 3 kali untuk membuat 3 card --}}
    @foreach (range(1, 3) as $index)
        <div
            class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-900 h-full min-h-[9rem] flex flex-col justify-between">

            <div class="h-4 bg-gray-200 rounded w-1/3 dark:bg-zinc-700"></div>

            <div class="my-4 space-y-3">
                <div class="h-8 bg-gray-200 rounded w-1/4 dark:bg-zinc-700"></div>
                <div class="h-3 bg-gray-200 rounded w-1/2 dark:bg-zinc-700"></div>
            </div>

            <div class="space-y-2 mt-auto">
                <div class="h-2 bg-gray-100 rounded w-full dark:bg-zinc-800"></div>
                <div class="h-2 bg-gray-100 rounded w-2/3 dark:bg-zinc-800"></div>
            </div>

        </div>
    @endforeach
</div>
