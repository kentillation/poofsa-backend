<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WebsiteSendingEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $websiteMessage;

    public function __construct($websiteMessage)
    {
        $this->websiteMessage = $websiteMessage;
    }

    public function build()
    {
        $emailContent = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Poofsa Software Development Services</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #e9e9e9ff; border-radius: 15px;">
                <tr>
                    <td align="center" style="padding: 40px 20px;">
                        <!-- Container with white background -->
                        <table width="100%" max-width="600" cellpadding="20" cellspacing="0" style="background: linear-gradient(135deg, #005e6e 0%, #31bcd4 100%); border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                            <tr>
                                <td>
                                    <div style="font-family: \'Poppins\', Arial, sans-serif; font-size: 14px; color: #fff4f4ff;">
                                        <!-- Logo or Header -->
                                        <div style="text-align: center; margin-bottom: 20px;">
                                            <h1 style="color: #c1e8ffff; margin: 0;">Poofsa</h1>
                                            <p style="color: #c1e8ffff; margin: 5px 0 0 0;">Software Development Services</p>
                                        </div>

                                        <h3 style="color: #c1e8ffff; margin-top: 20px;">Good day!</h3>
                                        <p style="line-height: 1.6; margin: 10px 0;">
                                            Thank you for reaching us!<br />
                                            We will get back immediately after reading your message.
                                        </p>

                                        <!-- Message Details -->
                                        <div style="background-color: #c1e8ffff; padding: 15px; border-left: 4px solid #2700b6ff; margin: 20px 0;">
                                            <p style="color: #00495cff ; margin: 5px 0;"><strong>Name:</strong> ' . htmlspecialchars($this->websiteMessage->full_name) . '</p>
                                            <p style="color: #00495cff ; margin: 5px 0;"><strong>Email:</strong> ' . htmlspecialchars($this->websiteMessage->email) . '</p>
                                            <p style="color: #00495cff ; margin: 5px 0;"><strong>Subject:</strong> ' . htmlspecialchars($this->websiteMessage->subject) . '</p>
                                            <p style="color: #00495cff ; margin: 5px 0;"><strong>Message:</strong><br>' . nl2br(htmlspecialchars($this->websiteMessage->message)) . '</p>
                                        </div>

                                        <p style="color: #c1e8ffff; font-size: 12px; font-style: italic; margin-top: 30px;">
                                            Note: This is a system-generated email. Please do not reply!
                                        </p>

                                        <!-- Footer -->
                                        <div style="border-top: 1px solid #eee; margin-top: 30px; padding-top: 20px; text-align: center; color: #ddddddff; font-size: 12px;">
                                            <p style="margin: 0;">© ' . date('Y') . ' Poofsa. All rights reserved.</p>
                                            <p style="margin: 5px 0;">Sagay City, Philippines</p>
                                            <p style="background-color: #f6fdffff; padding: 5px; margin: 5px 0;">support@poofsa.com</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';

        return $this->html($emailContent)
            ->subject('Poofsa - Message Successfully Sent');
    }
}
