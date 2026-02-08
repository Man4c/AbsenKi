<div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900 animate-pulse">

    {{-- 1. Header Card (Judul & Link) --}}
    <div class="p-5 border-b border-neutral-200 dark:border-neutral-700 flex items-start justify-between">
        <div>
            <div class="h-5 bg-gray-200 rounded w-48 mb-2 dark:bg-zinc-700"></div>
            <div class="h-3 bg-gray-200 rounded w-64 dark:bg-zinc-700"></div>
        </div>
        <div class="h-3 bg-gray-200 rounded w-32 mt-1 dark:bg-zinc-700"></div>
    </div>

    {{-- 2. Table Area --}}
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm text-left">

            {{-- Table Head --}}
            <thead class="bg-neutral-50 dark:bg-zinc-800 border-b border-neutral-200 dark:border-neutral-700">
                <tr>
                    {{-- Kita buat 6 kolom header sesuai gambar --}}
                    @foreach (range(1, 6) as $i)
                        <th class="px-4 py-3">
                            <div class="h-3 bg-gray-200 rounded w-16 dark:bg-zinc-700"></div>
                        </th>
                    @endforeach
                </tr>
            </thead>

            {{-- Table Body (Loop 5 baris) --}}
            <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700 bg-white dark:bg-zinc-900">
                @foreach (range(1, 5) as $row)
                    <tr>
                        {{-- Col 1: STAFF (Nama & Email) --}}
                        <td class="px-4 py-4 align-top">
                            <div class="flex flex-col gap-1.5">
                                <div class="h-4 bg-gray-200 rounded w-32 dark:bg-zinc-700"></div>
                                <div class="h-3 bg-gray-200 rounded w-24 dark:bg-zinc-700"></div>
                            </div>
                        </td>

                        {{-- Col 2: WAKTU --}}
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="h-3 bg-gray-200 rounded w-32 dark:bg-zinc-700"></div>
                        </td>

                        {{-- Col 3: JENIS (Badge) --}}
                        <td class="px-4 py-4">
                            <div class="h-6 bg-gray-200 rounded w-16 dark:bg-zinc-700"></div>
                        </td>

                        {{-- Col 4: LOKASI (Badge) --}}
                        <td class="px-4 py-4">
                            <div class="h-6 bg-gray-200 rounded w-24 dark:bg-zinc-700"></div>
                        </td>

                        {{-- Col 5: FACE MATCH --}}
                        <td class="px-4 py-4">
                            <div class="h-3 bg-gray-200 rounded w-12 dark:bg-zinc-700"></div>
                        </td>

                        {{-- Col 6: STATUS (Badge) --}}
                        <td class="px-4 py-4">
                            <div class="h-6 bg-gray-200 rounded w-28 dark:bg-zinc-700"></div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
