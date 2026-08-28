<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Runner migration khusus command line.
 *
 * Jalankan dari root project:
 *   C:\xampp\php\php.exe index.php migrate
 */
class Migrate extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->input->is_cli_request())
        {
            show_404();
        }
    }

    public function index()
    {
        $this->load->library('migration');

        if ($this->migration->current() === FALSE)
        {
            fwrite(STDERR, 'Migration gagal: '.$this->migration->error_string().PHP_EOL);
            exit(1);
        }

        fwrite(STDOUT, 'Database berhasil disinkronkan ke migration 20260828193000.'.PHP_EOL);
    }
}
