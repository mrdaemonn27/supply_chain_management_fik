<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('tanggal_indonesia')) {
    /**
     * Mengubah tanggal database menjadi format numerik Indonesia: dd/mm/yyyy.
     * Jika waktu diminta, hasilnya dd/mm/yyyy HH:mm WIB.
     */
    function tanggal_indonesia($date_string, $with_time = false) {
        if (empty($date_string) || $date_string === '0000-00-00' || $date_string === '0000-00-00 00:00:00') {
            return '-';
        }

        $timestamp = strtotime((string) $date_string);
        if (!$timestamp) {
            return '-';
        }

        $label = date('d/m/Y', $timestamp);
        return $with_time ? $label . ' ' . date('H:i', $timestamp) . ' WIB' : $label;
    }
}

if (!function_exists('waktu_indonesia')) {
    /**
     * Format tanggal dan jam yang seragam untuk seluruh tampilan aplikasi.
     */
    function waktu_indonesia($date_string) {
        return tanggal_indonesia($date_string, true);
    }
}

if (!function_exists('jam_indonesia')) {
    function jam_indonesia($date_string) {
        if (empty($date_string) || $date_string === '0000-00-00 00:00:00') {
            return '-';
        }
        $timestamp = strtotime((string) $date_string);
        return $timestamp ? date('H:i', $timestamp) . ' WIB' : '-';
    }
}

if (!function_exists('masa_pinjam_indonesia')) {
    function masa_pinjam_indonesia($tanggal_pinjam, $tanggal_kembali) {
        return tanggal_indonesia($tanggal_pinjam) . ' s.d. ' . tanggal_indonesia($tanggal_kembali);
    }
}

if (!function_exists('durasi_pinjam_hari')) {
    /**
     * Menghitung lama peminjaman secara inklusif (hari pinjam dan hari kembali dihitung).
     */
    function durasi_pinjam_hari($tanggal_pinjam, $tanggal_kembali) {
        $mulai = DateTime::createFromFormat('!Y-m-d', substr((string) $tanggal_pinjam, 0, 10));
        $selesai = DateTime::createFromFormat('!Y-m-d', substr((string) $tanggal_kembali, 0, 10));
        if (!$mulai || !$selesai || $selesai < $mulai) {
            return 0;
        }
        return (int) $mulai->diff($selesai)->days + 1;
    }
}

if (!function_exists('scm_parse_date_range')) {
    /**
     * Membaca nilai filter tanggal tunggal atau rentang YYYY-MM-DD..YYYY-MM-DD.
     * Hasil selalu diurutkan dari tanggal paling awal ke paling akhir.
     */
    function scm_parse_date_range($value) {
        $value = trim((string) $value);
        if (!preg_match('/^(\d{4}-\d{2}-\d{2})(?:\.\.(\d{4}-\d{2}-\d{2}))?$/', $value, $matches)) {
            return null;
        }

        $start = $matches[1];
        $end = !empty($matches[2]) ? $matches[2] : $start;
        foreach ([$start, $end] as $date) {
            $parsed = DateTime::createFromFormat('!Y-m-d', $date);
            if (!$parsed || $parsed->format('Y-m-d') !== $date) {
                return null;
            }
        }

        return $start <= $end
            ? ['start' => $start, 'end' => $end]
            : ['start' => $end, 'end' => $start];
    }
}

if (!function_exists('scm_date_in_range')) {
    function scm_date_in_range($date, $range_value) {
        $range = scm_parse_date_range($range_value);
        $date = substr(trim((string) $date), 0, 10);
        return $range && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            ? ($date >= $range['start'] && $date <= $range['end'])
            : false;
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
