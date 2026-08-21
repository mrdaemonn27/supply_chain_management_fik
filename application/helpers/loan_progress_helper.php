<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('scm_loan_progress')) {
    /**
     * Mengubah status internal peminjaman menjadi progres 8 tahap yang sama
     * untuk seluruh role. Indeks tahap menggunakan nilai 0 sampai 7.
     */
    function scm_loan_progress($loan)
    {
        $status = trim((string) ($loan->status ?? ''));
        $kaprodi = (string) ($loan->status_kaprodi ?? 'Pending');
        $laboran = (string) ($loan->status_laboran ?? 'Pending');
        $kaur = (string) ($loan->status_kaur ?? 'Pending');

        $steps = [
            'Diajukan',
            'Kaprodi',
            'Laboran',
            'Kaur',
            'Finalisasi QR',
            'Dipinjam',
            'Pengembalian',
            'Selesai',
        ];

        $current_index = 0;
        $stage_label = 'Diajukan';
        $status_label = 'Pengajuan dibuat';
        $tone = 'current';
        $rejected_index = null;

        switch ($status) {
            case 'Menunggu ACC Kaprodi':
                $current_index = 1;
                $stage_label = 'Kaprodi';
                $status_label = 'Menunggu Persetujuan Kaprodi';
                break;

            case 'Menunggu Verifikasi Laboran':
            case 'Menunggu Pengecekan Laboran':
            case 'Menunggu Persetujuan':
                $current_index = 2;
                $stage_label = 'Laboran';
                $status_label = 'Menunggu Verifikasi Laboran';
                break;

            case 'Menunggu ACC Kaur':
                $current_index = 3;
                $stage_label = 'Kaur';
                $status_label = 'Menunggu Persetujuan Kaur';
                break;

            case 'Disetujui (Menunggu Finalisasi QR)':
                $current_index = 4;
                $stage_label = 'Finalisasi QR';
                $status_label = 'Menunggu Finalisasi QR oleh Laboran';
                break;

            case 'Disetujui (Menunggu Pengambilan)':
                $current_index = 5;
                $stage_label = 'Pengambilan';
                $status_label = 'QR Aktif — Menunggu Pengambilan';
                $tone = 'ready';
                break;

            case 'Sedang Dipinjam':
            case 'Dipinjam':
                $current_index = 6;
                $stage_label = 'Pengembalian';
                $status_label = 'Sedang Dipinjam — Menunggu Pengembalian';
                $tone = 'active';
                break;

            case 'Dikembalikan':
            case 'Selesai':
                $current_index = 7;
                $stage_label = 'Selesai';
                $status_label = 'Selesai — Barang Dikembalikan';
                $tone = 'complete';
                break;

            case 'Ditolak':
                if ($kaprodi === 'Ditolak') {
                    $rejected_index = 1;
                    $stage_label = 'Kaprodi';
                    $status_label = 'Ditolak oleh Kaprodi';
                } elseif ($laboran === 'Ditolak') {
                    $rejected_index = 2;
                    $stage_label = 'Laboran';
                    $status_label = 'Ditolak oleh Laboran';
                } elseif ($kaur === 'Ditolak') {
                    $rejected_index = 3;
                    $stage_label = 'Kaur';
                    $status_label = 'Ditolak oleh Kaur';
                } else {
                    $rejected_index = 1;
                    $stage_label = 'Persetujuan';
                    $status_label = 'Pengajuan Ditolak';
                }
                $current_index = $rejected_index;
                $tone = 'rejected';
                break;

            case 'Kedaluwarsa / Ditolak Otomatis':
                $current_index = 1;
                $rejected_index = 1;
                $stage_label = 'Kaprodi';
                $status_label = 'Kedaluwarsa — Ditolak Otomatis';
                $tone = 'rejected';
                break;

            default:
                if ($status !== '') {
                    $status_label = $status;
                }
                break;
        }

        $progress_steps = [];
        foreach ($steps as $index => $label) {
            if ($tone === 'complete') {
                $state = 'is-complete';
            } elseif ($rejected_index !== null && $index === $rejected_index) {
                $state = 'is-rejected';
            } elseif ($index < $current_index) {
                $state = 'is-complete';
            } elseif ($index === $current_index) {
                $state = 'is-current';
            } else {
                $state = 'is-pending';
            }
            $progress_steps[] = ['label' => $label, 'state' => $state];
        }

        return [
            'raw_status' => $status,
            'status_label' => $status_label,
            'stage_label' => $stage_label,
            'current_index' => $current_index,
            'total_steps' => count($steps),
            'tone' => $tone,
            'kaprodi_deadline_at' => $loan->kaprodi_deadline_at ?? null,
            'steps' => $progress_steps,
        ];
    }
}

if (!function_exists('scm_loan_can_act')) {
    /** Hak aksi UI. Controller tetap melakukan validasi yang sama di server. */
    function scm_loan_can_act($loan, $role)
    {
        $status = (string) ($loan->status ?? '');
        $kaprodi = (string) ($loan->status_kaprodi ?? 'Pending');
        $laboran = (string) ($loan->status_laboran ?? 'Pending');
        $kaur = (string) ($loan->status_kaur ?? 'Pending');

        if ($role === 'kaprodi') {
            return $status === 'Menunggu ACC Kaprodi' && $kaprodi === 'Pending';
        }
        if ($role === 'laboran') {
            return in_array($status, ['Menunggu Verifikasi Laboran', 'Menunggu Pengecekan Laboran', 'Menunggu Persetujuan'], true)
                && $kaprodi === 'Disetujui' && $laboran === 'Pending';
        }
        if ($role === 'kaur') {
            return $status === 'Menunggu ACC Kaur'
                && $kaprodi === 'Disetujui' && $laboran === 'Disetujui' && $kaur === 'Pending';
        }
        if ($role === 'finalisasi_qr') {
            return $status === 'Disetujui (Menunggu Finalisasi QR)'
                && $kaprodi === 'Disetujui' && $laboran === 'Disetujui' && $kaur === 'Disetujui';
        }
        if ($role === 'serah_terima') {
            return $status === 'Disetujui (Menunggu Pengambilan)' && (int) ($loan->qr_locked ?? 0) === 1;
        }
        if ($role === 'pengembalian') {
            return in_array($status, ['Sedang Dipinjam', 'Dipinjam'], true);
        }

        return false;
    }
}
