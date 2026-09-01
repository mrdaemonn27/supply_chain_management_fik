<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller: Barang
 * Path: application/controllers/admin/Barang.php
 * Khusus untuk ROLE ADMIN/LABORAN mengelola Master Data Aset & Laboratorium
 */
#[\AllowDynamicProperties]
class Barang extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        // Proteksi: Wajib Login
        if(!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('auth');
        }

        // Proteksi: role admin lama tetap diperlakukan sebagai Laboran.
        if(!in_array(strtolower((string) $this->session->userdata('role')), ['admin', 'laboran'], true)) {
            $this->session->set_flashdata('error', 'Akses ditolak! Halaman ini khusus Laboran.');
            redirect('dashboard');
        }

        // Memanggil Model sesuai struktur folder Anda
        $this->load->helper('url');
        $this->load->model('admin/Barang_model', 'Barang_model');
        // Memastikan kolom ledger stok tersedia juga pada instalasi lama.
        $this->load->model('Peminjaman_model');
    }

    public function index() {
        $per_page_options = [10, 25, 50, 100];
        $per_page = (int) $this->input->get('per_page', true);
        if (!in_array($per_page, $per_page_options, true)) {
            $per_page = 10;
        }
        $page = max(1, (int) $this->input->get('page', true));
        $filters = ['criteria' => $this->read_filter_criteria(['kode', 'nama', 'ruangan', 'total', 'kondisi'])];
        $total = $this->Barang_model->count_all($filters);
        $data['barang'] = $this->Barang_model->get_all(array_merge($filters, [
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
        ]));
        $data['filters'] = $filters;
        $data['per_page_options'] = $per_page_options;
        $data['pagination'] = [
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => max(1, (int) ceil($total / $per_page)),
        ];
        $this->load->view('admin/barang_list', $data);
    }

    private function read_filter_criteria($allowed) {
        $fields = (array) $this->input->get('filter_field', true);
        $values = (array) $this->input->get('filter_value', true);
        $criteria = [];
        foreach (array_slice($fields, 0, 4) as $index => $field) {
            $value = trim((string) ($values[$index] ?? ''));
            if (in_array($field, $allowed, true) && $value !== '') {
                $criteria[] = ['field' => $field, 'value' => $value];
            }
        }
        return $criteria;
    }

    public function import() {
        $data['title'] = 'Import Inventory';
        $data['preview_rows'] = $this->session->userdata('inventory_import_preview') ?: [];
        $this->load->view('admin/import_inventory', $data);
    }

    public function preview_import() {
        $rows = [];
        $paste = trim((string) $this->input->post('paste_data'));
        if ($paste !== '') {
            $rows = $this->parse_pasted_table($paste);
        } elseif (!empty($_FILES['file_import']['name'])) {
            $rows = $this->parse_import_file($_FILES['file_import']);
        }

        $preview = $this->normalize_import_rows($rows);
        if (empty($preview)) {
            $this->session->set_flashdata('error', 'Data import kosong atau format kolom tidak terbaca.');
            redirect('admin/barang/import');
        }

        foreach ($preview as $index => &$row) {
            $duplicate = $this->Barang_model->find_duplicate($row);
            $row['duplicate_id'] = $duplicate ? (int) $duplicate->id_aset : null;
            $row['duplicate_label'] = $duplicate ? ($duplicate->kode_aset . ' - ' . $duplicate->nama_aset) : '';
        }
        unset($row);

        $this->session->set_userdata('inventory_import_preview', $preview);
        $this->session->set_flashdata('success', count($preview) . ' baris siap direview sebelum masuk inventory.');
        redirect('admin/barang/import');
    }

    public function proses_import() {
        $preview = $this->session->userdata('inventory_import_preview') ?: [];
        if (empty($preview)) {
            $this->session->set_flashdata('error', 'Tidak ada data preview untuk diimport.');
            redirect('admin/barang/import');
        }

        $duplicate_action = strtolower((string) $this->input->post('duplicate_action', true));
        if (!in_array($duplicate_action, ['skip', 'update', 'cancel'], true)) {
            $duplicate_action = 'skip';
        }
        if ($duplicate_action === 'cancel') {
            $this->session->unset_userdata('inventory_import_preview');
            $this->session->set_flashdata('error', 'Import dibatalkan. Tidak ada data yang diubah.');
            redirect('admin/barang');
        }

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $this->db->trans_start();
        foreach ($preview as $index => $row) {
            $aset = [
                'kode_aset' => trim((string) $row['kode_aset']),
                'nama_aset' => $row['nama_aset'],
                'id_ruangan' => $row['id_ruangan'],
                'jumlah_total' => max(0, (int) $row['jumlah_total']),
                'jumlah_reserved' => 0,
                'jumlah_dipinjam' => 0,
                'jumlah_tersedia' => max(0, (int) $row['jumlah_total']),
                'kondisi' => $row['kondisi'],
                'deskripsi' => $row['deskripsi'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $duplicate = $this->Barang_model->find_duplicate($aset);
            if ($duplicate) {
                if ($duplicate_action === 'skip') {
                    $skipped++;
                    continue;
                }
                $reserved = max(0, (int) ($duplicate->jumlah_reserved ?? 0));
                $borrowed = max(0, (int) ($duplicate->jumlah_dipinjam ?? 0));
                $allocated = $reserved + $borrowed;
                $aset['jumlah_total'] = max((int) $aset['jumlah_total'], $allocated);
                $aset['jumlah_reserved'] = $reserved;
                $aset['jumlah_dipinjam'] = $borrowed;
                $aset['jumlah_tersedia'] = $aset['jumlah_total'] - $allocated;
                $this->Barang_model->update($duplicate->id_aset, $aset);
                $updated++;
                continue;
            }
            $kode = $this->make_unique_kode($aset['kode_aset'] ?: ('IMP-' . date('YmdHis') . '-' . ($index + 1)));
            $aset['kode_aset'] = $kode;
            $this->db->insert('aset', $aset);
            $id_aset = $this->db->insert_id();
            if ($id_aset) {
                $this->db->where('id_aset', $id_aset)->update('aset', [
                    'qr_code' => 'ASET-' . $id_aset . '-' . strtoupper(substr(md5($kode), 0, 6)),
                    'qr_url' => site_url('peminjaman/detail_barang/' . $id_aset),
                ]);
                $inserted++;
            }
        }
        $this->db->trans_complete();

        if ($this->db->trans_status()) {
            $this->session->unset_userdata('inventory_import_preview');
            $this->session->set_flashdata('success', $inserted . ' data baru, ' . $updated . ' data diperbarui, ' . $skipped . ' duplikat dilewati.');
            redirect('admin/barang');
        }

        $this->session->set_flashdata('error', 'Gagal import inventory.');
        redirect('admin/barang/import');
    }

    private function parse_pasted_table($text) {
        $rows = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cells = str_getcsv($line, "\t");
            if (count($cells) <= 1) {
                $cells = str_getcsv($line);
            }
            $rows[] = $cells;
        }
        return $rows;
    }

    private function parse_import_file($file) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'xlsx'], true)) {
            $this->session->set_flashdata('error', 'Format file harus CSV atau XLSX.');
            return [];
        }

        if ($ext === 'csv') {
            $rows = [];
            $handle = fopen($file['tmp_name'], 'r');
            while ($handle && ($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }
            if ($handle) {
                fclose($handle);
            }
            return $rows;
        }

        return $this->parse_xlsx_rows($file['tmp_name']);
    }

    private function parse_xlsx_rows($path) {
        if (!class_exists('ZipArchive')) {
            $this->session->set_flashdata('error', 'Ekstensi ZipArchive PHP belum aktif, gunakan CSV atau copy-paste dari Excel.');
            return [];
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $shared = [];
        $shared_xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($shared_xml !== false) {
            $xml = simplexml_load_string($shared_xml);
            foreach ($xml->si as $si) {
                $shared[] = (string) ($si->t ?? '');
            }
        }

        $sheet_xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if ($sheet_xml === false) {
            return [];
        }

        $sheet = simplexml_load_string($sheet_xml);
        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $cell) {
                $value = (string) ($cell->v ?? '');
                if ((string) $cell['t'] === 's') {
                    $value = $shared[(int) $value] ?? '';
                }
                $cells[] = $value;
            }
            $rows[] = $cells;
        }
        return $rows;
    }

    private function normalize_import_rows($rows) {
        $rows = array_values(array_filter((array) $rows, static function ($row) {
            return trim(implode('', (array) $row)) !== '';
        }));
        if (empty($rows)) {
            return [];
        }

        $headers = array_map(static function ($value) {
            return strtolower(trim((string) $value));
        }, (array) $rows[0]);
        $has_header = count(array_intersect($headers, ['kode_aset', 'kode aset', 'kode', 'nama_aset', 'nama aset', 'nama barang', 'ruangan', 'lokasi'])) > 0;
        if ($has_header) {
            array_shift($rows);
        }

        $find = static function ($names) use ($headers) {
            foreach ($names as $name) {
                $idx = array_search($name, $headers, true);
                if ($idx !== false) {
                    return $idx;
                }
            }
            return null;
        };

        $idx_kode = $has_header ? $find(['kode_aset', 'kode aset', 'kode']) : 0;
        $idx_nama = $has_header ? $find(['nama_aset', 'nama aset', 'nama barang', 'barang', 'nama']) : 1;
        $idx_ruangan = $has_header ? $find(['ruangan', 'lokasi', 'laboratorium', 'lab']) : 2;
        $idx_total = $has_header ? $find(['jumlah_total', 'jumlah total', 'total fisik', 'stok', 'qty']) : 3;
        $idx_tersedia = $has_header ? $find(['jumlah_tersedia', 'jumlah tersedia', 'tersedia']) : 4;
        $idx_kondisi = $has_header ? $find(['kondisi', 'status']) : 5;
        $idx_deskripsi = $has_header ? $find(['deskripsi', 'keterangan', 'spesifikasi']) : 6;
        $idx_kode = $idx_kode ?? 0;
        $idx_nama = $idx_nama ?? 1;
        $idx_ruangan = $idx_ruangan ?? 2;
        $idx_total = $idx_total ?? 3;
        $idx_tersedia = $idx_tersedia ?? 4;
        $idx_kondisi = $idx_kondisi ?? 5;
        $idx_deskripsi = $idx_deskripsi ?? 6;

        $result = [];
        foreach ($rows as $row) {
            $row = array_values((array) $row);
            $nama = trim((string) ($row[$idx_nama] ?? ''));
            if ($nama === '') {
                continue;
            }

            $total = (int) preg_replace('/[^0-9]/', '', (string) ($row[$idx_total] ?? 1));
            $tersedia_raw = $row[$idx_tersedia] ?? $total;
            $tersedia = (int) preg_replace('/[^0-9]/', '', (string) $tersedia_raw);
            $kondisi = trim((string) ($row[$idx_kondisi] ?? 'Baik'));
            if (stripos($kondisi, 'hilang') !== false) {
                $kondisi = 'Hilang';
            } elseif (stripos($kondisi, 'rusak') !== false) {
                $kondisi = 'Rusak';
            } else {
                $kondisi = 'Baik';
            }

            $result[] = [
                'kode_aset' => trim((string) ($row[$idx_kode] ?? '')),
                'nama_aset' => $nama,
                'ruangan_label' => trim((string) ($row[$idx_ruangan] ?? '')),
                'id_ruangan' => $this->resolve_ruangan_id($row[$idx_ruangan] ?? null),
                'jumlah_total' => max(1, $total ?: 1),
                'jumlah_tersedia' => max(0, $tersedia ?: ($total ?: 1)),
                'kondisi' => $kondisi,
                'deskripsi' => trim((string) ($row[$idx_deskripsi] ?? '')),
            ];
        }
        return $result;
    }

    private function resolve_ruangan_id($value) {
        $value = trim((string) $value);
        if ($value !== '' && ctype_digit($value)) {
            $exists = $this->db->get_where('ruangan', ['id_ruangan' => (int) $value])->row();
            if ($exists) {
                return (int) $value;
            }
        }
        if ($value !== '') {
            $row = $this->db->like('nama_ruangan', $value)->limit(1)->get('ruangan')->row();
            if ($row) {
                return (int) $row->id_ruangan;
            }
        }
        $fallback = $this->db->order_by('id_ruangan', 'ASC')->limit(1)->get('ruangan')->row();
        return $fallback ? (int) $fallback->id_ruangan : null;
    }

    private function make_unique_kode($kode) {
        $base = preg_replace('/[^A-Za-z0-9._-]/', '-', strtoupper(trim($kode)));
        $base = $base ?: 'IMP-' . date('YmdHis');
        $kode = $base;
        $suffix = 1;
        while ($this->db->where('kode_aset', $kode)->count_all_results('aset') > 0) {
            $kode = $base . '-' . $suffix++;
        }
        return $kode;
    }

    /**
     * Kolom foto lama tidak dipakai oleh alur peminjaman. Gunakan sebagai
     * daftar JSON gambar galeri agar schema aset yang sudah ada tetap utuh.
     */
    private function read_gallery_filenames($value) {
        $value = trim((string) $value);
        if ($value === '') return [];

        $decoded = json_decode($value, true);
        $files = is_array($decoded) ? $decoded : [$value];
        $files = array_map(static function ($file) {
            $file = trim((string) $file);
            return basename($file) === $file ? $file : '';
        }, $files);

        return array_values(array_unique(array_filter($files)));
    }

    private function is_3d_asset_filename($filename) {
        return in_array(strtolower((string) pathinfo($filename, PATHINFO_EXTENSION)), ['glb', 'gltf'], true);
    }

    private function upload_asset_media($field) {
        $original_name = (string) ($_FILES[$field]['name'] ?? '');
        $is_3d_asset = $this->is_3d_asset_filename($original_name);
        $config = [
            'upload_path'   => './assets/uploads/barang/',
            'allowed_types' => $is_3d_asset ? 'glb|gltf' : 'gif|jpg|jpeg|png|webp',
            'max_size'      => $is_3d_asset ? 15360 : 2048,
            'encrypt_name'  => true,
            'file_ext_tolower' => true,
        ];

        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0777, true);
        }

        $this->load->library('upload');
        $this->upload->initialize($config, true);

        if (!$this->upload->do_upload($field)) {
            return null;
        }

        return (string) $this->upload->data('file_name');
    }

    private function remove_asset_image_file($filename) {
        $filename = basename((string) $filename);
        $path = './assets/uploads/barang/' . $filename;
        if ($filename !== '' && is_file($path)) {
            unlink($path);
        }
    }

    public function tambah() {
        $data['ruangan'] = $this->Barang_model->get_all_ruangan();
        $this->load->view('admin/barang_form', $data);
    }

    public function edit($id_aset) {
        $data['ruangan'] = $this->Barang_model->get_all_ruangan();
        $data['aset'] = $this->Barang_model->get_by_id($id_aset);
        
        if(!$data['aset']) {
            $this->session->set_flashdata('error', 'Data barang tidak ditemukan!');
            redirect('admin/barang');
        }
        
        $this->load->view('admin/barang_form', $data);
    }

    public function simpan() {
        $id_aset = $this->input->post('id_aset'); 
        
        $data = [
            'kode_aset'       => $this->input->post('kode_aset'),
            'nama_aset'       => $this->input->post('nama_aset'),
            'id_ruangan'      => $this->input->post('id_ruangan'),
            'jumlah_total'    => max(0, (int) $this->input->post('jumlah_total')),
            'jumlah_reserved' => 0,
            'jumlah_dipinjam' => 0,
            'jumlah_tersedia' => max(0, (int) $this->input->post('jumlah_total')),
            'kondisi'         => $this->input->post('kondisi')
        ];

        $duplicate = $this->Barang_model->find_duplicate($data, $id_aset);
        if ($duplicate) {
            $this->session->set_flashdata('error', 'Data duplikat terdeteksi. Kode atau kombinasi nama aset dan ruangan sudah terdaftar.');
            redirect(empty($id_aset) ? 'admin/barang/tambah' : 'admin/barang/edit/' . $id_aset);
        }

        // Menyimpan deskripsi jika fieldnya ada di form
        if($this->input->post('deskripsi') !== null) {
            $data['deskripsi'] = $this->input->post('deskripsi');
        }

        if (!empty($id_aset)) {
            $existing = $this->Barang_model->get_by_id($id_aset);
            $reserved = $existing ? max(0, (int) ($existing->jumlah_reserved ?? 0)) : 0;
            $borrowed = $existing ? max(0, (int) ($existing->jumlah_dipinjam ?? 0)) : 0;
            $allocated = $reserved + $borrowed;
            if ((int) $data['jumlah_total'] < $allocated) {
                $this->session->set_flashdata('error', 'Total fisik tidak boleh lebih kecil dari ' . $allocated . ' unit yang sedang dialokasikan.');
                redirect('admin/barang/edit/' . $id_aset);
            }
            $data['jumlah_reserved'] = $reserved;
            $data['jumlah_dipinjam'] = $borrowed;
            $data['jumlah_tersedia'] = (int) $data['jumlah_total'] - $allocated;
        }

        $existing = !empty($id_aset) ? $this->Barang_model->get_by_id($id_aset) : null;
        $existing_gallery = $existing ? $this->read_gallery_filenames($existing->foto ?? '') : [];
        $gallery = $existing_gallery;
        $files_to_delete = [];

        // Media utama tetap memakai alur upload yang sudah ada.
        if (!empty($_FILES['gambar']['name'])) {
            $primary_image = $this->upload_asset_media('gambar');
            if ($primary_image === null) {
                $this->session->set_flashdata('error', 'Gagal upload media utama: ' . $this->upload->display_errors('', ''));
                if (empty($id_aset)) {
                    redirect('admin/barang/tambah');
                } else {
                    redirect('admin/barang/edit/' . $id_aset); 
                }
                return;
            }

            $data['gambar'] = $primary_image;
            if ($existing && !empty($existing->gambar) && $existing->gambar !== $primary_image && !in_array($existing->gambar, $gallery, true)) {
                $files_to_delete[] = $existing->gambar;
            }
        }

        // Hapus media tambahan yang dipilih pada form edit.
        $remove_gallery = array_map('basename', (array) $this->input->post('hapus_galeri'));
        foreach ($remove_gallery as $filename) {
            $key = array_search($filename, $gallery, true);
            if ($key !== false) {
                unset($gallery[$key]);
                $files_to_delete[] = $filename;
            }
        }
        $gallery = array_values($gallery);

        // Maksimal lima media tambahan agar tetap aman dalam kolom foto existing.
        $new_gallery_names = array_filter((array) ($_FILES['galeri_tambahan']['name'] ?? []));
        if (count($gallery) + count($new_gallery_names) > 5) {
            $this->session->set_flashdata('error', 'Galeri aset maksimal berisi 5 media tambahan.');
            redirect(empty($id_aset) ? 'admin/barang/tambah' : 'admin/barang/edit/' . $id_aset);
            return;
        }

        foreach (array_keys($new_gallery_names) as $index) {
            $_FILES['galeri_upload_temp'] = [
                'name'     => $_FILES['galeri_tambahan']['name'][$index],
                'type'     => $_FILES['galeri_tambahan']['type'][$index],
                'tmp_name' => $_FILES['galeri_tambahan']['tmp_name'][$index],
                'error'    => $_FILES['galeri_tambahan']['error'][$index],
                'size'     => $_FILES['galeri_tambahan']['size'][$index],
            ];
            $gallery_image = $this->upload_asset_media('galeri_upload_temp');
            if ($gallery_image === null) {
                $this->session->set_flashdata('error', 'Gagal upload media galeri: ' . $this->upload->display_errors('', ''));
                redirect(empty($id_aset) ? 'admin/barang/tambah' : 'admin/barang/edit/' . $id_aset);
                return;
            }
            $gallery[] = $gallery_image;
        }
        unset($_FILES['galeri_upload_temp']);

        if (!empty($id_aset) || !empty($gallery)) {
            $data['foto'] = !empty($gallery) ? json_encode(array_values(array_unique($gallery))) : null;
        }

        // SIMPAN KE DATABASE
        if(empty($id_aset)) {
            $saved = $this->Barang_model->insert($data);
            $this->session->set_flashdata('success', 'Barang berhasil ditambahkan!');
        } else {
            $saved = $this->Barang_model->update($id_aset, $data);
            $this->session->set_flashdata('success', 'Master data berhasil diperbarui!');
        }

        if ($saved) {
            foreach (array_unique($files_to_delete) as $filename) {
                $this->remove_asset_image_file($filename);
            }
        }

        redirect('admin/barang'); 
    }

    public function hapus($id_aset) {
        $old_data = $this->Barang_model->get_by_id($id_aset);
        
        // Hapus seluruh gambar aset dari penyimpanan saat master aset dihapus.
        if ($old_data) {
            $this->remove_asset_image_file($old_data->gambar ?? '');
            foreach ($this->read_gallery_filenames($old_data->foto ?? '') as $filename) {
                $this->remove_asset_image_file($filename);
            }
        }
        
        $this->Barang_model->delete($id_aset);
        $this->session->set_flashdata('success', 'Data barang berhasil dihapus!');
        redirect('admin/barang');
    }
}
