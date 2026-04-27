<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User   $user,
        public string $code
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kode Verifikasi Email - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    private function buildHtml(): string
    {
        $appName = config('app.name');
        $name    = $this->user->name;
        $code    = $this->code;
        $year    = date('Y');

        // Pisah digit supaya tampil kotak-kotak
        $digits = implode('', array_map(
            fn($d) => "<span style='display:inline-block;width:48px;height:56px;line-height:56px;text-align:center;font-size:28px;font-weight:700;background:#f1f5f9;border:2px solid #e2e8f0;border-radius:8px;margin:0 4px;color:#1e40af;letter-spacing:0;'>$d</span>",
            str_split($code)
        ));

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
        <body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,sans-serif;">
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr><td align="center" style="padding:40px 16px;">
              <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);max-width:600px;width:100%;">

                <!-- Header -->
                <tr><td style="background:#2563eb;padding:32px;text-align:center;">
                  <p style="margin:0;color:#fff;font-size:22px;font-weight:700;">$appName</p>
                  <p style="margin:6px 0 0;color:#bfdbfe;font-size:13px;">Logistics Management System</p>
                </td></tr>

                <!-- Body -->
                <tr><td style="padding:40px 40px 32px;">
                  <p style="margin:0 0 8px;color:#111827;font-size:16px;">Hi <strong>$name</strong>,</p>
                  <p style="margin:0 0 24px;color:#6b7280;font-size:15px;line-height:1.6;">
                    Gunakan kode di bawah untuk memverifikasi email kamu. Kode berlaku selama <strong>10 menit</strong>.
                  </p>

                  <!-- Code Box -->
                  <div style="text-align:center;margin:32px 0;">
                    $digits
                  </div>

                  <p style="text-align:center;margin:16px 0 0;color:#9ca3af;font-size:13px;">
                    Jangan bagikan kode ini kepada siapapun.
                  </p>

                  <hr style="border:none;border-top:1px solid #e5e7eb;margin:32px 0;">

                  <p style="margin:0;color:#9ca3af;font-size:13px;line-height:1.6;">
                    Jika kamu tidak merasa mendaftar di <strong>$appName</strong>, abaikan email ini.
                  </p>
                </td></tr>

                <!-- Footer -->
                <tr><td style="padding:20px 40px;border-top:1px solid #f1f5f9;text-align:center;">
                  <p style="margin:0;color:#d1d5db;font-size:12px;">© $year $appName. All rights reserved.</p>
                </td></tr>

              </table>
            </td></tr>
          </table>
        </body>
        </html>
        HTML;
    }
}