<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('scm_pagination_tokens')) {
    /** Return compact page tokens with the first/last page and ellipses. */
    function scm_pagination_tokens($current, $last)
    {
        $last = max(1, (int) $last);
        $current = max(1, min((int) $current, $last));
        if ($last <= 7) return range(1, $last);
        if ($current <= 3) return array_merge(range(1, 5), ['ellipsis-after', $last]);
        if ($current >= $last - 2) return array_merge([1, 'ellipsis-before'], range($last - 4, $last));
        return array_merge([1, 'ellipsis-before'], range($current - 2, $current + 2), ['ellipsis-after', $last]);
    }
}

if (!function_exists('scm_read_per_page')) {
    function scm_read_per_page($value, array $allowed = [10, 25, 50, 100], $fallback = 10)
    {
        $value = (int) $value;
        return in_array($value, $allowed, true) ? $value : (int) $fallback;
    }
}
