<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sinkronisasi daftar fasilitas beranda/dashboard dengan aset WebP ruangan.
 * Migration ini idempoten: data yang sama diperbarui, data yang belum ada
 * ditambahkan, dan tidak ada ruangan/aset yang dihapus.
 */
class Migration_Sync_room_showcase extends CI_Migration
{
    private function rooms()
    {
        return array(
            array('nama_ruangan' => 'Aula FIK', 'icon' => 'building', 'warna' => '#F97316', 'foto' => 'aula.webp', 'deskripsi' => 'Ruang serbaguna untuk seminar, pameran, presentasi, dan kegiatan akademik Fakultas Industri Kreatif.'),
            array('nama_ruangan' => 'Lab Audio', 'icon' => 'soundwave', 'warna' => '#8B5CF6', 'foto' => 'LAB AUDIO.webp', 'deskripsi' => 'Laboratorium produksi audio untuk rekaman, penyuntingan suara, dan pengembangan karya audiovisual.'),
            array('nama_ruangan' => 'Lab Batik', 'icon' => 'palette-fill', 'warna' => '#B45309', 'foto' => 'lab batik.blend.webp', 'deskripsi' => 'Ruang eksplorasi batik untuk proses desain motif, pencantingan, pewarnaan, dan produksi tekstil.'),
            array('nama_ruangan' => 'Lab CGI', 'icon' => 'badge-3d-fill', 'warna' => '#2563EB', 'foto' => 'lab cgi.webp', 'deskripsi' => 'Laboratorium computer-generated imagery untuk pemodelan, animasi, rendering, dan produksi visual digital.'),
            array('nama_ruangan' => 'Lab Finishing', 'icon' => 'brush-fill', 'warna' => '#059669', 'foto' => 'Iab finishingblend.webp', 'deskripsi' => 'Laboratorium penyelesaian karya untuk proses perakitan, penghalusan, pewarnaan, dan kontrol kualitas.'),
            array('nama_ruangan' => 'Lab Green Screen', 'icon' => 'camera-reels-fill', 'warna' => '#16A34A', 'foto' => 'lab greenscreen..webp', 'deskripsi' => 'Studio green screen untuk produksi video, compositing, virtual set, dan eksperimen sinematografi.'),
            array('nama_ruangan' => 'Lab Idealoka', 'icon' => 'lightbulb-fill', 'warna' => '#DB2777', 'foto' => 'lab idealoka.webp', 'deskripsi' => 'Ruang kolaborasi ide dan pengembangan konsep kreatif lintas disiplin.'),
            array('nama_ruangan' => 'Lab Incubator', 'icon' => 'rocket-takeoff-fill', 'warna' => '#EA580C', 'foto' => 'lab incubator.webp', 'deskripsi' => 'Ruang inkubasi untuk pengembangan prototipe, bisnis kreatif, dan kolaborasi proyek mahasiswa.'),
            array('nama_ruangan' => 'Lab Lukis', 'icon' => 'easel2-fill', 'warna' => '#DC2626', 'foto' => 'lab lukis.webp', 'deskripsi' => 'Studio seni lukis untuk eksplorasi medium, warna, komposisi, dan praktik seni rupa.'),
            array('nama_ruangan' => 'Lab Mac', 'icon' => 'apple', 'warna' => '#475569', 'foto' => 'lab Mac.webp', 'deskripsi' => 'Laboratorium komputer Mac untuk desain, multimedia, penyuntingan, dan produksi konten digital.'),
            array('nama_ruangan' => 'Lab Multimedia', 'icon' => 'display', 'warna' => '#8B5CF6', 'foto' => 'lab multimedia.webp', 'deskripsi' => 'Laboratorium Multimedia untuk praktikum desain grafis, editing video, dan animasi.'),
            array('nama_ruangan' => 'Lab Pola dan Jahit', 'icon' => 'scissors', 'warna' => '#7C3AED', 'foto' => 'lab pola dan jahit.webp', 'deskripsi' => 'Laboratorium busana untuk pembuatan pola, pemotongan bahan, penjahitan, dan penyelesaian produk fesyen.'),
            array('nama_ruangan' => 'Lab Sablon', 'icon' => 'layers-fill', 'warna' => '#0891B2', 'foto' => 'lab sablon.webp', 'deskripsi' => 'Laboratorium cetak saring untuk persiapan desain, afdruk, pencampuran tinta, dan produksi sablon.'),
            array('nama_ruangan' => 'Lab Tab Cintiq', 'icon' => 'tablet-landscape', 'warna' => '#4F46E5', 'foto' => 'lab tab cintiq.webp', 'deskripsi' => 'Laboratorium ilustrasi digital dengan perangkat pen display untuk menggambar, desain, dan animasi.'),
        );
    }

    public function up()
    {
        if (!$this->db->table_exists('ruangan'))
        {
            throw new RuntimeException('Tabel ruangan belum tersedia. Import database dasar terlebih dahulu.');
        }

        $this->db->trans_start();

        foreach ($this->rooms() as $room)
        {
            $existing = $this->db
                ->select('id_ruangan')
                ->where('nama_ruangan', $room['nama_ruangan'])
                ->limit(1)
                ->get('ruangan')
                ->row();

            if ($existing)
            {
                $this->db
                    ->where('id_ruangan', (int) $existing->id_ruangan)
                    ->update('ruangan', $room);
            }
            else
            {
                $this->db->insert('ruangan', $room);
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            throw new RuntimeException('Transaksi sinkronisasi ruangan gagal.');
        }
    }

    public function down()
    {
        // Sengaja non-destruktif. Menghapus ruangan dapat menghapus aset terkait
        // karena foreign key tabel aset memakai ON DELETE CASCADE.
    }
}
