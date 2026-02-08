<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden animate-pulse">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    {{-- Generate 5 kolom header --}}
                    @foreach (range(1, 5) as $i)
                        <th class="px-6 py-3 text-left">
                            <div class="h-3 bg-gray-200 rounded w-20 dark:bg-zinc-700"></div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach (range(1, 5) as $row)
                    <tr>
                        {{-- 1. Tanggal & Waktu --}}
                        <td class="px-6 py-4">
                            <div class="h-4 bg-gray-200 rounded w-32 dark:bg-zinc-700"></div>
                        </td>
                        {{-- 2. Jenis --}}
                        <td class="px-6 py-4">
                            <div class="h-6 bg-gray-200 rounded w-16 dark:bg-zinc-700"></div>
                        </td>
                        {{-- 3. Status Lokasi --}}
                        <td class="px-6 py-4">
                            <div class="h-6 bg-gray-200 rounded w-28 dark:bg-zinc-700"></div>
                        </td>
                        {{-- 4. Face Match --}}
                        <td class="px-6 py-4">
                            <div class="h-4 bg-gray-200 rounded w-12 mb-1 dark:bg-zinc-700"></div>
                            <div class="h-3 bg-gray-200 rounded w-10 dark:bg-zinc-700"></div>
                        </td>
                        {{-- 5. Koordinat --}}
                        <td class="px-6 py-4">
                            <div class="h-3 bg-gray-200 rounded w-36 dark:bg-zinc-700"></div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
