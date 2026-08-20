-- Sumber data FAQ Assistant landing page SCM FIK.
-- Struktur ini siap dipakai untuk layar pengelolaan Laboran/Admin pada tahap berikutnya.

CREATE TABLE IF NOT EXISTS `faq` (
  `id_faq` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(120) NOT NULL,
  `category` varchar(80) NOT NULL DEFAULT 'Umum',
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `keywords` varchar(500) DEFAULT NULL,
  `source_reference` varchar(255) NOT NULL DEFAULT 'Fitur SCM FIK',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_faq`),
  UNIQUE KEY `uq_faq_slug` (`slug`),
  KEY `idx_faq_active_order` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `faq`
(`slug`, `category`, `question`, `answer`, `keywords`, `source_reference`, `sort_order`)
VALUES
('cara-meminjam-barang', 'Pengajuan', 'Bagaimana cara meminjam barang?', 'Masuk ke akun SCM FIK, buka katalog peminjaman, pilih ruangan atau laboratorium lalu pilih barang. Tekan Ajukan Peminjaman, isi jumlah, tanggal pinjam dan kembali, keperluan, kondisi awal, serta unggah foto kondisi barang. Setelah dikirim, pengajuan dapat dipantau melalui Riwayat Peminjaman.', 'pinjam barang ajukan katalog alat tanggal foto kondisi awal', 'Katalog dan Form Pengajuan Peminjaman SCM FIK', 10),
('mengetahui-status-pengajuan', 'Status', 'Bagaimana mengetahui status pengajuan?', 'Buka menu Riwayat Peminjaman setelah login. Status terbaru tampil pada setiap transaksi dan dapat dicari berdasarkan nama barang, status, atau tanggal. Sistem juga mengirim notifikasi ketika pengajuan berpindah tahap, disetujui, ditolak, QR diaktifkan, atau barang diserahkan.', 'status riwayat notifikasi proses cek pengajuan', 'Riwayat Peminjaman dan Notifikasi SCM FIK', 20),
('pihak-yang-menyetujui', 'Persetujuan', 'Siapa yang menyetujui peminjaman?', 'Persetujuan berjalan berurutan: Kaprodi memberi persetujuan awal, Laboran memverifikasi barang, lalu Kaur memberi persetujuan akhir. Stok sudah direservasi sejak pengajuan berhasil dikirim dan tetap teralokasi selama proses. Setelah seluruh tahap selesai, Laboran memfinalkan QR transaksi sebelum barang dapat diambil.', 'setuju approval kaprodi laboran admin kaur urutan', 'Alur Approval Peminjaman SCM FIK', 30),
('alasan-peminjaman-ditolak', 'Persetujuan', 'Kenapa peminjaman saya ditolak?', 'Pengajuan dapat ditolak oleh Kaprodi, Laboran, atau Kaur karena keperluan atau pertimbangan pada tahap pemeriksaan. Periksa notifikasi dan status di Riwayat Peminjaman untuk mengetahui tahap penolakannya. Reservasi stok akan dilepas otomatis ketika pengajuan ditolak. Jika alasan rinci belum terlihat, hubungi Laboran.', 'ditolak gagal alasan stok catatan kaprodi laboran kaur', 'Status Approval dan Notifikasi Peminjaman SCM FIK', 40),
('lama-proses-persetujuan', 'Persetujuan', 'Berapa lama proses persetujuan?', 'SCM FIK belum menetapkan durasi atau SLA otomatis. Lama proses bergantung pada waktu respons Kaprodi, pemeriksaan stok oleh Laboran, dan persetujuan Kaur. Pantau tahap aktif melalui Riwayat Peminjaman dan notifikasi.', 'berapa lama durasi waktu sla proses menunggu', 'Alur Status Persetujuan SCM FIK', 50),
('persetujuan-kaprodi-kedaluwarsa', 'Persetujuan', 'Bagaimana jika persetujuan Kaprodi kedaluwarsa?', 'Setiap pengajuan mempunyai batas waktu persetujuan Kaprodi sesuai pengaturan Laboran. Jika Kaprodi belum memberi keputusan sampai tenggat, status berubah menjadi Kedaluwarsa / Ditolak Otomatis, reservasi stok dilepas, dan user menerima notifikasi. Jika barang masih dibutuhkan, lakukan pengajuan peminjaman baru.', 'kaprodi kedaluwarsa kadaluarsa expired menunggu acc', 'Pengaturan Tenggat dan Status Approval Kaprodi SCM FIK', 60),
('cara-mengembalikan-barang', 'Pengembalian', 'Bagaimana cara mengembalikan barang?', 'Bawa barang ke Laboran sesuai tanggal pengembalian dan tunjukkan QR transaksi yang sama dengan saat pengambilan. Laboran memindai QR, memeriksa jumlah dan kondisi barang, mengunggah bukti bila diperlukan, lalu menyelesaikan pengembalian di sistem.', 'kembali pengembalian barang laboran kondisi tanggal', 'Validasi Pengembalian SCM FIK', 70),
('menggunakan-qr-peminjaman', 'QR', 'Bagaimana menggunakan QR peminjaman?', 'QR muncul di Riwayat Peminjaman setelah Kaprodi, Laboran, dan Kaur menyetujui pengajuan serta Laboran memfinalkan transaksi. Tunjukkan QR kepada Laboran saat pengambilan barang. Simpan QR tersebut karena QR yang sama digunakan kembali saat pengembalian.', 'qr kode scan pindai ambil serah terima riwayat finalisasi', 'QR Serah Terima Peminjaman SCM FIK', 80),
('mencari-ruangan-laboratorium', 'Katalog', 'Bagaimana mencari ruangan atau laboratorium?', 'Setelah login, pilih kartu ruangan atau laboratorium pada Dashboard lalu tekan Masuk Ruangan untuk melihat aset di lokasi tersebut. Di katalog, kolom pencarian dapat digunakan untuk mencari berdasarkan nama alat, kode aset, ruangan, kondisi, atau stok.', 'cari ruangan laboratorium lab lokasi masuk ruangan katalog', 'Dashboard Ruangan dan Pencarian Katalog SCM FIK', 90),
('barang-rusak', 'Pengembalian', 'Apa yang harus dilakukan jika barang rusak?', 'Segera laporkan kerusakan kepada Laboran dan jangan menyembunyikan kondisi barang. Saat pengembalian, Laboran memilih kondisi Rusak, mengisi keterangan wajib, dan mengunggah bukti kerusakan. Kondisi aset kemudian tercatat di SCM FIK untuk tindak lanjut.', 'rusak kerusakan hilang lapor bukti foto kondisi', 'Pencatatan Kondisi Pengembalian SCM FIK', 100);
