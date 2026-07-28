<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('scm_xlsx_column')) {
    function scm_xlsx_column($number) {
        $column = '';
        while ($number > 0) {
            $remainder = ($number - 1) % 26;
            $column = chr(65 + $remainder) . $column;
            $number = (int) (($number - $remainder - 1) / 26);
        }
        return $column;
    }
}

if (!function_exists('scm_xlsx_xml')) {
    function scm_xlsx_xml($value) {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('scm_xlsx_rows_from_html')) {
    function scm_xlsx_rows_from_html($html) {
        $rows = [];
        if (!class_exists('DOMDocument')) {
            return $rows;
        }
        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xpath = new DOMXPath($document);
        $table = $xpath->query('//table[1]')->item(0);
        if (!$table) {
            return $rows;
        }
        foreach ($xpath->query('.//tr', $table) as $tr) {
            $cells = [];
            foreach ($xpath->query('./th|./td', $tr) as $cell) {
                $text = preg_replace('/\s+/u', ' ', trim($cell->textContent));
                $span = max(1, (int) $cell->getAttribute('colspan'));
                $cells[] = $text;
                for ($i = 1; $i < $span; $i++) {
                    $cells[] = '';
                }
            }
            if (!empty($cells)) {
                $rows[] = $cells;
            }
        }
        return $rows;
    }
}

if (!function_exists('scm_xlsx_binary')) {
    function scm_xlsx_binary($html) {
        if (!class_exists('ZipArchive')) {
            return false;
        }
        $rows = scm_xlsx_rows_from_html($html);
        if (empty($rows)) {
            $rows = [['Tidak ada data']];
        }
        $max_columns = 0;
        foreach ($rows as $row) {
            $max_columns = max($max_columns, count($row));
        }

        $sheet_rows = '';
        foreach ($rows as $row_index => $row) {
            $sheet_rows .= '<row r="' . ($row_index + 1) . '">';
            foreach (array_values($row) as $column_index => $value) {
                $cell = scm_xlsx_column($column_index + 1) . ($row_index + 1);
                $sheet_rows .= '<c r="' . $cell . '" t="inlineStr"><is><t>' . scm_xlsx_xml($value) . '</t></is></c>';
            }
            $sheet_rows .= '</row>';
        }

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Laporan" sheetId="1" r:id="rId1"/></sheets></workbook>';
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:' . scm_xlsx_column(max(1, $max_columns)) . max(1, count($rows)) . '"/><sheetData>' . $sheet_rows . '</sheetData></worksheet>';
        $content_types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>';
        $root_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
        $workbook_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>';

        $temporary = tempnam(sys_get_temp_dir(), 'scm_xlsx_');
        $zip = new ZipArchive();
        if ($zip->open($temporary, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }
        $zip->addFromString('[Content_Types].xml', $content_types);
        $zip->addFromString('_rels/.rels', $root_rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbook_rels);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();
        $binary = file_get_contents($temporary);
        @unlink($temporary);
        return $binary;
    }
}

if (!function_exists('scm_download_xlsx')) {
    function scm_download_xlsx($filename, $html) {
        $binary = scm_xlsx_binary($html);
        if ($binary === false) {
            show_error('Ekstensi ZipArchive atau DOMDocument PHP belum aktif sehingga file XLSX tidak dapat dibuat.', 500);
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . strlen($binary));
        header('Cache-Control: max-age=0');
        echo $binary;
    }
}
