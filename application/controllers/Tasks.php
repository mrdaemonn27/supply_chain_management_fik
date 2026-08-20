<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Task scheduler internal. Hanya dapat dijalankan melalui PHP CLI. */
class Tasks extends CI_Controller {
    public function __construct() {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            show_404();
        }
        $this->load->model('Peminjaman_model');
    }

    public function expire_kaprodi_approvals() {
        $count = $this->Peminjaman_model->get_last_expired_count()
            + $this->Peminjaman_model->expire_overdue_kaprodi_approvals(1000);
        $this->output->set_output('Selesai. ' . $count . " pengajuan Kaprodi dikedaluwarsakan.\n");
    }
}
