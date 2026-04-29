<?php
// app/Mail/AccountApprovedMail.php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Akun Kamu Disetujui - ' . config('app.name'));
    }

    public function content(): Content
    {
        $appName     = config('app.name');
        $name        = $this->user->name;
        $email       = $this->user->email;
        $loginUrl    = config('app.frontend_url') . '/login';
        $year        = date('Y');

        return new Content(htmlString: <<<HTML
        <!DOCTYPE html>
        <html>
        <body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,sans-serif;">
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr><td align="center" style="padding:40px 16px;">
              <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);max-width:600px;width:100%;">
                <tr><td style="background:#16a34a;padding:32px;text-align:center;">
                  <p style="margin:0;color:#fff;font-size:22px;font-weight:700;">$appName</p>
                  <p style="margin:6px 0 0;color:#bbf7d0;font-size:13px;">Logistics Management System</p>
                </td></tr>
                <tr><td style="padding:40px;">
                  <p style="margin:0 0 16px;color:#111827;font-size:16px;">Hi <strong>$name</strong>,</p>
                  <p style="margin:0 0 24px;color:#6b7280;font-size:15px;line-height:1.6;">
                    🎉 Selamat! Akun kamu telah <strong>disetujui</strong> oleh admin. Kamu sekarang dapat login dan menggunakan aplikasi.
                  </p>
                  <div style="background:#f0fdf4;border:1px solid #bbf7d0;padding:20px;border-radius:8px;margin-bottom:24px;">
                    <p style="margin:0;color:#15803d;font-size:14px;">✅ Status: <strong>Approved</strong></p>
                    <p style="margin:8px 0 0;color:#15803d;font-size:14px;">📧 Email: $email</p>
                  </div>
                  <div style="text-align:center;">
                    <a href="$loginUrl" style="display:inline-block;background:#16a34a;color:#fff;text-decoration:none;padding:14px 40px;border-radius:8px;font-size:15px;font-weight:600;">Login Sekarang</a>
                  </div>
                </td></tr>
                <tr><td style="padding:20px 40px;border-top:1px solid #f1f5f9;text-align:center;">
                  <p style="margin:0;color:#d1d5db;font-size:12px;">© $year $appName. All rights reserved.</p>
                </td></tr>
              </table>
            </td></tr>
          </table>
        </body>
        </html>
        HTML);
    }
}