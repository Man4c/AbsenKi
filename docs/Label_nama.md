Tujuan
Tambahkan “label nama + persen kecocokan” di atas kotak hijau wajah saat verifikasi absen staff. Label hanya muncul kalau wajah yang terdeteksi milik user yang sedang login.
Lingkup perubahan

1. Saat enroll (admin upload wajah)
   o Simpan ExternalImageId Rekognition dengan format:
   "{user_id}|{user_name}" (contoh: 2|Firman).
2. Saat verifikasi (staff absen)
   o Ambil hasil SearchFacesByImage teratas (top-1).
   o Parse ExternalImageId → matchedUserId & matchedUserName.
   o Validasi: matchedUserId harus sama dengan auth()->id().
   o Jika cocok, kirim ke frontend:
    bbox (normalized Rekognition box)
    name (mis. “Firman”)
    score (persen dibulatkan, mis. 97)
3. UI/Overlay
   o Di canvas overlay, tampilkan label kecil “Firman (97%)” di atas kotak hijau.
   o Tampilkan juga chip status kecil di panel: Terdeteksi: Firman • 97%.
   o Jika bukan milik user → jangan tampilkan nama; tetap error “Wajah terdeteksi bukan milik Anda”.
   File yang perlu disentuh
   • Backend
   o app/Services/FaceEnrollService.php
    Saat indexFaces, set ExternalImageId = "{user_id}|{user_name}".
   o app/Services/FaceVerificationService.php
    Setelah searchFacesByImage, parse ExternalImageId, bandingkan user_id, siapkan payload { ok, bbox, name, score }.
   o (Jika payload Livewire khusus) app/Livewire/Staff/Absen.php
    Terima data { bbox, name, score } untuk dipakai di view.
   • Frontend (komponen absen staff)
   o Blade/Livewire view absen (mis. resources/views/staff/absen.blade.php)
    Render kanvas overlay dan label “Nama (Persen%)”.
   o JS kecil untuk menggambar label di atas bbox.
   Acceptance criteria
   • Enroll menyimpan ExternalImageId dengan format {id}|{name}.
   • Verifikasi sukses: kotak hijau + label “Nama (xx%)” tampil, dan chip status muncul.
   • Verifikasi bukan milik user: gagal, tidak ada label nama.
   • Tidak mengubah alur geofence/absen selain overlay.
   • .env FACE_THRESHOLD tetap dipakai sebagai ambang similarity.
   • Dark mode/desktop OK.
   • Log singkat saat verifikasi: userId, matchedId, score.
   Keamanan & Privasi
   • Label nama hanya muncul jika matchedUserId == auth()->id().
   • Jangan tampilkan nama orang lain ketika tidak cocok.
   Catatan implementasi
   • Pakai bbox dari Rekognition (normalized left, top, width, height) → konversi ke pixel sesuai ukuran gambar snapshot.
   • Letakkan label tepat di atas bbox, beri background semi-transparan agar terbaca.
   Output yang diharapkan
   • PR dengan judul: feat(verify): tampilkan label nama + skor di bbox verifikasi
   • Ringkas perubahan & cara uji di deskripsi PR.

Testing manual (QA)

1. Enroll wajah user A (punya beberapa foto).
2. Login sebagai user A → ambil foto → harus tampil “A (≥threshold%)” & lolos.
3. Login sebagai user B → ambil foto user A → harus gagal tanpa label nama.
4. Coba pencahayaan gelap/blur → quality gate tetap berlaku.
