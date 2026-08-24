<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('scm_sort_url')) {
    function scm_sort_url($field, $current_field = '', $current_direction = '', $sort_param = 'sort_by', $direction_param = 'sort_dir', $page_param = 'page') {
        $query = $_GET;
        $query[$sort_param] = (string) $field;
        $query[$direction_param] = ((string) $current_field === (string) $field && strtolower((string) $current_direction) === 'asc') ? 'desc' : 'asc';
        $query[$page_param] = 1;
        return current_url() . '?' . http_build_query($query);
    }
}

if (!function_exists('scm_sort_icon_class')) {
    function scm_sort_icon_class($field, $current_field = '', $current_direction = '') {
        if ((string) $current_field !== (string) $field) return 'bi-arrow-down-up';
        return strtolower((string) $current_direction) === 'asc' ? 'bi-sort-up-alt' : 'bi-sort-down';
    }
}

if (!function_exists('scm_sort_aria')) {
    function scm_sort_aria($field, $current_field = '', $current_direction = '') {
        if ((string) $current_field !== (string) $field) return 'none';
        return strtolower((string) $current_direction) === 'asc' ? 'ascending' : 'descending';
    }
}
