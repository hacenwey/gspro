<?php
namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Renders the invoices/pdf.php template to a real PDF via dompdf.
 *
 * The template stays plain PHP/HTML (still usable as a printable HTML preview).
 * This service only controls output format and HTTP disposition.
 */
final class InvoicePdfService
{
    /**
     * @param array $invoice  row from invoices + joined customer
     * @param array $items    invoice_items rows
     * @param array $settings key=>value settings map for the tenant
     */
    public function renderHtml(array $invoice, array $items, array $settings): string
    {
        ob_start();
        // Variables expected by the template
        $invoice = $invoice;
        $items = $items;
        $settings = $settings;
        require APP_ROOT . '/views/invoices/pdf.php';
        return (string)ob_get_clean();
    }

    public function stream(array $invoice, array $items, array $settings, bool $attachment = false): void
    {
        $opts = new Options();
        $opts->set('isHtml5ParserEnabled', true);
        $opts->set('isRemoteEnabled', true);
        $opts->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($opts);
        $dompdf->loadHtml($this->renderHtml($invoice, $items, $settings), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = $this->filename($invoice);
        $dompdf->stream($filename, ['Attachment' => $attachment ? 1 : 0]);
    }

    public function toBinary(array $invoice, array $items, array $settings): string
    {
        $opts = new Options();
        $opts->set('isHtml5ParserEnabled', true);
        $opts->set('isRemoteEnabled', true);
        $opts->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($opts);
        $dompdf->loadHtml($this->renderHtml($invoice, $items, $settings), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return (string)$dompdf->output();
    }

    public function filename(array $invoice): string
    {
        $safe = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $invoice['number'] ?? 'document');
        return $safe . '.pdf';
    }
}
