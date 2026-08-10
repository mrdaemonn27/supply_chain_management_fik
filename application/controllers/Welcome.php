<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/userguide3/general/urls.html
	 */
	public function index()
	{
		// Daftar laboratorium di halaman depan diambil langsung dari basis data
		// supaya ikut berubah ketika ruangan ditambah atau diubah dari dashboard.
		// Halaman depan tetap harus tampil walau basis data sedang tidak bisa
		// dihubungi, jadi kegagalan di sini cukup menghasilkan daftar kosong.
		$ruangan = array();

		try
		{
			$this->load->model('Ruangan_model');
			$rows = $this->Ruangan_model->get_all_ruangan();

			if (is_array($rows))
			{
				// Model mengurutkan dari yang terbaru; untuk halaman depan urutan
				// nama lebih mudah dibaca dan tidak berubah-ubah tiap ada data baru.
				usort($rows, function ($a, $b) {
					return strcasecmp($a->nama_ruangan, $b->nama_ruangan);
				});
				$ruangan = $rows;
			}
		}
		catch (Exception $e)
		{
			log_message('error', 'Landing page gagal memuat daftar ruangan: '.$e->getMessage());
		}

		$this->load->view('landing/index', array('ruangan' => $ruangan));
	}
}
