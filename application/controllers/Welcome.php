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

		$faqs = array();
		try
		{
			$this->load->model('Faq_model');
			$rows = $this->Faq_model->get_active();
			if (is_array($rows)) $faqs = $rows;
		}
		catch (Exception $e)
		{
			log_message('error', 'Landing page gagal memuat FAQ: '.$e->getMessage());
		}

		$this->load->view('landing/index', array('ruangan' => $ruangan, 'faqs' => $faqs));
	}

	/** Endpoint publik khusus chatbot FAQ; hanya membaca FAQ aktif. */
	public function faq_search()
	{
		$this->output->set_content_type('application/json', 'utf-8');
		$question = trim((string) $this->input->post('question', TRUE));
		$context_json = (string) $this->input->post('context', TRUE);
		$context = json_decode($context_json, TRUE);
		if (!is_array($context)) $context = array();
		$context = array_slice($context, -6);

		if ($question === '')
		{
			return $this->output->set_output(json_encode(array(
				'ok' => FALSE,
				'message' => 'Tulis pertanyaan tentang SCM FIK terlebih dahulu.',
				'suggestions' => array(),
			), JSON_UNESCAPED_UNICODE));
		}

		if (function_exists('mb_strlen') ? mb_strlen($question, 'UTF-8') > 500 : strlen($question) > 500)
		{
			return $this->output->set_output(json_encode(array(
				'ok' => FALSE,
				'message' => 'Pertanyaan maksimal 500 karakter.',
				'suggestions' => array(),
			), JSON_UNESCAPED_UNICODE));
		}

		$this->load->model('Faq_model');
		$result = $this->Faq_model->search_faq($question, $context);

		if ($result['match'])
		{
			return $this->output->set_output(json_encode(array(
				'ok' => TRUE,
				'faq' => $result['match'],
				'confidence' => $result['confidence'],
				'suggestions' => $result['confidence'] === 'medium' ? $result['suggestions'] : array(),
			), JSON_UNESCAPED_UNICODE));
		}

		$message = !empty($result['out_of_scope'])
			? 'Saya khusus membantu informasi seputar SCM FIK. Kamu bisa bertanya tentang pengajuan, peminjaman, persetujuan, aset, pengembalian, BAST, atau fitur sistem lainnya.'
			: 'Maaf, saya belum menemukan informasi yang cukup untuk menjawab pertanyaan tersebut dari data SCM FIK.';

		return $this->output->set_output(json_encode(array(
			'ok' => FALSE,
			'message' => $message,
			'confidence' => $result['confidence'],
			'suggestions' => $result['suggestions'],
		), JSON_UNESCAPED_UNICODE));
	}
}
