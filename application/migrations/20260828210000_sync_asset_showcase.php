<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pasangkan aset WebP katalog dengan data barang dan laboratorium yang tepat.
 * Data lama dicari berdasarkan kode agar stok dan riwayat tetap terjaga.
 */
class Migration_Sync_asset_showcase extends CI_Migration
{
    private function assets()
    {
        return array(
            array('kode_aset' => 'MTL-002', 'nama_aset' => 'Air Compressor Orange', 'room' => 'Lab Metal Working', 'gambar' => '9a9e716d169ba27989cf2819795d8a96.webp', 'deskripsi' => 'Kompresor angin portabel untuk pekerjaan di laboratorium metal working.'),
            array('kode_aset' => 'SBL-001', 'nama_aset' => 'Alat Sablon', 'room' => 'Lab Sablon', 'gambar' => 'alat sablon.webp', 'deskripsi' => 'Peralatan pendukung proses cetak saring dan produksi sablon.'),
            array('kode_aset' => 'MTL-004', 'nama_aset' => 'Bending Pipa', 'room' => 'Lab Metal Working', 'gambar' => 'blending pipa.webp', 'deskripsi' => 'Alat pembengkok pipa untuk pembentukan material logam.'),
            array('kode_aset' => 'WAC-001', 'nama_aset' => 'Wacom Cintiq 13 HD', 'room' => 'Lab Tab Cintiq', 'gambar' => 'cintiq.webp', 'deskripsi' => 'Pen display untuk ilustrasi digital, desain, dan animasi.'),
            array('kode_aset' => 'WOD-008', 'nama_aset' => 'Cordless Electric Drill', 'room' => 'Lab Woodworking', 'gambar' => 'cordless electric drill.webp', 'deskripsi' => 'Bor listrik tanpa kabel untuk perakitan dan pengerjaan material kayu.'),
            array('kode_aset' => 'KB-001', 'nama_aset' => 'Keyboard USB', 'room' => 'Lab Multimedia', 'gambar' => 'keyboard.webp', 'deskripsi' => 'Keyboard USB untuk workstation multimedia.'),
            array('kode_aset' => 'GRN-001', 'nama_aset' => 'Kino Flo LED', 'room' => 'Lab Green Screen', 'gambar' => 'kino flo led.webp', 'deskripsi' => 'Lampu LED sinematik untuk produksi video dan pencahayaan green screen.'),
            array('kode_aset' => 'IDL-001', 'nama_aset' => 'Kursi Lab', 'room' => 'Lab Idealoka', 'gambar' => 'kursi lab.webp', 'deskripsi' => 'Kursi kerja untuk kegiatan kolaborasi dan pengembangan ide.'),
            array('kode_aset' => 'MTL-008', 'nama_aset' => 'Mesin Kompresor Besar Izumi', 'room' => 'Lab Metal Working', 'gambar' => 'mesin kompresor besar izumi.webp', 'deskripsi' => 'Kompresor berkapasitas besar untuk mendukung peralatan pneumatik.'),
            array('kode_aset' => 'MTL-005', 'nama_aset' => 'Mesin Las Listrik', 'room' => 'Lab Metal Working', 'gambar' => 'mesin las lisrtrik.webp', 'deskripsi' => 'Mesin las listrik untuk penyambungan dan fabrikasi logam.'),
            array('kode_aset' => 'WOD-014', 'nama_aset' => 'Mesin Serut Kayu', 'room' => 'Lab Woodworking', 'gambar' => 'mesin serut kayu.webp', 'deskripsi' => 'Mesin serut untuk meratakan dan membentuk permukaan kayu.'),
            array('kode_aset' => 'CGI-MON-001', 'nama_aset' => 'Monitor HP', 'room' => 'Lab CGI', 'gambar' => 'monitor hp.webp', 'deskripsi' => 'Monitor HP untuk pemodelan, rendering, dan produksi visual digital.'),
            array('kode_aset' => 'MON-002', 'nama_aset' => 'Monitor HP P24', 'room' => 'Lab Multimedia', 'gambar' => 'monitor hp24.webp', 'deskripsi' => 'Monitor HP 24 inci untuk workstation multimedia.'),
            array('kode_aset' => 'MAC-MON-001', 'nama_aset' => 'Monitor', 'room' => 'Lab Mac', 'gambar' => 'monitor.webp', 'deskripsi' => 'Monitor tambahan untuk workstation desain dan penyuntingan.'),
            array('kode_aset' => 'MTL-001', 'nama_aset' => 'Portable Cut Off', 'room' => 'Lab Metal Working', 'gambar' => 'portable cutoff.webp', 'deskripsi' => 'Mesin potong portabel untuk material logam.'),
            array('kode_aset' => 'PROJ-001', 'nama_aset' => 'Projector', 'room' => 'Aula FIK', 'gambar' => 'projector.webp', 'deskripsi' => 'Proyektor untuk presentasi, seminar, dan kegiatan di Aula FIK.'),
            array('kode_aset' => 'FOTO-014', 'nama_aset' => 'Red Lamp', 'room' => 'Lab Fotografi', 'gambar' => 'red lamp.webp', 'deskripsi' => 'Lampu merah studio untuk kebutuhan pencahayaan dan proses fotografi.'),
            array('kode_aset' => 'FOTO-006', 'nama_aset' => 'Reflector Flash', 'room' => 'Lab Fotografi', 'gambar' => 'reflector flash.webp', 'deskripsi' => 'Reflektor untuk mengarahkan dan melembutkan cahaya flash.'),
            array('kode_aset' => 'FOTO-001', 'nama_aset' => 'Studio Flash Godox QS400 II', 'room' => 'Lab Fotografi', 'gambar' => 'studio flash godox400.webp', 'deskripsi' => 'Lampu flash studio Godox untuk produksi fotografi profesional.'),
            array('kode_aset' => 'WAC-003', 'nama_aset' => 'Wacom Pen Tablet', 'room' => 'Lab Tab Cintiq', 'gambar' => 'tab.webp', 'deskripsi' => 'Pen tablet untuk ilustrasi, desain grafis, dan olah visual.'),
            array('kode_aset' => 'WOD-007', 'nama_aset' => 'Trimer Makita RP2300FC', 'room' => 'Lab Woodworking', 'gambar' => 'trimer makita rp2300fc.webp', 'deskripsi' => 'Router kayu Makita untuk pemotongan profil dan pembentukan detail.'),
            array('kode_aset' => 'WOD-005', 'nama_aset' => 'Trimer', 'room' => 'Lab Woodworking', 'gambar' => 'trimer.webp', 'deskripsi' => 'Mesin trimer untuk merapikan tepian dan detail kayu.'),
            array('kode_aset' => 'FOTO-013', 'nama_aset' => 'Tripod', 'room' => 'Lab Fotografi', 'gambar' => 'tripod.webp', 'deskripsi' => 'Tripod penyangga kamera untuk pemotretan dan produksi video.'),
            array('kode_aset' => 'VGA-001', 'nama_aset' => 'VGA Splitter 1 to 2', 'room' => 'Lab Multimedia', 'gambar' => 'vga 1 to 2.webp', 'deskripsi' => 'Pembagi sinyal VGA satu masukan ke dua keluaran display.'),
        );
    }

    public function up()
    {
        if (!$this->db->table_exists('aset') || !$this->db->table_exists('ruangan'))
        {
            throw new RuntimeException('Tabel aset atau ruangan belum tersedia. Import database dasar terlebih dahulu.');
        }

        $this->db->trans_start();

        foreach ($this->assets() as $asset)
        {
            $image_path = FCPATH.'assets/uploads/barang/'.$asset['gambar'];
            if (!is_file($image_path))
            {
                throw new RuntimeException('Aset gambar tidak ditemukan: '.$asset['gambar']);
            }

            $room = $this->db
                ->select('id_ruangan')
                ->where('nama_ruangan', $asset['room'])
                ->limit(1)
                ->get('ruangan')
                ->row();

            if (!$room)
            {
                throw new RuntimeException('Ruangan tujuan tidak ditemukan: '.$asset['room']);
            }

            $existing = $this->db
                ->select('id_aset')
                ->where('kode_aset', $asset['kode_aset'])
                ->limit(1)
                ->get('aset')
                ->row();

            $data = array(
                'id_ruangan' => (int) $room->id_ruangan,
                'nama_aset' => $asset['nama_aset'],
                'kode_aset' => $asset['kode_aset'],
                'deskripsi' => $asset['deskripsi'],
                'gambar' => $asset['gambar'],
            );

            if ($existing)
            {
                $this->db
                    ->where('id_aset', (int) $existing->id_aset)
                    ->update('aset', $data);
            }
            else
            {
                $data['jumlah_total'] = 1;
                $data['jumlah_reserved'] = 0;
                $data['jumlah_dipinjam'] = 0;
                $data['jumlah_tersedia'] = 1;
                $data['kondisi'] = 'Baik';
                $data['total_peminjaman'] = 0;
                $this->db->insert('aset', $data);
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            throw new RuntimeException('Transaksi sinkronisasi aset katalog gagal.');
        }
    }

    public function down()
    {
        // Non-destruktif: barang dapat sudah memiliki peminjaman atau riwayat.
    }
}
