<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden animate-pulse">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    {{-- Generate 10 kolom header --}}
                    @foreach (range(1, 10) as $i)
                        <th class="px-6 py-3 text-left">
                            <div class="h-3 bg-gray-200 rounded w-16 dark:bg-zinc-700"></div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach (range(1, 10) as $row)
                    <tr>
                        {{-- 1. Nama Staff --}}
                        <td class="px-6 py-4">
                            <div class="h-4 bg-gray-200 rounded w-32 mb-2 dark:bg-zinc-700"></div>
                            <div class="h-3 bg-gray-200 rounded w-24 dark:bg-zinc-700"></div>
                        </td>
                        {{-- 2. Waktu --}}
                        <td class="px-6 py-4">
                            <div class="h-3 bg-gray-200 rounded w-24 dark:bg-zinc-700"></div>
                        </td>
                        {{-- 3. Jenis --}}
                        <td class="px-6 py-4">
                            <div class="h-6 bg-gray-200 rounded w-16 dark:bg-zinc-700"></div>
                        </td>
                        {{-- 4. Lokasi --}}
                        <td class="px-6 py-4">
                            <div class="h-3 bg-gray-200 rounded w-20 dark:bg-zinc-700"></div>
                        </td>
                        {{-- 5. Status Lokasi --}}
                        <td class="px-6 py-4">
                            <div class="h-4 bg-gray-200 rounded w-28 dark:bg-zinc-700"></div>
                        </td>
                        {{-- 6. Face Match --}}
                        <td class="px-6 py-4">
                            <div class="h-3 bg-gray-200 rounded w-12 dark:bg-zinc-700"></div>
                        </td>
                        {{-- 7. Quality --}}
                        <td class="px-6 py-4">
                            <div class="h-3 bg-gray-200 rounded w-20 mb-1 dark:bg-zinc-700"></div>
                            <div class="h-3 bg-gray-200 rounded w-20 dark:bg-zinc-700"></div>
                        </td>
                        {{-- 8. Koordinat --}}
                        <td class="px-6 py-4">
                            <div class="h-3 bg-gray-200 rounded w-24 dark:bg-zinc-700"></div>
                        </td>
                        {{-- 9. Bukti --}}
                        <td class="px-6 py-4">
                            <div class="h-8 bg-gray-200 rounded w-16 dark:bg-zinc-700"></div>
                        </td>
                        {{-- 10. Device --}}
                        <td class="px-6 py-4">
                            <div class="h-3 bg-gray-200 rounded w-10 dark:bg-zinc-700"></div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
