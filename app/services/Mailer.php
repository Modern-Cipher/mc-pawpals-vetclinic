<?php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../vendor/autoload.php';

class Mailer {
    private PHPMailer $mail;
    private array $brand = [
        'brand_name'  => 'PawPals',
        'brand_email' => '',
        'brand_logo'  => '',
    ];
    private array $settings = [];

    public function __construct() {
        $pdo = db();
        $cfg = null;
        try {
            $stmt = $pdo->query("SELECT * FROM email_configs WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
            $cfg  = $stmt?->fetch() ?: null;
        } catch (\Throwable $e) {}
        
        try {
            $rs = $pdo->query("SELECT setting_key, setting_value FROM settings");
            foreach ($rs?->fetchAll() ?? [] as $r) $this->settings[$r['setting_key']] = $r['setting_value'];
        } catch (\Throwable $e) {}
        
        if (!$cfg) {
            $cfg = [
                'smtp_host'     => $this->settings['smtp_host']     ?? 'smtp.gmail.com',
                'smtp_port'     => (int)($this->settings['smtp_port'] ?? 587),
                'smtp_secure'   => strtolower($this->settings['smtp_secure'] ?? 'tls'),
                'smtp_user'     => $this->settings['smtp_user']     ?? ($this->settings['contact_email'] ?? ''),
                'smtp_pass_enc' => $this->settings['smtp_pass']     ?? '',
                'from_email'    => $this->settings['smtp_from_email'] ?? ($this->settings['contact_email'] ?? ''),
                'from_name'     => $this->settings['smtp_from_name']  ?? ($this->settings['clinic_name'] ?? 'PawPals'),
            ];
        }
        $this->brand['brand_name']  = $cfg['from_name']  ?? ($this->settings['clinic_name'] ?? 'PawPals');
        $this->brand['brand_email'] = $cfg['from_email'] ?? ($this->settings['contact_email'] ?? '');
        $this->brand['brand_logo']  = $this->makeUrl($this->settings['hero_image_path'] ?? '');
        
        $this->mail = new PHPMailer(true);
        $this->mail->isSMTP();
        $this->mail->Host       = $cfg['smtp_host']   ?? 'smtp.gmail.com';
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = $cfg['smtp_user']   ?? '';
        $this->mail->Password   = $cfg['smtp_pass_enc'] ?? '';
        $this->mail->Port       = (int)($cfg['smtp_port'] ?? 587);
        $secure = strtolower($cfg['smtp_secure'] ?? 'tls');
        $this->mail->SMTPSecure = ($secure === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->CharSet    = 'UTF-8';
        $this->mail->isHTML(true);
        
        $fromEmail = $cfg['from_email'] ?? '';
        $fromName  = $cfg['from_name']  ?? 'PawPals';
        if ($fromEmail) {
            $this->mail->setFrom($fromEmail, $fromName);
            $this->mail->addReplyTo($fromEmail, $fromName);
            $this->mail->Sender = $fromEmail;
        }
        $this->mail->XMailer = 'PawPals Mailer';
    }

    private function makeUrl(?string $p): string {
        if (!$p) return '';
        if (preg_match('~^(https?://|/)~i', $p)) return $p;
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        return "{$protocol}://{$host}{$base}/" . ltrim($p, '/');
    }

    public function send(string $toEmail, string $toName, string $subject, string $html, ?string $alt = null): bool {
        try {
            $this->mail->clearAllRecipients();
            $this->mail->addAddress($toEmail, $toName ?: '');
            $this->mail->Subject = $subject;
            $this->mail->Body    = $this->wrapBrand($html);
            $this->mail->AltBody = $alt ?: strip_tags($html);
            return $this->mail->send();
        } catch (Exception $e) {
            error_log('Mail error: ' . $this->mail->ErrorInfo);
            return false;
        }
    }

    public function sendTemplate(string $slug, string $toEmail, string $toName, array $vars): bool {
        $tpl = $this->getTemplate($slug);
        if ($tpl) {
            $data = array_merge($this->brand, $vars);
            $repl = static function($s, $arr) {
                return preg_replace_callback('/{{\s*([a-z0-9_]+)\s*}}/i', function($m) use ($arr){
                    return htmlspecialchars((string)($arr[$m[1]] ?? ''), ENT_QUOTES, 'UTF-8');
                }, $s);
            };
            $subject = $repl($tpl['subject'], $data);
            $html    = $repl($tpl['html'],    $data);
            $text    = $tpl['text'] ? $repl($tpl['text'], $data) : null;
            return $this->send($toEmail, $toName, $subject, $html, $text);
        }
        return false;
    }

    public function sendOTP(string $toEmail, string $toName, string $code, int $ttlMin = 10): bool {
        $ok = $this->sendTemplate('otp', $toEmail, $toName, [
            'title' => 'Verify your login', 'code' => $code, 'ttl' => $ttlMin,
        ]);
        if ($ok) return true;
        $html = "<h2>Your verification code</h2><p>Use this one-time code within {$ttlMin} minutes:</p>
                 <div style='font-size:28px;font-weight:700;letter-spacing:4px;background:#f8fafc;padding:12px 16px;border-radius:8px;width:max-content'>{$code}</div>";
        return $this->send($toEmail, $toName, 'Your verification code', $html);
    }

    public function sendReset(string $toEmail, string $toName, string $resetUrl, int $ttlMin = 30): bool {
        $ok = $this->sendTemplate('password_reset', $toEmail, $toName, [
            'title' => 'Password reset', 'reset_url' => $resetUrl, 'ttl' => $ttlMin,
        ]);
        if ($ok) return true;
        $html = "<h2>Password reset</h2><p>Click the button (valid for {$ttlMin} minutes):</p>
                 <p><a href='{$resetUrl}' style='display:inline-block;background:#16a34a;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none'>Reset password</a></p>";
        return $this->send($toEmail, $toName, 'Reset your password', $html);
    }

    public function sendEmailVerification(string $toEmail, string $toName, string $verifyUrl, int $ttlMin = 60): bool {
        $ok = $this->sendTemplate('email_verify', $toEmail, $toName, [
            'title' => 'Verify your email',
            'verify_url' => $verifyUrl,
            'ttl' => $ttlMin,
        ]);
        if ($ok) return true;
        $html = "<h2>Verify your email</h2><p>Click the link (valid for {$ttlMin} minutes):</p>
                 <p><a href='{$verifyUrl}' style='display:inline-block;background:#16a34a;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none'>Verify my email</a></p>";
        return $this->send($toEmail, $toName, 'Verify your email', $html);
    }

    public function sendStaffWelcome(string $toEmail, string $toName, string $username, string $tempPassword, string $loginUrl): bool {
        $ok = $this->sendTemplate('staff_welcome', $toEmail, $toName, [
            'first_name'    => trim(explode(' ', $toName)[0] ?? $toName),
            'username'      => $username,
            'temp_password' => $tempPassword,
            'login_url'     => $loginUrl,
        ]);
        if ($ok) return true;
        $html = "<h2>Welcome to {$this->brand['brand_name']}</h2>
                 <p>Your staff account is ready.</p>
                 <p><b>Username:</b> {$username}<br>
                 <b>Temporary password:</b> {$tempPassword}</p>
                 <p><a href='{$loginUrl}' style='background:#10b981;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;display:inline-block'>Open Dashboard</a></p>
                 <p style='color:#6b7280'>For security, you will be asked to change your password after logging in.</p>";
        return $this->send($toEmail, $toName, 'Your PawPals staff account', $html);
    }

    public function sendOwnerPasswordReset(string $toEmail, string $toName, string $username, string $tempPassword, string $loginUrl): bool {
        $ok = $this->sendTemplate('owner_password_reset', $toEmail, $toName, [
            'first_name'    => trim(explode(' ', $toName)[0] ?? $toName),
            'username'      => $username,
            'temp_password' => $tempPassword,
            'login_url'     => $loginUrl,
        ]);
        if ($ok) return true;
        $html = "<h2>Password Reset</h2>
                 <p>Hi " . htmlspecialchars($toName) . ", your password has been reset by an administrator.</p>
                 <p><b>Username:</b> {$username}<br>
                 <b>Temporary password:</b> {$tempPassword}</p>
                 <p><a href='{$loginUrl}' style='background:#10b981;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;display:inline-block'>Login Now</a></p>
                 <p style='color:#6b7280'>You will be asked to change this password after logging in.</p>";
        return $this->send($toEmail, $toName, 'Your PawPals Temporary Password', $html);
    }
    
    public function sendAppointmentConfirmation(array $data): bool {
        $dt = new \DateTime($data['appointment_datetime']);
        $vars = [
            'pet_owner_name' => $data['owner_name'],
            'pet_name' => $data['pet_name'],
            'service' => $data['service'],
            'appointment_datetime' => $dt->format('F j, Y \a\t g:i A'),
            'assigned_vet_name' => $data['vet_name'],
            'clinic_name' => $this->settings['clinic_name'] ?? 'PawPals',
            'clinic_address' => $this->getFullAddress(),
            'clinic_phone' => $this->settings['contact_phone'] ?? '',
            'clinic_email' => $this->settings['contact_email'] ?? '',
        ];
        return $this->sendTemplate('appt_confirmed', $data['owner_email'], $data['owner_name'], $vars);
    }

    public function sendAppointmentStatusUpdate(string $slug, array $data): bool {
        $dt = new \DateTime($data['appointment_datetime']);
        $vars = [
            'pet_owner_name' => $data['owner_name'],
            'pet_name' => $data['pet_name'],
            'appointment_datetime' => $dt->format('F j, Y \a\t g:i A'),
            'reason' => $data['reason'] ?? 'No reason provided.',
        ];
        return $this->sendTemplate($slug, $data['owner_email'], $data['owner_name'], $vars);
    }

    private function getFullAddress(): string {
        $parts = [
            $this->settings['contact_houseno'] ?? '',
            $this->settings['contact_street'] ?? '',
            $this->settings['contact_barangay'] ?? '',
            $this->settings['contact_municipality'] ?? '',
            $this->settings['contact_province'] ?? '',
            $this->settings['contact_zipcode'] ?? '',
        ];
        return trim(implode(', ', array_filter($parts)));
    }

    private function getTemplate(string $slug): ?array {
        try {
            $pdo = db();
            $st = $pdo->prepare("SELECT * FROM email_templates WHERE slug=? AND is_active=1 LIMIT 1");
            $st->execute([$slug]);
            return $st->fetch() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function wrapBrand(string $inner): string {
        $name = htmlspecialchars($this->brand['brand_name'] ?? 'PawPals', ENT_QUOTES, 'UTF-8');
        $year = date('Y');
        
        $logoHtml = '<div style="font-size: 24px; width: 40px; height: 40px; line-height: 40px; text-align: center; background-color: #E0F2F1; border-radius: 8px; color: #00796B;">🐾</div>';
        $headerBackgroundColor = '#20B2AA';

        return <<<HTML
<!doctype html><html><body style="margin:0;padding:0;background:#f6f9fc;font-family:Arial,sans-serif">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f6f9fc;padding:24px 0">
    <tr><td align="center">
      <table role="presentation" width="580" cellspacing="0" cellpadding="0" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden">
        <tr><td style="padding:20px 24px; background-color: {$headerBackgroundColor};">
          <div style="display:flex;align-items:center;gap:12px">{$logoHtml}<strong style="font-size:18px;color:#ffffff;">{$name}</strong></div>
        </td></tr>
        <tr><td style="padding:24px; font-size: 16px; color: #333;">{$inner}</td></tr>
        <tr><td style="padding:16px 24px;background:#f8fafc;color:#6b7280;font-size:12px;text-align:center;border-top:1px solid #e2e8f0;">
          &copy; {$year} {$name}. All rights reserved.
        </td></tr>
      </table>
    </td></tr>
  </table>
</body></html>
HTML;
    }
}