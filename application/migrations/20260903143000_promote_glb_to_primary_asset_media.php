<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Promosikan model GLB yang sebelumnya dipasang sebagai media galeri menjadi
 * media utama aset. Pemetaan ini melanjutkan migration 20260902150000.
 */
class Migration_Promote_glb_to_primary_asset_media extends CI_Migration
{
    private function media_pairs()
    {
        return array(
            array('images' => array('48070b32da97806ac10dd90e5e8be1ac.webp', '9a9e716d169ba27989cf2819795d8a96.webp'), 'model' => 'aie compressor orange.glb'),
            array('images' => array('alat finishing.webp'), 'model' => 'alat finishing.glb'),
            array('images' => array('alat sablon.webp'), 'model' => 'alat sablon.glb'),
            array('images' => array('blending pipa.webp'), 'model' => 'bending pipa.glb'),
            array('images' => array('cintiq.webp'), 'model' => 'cintiq.glb'),
            array('images' => array('cordless electric drill.webp'), 'model' => 'cordless electric drill.glb'),
            array('images' => array('keyboard.webp'), 'model' => 'keyboard object.glb'),
            array('images' => array('kino flo led.webp'), 'model' => 'kino flo led.glb'),
            array('images' => array('kursi lab.webp'), 'model' => 'kursi lab.glb'),
            array('images' => array('mesin kompresor besar izumi.webp'), 'model' => 'mesin kompresor besar izumi.glb'),
            array('images' => array('mesin las lisrtrik.webp'), 'model' => 'mesin las listrik.glb'),
            array('images' => array('mesin serut kayu.webp'), 'model' => 'mesin serut kayu.glb'),
            array('images' => array('monitor hp.webp'), 'model' => 'monitor hp.glb'),
            array('images' => array('monitor hp24.webp'), 'model' => 'monitor dell.glb'),
            array('images' => array('monitor LG.webp'), 'model' => 'monitor LG Flatron.glb'),
            array('images' => array('monitor.webp'), 'model' => 'monitor.glb'),
            array('images' => array('pc intel core i5.webp'), 'model' => 'pc  intel core i5.glb'),
            array('images' => array('portable cutoff.webp'), 'model' => '4053c003e3a62ababd3bae5132bcea54.glb'),
            array('images' => array('projector.webp'), 'model' => 'projector.glb'),
            array('images' => array('red lamp.webp'), 'model' => 'red lamp.glb'),
            array('images' => array('reflector flash.webp'), 'model' => 'reflector flash.glb'),
            array('images' => array('studio flash godox400.webp'), 'model' => 'studio flash godox400.glb'),
            array('images' => array('switch hub 8 port.webp'), 'model' => 'switch hub 8 port.glb'),
            array('images' => array('tab.webp'), 'model' => 'tab.glb'),
            array('images' => array('trimer makita rp2300fc.webp'), 'model' => 'trimer makita rp2300fc.glb'),
            array('images' => array('trimer.webp'), 'model' => 'trimer.glb'),
            array('images' => array('tripod.webp'), 'model' => 'tripod.glb'),
            array('images' => array('vga 1 to 2.webp'), 'model' => 'vga 1 to 2.glb'),
        );
    }

    private function media_path($filename)
    {
        return FCPATH.'assets/uploads/barang/'.basename((string) $filename);
    }

    private function read_gallery($value)
    {
        $decoded = json_decode(trim((string) $value), TRUE);
        if (!is_array($decoded))
        {
            return array();
        }

        return array_values(array_unique(array_filter(array_map(static function ($file) {
            $file = trim((string) $file);
            return basename($file) === $file ? $file : '';
        }, $decoded))));
    }

    public function up()
    {
        if (!$this->db->table_exists('aset') || !$this->db->field_exists('foto', 'aset'))
        {
            throw new RuntimeException('Tabel aset atau kolom media tambahan belum tersedia.');
        }

        $webp_assets = $this->db
            ->select('id_aset, kode_aset, gambar, foto')
            ->where("LOWER(gambar) LIKE '%.webp'", NULL, FALSE)
            ->get('aset')
            ->result();
        $updates = array();
        $mapped_ids = array();

        foreach ($this->media_pairs() as $pair)
        {
            if (!is_file($this->media_path($pair['model'])))
            {
                throw new RuntimeException('Model GLB tidak ditemukan: '.$pair['model']);
            }

            foreach ($webp_assets as $asset)
            {
                if (!in_array($asset->gambar, $pair['images'], TRUE))
                {
                    continue;
                }

                $gallery = $this->read_gallery($asset->foto);
                if (!in_array($pair['model'], $gallery, TRUE))
                {
                    throw new RuntimeException('Pasangan GLB untuk barang '.$asset->kode_aset.' tidak sesuai.');
                }

                $updates[] = array(
                    'id_aset' => (int) $asset->id_aset,
                    'gambar'  => $pair['model'],
                    'foto'    => NULL,
                );
                $mapped_ids[] = (int) $asset->id_aset;
            }
        }

        if (count($mapped_ids) !== count($webp_assets))
        {
            $unmapped = array();
            foreach ($webp_assets as $asset)
            {
                if (!in_array((int) $asset->id_aset, $mapped_ids, TRUE))
                {
                    $unmapped[] = $asset->kode_aset.' ('.$asset->gambar.')';
                }
            }
            throw new RuntimeException('Masih ada media WEBP tanpa pasangan GLB: '.implode(', ', $unmapped));
        }

        $this->db->trans_begin();
        foreach ($updates as $update)
        {
            $this->db->where('id_aset', $update['id_aset'])->update('aset', array(
                'gambar' => $update['gambar'],
                'foto' => NULL,
            ));
        }

        // Sesuai kebutuhan katalog baru, seluruh media tambahan dikosongkan.
        $this->db->where('foto IS NOT NULL', NULL, FALSE)->update('aset', array('foto' => NULL));

        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            throw new RuntimeException('Promosi model GLB menjadi media utama gagal.');
        }
        $this->db->trans_commit();
    }

    public function down()
    {
        $this->db->trans_begin();
        foreach ($this->media_pairs() as $pair)
        {
            $this->db
                ->where('gambar', $pair['model'])
                ->where('foto IS NULL', NULL, FALSE)
                ->update('aset', array(
                    'gambar' => $pair['images'][0],
                    'foto' => json_encode(array($pair['model']), JSON_UNESCAPED_SLASHES),
                ));
        }

        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            throw new RuntimeException('Rollback media utama GLB gagal.');
        }
        $this->db->trans_commit();
    }
}
