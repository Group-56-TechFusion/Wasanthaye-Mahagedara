<?php
/**
 * Tiny multi-sheet .xlsx writer (no Composer packages needed; uses PHP's ZipArchive).
 *
 * A cell is a scalar (int/float => number, string => text, null => empty)
 * or [value, style] where style is one of: title, head, money, int, bold, boldmoney, boldint.
 */
class XlsxWriter
{
    private const STYLES = ['title' => 1, 'head' => 2, 'money' => 3, 'bold' => 4, 'boldmoney' => 5, 'int' => 6, 'boldint' => 7];

    /** @var array<int, array{name:string, rows:array, freeze:int}> */
    private array $sheets = [];

    public static function available(): bool
    {
        return class_exists('ZipArchive');
    }

    /** $freeze = number of top rows to keep visible while scrolling. */
    public function sheet(string $name, array $rows, int $freeze = 0): void
    {
        $this->sheets[] = ['name' => $this->uniqueName($name), 'rows' => $rows, 'freeze' => $freeze];
    }

    // ------------------------------------------------------------------ xlsx

    public function xlsx(): string
    {
        if (!self::available()) {
            throw new RuntimeException('The PHP zip extension is not enabled.');
        }
        if (!$this->sheets) {
            $this->sheet('Report', [['No data']]);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the export file.');
        }

        $types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $wbSheets = '';
        $wbRels   = '';
        foreach ($this->sheets as $i => $s) {
            $n = $i + 1;
            $types   .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $wbSheets .= '<sheet name="' . $this->x($s['name']) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
            $wbRels  .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
            $zip->addFromString('xl/worksheets/sheet' . $n . '.xml', $this->sheetXml($s, $i === 0));
        }
        $types .= '</Types>';
        $styleRel = count($this->sheets) + 1;
        $wbRels  .= '<Relationship Id="rId' . $styleRel . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        $zip->addFromString('[Content_Types].xml', $types);
        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $wbSheets . '</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $wbRels . '</Relationships>');
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->close();

        $data = (string) file_get_contents($tmp);
        @unlink($tmp);
        return $data;
    }

    private function sheetXml(array $s, bool $selected): string
    {
        $rows  = $s['rows'];
        $width = [];
        $xml   = '';

        foreach ($rows as $r => $row) {
            $xml .= '<row r="' . ($r + 1) . '">';
            foreach (array_values($row) as $c => $cell) {
                [$v, $style] = is_array($cell) ? [$cell[0] ?? null, $cell[1] ?? null] : [$cell, null];
                if ($v === null || $v === '') {
                    continue;
                }
                $ref = $this->col($c) . ($r + 1);
                $s_  = $style && isset(self::STYLES[$style]) ? ' s="' . self::STYLES[$style] . '"' : '';

                if (is_int($v) || is_float($v)) {
                    $xml .= '<c r="' . $ref . '"' . $s_ . '><v>' . (is_float($v) ? rtrim(rtrim(sprintf('%.4f', $v), '0'), '.') : $v) . '</v></c>';
                    $len = strlen(number_format((float) $v, 2)) + 1;
                } else {
                    $text = $this->clean((string) $v);
                    $xml .= '<c r="' . $ref . '"' . $s_ . ' t="inlineStr"><is><t xml:space="preserve">' . $this->x($text) . '</t></is></c>';
                    $len = $style === 'title' ? 0 : mb_strlen($text) + ($style === 'head' ? 2 : 1);
                }
                $width[$c] = max($width[$c] ?? 8, min($len, 55));
            }
            $xml .= '</row>';
        }

        $cols = '';
        foreach ($width as $c => $w) {
            $cols .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . $w . '" customWidth="1"/>';
        }

        $pane = $s['freeze'] > 0
            ? '<pane ySplit="' . $s['freeze'] . '" topLeftCell="A' . ($s['freeze'] + 1) . '" activePane="bottomLeft" state="frozen"/>'
            : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"' . ($selected ? ' tabSelected="1"' : '') . '>' . $pane . '</sheetView></sheetViews>'
            . ($cols !== '' ? '<cols>' . $cols . '</cols>' : '')
            . '<sheetData>' . $xml . '</sheetData></worksheet>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="4">'
            .   '<font><sz val="11"/><name val="Calibri"/></font>'
            .   '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            .   '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            .   '<font><b/><sz val="14"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            .   '<fill><patternFill patternType="none"/></fill>'
            .   '<fill><patternFill patternType="gray125"/></fill>'
            .   '<fill><patternFill patternType="solid"><fgColor rgb="FF14213D"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="8">'
            .   '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .   '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .   '<xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            .   '<xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .   '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .   '<xf numFmtId="4" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1"/>'
            .   '<xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .   '<xf numFmtId="3" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    // ------------------------------------------------------------------ csv (fallback when zip is missing)

    /** All sheets stacked in one UTF-8 CSV (with BOM so Excel reads it correctly). */
    public function csv(): string
    {
        $fh = fopen('php://temp', 'w+');
        fwrite($fh, "\xEF\xBB\xBF");
        foreach ($this->sheets as $i => $s) {
            if ($i > 0) {
                fputcsv($fh, []);
            }
            fputcsv($fh, ['=== ' . $s['name'] . ' ===']);
            foreach ($s['rows'] as $row) {
                $line = [];
                foreach (array_values($row) as $cell) {
                    $v = is_array($cell) ? ($cell[0] ?? '') : $cell;
                    if (is_float($v)) {
                        $v = number_format($v, 2, '.', '');
                    }
                    $line[] = $this->csvSafe((string) $v);
                }
                fputcsv($fh, $line);
            }
        }
        rewind($fh);
        $out = (string) stream_get_contents($fh);
        fclose($fh);
        return $out;
    }

    /** Stop spreadsheet formula injection from customer-entered text. */
    private function csvSafe(string $v): string
    {
        return ($v !== '' && strpbrk($v[0], "=+@\t\r") !== false) ? "'" . $v : $v;
    }

    // ------------------------------------------------------------------ helpers

    private function col(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }
        return $s;
    }

    private function x(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function clean(string $s): string
    {
        return (string) preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $s);
    }

    private function uniqueName(string $name): string
    {
        $name = trim((string) preg_replace('/[\[\]\:\*\?\/\\\\]/', '-', $name));
        $name = mb_substr($name !== '' ? $name : 'Sheet', 0, 31);
        $base = $name;
        $n = 2;
        $taken = array_map(fn($s) => mb_strtolower($s['name']), $this->sheets);
        while (in_array(mb_strtolower($name), $taken, true)) {
            $name = mb_substr($base, 0, 28) . ' ' . $n++;
        }
        return $name;
    }
}
