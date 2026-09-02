<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pasangkan setiap model GLB unik dengan barang yang memakai render WebP
 * terkait. Kolom foto dipakai sebagai galeri media tambahan oleh master data.
 */
class Migration_Attach_glb_assets_to_matching_items extends CI_Migration
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
        return FCPATH.'assets/uploads/barang/'.$filename;
    }

    private function read_gallery($value)
    {
        $value = trim((string) $value);
        if ($value === '')
        {
            return array();
        }

        $decoded = json_decode($value, TRUE);
        $files = is_array($decoded) ? $decoded : array($value);
        $gallery = array();
        foreach ($files as $file)
        {
            $filename = basename(trim((string) $file));
            if ($filename !== '' && $filename === trim((string) $file))
            {
                $gallery[] = $filename;
            }
        }

        return array_values(array_unique($gallery));
    }

    private function assert_complete_glb_mapping($pairs)
    {
        $mapped_hashes = array();
        foreach ($pairs as $pair)
        {
            foreach ($pair['images'] as $image)
            {
                if (!is_file($this->media_path($image)))
                {
                    throw new RuntimeException('Render barang tidak ditemukan: '.$image);
                }
            }

            $model_path = $this->media_path($pair['model']);
            if (!is_file($model_path))
            {
                throw new RuntimeException('Model barang tidak ditemukan: '.$pair['model']);
            }
            $mapped_hashes[] = hash_file('sha256', $model_path);
        }

        if (count($mapped_hashes) !== count(array_unique($mapped_hashes)))
        {
            throw new RuntimeException('Pemetaan model barang memuat GLB unik yang sama lebih dari sekali.');
        }

        $available_hashes = array();
        foreach ((array) glob($this->media_path('*.glb')) as $model_path)
        {
            if (is_file($model_path))
            {
                $available_hashes[] = hash_file('sha256', $model_path);
            }
        }

        $unmapped_hashes = array_diff(array_unique($available_hashes), $mapped_hashes);
        if (!empty($unmapped_hashes))
        {
            throw new RuntimeException('Masih ada model GLB unik di folder barang yang belum dipetakan.');
        }
    }

    public function up()
    {
        if (!$this->db->table_exists('aset') || !$this->db->field_exists('foto', 'aset'))
        {
            throw new RuntimeException('Tabel aset atau kolom galeri foto belum tersedia.');
        }

        $pairs = $this->media_pairs();
        $this->assert_complete_glb_mapping($pairs);
        $updates = array();

        foreach ($pairs as $pair)
        {
            $assets = $this->db
                ->select('id_aset, kode_aset, foto')
                ->where_in('gambar', $pair['images'])
                ->get('aset')
                ->result();

            if (empty($assets))
            {
                throw new RuntimeException('Barang untuk model '.$pair['model'].' tidak ditemukan.');
            }

            $target_hash = hash_file('sha256', $this->media_path($pair['model']));
            foreach ($assets as $asset)
            {
                $gallery = array();
                foreach ($this->read_gallery($asset->foto) as $filename)
                {
                    $gallery_path = $this->media_path($filename);
                    if (is_file($gallery_path) && hash_file('sha256', $gallery_path) === $target_hash)
                    {
                        continue;
                    }
                    $gallery[] = $filename;
                }

                if (count($gallery) >= 5)
                {
                    throw new RuntimeException('Galeri barang '.$asset->kode_aset.' sudah penuh; model 3D belum dapat ditambahkan.');
                }

                array_unshift($gallery, $pair['model']);
                $updates[] = array(
                    'id_aset' => (int) $asset->id_aset,
                    'foto' => json_encode(array_values(array_unique($gallery)), JSON_UNESCAPED_SLASHES),
                );
            }
        }

        $this->db->trans_start();
        foreach ($updates as $update)
        {
            $this->db->where('id_aset', $update['id_aset'])->update('aset', array('foto' => $update['foto']));
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            throw new RuntimeException('Pemasangan galeri model 3D barang gagal.');
        }
    }

    public function down()
    {
        foreach ($this->media_pairs() as $pair)
        {
            $assets = $this->db
                ->select('id_aset, foto')
                ->where_in('gambar', $pair['images'])
                ->get('aset')
                ->result();

            foreach ($assets as $asset)
            {
                $gallery = array_values(array_diff($this->read_gallery($asset->foto), array($pair['model'])));
                $this->db
                    ->where('id_aset', $asset->id_aset)
                    ->update('aset', array('foto' => empty($gallery) ? NULL : json_encode($gallery, JSON_UNESCAPED_SLASHES)));
            }
        }
    }
}
