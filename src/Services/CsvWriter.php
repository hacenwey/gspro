<?php
namespace App\Services;

/**
 * Streams a CSV export directly to the browser.
 *
 * Writes a UTF-8 BOM first so Excel on Windows opens the file correctly.
 * Uses php://output so large exports don't buffer into memory.
 */
final class CsvWriter
{
    /**
     * @param string[] $headers
     * @param iterable<int, array<int, mixed>> $rows
     */
    public function stream(string $filename, array $headers, iterable $rows): void
    {
        $safe = preg_replace('/[^A-Za-z0-9_\-\.]+/', '_', $filename);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $safe . '"');
        header('Cache-Control: no-store');

        $fh = fopen('php://output', 'w');
        fwrite($fh, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
        fputcsv($fh, $headers, ';');
        foreach ($rows as $r) {
            fputcsv($fh, $r, ';');
        }
        fclose($fh);
    }
}
