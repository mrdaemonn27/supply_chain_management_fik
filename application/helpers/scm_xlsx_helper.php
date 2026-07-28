<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| scm_xlsx helper (perbaikan)
|--------------------------------------------------------------------------
| Mengonversi HTML (hasil render view) menjadi file .xlsx asli (OOXML),
| tanpa dependency composer/PhpSpreadsheet.
|
| Perbaikan dari versi sebelumnya:
| 1. Semua <table> pada HTML diproses (bukan hanya tabel pertama) -> setiap
|    <table> menjadi satu SHEET terpisah. Ini memperbaiki bug export
|    multi-pengajuan (export_pengajuan / export_pengajuan_acc) yang
|    sebelumnya hanya menampilkan pengajuan pertama.
| 2. rowspan ditangani dengan benar lewat algoritma "occupied grid", jadi
|    baris kedua header (Harga Sat, Jmlh Harga, dst) tidak lagi geser kolom.
| 3. colspan & rowspan ditulis sebagai merge cell asli (<mergeCell>) di xlsx.
| 4. Style dasar (bold, border, background header, alignment, ukuran font)
|    dibawa dari class CSS pada tabel (th, .title, .meta, .center, .right,
|    .bold, .group-row, .no-border) ke styles.xml.
| 5. Nilai yang berupa angka (format Indonesia: titik ribuan, koma desimal)
|    ditulis sebagai angka asli (bisa dihitung ulang di Excel), bukan teks.
| 6. Lebar kolom mengikuti persentase pada <colgroup><col style="width:..%">.
| 7. Nama sheet diambil dari nilai "Kode" pengajuan bila ditemukan, dan
|    dijamin unik antar sheet.
*/

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

if (!function_exists('scm_xlsx_mb_strlen')) {
    function scm_xlsx_mb_strlen($value) {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}

if (!function_exists('scm_xlsx_mb_substr')) {
    function scm_xlsx_mb_substr($value, $start, $length) {
        return function_exists('mb_substr') ? mb_substr($value, $start, $length, 'UTF-8') : substr($value, $start, $length);
    }
}

if (!function_exists('scm_xlsx_sanitize_sheet_name')) {
    function scm_xlsx_sanitize_sheet_name($name, $fallback_index) {
        $name = trim((string) $name);
        $name = preg_replace('/[\\\\\/\?\*\[\]\:]/', '-', $name);
        $name = trim($name, " '");
        if ($name === '') {
            $name = 'Pengajuan ' . $fallback_index;
        }
        if (scm_xlsx_mb_strlen($name) > 31) {
            $name = scm_xlsx_mb_substr($name, 0, 31);
        }
        return $name;
    }
}

if (!function_exists('scm_xlsx_unique_sheet_names')) {
    function scm_xlsx_unique_sheet_names($tables) {
        $used = [];
        foreach ($tables as $i => &$table) {
            $base = $table['name'];
            $name = $base;
            $suffix = 2;
            while (isset($used[$name])) {
                $tail = ' (' . $suffix . ')';
                $max_base_len = 31 - strlen($tail);
                $name = scm_xlsx_mb_substr($base, 0, max(1, $max_base_len)) . $tail;
                $suffix++;
            }
            $used[$name] = true;
            $table['name'] = $name;
        }
        unset($table);
        return $tables;
    }
}

if (!function_exists('scm_xlsx_cell_classes')) {
    function scm_xlsx_cell_classes(DOMElement $tr, DOMElement $cell) {
        $classes = [];
        foreach ([$tr, $cell] as $node) {
            $attr = $node->getAttribute('class');
            if ($attr !== '') {
                foreach (preg_split('/\s+/', trim($attr)) as $c) {
                    if ($c !== '') {
                        $classes[$c] = true;
                    }
                }
            }
        }
        return $classes;
    }
}

if (!function_exists('scm_xlsx_cell_style')) {
    function scm_xlsx_cell_style(DOMElement $tr, DOMElement $cell) {
        $tag = strtolower($cell->nodeName);
        $classes = scm_xlsx_cell_classes($tr, $cell);
        $is_header = $tag === 'th';
        $is_title = isset($classes['title']);
        $is_meta = isset($classes['meta']);
        $is_group = isset($classes['group-row']);
        $no_border = $is_title || $is_meta || isset($classes['no-border']);

        $bold = $is_header || $is_title || $is_group || isset($classes['bold']);

        $align = 'left';
        if ($is_title || $is_header || isset($classes['center'])) {
            $align = 'center';
        }
        if (isset($classes['right'])) {
            $align = 'right';
        }

        $fill = null;
        if ($is_header) {
            $fill = 'header';
        } elseif ($is_group) {
            $fill = 'group';
        }

        $size = 10;
        if ($is_title) {
            $size = 13;
        } elseif ($is_meta) {
            $size = 9;
        }

        return [
            'bold' => $bold,
            'align' => $align,
            'fill' => $fill,
            'border' => !$no_border,
            'size' => $size,
        ];
    }
}

if (!function_exists('scm_xlsx_is_numeric_text')) {
    function scm_xlsx_is_numeric_text($text) {
        $text = trim((string) $text);
        if ($text === '' || $text === '-') {
            return false;
        }
        // Format angka Indonesia: titik = ribuan, koma = desimal. Contoh: 1.234.567 atau 55.500 atau 2,5
        return (bool) preg_match('/^-?\d{1,3}(\.\d{3})*(,\d+)?$/', $text);
    }
}

if (!function_exists('scm_xlsx_numeric_value')) {
    function scm_xlsx_numeric_value($text) {
        $text = trim((string) $text);
        $text = str_replace('.', '', $text);
        $text = str_replace(',', '.', $text);
        return (float) $text;
    }
}

if (!function_exists('scm_xlsx_cell_text')) {
    function scm_xlsx_cell_text(DOMElement $cell) {
        // Normalisasi &nbsp; (U+00A0) jadi spasi biasa supaya sel "kosong" (spacer)
        // benar-benar terdeteksi kosong, bukan berisi karakter nbsp tak terlihat.
        $raw = str_replace("\xC2\xA0", ' ', $cell->textContent);
        return trim(preg_replace('/\s+/u', ' ', $raw));
    }
}

if (!function_exists('scm_xlsx_parse_tables')) {
    function scm_xlsx_parse_tables($html) {
        $tables = [];
        if (!class_exists('DOMDocument')) {
            return $tables;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xpath = new DOMXPath($document);

        $table_index = 0;
        foreach ($xpath->query('//table') as $table_node) {
            $table_index++;
            $grid = [];
            $merges = [];
            $occupied = [];
            $row_index = 0;
            $max_col = 0;

            foreach ($xpath->query('.//tr', $table_node) as $tr) {
                $row_index++;
                $col_pointer = 1;

                foreach ($xpath->query('./th|./td', $tr) as $cell) {
                    while (!empty($occupied[$row_index][$col_pointer])) {
                        $col_pointer++;
                    }

                    $colspan = max(1, (int) $cell->getAttribute('colspan'));
                    $rowspan = max(1, (int) $cell->getAttribute('rowspan'));
                    $text = scm_xlsx_cell_text($cell);
                    $style = scm_xlsx_cell_style($tr, $cell);
                    $is_numeric = scm_xlsx_is_numeric_text($text);

                    $grid[$row_index][$col_pointer] = [
                        'text' => $text,
                        'is_numeric' => $is_numeric,
                        'value' => $is_numeric ? scm_xlsx_numeric_value($text) : null,
                        'style' => $style,
                    ];

                    if ($colspan > 1 || $rowspan > 1) {
                        $merges[] = [
                            'r1' => $row_index,
                            'c1' => $col_pointer,
                            'r2' => $row_index + $rowspan - 1,
                            'c2' => $col_pointer + $colspan - 1,
                        ];
                    }

                    for ($r = $row_index; $r < $row_index + $rowspan; $r++) {
                        for ($c = $col_pointer; $c < $col_pointer + $colspan; $c++) {
                            $occupied[$r][$c] = true;
                        }
                    }

                    $max_col = max($max_col, $col_pointer + $colspan - 1);
                    $col_pointer += $colspan;
                }
            }

            $col_widths = [];
            foreach ($xpath->query('.//colgroup/col', $table_node) as $col_node) {
                $style_attr = $col_node->getAttribute('style');
                if (preg_match('/width\s*:\s*([\d.]+)\s*%/i', $style_attr, $m)) {
                    $col_widths[] = max(4, (float) $m[1]);
                } else {
                    $col_widths[] = null;
                }
            }

            // Cari nama sheet dari baris "Kode : <nilai>" bila ada.
            $name_hint = null;
            foreach ($grid as $row) {
                ksort($row);
                $cols = array_keys($row);
                foreach ($cols as $ci => $col) {
                    if (strcasecmp($row[$col]['text'], 'Kode') === 0) {
                        for ($j = $ci + 1; $j < count($cols); $j++) {
                            $candidate = trim($row[$cols[$j]]['text']);
                            if ($candidate !== '') {
                                $name_hint = preg_replace('/^:\s*/', '', $candidate);
                                break;
                            }
                        }
                    }
                    if ($name_hint !== null) {
                        break;
                    }
                }
                if ($name_hint !== null) {
                    break;
                }
            }

            $tables[] = [
                'name' => scm_xlsx_sanitize_sheet_name($name_hint, $table_index),
                'rows' => $grid,
                'row_count' => $row_index,
                'col_count' => $max_col,
                'merges' => $merges,
                'col_widths' => $col_widths,
            ];
        }

        return scm_xlsx_unique_sheet_names($tables);
    }
}

if (!function_exists('scm_xlsx_binary')) {
    function scm_xlsx_binary($html) {
        if (!class_exists('ZipArchive')) {
            return false;
        }

        $tables = scm_xlsx_parse_tables($html);
        if (empty($tables)) {
            $tables = [[
                'name' => 'Laporan',
                'rows' => [1 => [1 => [
                    'text' => 'Tidak ada data',
                    'is_numeric' => false,
                    'value' => null,
                    'style' => ['bold' => false, 'align' => 'left', 'fill' => null, 'border' => true, 'size' => 10],
                ]]],
                'row_count' => 1,
                'col_count' => 1,
                'merges' => [],
                'col_widths' => [],
            ]];
        }

        // ---------- Registry style (font, fill, border, cellXfs) ----------
        $fonts = ['<font><sz val="10"/><name val="Arial"/></font>']; // index 0 = default
        $font_index = ['|10' => 0];
        $get_font = function ($bold, $size) use (&$fonts, &$font_index) {
            $key = ($bold ? '1' : '') . '|' . $size;
            if (isset($font_index[$key])) {
                return $font_index[$key];
            }
            $idx = count($fonts);
            $fonts[] = '<font>' . ($bold ? '<b/>' : '') . '<sz val="' . $size . '"/><name val="Arial"/></font>';
            $font_index[$key] = $idx;
            return $idx;
        };

        $fills = [
            '<fill><patternFill patternType="none"/></fill>',
            '<fill><patternFill patternType="gray125"/></fill>',
        ];
        $fill_index = ['none' => 0];
        $get_fill = function ($fill) use (&$fills, &$fill_index) {
            if ($fill === null) {
                return 0;
            }
            if (isset($fill_index[$fill])) {
                return $fill_index[$fill];
            }
            $color = $fill === 'header' ? 'FFD9D9D9' : 'FFEEEEEE';
            $idx = count($fills);
            $fills[] = '<fill><patternFill patternType="solid"><fgColor rgb="' . $color . '"/><bgColor indexed="64"/></patternFill></fill>';
            $fill_index[$fill] = $idx;
            return $idx;
        };

        $borders = [
            '<border><left/><right/><top/><bottom/><diagonal/></border>',
            '<border><left style="thin"><color indexed="64"/></left><right style="thin"><color indexed="64"/></right><top style="thin"><color indexed="64"/></top><bottom style="thin"><color indexed="64"/></bottom><diagonal/></border>',
        ];

        $number_format_id = 164;

        $cell_xfs = ['<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>']; // index 0 = default
        $xf_index = [];
        $get_xf = function ($font_id, $fill_id, $border_id, $align, $num_fmt_id) use (&$cell_xfs, &$xf_index) {
            $key = $font_id . '-' . $fill_id . '-' . $border_id . '-' . $align . '-' . $num_fmt_id;
            if (isset($xf_index[$key])) {
                return $xf_index[$key];
            }
            $apply_num = $num_fmt_id ? ' applyNumberFormat="1"' : '';
            $xml = '<xf numFmtId="' . $num_fmt_id . '" fontId="' . $font_id . '" fillId="' . $fill_id . '" borderId="' . $border_id
                . '" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"' . $apply_num . '>'
                . '<alignment horizontal="' . $align . '" vertical="center" wrapText="1"/></xf>';
            $idx = count($cell_xfs);
            $cell_xfs[] = $xml;
            $xf_index[$key] = $idx;
            return $idx;
        };

        // ---------- Bangun tiap sheet ----------
        $sheets_xml = [];
        $sheet_names = [];

        foreach ($tables as $table) {
            $rows = $table['rows'];
            $row_count = max(1, $table['row_count']);
            $col_count = max(1, $table['col_count']);

            $sheet_rows = '';
            for ($r = 1; $r <= $row_count; $r++) {
                $row_cells = '';
                if (!empty($rows[$r])) {
                    ksort($rows[$r]);
                    foreach ($rows[$r] as $c => $cell) {
                        $font_id = $get_font($cell['style']['bold'], $cell['style']['size']);
                        $fill_id = $get_fill($cell['style']['fill']);
                        $border_id = $cell['style']['border'] ? 1 : 0;
                        $num_fmt = $cell['is_numeric'] ? $number_format_id : 0;
                        $xf = $get_xf($font_id, $fill_id, $border_id, $cell['style']['align'], $num_fmt);
                        $ref = scm_xlsx_column($c) . $r;

                        if ($cell['is_numeric']) {
                            $row_cells .= '<c r="' . $ref . '" s="' . $xf . '"><v>' . $cell['value'] . '</v></c>';
                        } elseif ($cell['text'] !== '') {
                            $row_cells .= '<c r="' . $ref . '" s="' . $xf . '" t="inlineStr"><is><t xml:space="preserve">'
                                . scm_xlsx_xml($cell['text']) . '</t></is></c>';
                        } else {
                            $row_cells .= '<c r="' . $ref . '" s="' . $xf . '"/>';
                        }
                    }
                }
                $sheet_rows .= '<row r="' . $r . '">' . $row_cells . '</row>';
            }

            $merge_xml = '';
            if (!empty($table['merges'])) {
                $merge_xml = '<mergeCells count="' . count($table['merges']) . '">';
                foreach ($table['merges'] as $m) {
                    $merge_xml .= '<mergeCell ref="' . scm_xlsx_column($m['c1']) . $m['r1'] . ':'
                        . scm_xlsx_column($m['c2']) . $m['r2'] . '"/>';
                }
                $merge_xml .= '</mergeCells>';
            }

            $cols_xml = '';
            if (!empty($table['col_widths'])) {
                $cols_xml = '<cols>';
                foreach ($table['col_widths'] as $ci => $w) {
                    if ($w === null) {
                        continue;
                    }
                    $cols_xml .= '<col min="' . ($ci + 1) . '" max="' . ($ci + 1) . '" width="' . $w . '" customWidth="1"/>';
                }
                $cols_xml .= '</cols>';
            }

            $dimension = 'A1:' . scm_xlsx_column($col_count) . $row_count;
            $sheets_xml[] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . '<dimension ref="' . $dimension . '"/>'
                . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
                . '<sheetFormatPr defaultRowHeight="15"/>'
                . $cols_xml
                . '<sheetData>' . $sheet_rows . '</sheetData>'
                . $merge_xml
                . '</worksheet>';
            $sheet_names[] = scm_xlsx_xml($table['name']);
        }

        // ---------- Rangkai workbook & relationship ----------
        $sheets_entries = '';
        $sheet_rels = '';
        foreach ($sheet_names as $i => $name) {
            $sheet_id = $i + 1;
            $sheets_entries .= '<sheet name="' . $name . '" sheetId="' . $sheet_id . '" r:id="rId' . $sheet_id . '"/>';
            $sheet_rels .= '<Relationship Id="rId' . $sheet_id
                . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
                . ' Target="worksheets/sheet' . $sheet_id . '.xml"/>';
        }
        $styles_rel_id = 'rId' . (count($sheet_names) + 1);
        $sheet_rels .= '<Relationship Id="' . $styles_rel_id
            . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheets_entries . '</sheets></workbook>';

        $workbook_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $sheet_rels . '</Relationships>';

        $content_types_overrides = '';
        foreach ($sheet_names as $i => $name) {
            $sheet_id = $i + 1;
            $content_types_overrides .= '<Override PartName="/xl/worksheets/sheet' . $sheet_id
                . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        $content_types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $content_types_overrides
            . '</Types>';

        $root_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="' . $number_format_id . '" formatCode="#,##0.##"/></numFmts>'
            . '<fonts count="' . count($fonts) . '">' . implode('', $fonts) . '</fonts>'
            . '<fills count="' . count($fills) . '">' . implode('', $fills) . '</fills>'
            . '<borders count="' . count($borders) . '">' . implode('', $borders) . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count($cell_xfs) . '">' . implode('', $cell_xfs) . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';

        $temporary = tempnam(sys_get_temp_dir(), 'scm_xlsx_');
        $zip = new ZipArchive();
        if ($zip->open($temporary, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }
        $zip->addFromString('[Content_Types].xml', $content_types);
        $zip->addFromString('_rels/.rels', $root_rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbook_rels);
        $zip->addFromString('xl/styles.xml', $styles);
        foreach ($sheets_xml as $i => $xml) {
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $xml);
        }
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