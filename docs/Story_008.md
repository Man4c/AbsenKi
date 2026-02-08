Story_008 — OpenCV Quality Gate (Enroll + Verify)

1. Tujuan
   Biar foto wajah yang dipakai bagus dulu sebelum dikirim ke AWS Rekognition.
   Kita cek:
   • Blur (tajam atau goyang?)
   • Terang (gelap/terlalu redup?)
   • Wajah kebaca (ada muka yang jelas, ukuran masuk akal)
   Kalau kualitas jelek → tolak dulu dengan pesan gampang dimengerti.
   Ini dipakai di Enroll (admin upload) dan Verify (absen staff).

---

2. Lingkup kerja (Scope)
   • Tambah Quality Gate di service FaceProcessor:
   o Hitung Laplacian variance → nilai ketajaman.
   o Hitung Brightness (rata-rata channel V/HSV).
   o Cek dimensi minimum (lebar/tinggi).
   o (opsional) cek rasio wajah: bounding-box wajah minimal XX% dari gambar.
   • Integrasi quality gate ke dua alur:

    1. Enroll: sebelum IndexFaces → kalau gagal, jangan lanjut ke AWS.
    2. Verify: sebelum SearchFacesByImage → kalau gagal, tampilkan pesan di UI, jangan panggil AWS.

    • Simpan nilai kualitas (blur_var & brightness) ke attendance saat Verify (biar ada jejak uji).
    • Ambang batas kualitas bisa diatur dari .env.
    Catatan: Deteksi identitas tetap AWS Rekognition. OpenCV cuma buat cek kualitas.

3. Arsitektur Mini
   (Upload/capture)
   │
   ▼
   [FaceProcessor::qualityCheck()]
   ├─ OpenCV: laplacianVariance → blur_var
   ├─ OpenCV: brightness (HSV-V) → brightness
   ├─ cek min width/height
   └─ (opsional) cek rasio bounding-box wajah
   │
   ├─ FAIL → balikin error ke UI (“Foto buram/gelap, coba ulang ya”)
   └─ PASS → teruskan ke AWS Rekognition

4. Variabel .env (baru)
   Tambahkan ke .env + .env.example: # Quality Gate (OpenCV)

    # Quality Gate (OpenCV)

    FACE_MIN_LAPLACE=80 # minimal ketajaman (semakin tinggi semakin tajam)
    FACE_MIN_BRIGHTNESS=60 # minimal kecerahan (0-255 skala rata-rata V/HSV)
    FACE_MIN_WIDTH=200 # minimal lebar gambar
    FACE_MIN_HEIGHT=200 # minimal tinggi gambar
    FACE_MIN_BOX_PERCENT=10 # (opsional) min % area wajah dari gambar (0-100)

5. Perubahan DB
   Tidak bikin tabel baru.
   Tambahan kolom (opsional tapi dianjurkan):
   • Di attendance (untuk Verify):
   o quality_blur_var (float, nullable)
   o quality_brightness (float, nullable)
   Enroll tidak wajib simpan kualitas ke DB (boleh diabaikan), tapi kalau mau, bisa tambah kolom serupa di face_profiles.
   Migrasi contoh singkat:
   Schema::table('attendance', function (Blueprint $table) {
   $table->float('quality_blur_var')->nullable();
   $table->float('quality_brightness')->nullable();
   });

6. Implementasi teknik (arahan ke Copilot)
   6.1 Service helper Python (OpenCV)
   Buat file tools/opencv_quality.py (pakai Python 3 + OpenCV). Input: path gambar. Output: JSON.
   Pseudocode:

# tools/opencv_quality.py

# args: --image /path/to/file.jpg

# print JSON: { "ok": true/false, "laplace": 123.4, "brightness": 88.2, "message": "" }

import cv2, json, argparse, numpy as np

parser = argparse.ArgumentParser()
parser.add_argument('--image', required=True)
args = parser.parse_args()

img = cv2.imread(args.image)
if img is None:
print(json.dumps({"ok": False, "message": "Gambar tidak bisa dibaca"}))
exit(0)

gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

# Laplacian variance (ketajaman)

lap_var = float(cv2.Laplacian(gray, cv2.CV_64F).var())

# Brightness (pakai V di HSV)

hsv = cv2.cvtColor(img, cv2.COLOR_BGR2HSV)
brightness = float(np.mean(hsv[:,:,2]))

print(json.dumps({
"ok": True,
"laplace": lap_var,
"brightness": brightness,
"width": int(img.shape[1]),
"height": int(img.shape[0])
}))
Catatan: Kita tidak melakukan face detection di sini (biar simpel). Deteksi wajah tetap di AWS. Kalau mau, nanti Story_010 bisa nambah DNN bbox untuk cek rasio box.

6.2 Panggil Python dari PHP (Symfony Process)
Di app/Services/FaceProcessor.php tambahkan method:
use Symfony\Component\Process\Process;

public function qualityCheck(string $localPath): array
{
$minLaplace = (float) env('FACE_MIN_LAPLACE', 80);
$minBright = (float) env('FACE_MIN_BRIGHTNESS', 60);
$minW = (int) env('FACE_MIN_WIDTH', 200);
$minH = (int) env('FACE_MIN_HEIGHT', 200);

    $process = new Process([
        PHP_BINARY, // atau 'python3' jika python terpisah
        base_path('tools/opencv_quality.py'),
        '--image', $localPath,
    ]);

    $process->run();

    if (!$process->isSuccessful()) {
        return ['success' => false, 'message' => 'Gagal menjalankan quality check'];
    }

    $out = json_decode($process->getOutput(), true);
    if (!$out || empty($out['ok'])) {
        return ['success' => false, 'message' => $out['message'] ?? 'Quality check gagal'];
    }

    $lap = (float) ($out['laplace'] ?? 0);
    $bri = (float) ($out['brightness'] ?? 0);
    $w   = (int) ($out['width'] ?? 0);
    $h   = (int) ($out['height'] ?? 0);

    // rule sederhana
    if ($w < $minW || $h < $minH) {
        return ['success' => false, 'message' => 'Resolusi gambar terlalu kecil'];
    }
    if ($lap < $minLaplace) {
        return ['success' => false, 'message' => 'Foto buram/goyang, coba ulangi ya'];
    }
    if ($bri < $minBright) {
        return ['success' => false, 'message' => 'Foto terlalu gelap, cari cahaya yang cukup ya'];
    }

    return [
        'success' => true,
        'laplace' => $lap,
        'brightness' => $bri,
        'width' => $w,
        'height' => $h,
    ];

}
Jika Python tidak inline via PHP_BINARY, ganti proses jadi ['python3', base_path('tools/opencv_quality.py'), ...].

6.3 Integrasi di Enroll (Admin)
Di App\Livewire\Admin\Faces\Manage::uploadFace() sebelum enrollService->enrollFace(...):
$qc = $faceProcessor->qualityCheck($processed['cropped_path']); // atau path asli kalau belum crop
if (!$qc['success']) {
    throw new \Exception($qc['message']);
}
Setelah sukses, lanjut seperti biasa: panggil Rekognition IndexFaces, simpan FaceId.

6.4 Integrasi di Verify (Absen Staff)
Di alur verify (komponen Livewire staff absen), sebelum SearchFacesByImage:
$qc = $faceProcessor->qualityCheck($snapshotLocalPath);
if (!$qc['success']) {
// kirim pesan ke UI & hentikan
$this->addError('face', $qc['message']);
return;
}

// simpan qc metric ke attendance saat commit absen
// (setelah Rekognition sukses & geofence ok)
Attendance::create([
// ... field lain ...
'quality_blur_var' => $qc['laplace'] ?? null,
'quality_brightness' => $qc['brightness'] ?? null,
]);
Di UI staff, kalau gagal quality:
• tampilkan alert kecil: “Foto buram/gelap, coba ulangi ya” + tombol “Ambil Ulang”.

7. Acceptance Criteria

1) Enroll (Admin)
   o Upload foto buram → ditolak dengan pesan “Foto buram/goyang…”
   o Upload foto gelap → ditolak dengan pesan “Foto terlalu gelap…”
   o Upload foto bagus → lanjut ke AWS, FaceId tersimpan.
2) Verify (Staff)
   o Ambil snapshot buram/gelap → tidak memanggil AWS; UI kasih pesan tolong ambil ulang.
   o Ambil snapshot bagus → panggil AWS + dapat face_score seperti biasa.
   o Rekam quality_blur_var & quality_brightness ke attendance (nullable kalau tak tersedia).
3) Konfigurasi
   o Ubah ambang di .env → perilaku ikut berubah tanpa ganti kode.
   o Jika Python/OpenCV error → sistem tampilkan error ramah, tidak crash (pesan “Quality check gagal”).
4) Keamanan
   o Tidak menyimpan foto sensitif di publik. File temp dibersihkan jika tidak perlu.
   o Pesan error tidak membeberkan path server.

8. QA Checklist
   • Enroll: coba 3 foto (tajam, buram, gelap). Hanya yang tajam & terang yang lolos.
   • Verify: capture 3 kondisi di halaman absen. Buram & gelap tertolak sebelum AWS.
   • Cek DB attendance: kolom quality_blur_var & quality_brightness terisi saat verify sukses.
   • Ubah .env nilai FACE_MIN_LAPLACE jadi tinggi → foto borderline jadi tertolak.
   • Matikan Python/OpenCV sementara → pesan “Quality check gagal” muncul, sistem tetap stabil.

9. Catatan
   • Kita sengaja tidak deteksi bounding-box wajah di OpenCV (biar ringan). Deteksi identitas & wajah tetap di AWS.
   • Kalau nanti mau tambah cek rasio wajah (supaya wajah harus mengisi minimal 10–15% frame), bisa ditambah di Story lanjutan dengan pakai AWS DetectFaces atau OpenCV DNN bbox untuk ukur luas wajah → dibanding luas gambar.
