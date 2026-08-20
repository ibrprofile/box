<?php
declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

final class Mailer
{
    public static function send(string $to, string $toName, string $subject, string $htmlBody, string $altBody = ''): bool
    {
        $cfg = config('mail', []);
        $fromAddr = $cfg['from']['address'] ?? 'noreply@kayford.ru';
        $fromName = $cfg['from']['name'] ?? 'Кайфорд';
        $altBody = $altBody !== '' ? $altBody : strip_tags($htmlBody);

        if (!empty($cfg['username']) && !empty($cfg['password'])) {
            try {
                return self::sendSmtp($to, $toName, $subject, $htmlBody, $altBody, $cfg, $fromAddr, $fromName);
            } catch (\Throwable $e) {
                error_log('[Mailer] SMTP failed: ' . $e->getMessage());
            }
        }

        return self::sendNative($to, $toName, $subject, $htmlBody, $altBody, $fromAddr, $fromName);
    }

    /** @throws Exception */
    private static function sendSmtp(string $to, string $toName, string $subject, string $html, string $alt, array $cfg, string $from, string $fromName): bool
    {
        $mail = new PHPMailer(true);
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->isSMTP();
        $mail->Host       = (string) ($cfg['host'] ?? '');
        $mail->SMTPAuth   = true;
        $mail->Username   = (string) ($cfg['username'] ?? '');
        $mail->Password   = (string) ($cfg['password'] ?? '');
        $enc              = strtolower((string) ($cfg['encryption'] ?? 'ssl'));
        if ($enc === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($enc === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->Port       = (int) ($cfg['port'] ?? 465);
        $mail->setFrom($from, $fromName);
        if ($toName !== '') {
            $mail->addAddress($to, $toName);
        } else {
            $mail->addAddress($to);
        }
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = $alt;
        return $mail->send();
    }

    private static function sendNative(string $to, string $toName, string $subject, string $html, string $alt, string $from, string $fromName): bool
    {
        $boundary = 'Kayford_' . bin2hex(random_bytes(8));
        $toEncoded = $toName !== '' ? "=?UTF-8?B?" . base64_encode($toName) . "?= <{$to}>" : $to;
        $fromEncoded = "=?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>";
        $subjectEncoded = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $headers  = "From: {$fromEncoded}\r\n";
        $headers .= "Reply-To: {$fromEncoded}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $alt . "\r\n\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $html . "\r\n\r\n";
        $body .= "--{$boundary}--";

        return @mail($toEncoded, $subjectEncoded, $body, $headers);
    }

    public static function emailCodeHtml(string $code, string $purpose = 'register', string $name = ''): string
    {
        $title = $purpose === 'login' ? 'Вход в аккаунт' : 'Подтверждение регистрации';
        $hint  = $purpose === 'login' ? 'Код для входа в Kayford. Никому не сообщайте его.' : 'Код для завершения регистрации в Kayford.';
        $nameHtml = $name !== '' ? '<p style="margin:0 0 24px;color:#0f172a;font-size:15px;line-height:1.5">Здравствуйте, ' . htmlspecialchars($name) . '!</p>' : '';

        return <<<HTML
<!DOCTYPE html>
<html><body style="margin:0;padding:24px;background:#f5f6fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Inter',sans-serif;color:#0f172a">
<div style="max-width:480px;margin:0 auto;background:#ffffff;border-radius:16px;padding:32px;box-shadow:0 1px 2px rgba(15,23,42,.06)">
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:24px">
    <div style="width:36px;height:36px;border-radius:10px;background:#6c4cf1;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:18px">K</div>
    <span style="font-size:18px;font-weight:700;color:#0f172a">Kayford</span>
  </div>
  <h1 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#0f172a">{$title}</h1>
  {$nameHtml}
  <p style="margin:0 0 24px;color:#475569;font-size:14px;line-height:1.5">{$hint}</p>
  <div style="background:#f5f6fa;border-radius:12px;padding:20px;text-align:center;margin-bottom:24px">
    <div style="font-size:34px;letter-spacing:10px;font-weight:800;color:#6c4cf1;font-family:ui-monospace,Menlo,monospace">{$code}</div>
  </div>
  <p style="margin:0 0 8px;color:#64748b;font-size:13px;line-height:1.5">Код действует 15 минут.</p>
  <p style="margin:0;color:#64748b;font-size:13px;line-height:1.5">Если вы не запрашивали это письмо — просто проигнорируйте его.</p>
  <hr style="border:none;border-top:1px solid #e2e8f0;margin:28px 0 16px">
  <p style="margin:0;color:#94a3b8;font-size:12px;line-height:1.5">© Кайфорд. Подготовка к ЕГЭ.</p>
</div>
</body></html>
HTML;
    }
}
