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
    }

    public function index() {
        $data['barang'] = $this->Barang_model->get_all();
        $this->load->view('admin/barang_list', $data);
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

        $inserted = 0;
        $this->db->trans_start();
        foreach ($preview as $index => $row) {
            $kode = $this->make_unique_kode($row['kode_aset'] ?: ('IMP-' . date('YmdHis') . '-' . ($index + 1)));
            $aset = [
                'kode_aset' => $kode,
                'nama_aset' => $row['nama_aset'],
                'id_ruangan' => $row['id_ruangan'],
                'jumlah_total' => max(0, (int) $row['jumlah_total']),
                'jumlah_tersedia' => max(0, (int) $row['jumlah_tersedia']),
                'kondisi' => $row['kondisi'],
                'deskripsi' => $row['deskripsi'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
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
            $this->session->set_flashdata('success', $inserted . ' data inventory berhasil diimport.');
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
            'jumlah_total'    => $this->input->post('jumlah_total'),
            // PERBAIKAN STOK: Memastikan jumlah_tersedia ikut diperbarui baik saat Tambah maupun Edit
            'jumlah_tersedia' => $this->input->post('jumlah_total'),
            'kondisi'         => $this->input->post('kondisi')
        ];

        // Menyimpan deskripsi jika fieldnya ada di form
        if($this->input->post('deskripsi') !== null) {
            $data['deskripsi'] = $this->input->post('deskripsi');
        }

        // LOGIKA UPLOAD GAMBAR DINAMIS
        if (!empty($_FILES['gambar']['name'])) {
            // PERBAIKAN PATH: Disamakan dengan yang dipanggil di View User (assets/uploads/barang/)
            $config['upload_path']   = './assets/uploads/barang/'; 
            
            // Buat folder otomatis jika belum ada di dalam project Anda
            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0777, TRUE);
            }

            $config['allowed_types'] = 'gif|jpg|jpeg|png|webp';
            $config['max_size']      = 2048; // 2MB
            $config['encrypt_name']  = TRUE;

            $this->load->library('upload', $config);

            if ($this->upload->do_upload('gambar')) {
                $upload_data = $this->upload->data();
                
                // PERBAIKAN DB: Hanya simpan nama file-nya saja agar URL di view User tidak rusak
                $data['gambar'] = $upload_data['file_name'];
                
                // Jika edit, hapus gambar lama dari folder agar tidak menumpuk di server
                if (!empty($id_aset)) {
                    $old_data = $this->Barang_model->get_by_id($id_aset);
                    if ($old_data && !empty($old_data->gambar) && file_exists('./assets/uploads/barang/' . $old_data->gambar)) {
                        unlink('./assets/uploads/barang/' . $old_data->gambar);
                    }
                }
            } else {
                // Jika error upload
                $this->session->set_flashdata('error', 'Gagal upload foto: ' . $this->upload->display_errors('', ''));
                if (empty($id_aset)) {
                    redirect('admin/barang/tambah');
                } else {
                    redirect('admin/barang/edit/' . $id_aset); 
                }
                return; // Hentikan eksekusi
            }
        }

        // SIMPAN KE DATABASE
        if(empty($id_aset)) {
            $this->Barang_model->insert($data);
            $this->session->set_flashdata('success', 'Barang berhasil ditambahkan!');
        } else {
            $this->Barang_model->update($id_aset, $data);
            $this->session->set_flashdata('success', 'Master data berhasil diperbarui!');
        }

        redirect('admin/barang'); 
    }

    public function hapus($id_aset) {
        $old_data = $this->Barang_model->get_by_id($id_aset);
        
        // PERBAIKAN PATH HAPUS: Hapus file gambar dari server jika ada sesuai path yang benar
        if ($old_data && !empty($old_data->gambar) && file_exists('./assets/uploads/barang/' . $old_data->gambar)) {
            unlink('./assets/uploads/barang/' . $old_data->gambar);
        }
        
        $this->Barang_model->delete($id_aset);
        $this->session->set_flashdata('success', 'Data barang berhasil dihapus!');
        redirect('admin/barang');
    }
}
