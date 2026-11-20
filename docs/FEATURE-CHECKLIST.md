# Checklist Fitur Pesona Cloud Drive

Dokumen ini menjadi daftar kerja pengembangan. Status disusun dari fitur yang tercantum pada kode dan dokumentasi repository saat diperiksa; status **Ada** belum berarti seluruh alur sudah lulus uji integrasi di server produksi.

**Legenda**
- [x] Ada di kode / tersedia menurut dokumentasi proyek.
- [~] Ada sebagian atau masih memiliki batasan yang perlu diselesaikan.
- [ ] Belum tersedia / belum terverifikasi implementasinya.

## 1. Akun, autentikasi, dan hak akses
- [x] Setup administrator pertama melalui halaman setup.
- [x] Login dan logout.
- [x] Role Admin dan User dengan filter akses server.
- [x] Ubah password sendiri.
- [x] Password hash dan regenerasi session setelah perubahan password.
- [x] Admin membuat akun pengguna.
- [x] Admin mengubah status akun aktif / suspend.
- [x] Admin mengatur kuota penyimpanan per pengguna.
- [x] Admin reset password pengguna dan mewajibkan ganti password.
- [ ] Lupa password / reset password mandiri melalui email.
- [ ] Verifikasi alamat email.
- [ ] Two-factor authentication (2FA).
- [ ] Manajemen sesi/perangkat aktif dan logout dari perangkat lain.

## 2. Dashboard dan file manager
- [x] Dashboard pengguna.
- [x] Buat folder dan subfolder.
- [x] Upload beberapa file sekaligus.
- [x] Download file.
- [x] Rename file dan folder.
- [x] Pindahkan file ke folder lain.
- [x] Tampilan daftar dan grid.
- [x] Preferensi tampilan list/grid disimpan di browser.
- [x] Informasi pemakaian kuota dan kuota akun.
- [x] Breadcrumb navigasi folder.
- [x] Tandai file sebagai favorit / batalkan favorit.
- [~] Pilih banyak file dan folder sekaligus.
  - Catatan: bulk trash mendukung file dan folder; folder dipindahkan beserta isi turunannya.
  - Bagikan, download, dan pindah folder massal saat ini hanya memproses file terpilih; folder yang ikut dipilih dilewati.
- [ ] Pindahkan folder ke folder lain.
- [ ] Duplikasi / salin file dan folder.
- [ ] Drag-and-drop upload.
- [ ] Upload dengan progress bar, pause, dan resume.
- [ ] Upload folder utuh dengan struktur subfolder.
- [ ] Sortir berdasarkan nama, ukuran, tipe, dan tanggal.
- [ ] Filter berdasarkan tipe file, tanggal, dan pemilik.
- [ ] Preview cepat dari panel samping.
- [ ] Shortcut keyboard dan pemilihan rentang item.

## 3. Pencarian
- [x] Cari nama file.
- [x] Cari nama folder.
- [x] Hasil pencarian dibatasi pada drive milik akun.
- [ ] Pencarian isi dokumen (full-text search).
- [ ] Filter lanjutan dan pencarian tersimpan.

## 4. Preview dan pengelolaan dokumen
- [x] Thumbnail gambar pada daftar file.
- [x] Preview JPEG, PNG, GIF, dan WebP.
- [x] Preview PDF.
- [x] Preview video MP4, WebM, dan OGG.
- [x] Preview dokumen Office melalui konversi PDF dengan LibreOffice (opsional di server).
- [~] Pengiriman file preview besar belum sepenuhnya dioptimalkan untuk semua skenario; dukungan range/streaming perlu diuji dan ditingkatkan.
- [ ] Preview audio dengan pemutar audio.
- [ ] Editor dokumen / spreadsheet di browser.
- [ ] Komentar dan anotasi dokumen.
- [ ] File versioning dan riwayat revisi.

## 5. Sampah dan pemulihan
- [x] Pindahkan file ke Sampah.
- [x] Pindahkan folder ke Sampah beserta isi turunannya.
- [x] Pulihkan file.
- [x] Pulihkan folder.
- [x] Hapus permanen file.
- [x] Hapus permanen folder beserta seluruh isi.
- [x] Pilih banyak item di Sampah.
- [x] Pulihkan massal.
- [x] Hapus permanen massal.
- [x] Tampilan list dan grid di Sampah.
- [~] Pemulihan folder bertingkat dilakukan bertahap dari parent ke child.
- [ ] Kebijakan retensi otomatis (mis. hapus permanen setelah N hari).
- [ ] Pengosongan Sampah otomatis terjadwal dan pengaturan retensi per akun.

## 6. Berbagi file
- [x] Buat tautan berbagi untuk file.
- [x] Tautan publik menggunakan token acak dan hash token di database.
- [x] URL token disimpan terenkripsi agar dapat ditampilkan kembali kepada pemilik.
- [x] Masa berlaku 1 hari, 7 hari, 30 hari, atau tanpa kedaluwarsa.
- [x] Halaman publik untuk membuka file yang dibagikan.
- [x] Preview dan download melalui tautan berbagi.
- [x] Daftar link berbagi milik pengguna.
- [x] Ubah masa berlaku link.
- [x] Cabut satu link atau seluruh link untuk satu file.
- [x] Buat link untuk beberapa file sekaligus.
- [~] Link bersifat bearer link: siapa pun yang memiliki URL dapat mengakses sampai link dicabut/kedaluwarsa.
- [ ] Bagikan folder beserta seluruh isinya.
- [ ] Batasi link dengan password.
- [ ] Batasi jumlah download / jumlah akses.
- [ ] Izin berbagi granular (viewer, commenter, editor).
- [ ] Undangan berbagi privat berdasarkan email dan akun penerima.
- [ ] Tanggal mulai aktif untuk link berbagi.

## 7. Aktivitas dan administrasi
- [x] Catat aktivitas login/logout.
- [x] Catat upload, rename, move, favorit, trash, restore, purge, share, dan revoke.
- [x] Halaman riwayat aktivitas.
- [x] Filter aktivitas berdasarkan tanggal dan jenis aktivitas.
- [x] Admin dapat melihat/filter aktivitas pengguna.
- [ ] Export audit log ke CSV/Excel.
- [ ] Notifikasi keamanan untuk login baru dan perubahan akun.
- [ ] Dashboard admin dengan statistik penggunaan seluruh pengguna.
- [ ] Manajemen kebijakan kuota dan batas upload secara terpusat.

## 8. Penyimpanan, performa, dan keamanan
- [x] Penyimpanan file lokal privat di luar document root.
- [x] Pemeriksaan kepemilikan file/folder pada alur utama.
- [x] Pemeriksaan kuota saat upload.
- [x] CSRF dan validasi input pada form yang tersedia.
- [x] Database migrations.
- [x] Pengiriman file privat melalui helper delivery yang mendukung konfigurasi internal redirect.
- [ ] Antivirus / malware scanning saat upload.
- [ ] Pemeriksaan konten file lebih mendalam dan kebijakan blokir ekstensi berisiko.
- [ ] Rate limiting login, setup, dan endpoint publik.
- [ ] Audit keamanan menyeluruh dan penetration test.
- [ ] Penyimpanan object storage (S3-compatible).
- [ ] Enkripsi file saat tersimpan (at-rest) dengan pengelolaan kunci.
- [ ] Deduplication file.
- [ ] Optimasi thumbnail dan preview melalui background queue.
- [ ] Monitoring kapasitas disk dan peringatan storage hampir penuh.

## 9. Backup, pemulihan, dan operasional
- [x] Installer aaPanel.
- [x] Updater in-place aaPanel yang mempertahankan .env, database, private storage, dan data pengguna.
- [x] Migration dijalankan saat update.
- [ ] Backup database terjadwal.
- [ ] Backup private storage terjadwal.
- [ ] Backup off-site / remote storage.
- [ ] Prosedur restore lengkap yang diuji.
- [ ] Pemeriksaan integritas backup dan notifikasi kegagalan.
- [ ] Health check aplikasi, database, dan storage.
- [ ] Dokumentasi rollback versi aplikasi.

## 10. Pengujian dan kualitas rilis
- [x] GitHub Actions untuk pemeriksaan sintaks PHP.
- [ ] Unit tests untuk model, service, dan controller.
- [ ] Feature/integration tests untuk login, upload, download, share, trash, dan restore.
- [ ] Test isolasi data antar pengguna dan eskalasi hak akses.
- [ ] Test batas kuota, file besar, nama file khusus, dan upload gagal.
- [ ] Test browser desktop dan mobile untuk seluruh halaman.
- [ ] Test backup/restore pada server bersih.
- [ ] Pipeline staging sebelum rilis produksi.

---

## Urutan pengembangan yang disarankan

1. **Prioritas 1 — Keamanan dan keandalan:** rate limiting, validasi upload, tes isolasi antar pengguna, backup/restore otomatis.
2. **Prioritas 2 — Pengalaman file manager:** upload progress/resume, drag-and-drop, sortir/filter, pindah folder, salin file/folder.
3. **Prioritas 3 — Kolaborasi:** share folder, password link, izin granular, undangan email, komentar.
4. **Prioritas 4 — Fitur lanjutan:** file versioning, pencarian isi dokumen, editor online.
5. **Prioritas 5 — Skala dan operasional:** object storage, background queue, monitoring, audit dan pipeline rilis.

## Cara memperbarui checklist

Setelah fitur selesai dan diuji, ubah `[ ]` menjadi `[x]`. Gunakan `[~]` jika fitur baru sebagian atau masih memiliki batasan. Jangan tandai selesai hanya karena tampilan sudah dibuat; pastikan backend, hak akses, dan alur pengguna juga sudah diuji.
