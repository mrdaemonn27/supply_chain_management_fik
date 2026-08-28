<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Koreksi nama file foto Lab Batik dan Lab Green Screen.
 * Migration sebelumnya menyimpan akhiran nama file yang tidak sesuai dengan
 * file WebP di assets/uploads/ruangan sehingga kartu memakai ikon fallback.
 */
class Migration_Fix_batik_green_screen_photos extends CI_Migration
{
    private function rooms()
    {
        return array(
            'Lab Batik' => 'lab batik.webp',
            'Lab Green Screen' => 'lab greenscreen.webp',
        );
    }

    public function up()
    {
        if (!$this->db->table_exists('ruangan'))
        {
            throw new RuntimeException('Tabel ruangan belum tersedia. Import database dasar terlebih dahulu.');
        }

        foreach ($this->rooms() as $room => $filename)
        {
            if (!is_file(FCPATH.'assets/uploads/ruangan/'.$filename))
            {
                throw new RuntimeException('Foto ruangan tidak ditemukan: '.$filename);
            }

            $exists = $this->db
                ->select('id_ruangan')
                ->where('nama_ruangan', $room)
                ->limit(1)
                ->get('ruangan')
                ->row();

            if (!$exists)
            {
                throw new RuntimeException('Ruangan tidak ditemukan: '.$room);
            }
        }

        $this->db->trans_start();

        foreach ($this->rooms() as $room => $filename)
        {
            $this->db
                ->where('nama_ruangan', $room)
                ->update('ruangan', array('foto' => $filename));
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            throw new RuntimeException('Koreksi foto Lab Batik dan Lab Green Screen gagal.');
        }
    }

    public function down()
    {
        // Non-destruktif: foto valid tetap dipertahankan bila migration diturunkan.
    }
}
