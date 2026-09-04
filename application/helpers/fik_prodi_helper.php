<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('fik_program_studi')) {
    function fik_program_studi()
    {
        return [
            'S1 Desain Komunikasi Visual (DKV)',
            'S1 Desain Interior',
            'S1 Desain Produk',
            'S1 Kriya Tekstil dan Mode',
            'S1 Seni Rupa',
            'S1 Film & Animasi',
            'S2 Magister Desain',
        ];
    }
}

if (!function_exists('fik_normalize_prodi')) {
    function fik_normalize_prodi($value)
    {
        $value = trim((string) $value);
        if ($value === '') return null;

        foreach (fik_program_studi() as $program) {
            if (strcasecmp($value, $program) === 0) return $program;
        }

        $aliases = [
            'dkv' => 'S1 Desain Komunikasi Visual (DKV)',
            's1 dkv' => 'S1 Desain Komunikasi Visual (DKV)',
            'desain komunikasi visual' => 'S1 Desain Komunikasi Visual (DKV)',
            'desain interior' => 'S1 Desain Interior',
            'desain produk' => 'S1 Desain Produk',
            'kriya tekstil dan mode' => 'S1 Kriya Tekstil dan Mode',
            'seni rupa' => 'S1 Seni Rupa',
            'film dan animasi' => 'S1 Film & Animasi',
            'film & animasi' => 'S1 Film & Animasi',
            'magister desain' => 'S2 Magister Desain',
            's2 desain' => 'S2 Magister Desain',
        ];
        $key = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        return $aliases[$key] ?? null;
    }
}

if (!function_exists('fik_jenis_peminjam')) {
    function fik_jenis_peminjam()
    {
        return ['Mahasiswa', 'Dosen', 'Staff'];
    }
}

if (!function_exists('fik_normalize_jenis_peminjam')) {
    function fik_normalize_jenis_peminjam($value)
    {
        foreach (fik_jenis_peminjam() as $jenis) {
            if (strcasecmp(trim((string) $value), $jenis) === 0) return $jenis;
        }
        return null;
    }
}
