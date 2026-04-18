<?php
namespace App\Services;

use App\Logging\Logger;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;
use RuntimeException;

/**
 * Thin wrapper around PHPMailer that reads SMTP config from env vars.
 *
 * Env:
 *   SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_ENCRYPTION (tls|ssl|""),
 *   SMTP_FROM_EMAIL, SMTP_FROM_NAME
 *
 * If SMTP_HOST is empty, falls back to PHP's mail() function (via PHPMailer's
 * default). This keeps local dev usable without an SMTP relay.
 */
final class Mailer
{
    /**
     * @param array<int, array{content:string, filename:string, mime:string}> $attachments
     */
    public function send(string $to, string $subject, string $html, string $text = '', array $attachments = [], ?string $toName = null): void
    {
        if ($to === '') {
            throw new RuntimeException('Destinataire manquant');
        }

        $m = new PHPMailer(true);
        try {
            $host = (string)(getenv('SMTP_HOST') ?: '');
            if ($host !== '') {
                $m->isSMTP();
                $m->Host       = $host;
                $m->Port       = (int)(getenv('SMTP_PORT') ?: 587);
                $m->SMTPAuth   = (bool)(getenv('SMTP_USER') ?: false);
                $m->Username   = (string)(getenv('SMTP_USER') ?: '');
                $m->Password   = (string)(getenv('SMTP_PASS') ?: '');
                $enc = strtolower((string)(getenv('SMTP_ENCRYPTION') ?: 'tls'));
                if ($enc === 'ssl') {
                    $m->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } elseif ($enc === 'tls') {
                    $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                }
            }

            $m->CharSet = 'UTF-8';
            $from      = (string)(getenv('SMTP_FROM_EMAIL') ?: 'no-reply@gestionpro.it.com');
            $fromName  = (string)(getenv('SMTP_FROM_NAME')  ?: 'GestionPro');
            $m->setFrom($from, $fromName);
            $m->addAddress($to, $toName ?? '');

            $m->Subject = $subject;
            $m->isHTML(true);
            $m->Body    = $html;
            $m->AltBody = $text !== '' ? $text : strip_tags($html);

            foreach ($attachments as $a) {
                $m->addStringAttachment($a['content'], $a['filename'], PHPMailer::ENCODING_BASE64, $a['mime'] ?? 'application/octet-stream');
            }

            $m->send();
            Logger::info('email_sent', ['to' => $to, 'subject' => $subject, 'via' => $host ?: 'mail()']);
        } catch (MailerException $e) {
            Logger::exception($e, ['op' => 'email_send', 'to' => $to]);
            throw new RuntimeException('Envoi email echoue: ' . $m->ErrorInfo, 0, $e);
        }
    }
}
