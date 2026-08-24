<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('scm_json_response')) {
    /**
     * Send a consistent JSON response for progressive AJAX enhancements.
     * Existing non-AJAX form flows continue to use flashdata + redirect.
     */
    function scm_json_response(array $payload, $status = 200)
    {
        $ci =& get_instance();
        $payload += ['success' => $status >= 200 && $status < 300];
        $ci->output
            ->set_status_header((int) $status)
            ->set_content_type('application/json', 'utf-8')
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0')
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}

if (!function_exists('scm_is_ajax')) {
    function scm_is_ajax()
    {
        $ci =& get_instance();
        return $ci->input->is_ajax_request()
            || strpos(strtolower((string) $ci->input->get_request_header('Accept')), 'application/json') !== false;
    }
}

if (!function_exists('scm_json_abort')) {
    /** Emit JSON immediately when a constructor guard must stop dispatch. */
    function scm_json_abort(array $payload, $status = 400)
    {
        scm_json_response($payload, $status);
        $ci =& get_instance();
        $ci->output->_display();
        exit;
    }
}
