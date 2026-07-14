<?php
namespace App\Services;

/**
 * Minimal XLSX writer used to emit downloadable import templates.
 *
 * Builds the smallest valid workbook (one sheet) that Excel and LibreOffice
 * open cleanly. Every cell is written as an inline string, so no shared-string
 * table or number formatting is needed — templates are text prompts, not data.
 */
final class XlsxWriter
{
    /**
     * Stream an .xlsx file (one sheet) to the browser as a download.
     *
     * @param string                        $filename Suggested download name.
     * @param array<int, array<int, string>> $rows     Rows of cell strings; row 0 is typically the header.
     */
    public function download(string $filename, array $rows): void
    {
        $safe = preg_replace('/[^A-Za-z0-9_\-\.]+/', '_', $filename);
        $binary = $this->build($rows);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $safe . '"');
        header('Content-Length: ' . strlen($binary));
        header('Cache-Control: no-store');
        echo $binary;
    }

    /** @param array<int, array<int, string>> $rows */
    public function build(array $rows): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet($rows));

        $zip->close();
        $binary = (string)file_get_contents($tmp);
        @unlink($tmp);
        return $binary;
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Import" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '</Relationships>';
    }

    /** @param array<int, array<int, string>> $rows */
    private function sheet(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach ($rows as $r => $cells) {
            $rowNum = $r + 1;
            $xml .= '<row r="' . $rowNum . '">';
            foreach (array_values($cells) as $c => $value) {
                $ref = $this->colLetter($c) . $rowNum;
                $text = htmlspecialchars((string)$value, ENT_QUOTES | ENT_XML1, 'UTF-8');
                $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . $text . '</t></is></c>';
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    /** Convert a 0-based column index to an Excel column letter (0 => A). */
    private function colLetter(int $index): string
    {
        $letters = '';
        $index++;
        while ($index > 0) {
            $rem = ($index - 1) % 26;
            $letters = chr(65 + $rem) . $letters;
            $index = intdiv($index - 1, 26);
        }
        return $letters;
    }
}
