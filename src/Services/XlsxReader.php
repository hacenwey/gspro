<?php
namespace App\Services;

/**
 * Lightweight XLSX reader (no external dependency).
 *
 * An .xlsx file is a ZIP archive of XML parts. This reader opens the first
 * worksheet, resolves shared strings, and returns the sheet as a list of rows,
 * where each row is a 0-indexed array of cell values (strings). Empty cells are
 * filled so column indexes stay aligned across rows.
 *
 * Only what the product/category import needs is supported: shared strings,
 * inline strings, and numeric/plain values. Formatting, dates as serials, and
 * multi-sheet selection beyond "first sheet" are intentionally out of scope.
 */
final class XlsxReader
{
    /**
     * @return array<int, array<int, string>> Rows of cell values.
     * @throws \RuntimeException if the file cannot be opened or parsed.
     */
    public function read(string $path): array
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException("L'extension PHP 'zip' est requise pour lire les fichiers Excel.");
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException("Fichier Excel illisible ou corrompu.");
        }

        try {
            $shared    = $this->readSharedStrings($zip);
            $sheetPath = $this->firstSheetPath($zip);
            $xml       = $this->loadXml($zip, $sheetPath);

            $rows = [];
            foreach ($xml->sheetData->row as $row) {
                $cells = [];
                $maxCol = -1;
                foreach ($row->c as $c) {
                    $ref  = (string)($c['r'] ?? '');
                    $col  = $ref !== '' ? $this->colIndex($ref) : count($cells);
                    $cells[$col] = $this->cellValue($c, $shared);
                    if ($col > $maxCol) { $maxCol = $col; }
                }
                // Normalise to a dense 0..maxCol array.
                $dense = [];
                for ($i = 0; $i <= $maxCol; $i++) {
                    $dense[$i] = $cells[$i] ?? '';
                }
                $rows[] = $dense;
            }
            return $rows;
        } finally {
            $zip->close();
        }
    }

    /** @return array<int, string> */
    private function readSharedStrings(\ZipArchive $zip): array
    {
        $data = $zip->getFromName('xl/sharedStrings.xml');
        if ($data === false) {
            return [];
        }
        $xml = simplexml_load_string($data);
        if ($xml === false) {
            return [];
        }
        $strings = [];
        foreach ($xml->si as $si) {
            $strings[] = $this->siText($si);
        }
        return $strings;
    }

    /** Concatenate text from a shared-string <si>, including rich-text runs. */
    private function siText(\SimpleXMLElement $si): string
    {
        if (isset($si->t)) {
            return (string)$si->t;
        }
        $text = '';
        foreach ($si->r as $run) {
            $text .= (string)$run->t;
        }
        return $text;
    }

    /** Locate the first worksheet's XML path via the workbook relationships. */
    private function firstSheetPath(\ZipArchive $zip): string
    {
        $wb = $this->loadXml($zip, 'xl/workbook.xml');
        $rid = '';
        foreach ($wb->sheets->sheet as $sheet) {
            $attrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $rid = (string)($attrs['id'] ?? '');
            break;
        }

        if ($rid !== '') {
            $rels = $this->loadXml($zip, 'xl/_rels/workbook.xml.rels');
            foreach ($rels->Relationship as $rel) {
                if ((string)$rel['Id'] === $rid) {
                    $target = ltrim((string)$rel['Target'], '/');
                    return str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
                }
            }
        }
        // Fallback to the conventional path.
        return 'xl/worksheets/sheet1.xml';
    }

    private function loadXml(\ZipArchive $zip, string $name): \SimpleXMLElement
    {
        $data = $zip->getFromName($name);
        if ($data === false) {
            throw new \RuntimeException("Partie manquante dans le fichier Excel: $name");
        }
        $xml = simplexml_load_string($data);
        if ($xml === false) {
            throw new \RuntimeException("XML invalide dans: $name");
        }
        return $xml;
    }

    /** Resolve a single cell to its text value. */
    private function cellValue(\SimpleXMLElement $c, array $shared): string
    {
        $type = (string)($c['t'] ?? '');
        if ($type === 's') {
            $idx = (int)$c->v;
            return $shared[$idx] ?? '';
        }
        if ($type === 'inlineStr') {
            return isset($c->is) ? $this->siText($c->is) : '';
        }
        // "str" (formula result) or numeric/boolean.
        return isset($c->v) ? (string)$c->v : '';
    }

    /** Convert an Excel cell ref (e.g. "AB12") to a 0-based column index. */
    private function colIndex(string $ref): int
    {
        if (!preg_match('/^([A-Z]+)/', $ref, $m)) {
            return 0;
        }
        $letters = $m[1];
        $n = 0;
        for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
            $n = $n * 26 + (ord($letters[$i]) - 64);
        }
        return $n - 1;
    }
}
