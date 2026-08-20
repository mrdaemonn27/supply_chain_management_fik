<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sumber data FAQ Assistant pada landing page.
 *
 * Tabel dibuat dan diisi satu kali agar instalasi lama tetap langsung dapat
 * memakai FAQ. Kolom status dan urutan disiapkan supaya pengelolaan oleh
 * Laboran/Admin dapat ditambahkan tanpa mengubah struktur datanya lagi.
 */
class Faq_model extends CI_Model
{
    private $table = 'faq';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->ensure_schema();
    }

    public function get_active_faqs()
    {
        return $this->db
            ->select('id_faq, slug, category, question, answer, keywords, source_reference, sort_order')
            ->from($this->table)
            ->where('is_active', 1)
            ->order_by('sort_order', 'ASC')
            ->order_by('id_faq', 'ASC')
            ->get()
            ->result_array();
    }

    private function ensure_schema()
    {
        if ($this->db->table_exists($this->table)) {
            if ((int) $this->db->count_all($this->table) === 0) {
                $this->db->insert_batch($this->table, $this->default_faqs());
            }
            return;
        }

        $created = $this->db->query("CREATE TABLE `faq` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        if ($created) {
            $this->db->insert_batch($this->table, $this->default_faqs());
        }
    }

    private function default_faqs()
    {
        return [
            [
                'slug' => 'cara-meminjam-barang',
                'category' => 'Pengajuan',
                'question' => 'Bagaimana cara meminjam barang?',
                'answer' => 'Masuk ke akun SCM FIK, buka katalog peminjaman, pilih ruangan atau laboratorium lalu pilih barang. Tekan Ajukan Peminjaman, isi jumlah, tanggal pinjam dan kembali, keperluan, kondisi awal, serta unggah foto kondisi barang. Setelah dikirim, pengajuan dapat dipantau melalui Riwayat Peminjaman.',
                'keywords' => 'pinjam barang ajukan katalog alat tanggal foto kondisi awal',
                'source_reference' => 'Katalog dan Form Pengajuan Peminjaman SCM FIK',
                'sort_order' => 10,
            ],
            [
                'slug' => 'mengetahui-status-pengajuan',
                'category' => 'Status',
                'question' => 'Bagaimana mengetahui status pengajuan?',
                'answer' => 'Buka menu Riwayat Peminjaman setelah login. Status terbaru tampil pada setiap transaksi dan dapat dicari berdasarkan nama barang, status, atau tanggal. Sistem juga mengirim notifikasi ketika pengajuan berpindah tahap, disetujui, ditolak, QR diaktifkan, atau barang diserahkan.',
                'keywords' => 'status riwayat notifikasi proses cek pengajuan',
                'source_reference' => 'Riwayat Peminjaman dan Notifikasi SCM FIK',
                'sort_order' => 20,
            ],
            [
                'slug' => 'pihak-yang-menyetujui',
                'category' => 'Persetujuan',
                'question' => 'Siapa yang menyetujui peminjaman?',
                'answer' => 'Persetujuan berjalan berurutan: Kaprodi memberi persetujuan awal, Laboran memverifikasi barang, lalu Kaur memberi persetujuan akhir. Stok sudah direservasi sejak pengajuan berhasil dikirim dan tetap teralokasi selama proses. Setelah seluruh tahap selesai, Laboran memfinalkan QR transaksi sebelum barang dapat diambil.',
                'keywords' => 'setuju approval kaprodi laboran admin kaur urutan',
                'source_reference' => 'Alur Approval Peminjaman SCM FIK',
                'sort_order' => 30,
            ],
            [
                'slug' => 'alasan-peminjaman-ditolak',
                'category' => 'Persetujuan',
                'question' => 'Kenapa peminjaman saya ditolak?',
                'answer' => 'Pengajuan dapat ditolak oleh Kaprodi, Laboran, atau Kaur karena keperluan atau pertimbangan pada tahap pemeriksaan. Periksa notifikasi dan status di Riwayat Peminjaman untuk mengetahui tahap penolakannya. Reservasi stok akan dilepas otomatis ketika pengajuan ditolak. Jika alasan rinci belum terlihat, hubungi Laboran.',
                'keywords' => 'ditolak gagal alasan stok catatan kaprodi laboran kaur',
                'source_reference' => 'Status Approval dan Notifikasi Peminjaman SCM FIK',
                'sort_order' => 40,
            ],
            [
                'slug' => 'lama-proses-persetujuan',
                'category' => 'Persetujuan',
                'question' => 'Berapa lama proses persetujuan?',
                'answer' => 'SCM FIK belum menetapkan durasi atau SLA otomatis. Lama proses bergantung pada waktu respons Kaprodi, pemeriksaan stok oleh Laboran, dan persetujuan Kaur. Pantau tahap aktif melalui Riwayat Peminjaman dan notifikasi.',
                'keywords' => 'berapa lama durasi waktu sla proses menunggu',
                'source_reference' => 'Alur Status Persetujuan SCM FIK',
                'sort_order' => 50,
            ],
            [
                'slug' => 'persetujuan-kaprodi-kedaluwarsa',
                'category' => 'Persetujuan',
                'question' => 'Bagaimana jika persetujuan Kaprodi kedaluwarsa?',
                'answer' => 'Setiap pengajuan mempunyai batas waktu persetujuan Kaprodi sesuai pengaturan Laboran. Jika Kaprodi belum memberi keputusan sampai tenggat, status berubah menjadi Kedaluwarsa / Ditolak Otomatis, reservasi stok dilepas, dan user menerima notifikasi. Jika barang masih dibutuhkan, lakukan pengajuan peminjaman baru.',
                'keywords' => 'kaprodi kedaluwarsa kadaluarsa expired menunggu acc',
                'source_reference' => 'Pengaturan Tenggat dan Status Approval Kaprodi SCM FIK',
                'sort_order' => 60,
            ],
            [
                'slug' => 'cara-mengembalikan-barang',
                'category' => 'Pengembalian',
                'question' => 'Bagaimana cara mengembalikan barang?',
                'answer' => 'Bawa barang ke Laboran sesuai tanggal pengembalian dan tunjukkan QR transaksi yang sama dengan saat pengambilan. Laboran memindai QR, memeriksa jumlah dan kondisi barang, mengunggah bukti bila diperlukan, lalu menyelesaikan pengembalian di sistem.',
                'keywords' => 'kembali pengembalian barang laboran kondisi tanggal',
                'source_reference' => 'Validasi Pengembalian SCM FIK',
                'sort_order' => 70,
            ],
            [
                'slug' => 'menggunakan-qr-peminjaman',
                'category' => 'QR',
                'question' => 'Bagaimana menggunakan QR peminjaman?',
                'answer' => 'QR muncul di Riwayat Peminjaman setelah Kaprodi, Laboran, dan Kaur menyetujui pengajuan serta Laboran memfinalkan transaksi. Tunjukkan QR kepada Laboran saat pengambilan barang. Simpan QR tersebut karena QR yang sama digunakan kembali saat pengembalian.',
                'keywords' => 'qr kode scan pindai ambil serah terima riwayat finalisasi',
                'source_reference' => 'QR Serah Terima Peminjaman SCM FIK',
                'sort_order' => 80,
            ],
            [
                'slug' => 'mencari-ruangan-laboratorium',
                'category' => 'Katalog',
                'question' => 'Bagaimana mencari ruangan atau laboratorium?',
                'answer' => 'Setelah login, pilih kartu ruangan atau laboratorium pada Dashboard lalu tekan Masuk Ruangan untuk melihat aset di lokasi tersebut. Di katalog, kolom pencarian dapat digunakan untuk mencari berdasarkan nama alat, kode aset, ruangan, kondisi, atau stok.',
                'keywords' => 'cari ruangan laboratorium lab lokasi masuk ruangan katalog',
                'source_reference' => 'Dashboard Ruangan dan Pencarian Katalog SCM FIK',
                'sort_order' => 90,
            ],
            [
                'slug' => 'barang-rusak',
                'category' => 'Pengembalian',
                'question' => 'Apa yang harus dilakukan jika barang rusak?',
                'answer' => 'Segera laporkan kerusakan kepada Laboran dan jangan menyembunyikan kondisi barang. Saat pengembalian, Laboran memilih kondisi Rusak, mengisi keterangan wajib, dan mengunggah bukti kerusakan. Kondisi aset kemudian tercatat di SCM FIK untuk tindak lanjut.',
                'keywords' => 'rusak kerusakan hilang lapor bukti foto kondisi',
                'source_reference' => 'Pencatatan Kondisi Pengembalian SCM FIK',
                'sort_order' => 100,
            ],
        ];
    }
}
