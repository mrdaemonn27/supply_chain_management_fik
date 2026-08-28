<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pastikan setiap ruangan memiliki sedikitnya satu barang dengan visual WebP.
 * Beberapa visual generik dipakai ulang untuk unit inventaris yang berbeda;
 * setiap unit tetap mempunyai kode, ruangan, dan stoknya sendiri.
 */
class Migration_Complete_room_asset_showcase extends CI_Migration
{
    private function assets()
    {
        return array(
            array('kode_aset' => 'AUD-3D-001', 'nama_aset' => 'Keyboard Audio Workstation', 'room' => 'Lab Audio', 'gambar' => 'keyboard.webp', 'deskripsi' => 'Keyboard untuk pengoperasian workstation produksi, rekaman, dan penyuntingan audio.'),
            array('kode_aset' => 'BAT-3D-001', 'nama_aset' => 'Peralatan Cetak Batik', 'room' => 'Lab Batik', 'gambar' => 'alat sablon.webp', 'deskripsi' => 'Peralatan cetak untuk eksplorasi motif, pewarnaan, dan produksi tekstil batik.'),
            array('kode_aset' => 'FIN-3D-001', 'nama_aset' => 'Trimer Finishing', 'room' => 'Lab Finishing', 'gambar' => 'trimer.webp', 'deskripsi' => 'Alat trimer untuk perapian detail dan tahap penyelesaian karya.'),
            array('kode_aset' => 'GRN-3D-002', 'nama_aset' => 'Tripod Green Screen', 'room' => 'Lab Green Screen', 'gambar' => 'tripod.webp', 'deskripsi' => 'Tripod kamera untuk produksi video dan pengambilan gambar pada studio green screen.'),
            array('kode_aset' => 'INC-3D-001', 'nama_aset' => 'Projector Inkubasi', 'room' => 'Lab Incubator', 'gambar' => 'projector.webp', 'deskripsi' => 'Proyektor untuk pitching, presentasi prototipe, dan kolaborasi program inkubasi.'),
            array('kode_aset' => 'LKS-3D-001', 'nama_aset' => 'Kursi Studio Lukis', 'room' => 'Lab Lukis', 'gambar' => 'kursi lab.webp', 'deskripsi' => 'Kursi kerja studio untuk praktik melukis dan eksplorasi seni rupa.'),
            array('kode_aset' => 'POL-3D-001', 'nama_aset' => 'Kursi Pola dan Jahit', 'room' => 'Lab Pola dan Jahit', 'gambar' => 'kursi lab.webp', 'deskripsi' => 'Kursi kerja untuk kegiatan pembuatan pola, pemotongan bahan, dan penjahitan.'),
            array('kode_aset' => 'IK1-VGA-3D', 'nama_aset' => 'VGA Splitter IK1.03.02', 'room' => 'IK1.03.02', 'gambar' => 'vga 1 to 2.webp', 'deskripsi' => 'Pembagi sinyal display untuk perangkat presentasi di ruang IK1.03.02.'),
            array('kode_aset' => 'IOT-MON-3D', 'nama_aset' => 'Monitor IoT', 'room' => 'LAB IOT', 'gambar' => 'monitor hp.webp', 'deskripsi' => 'Monitor untuk pemrograman, pemantauan perangkat, dan praktikum Internet of Things.'),
            array('kode_aset' => 'GEN-LAB-3D', 'nama_aset' => 'Kursi Lab Serbaguna', 'room' => 'lab apa aja', 'gambar' => 'kursi lab.webp', 'deskripsi' => 'Kursi kerja serbaguna untuk mendukung kegiatan laboratorium.'),
        );
    }

    public function up()
    {
        if (!$this->db->table_exists('aset') || !$this->db->table_exists('ruangan'))
        {
            throw new RuntimeException('Tabel aset atau ruangan belum tersedia. Import database dasar terlebih dahulu.');
        }

        $prepared = array();
        foreach ($this->assets() as $asset)
        {
            if (!is_file(FCPATH.'assets/uploads/barang/'.$asset['gambar']))
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

            $asset['id_ruangan'] = (int) $room->id_ruangan;
            $prepared[] = $asset;
        }

        $this->db->trans_start();

        foreach ($prepared as $asset)
        {
            $existing = $this->db
                ->select('id_aset')
                ->where('kode_aset', $asset['kode_aset'])
                ->limit(1)
                ->get('aset')
                ->row();

            $data = array(
                'id_ruangan' => $asset['id_ruangan'],
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
            throw new RuntimeException('Transaksi pemerataan aset ruangan gagal.');
        }
    }

    public function down()
    {
        // Non-destruktif: barang dapat sudah dipakai dalam transaksi peminjaman.
    }
}
