<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Hubungkan seluruh render WebP barang 3D dengan inventaris dan lab yang tepat.
 *
 * Folder barang juga berisi unggahan JPG/PNG lama (logo, tangkapan layar, dan
 * gambar kosong). Hanya WebP yang merupakan render barang 3D dan diaudit di
 * migration ini. Daftar dibuat lengkap agar penambahan file tanpa pemetaan
 * tidak bisa lolos tanpa sengaja.
 */
class Migration_Assign_all_3d_assets_to_labs extends CI_Migration
{
    private function assets()
    {
        return array(
            array('kode_aset' => 'MTL-002', 'room' => 'Lab Metal Working', 'gambar' => '9a9e716d169ba27989cf2819795d8a96.webp'),
            array('kode_aset' => 'FIN-3D-001', 'room' => 'Lab Finishing', 'gambar' => 'alat finishing.webp', 'nama_aset' => 'Alat Finishing', 'deskripsi' => 'Peralatan untuk perapian detail dan tahap penyelesaian karya.'),
            array('kode_aset' => 'SBL-001', 'room' => 'Lab Sablon', 'gambar' => 'alat sablon.webp'),
            array('kode_aset' => 'MTL-004', 'room' => 'Lab Metal Working', 'gambar' => 'blending pipa.webp'),
            array('kode_aset' => 'WAC-001', 'room' => 'Lab Tab Cintiq', 'gambar' => 'cintiq.webp'),
            array('kode_aset' => 'WOD-008', 'room' => 'Lab Woodworking', 'gambar' => 'cordless electric drill.webp'),
            array('kode_aset' => 'KB-001', 'room' => 'Lab Multimedia', 'gambar' => 'keyboard.webp'),
            array('kode_aset' => 'GRN-001', 'room' => 'Lab Green Screen', 'gambar' => 'kino flo led.webp'),
            array('kode_aset' => 'IDL-001', 'room' => 'Lab Idealoka', 'gambar' => 'kursi lab.webp'),
            array('kode_aset' => 'MTL-008', 'room' => 'Lab Metal Working', 'gambar' => 'mesin kompresor besar izumi.webp'),
            array('kode_aset' => 'MTL-005', 'room' => 'Lab Metal Working', 'gambar' => 'mesin las lisrtrik.webp'),
            array('kode_aset' => 'WOD-014', 'room' => 'Lab Woodworking', 'gambar' => 'mesin serut kayu.webp'),
            array('kode_aset' => 'CGI-MON-001', 'room' => 'Lab CGI', 'gambar' => 'monitor hp.webp'),
            array('kode_aset' => 'MON-002', 'room' => 'Lab Multimedia', 'gambar' => 'monitor hp24.webp'),
            array('kode_aset' => 'MON-001', 'room' => 'Lab Multimedia', 'gambar' => 'monitor LG.webp'),
            array('kode_aset' => 'MAC-MON-001', 'room' => 'Lab Mac', 'gambar' => 'monitor.webp'),
            array('kode_aset' => 'PC-002', 'room' => 'Lab Multimedia', 'gambar' => 'pc intel core i5.webp'),
            array('kode_aset' => 'MTL-001', 'room' => 'Lab Metal Working', 'gambar' => 'portable cutoff.webp'),
            array('kode_aset' => 'PROJ-001', 'room' => 'Aula FIK', 'gambar' => 'projector.webp'),
            array('kode_aset' => 'FOTO-014', 'room' => 'Lab Fotografi', 'gambar' => 'red lamp.webp'),
            array('kode_aset' => 'FOTO-006', 'room' => 'Lab Fotografi', 'gambar' => 'reflector flash.webp'),
            array('kode_aset' => 'FOTO-001', 'room' => 'Lab Fotografi', 'gambar' => 'studio flash godox400.webp'),
            array('kode_aset' => 'SW-004', 'room' => 'Lab Multimedia', 'gambar' => 'switch hub 8 port.webp'),
            array('kode_aset' => 'WAC-003', 'room' => 'Lab Tab Cintiq', 'gambar' => 'tab.webp'),
            array('kode_aset' => 'WOD-007', 'room' => 'Lab Woodworking', 'gambar' => 'trimer makita rp2300fc.webp'),
            array('kode_aset' => 'WOD-005', 'room' => 'Lab Woodworking', 'gambar' => 'trimer.webp'),
            array('kode_aset' => 'FOTO-013', 'room' => 'Lab Fotografi', 'gambar' => 'tripod.webp'),
            array('kode_aset' => 'VGA-001', 'room' => 'Lab Multimedia', 'gambar' => 'vga 1 to 2.webp'),
        );
    }

    private function assert_complete_webp_mapping($assets)
    {
        $expected = array();
        foreach ($assets as $asset)
        {
            $expected[] = $asset['gambar'];
        }

        if (count($expected) !== count(array_unique($expected)))
        {
            throw new RuntimeException('Daftar pemetaan barang 3D memuat nama file ganda.');
        }

        $actual = array();
        $files = glob(FCPATH.'assets/uploads/barang/*');
        if ($files === FALSE)
        {
            throw new RuntimeException('Folder aset barang tidak dapat dibaca.');
        }

        foreach ($files as $file)
        {
            if (is_file($file) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'webp')
            {
                $actual[] = basename($file);
            }
        }

        $missing = array_values(array_diff($expected, $actual));
        $unmapped = array_values(array_diff($actual, $expected));
        if (!empty($missing) || !empty($unmapped))
        {
            $messages = array();
            if (!empty($missing))
            {
                $messages[] = 'file hilang: '.implode(', ', $missing);
            }
            if (!empty($unmapped))
            {
                $messages[] = 'file belum dipetakan: '.implode(', ', $unmapped);
            }
            throw new RuntimeException('Audit aset barang 3D gagal ('.implode('; ', $messages).').');
        }
    }

    public function up()
    {
        if (!$this->db->table_exists('aset') || !$this->db->table_exists('ruangan'))
        {
            throw new RuntimeException('Tabel aset atau ruangan belum tersedia. Import database dasar terlebih dahulu.');
        }

        $assets = $this->assets();
        $this->assert_complete_webp_mapping($assets);

        $prepared = array();
        foreach ($assets as $asset)
        {
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

            if (!$existing)
            {
                throw new RuntimeException('Barang inventaris tidak ditemukan: '.$asset['kode_aset']);
            }

            $asset['id_ruangan'] = (int) $room->id_ruangan;
            $asset['id_aset'] = (int) $existing->id_aset;
            $prepared[] = $asset;
        }

        $this->db->trans_start();

        foreach ($prepared as $asset)
        {
            $data = array(
                'id_ruangan' => $asset['id_ruangan'],
                'gambar' => $asset['gambar'],
            );

            if (isset($asset['nama_aset']))
            {
                $data['nama_aset'] = $asset['nama_aset'];
            }
            if (isset($asset['deskripsi']))
            {
                $data['deskripsi'] = $asset['deskripsi'];
            }

            $this->db
                ->where('id_aset', $asset['id_aset'])
                ->update('aset', $data);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            throw new RuntimeException('Transaksi pemetaan seluruh barang 3D ke laboratorium gagal.');
        }
    }

    public function down()
    {
        // Non-destruktif: lokasi dan gambar dapat sudah dipakai dalam transaksi.
    }
}
