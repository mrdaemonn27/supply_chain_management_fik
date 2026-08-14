<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Maintenance_model extends CI_Model
{
    private $table = 'maintenance';
    private $assetTable = 'aset';
    private $roomTable = 'ruangan';
    private $primaryKey = 'id_maintenance';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Base query untuk maintenance
     */
    private function baseQuery()
    {
        $this->db
            ->select('
                maintenance.*,
                aset.nama_aset,
                aset.kode_aset,
                aset.id_ruangan,
                ruangan.nama_ruangan
            ')
            ->from($this->table)
            ->join($this->assetTable, 'maintenance.id_aset = aset.id_aset')
            ->join($this->roomTable, 'aset.id_ruangan = ruangan.id_ruangan', 'left');
    }

    /**
     * Semua data maintenance
     */
    public function get_all_maintenance($limit = null, $offset = 0, $criteria = [])
    {
        $this->baseQuery();
        $this->applyCriteria($criteria);

        $this->db->order_by('maintenance.tanggal_maintenance', 'DESC');

        if ($limit !== null) {
            $this->db->limit($limit, $offset);
        }

        return $this->db->get()->result();
    }

    /**
     * Detail maintenance
     */
    public function get_maintenance_by_id($id)
    {
        $this->baseQuery();

        return $this->db
            ->where("maintenance.{$this->primaryKey}", $id)
            ->get()
            ->row();
    }

    /**
     * Riwayat maintenance berdasarkan aset
     */
    public function get_maintenance_by_aset($id_aset, $limit = null)
    {
        $this->baseQuery();

        $this->db
            ->where('maintenance.id_aset', $id_aset)
            ->order_by('maintenance.tanggal_maintenance', 'DESC');

        if ($limit !== null) {
            $this->db->limit($limit);
        }

        return $this->db->get()->result();
    }

    /**
     * Tambah maintenance
     */
    public function insert_maintenance($data)
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * Update maintenance
     */
    public function update_maintenance($id, $data)
    {
        return $this->db
            ->where($this->primaryKey, $id)
            ->update($this->table, $data);
    }

    /**
     * Hapus maintenance
     */
    public function delete_maintenance($id)
    {
        return $this->db
            ->where($this->primaryKey, $id)
            ->delete($this->table);
    }

    /**
     * Cek apakah aset pernah maintenance
     */
    public function has_maintenance($id_aset)
    {
        return $this->db
            ->where('id_aset', $id_aset)
            ->count_all_results($this->table) > 0;
    }

    /**
     * Maintenance terakhir
     */
    public function get_latest_maintenance($id_aset)
    {
        return $this->db
            ->where('id_aset', $id_aset)
            ->order_by('tanggal_maintenance', 'DESC')
            ->limit(1)
            ->get($this->table)
            ->row();
    }

    /**
     * Total seluruh maintenance
     */
    public function count($criteria = [])
    {
        $this->baseQuery();
        $this->applyCriteria($criteria);
        return (int) $this->db->count_all_results();
    }

    private function applyCriteria($criteria)
    {
        foreach ((array) $criteria as $criterion) {
            $field = $criterion['field'] ?? '';
            $value = trim((string) ($criterion['value'] ?? ''));
            if ($value === '') continue;
            if ($field === 'aset') {
                $this->db->group_start()->like('aset.nama_aset', $value)->or_like('aset.kode_aset', $value)->group_end();
            } elseif ($field === 'ruangan') {
                $this->db->like('ruangan.nama_ruangan', $value);
            } elseif ($field === 'tanggal') {
                $this->db->like('maintenance.tanggal_maintenance', $value, 'after');
            } elseif ($field === 'kondisi') {
                $this->db->like('maintenance.kondisi_setelah', $value);
            } elseif ($field === 'deskripsi') {
                $this->db->group_start()->like('maintenance.deskripsi', $value)->or_like('maintenance.catatan', $value)->group_end();
            }
        }
    }

    /**
     * Total maintenance berdasarkan aset
     */
    public function count_by_aset($id_aset)
    {
        return $this->db
            ->where('id_aset', $id_aset)
            ->count_all_results($this->table);
    }

    /**
     * Maintenance berdasarkan status
     */
    public function get_by_status($status)
    {
        $this->baseQuery();

        return $this->db
            ->where('maintenance.status', $status)
            ->order_by('maintenance.tanggal_maintenance', 'DESC')
            ->get()
            ->result();
    }

    /**
     * Hitung maintenance berdasarkan status
     */
    public function count_by_status($status)
    {
        return $this->db
            ->where('status', $status)
            ->count_all_results($this->table);
    }
}
