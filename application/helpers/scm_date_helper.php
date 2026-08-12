<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('tanggal_indonesia')) {
    /**
     * Mengubah tanggal database menjadi format Indonesia, misalnya 12 Agustus 2026.
     */
    function tanggal_indonesia($date_string, $with_time = false) {
        if (empty($date_string) || $date_string === '0000-00-00' || $date_string === '0000-00-00 00:00:00') {
            return '-';
        }

        $timestamp = strtotime((string) $date_string);
        if (!$timestamp) {
            return '-';
        }

        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];
        $label = date('j', $timestamp) . ' ' . $bulan[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);

        return $with_time ? $label . ', ' . date('H:i', $timestamp) : $label;
    }
}

if (!function_exists('masa_pinjam_indonesia')) {
    function masa_pinjam_indonesia($tanggal_pinjam, $tanggal_kembali) {
        return tanggal_indonesia($tanggal_pinjam) . ' s.d. ' . tanggal_indonesia($tanggal_kembali);
    }
}

if (!function_exists('scm_upload_url')) {
    /**
     * Membentuk URL upload baik ketika database menyimpan nama file saja
     * maupun path relatif lengkap seperti assets/uploads/....
     */
    function scm_upload_url($file, $default_directory = '') {
        $file = trim(str_replace('\\', '/', (string) $file));
        if ($file === '') {
            return '';
        }
        if (preg_match('#^(?:https?:)?//#i', $file) || strpos($file, 'data:') === 0) {
            return $file;
        }

        $relative = ltrim($file, '/');
        if (strpos($relative, '/') === false) {
            $relative = trim($default_directory, '/') . '/' . rawurlencode($relative);
        }
        return base_url($relative);
    }
}

if (!function_exists('scm_upload_exists')) {
    function scm_upload_exists($file, $default_directory = '') {
        $file = trim(str_replace('\\', '/', (string) $file));
        if ($file === '' || preg_match('#^(?:https?:)?//#i', $file) || strpos($file, 'data:') === 0) {
            return $file !== '';
        }
        $relative = ltrim($file, '/');
        if (strpos($relative, '/') === false) {
            $relative = trim($default_directory, '/') . '/' . $relative;
        }
        return is_file(FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relative));
    }
}
