<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Laporan Absensi Perangkat Desa</title>
    <style>
        @page {
            margin: 2cm 2cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            /* Font resmi surat dinas */
            font-size: 11px;
            line-height: 1.3;
            color: #000;
        }

        /* KOP SURAT */
        .header-table {
            width: 100%;
            margin-bottom: 5px;
        }

        .logo-placeholder {
            width: 70px;
            height: 70px;
            background: #eee;
            border: 1px dashed #999;
            text-align: center;
            line-height: 70px;
            font-size: 9px;
            color: #555;
        }

        .header-text {
            text-align: center;
        }

        .pemkab {
            font-size: 16px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .kecamatan {
            font-size: 25px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .nama-desa {
            font-size: 18px;
            text-transform: uppercase;
            font-weight: 900;
            margin-top: 2px;
        }

        .alamat-desa {
            font-size: 10px;
            font-style: italic;
            margin-top: 2px;
        }

        /* GARIS PEMBATAS KOP */
        .double-line {
            border-top: 3px solid #000;
            border-bottom: 1px solid #000;
            height: 2px;
            margin-bottom: 20px;
        }

        /* JUDUL LAPORAN */
        .report-title {
            text-align: center;
            text-transform: uppercase;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 20px;
            text-decoration: underline;
        }

        /* TABEL DATA */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #000;
            /* Border hitam tegas */
            padding: 6px 5px;
            vertical-align: middle;
        }

        table.data-table th {
            background-color: #E5E7EB;
            /* Abu-abu tipis resmi */
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            font-size: 10px;
        }

        table.data-table td {
            font-size: 10px;
        }

        /* STATUS FORMATTING */
        .text-center {
            text-align: center;
        }

        .status-late {
            color: #D8000C;
            font-weight: bold;
            font-style: italic;
        }

        /* Merah untuk terlambat */
        .status-ok {
            color: #000;
        }

        /* TANDA TANGAN */
        .signature-section {
            width: 100%;
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signature-box {
            width: 40%;
            float: right;
            /* Posisi di kanan bawah */
            text-align: center;
        }

        .ttd-date {
            margin-bottom: 5px;
        }

        .ttd-jabatan {
            font-weight: bold;
            margin-bottom: 50px;
        }

        /* Jarak untuk tanda tangan */
        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
        }

        .ttd-nip {
            font-size: 10px;
        }

        /* HELPER */
        .w-time {
            width: 12%;
        }

        .w-name {
            width: 20%;
        }
    </style>
</head>

<body>

    @php
        $path = public_path('images/logo.png');
        if (file_exists($path)) {
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        } else {
            $base64 = '';
        }
    @endphp

    <table class="header-table" style="position: relative;">
        <tr>
            <td style="text-align: center; position: relative;">
                @if ($base64)
                    <img src="{{ $base64 }}" alt="Logo"
                        style="width: 65px; position: absolute; left: 0; top: 0;">
                @endif
                <div style="margin: 0 auto;">
                    <div class="pemkab">PEMERINTAH KABUPATEN LUWU TIMUR</div>
                    <div class="kecamatan">KECAMATAN MANGKUTANA</div>
                    <div class="nama-desa">KANTOR DESA TEROMU</div>
                    <div class="alamat-desa">
                        Alamat: Jalan Tadulako, Desa Teromu, Kec. Mangkutana, Kab. Luwu Timur, Kode Pos 92973
                    </div>
                </div>
            </td>
        </tr>
    </table>
    <div class="double-line"></div>

    <div class="report-title">LAPORAN REKAPITULASI KEHADIRAN PERANGKAT DESA</div>

    <div style="margin-bottom: 15px; font-size: 11px;">
        <table width="100%">
            <tr>
                <td width="15%">Periode/Filter</td>
                <td width="2%">:</td>
                <td>{{ $filterInfo }}</td>
            </tr>
            <tr>
                <td>Tanggal Cetak</td>
                <td>:</td>
                <td>{{ $printDate }}</td>
            </tr>
        </table>
    </div>

    @if ($records->isEmpty())
        <div style="text-align: center; padding: 30px; border: 1px solid #000;">
            -- Data Absensi Tidak Ditemukan --
        </div>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th class="w-name">Nama Perangkat</th>
                    <th class="w-time">Tanggal</th>
                    <th class="w-time">Jam</th>
                    <th style="width: 8%;">Jenis</th>
                    <th style="width: 15%;">Status Kehadiran</th>
                    <th style="width: 15%;">Lokasi Absen</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($records as $index => $record)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $record->user->name }}</strong><br>
                            <span style="font-size: 9px; color: #555;">{{ $record->user->email }}</span>
                        </td>
                        <td class="text-center">{{ $record->created_at->format('d/m/Y') }}</td>
                        <td class="text-center">{{ $record->created_at->format('H:i') }} WITA</td>
                        <td class="text-center">
                            @if ($record->type === 'in')
                                MASUK
                            @else
                                KELUAR
                            @endif
                        </td>
                        <td class="text-center">
                            {{-- Logika Status Formal --}}
                            @if ($record->status_label && $record->status_label !== '-' && $record->status_label !== '—')
                                @if (Str::contains(strtolower($record->status_label), 'terlambat'))
                                    <span
                                        class="status-late">{{ strtoupper($record->status_label_with_duration) }}</span>
                                @else
                                    {{ strtoupper($record->status_label_with_duration) }}
                                @endif
                            @else
                                <span style="color: #777;">-</span>
                            @endif
                        </td>
                        <td>
                            @if ($record->is_offsite)
                                <i>Luar Kantor (Dinas)</i>
                            @elseif ($record->geo_ok)
                                Di Kantor Desa
                            @else
                                <span style="color:red">Di Luar Radius</span>
                            @endif
                        </td>
                        <td>
                            @if ($record->is_offsite)
                                Input Admin
                            @elseif($record->has_evidence)
                                Absensi Mandiri (Foto Lampir)
                            @else
                                Absensi Mandiri
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="signature-section">
        <div class="signature-box">
            <div class="ttd-date">Teromu, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
            <div class="ttd-jabatan">Kepala Desa Teromu</div>

            <br><br><br><br>

            <div class="ttd-nama">( Bertho Taruku )</div>
            <div class="ttd-nip">NIP. ...........................</div>
        </div>
    </div>

</body>

</html>
