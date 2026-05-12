<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendRecoveryCode extends Mailable
{
    use Queueable, SerializesModels;

    public  $recovery_code;

    public function __construct($recovery_code)
    {
        $this->recovery_code = $recovery_code;
    }

    public function build()
    {

        $emailContent = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Locinder - Your Local Food Finder Buddy</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: rgb(233, 233, 233); border-radius: 15px;">
                <tr>
                    <td align="center" style="padding: 40px 20px;">
                        <!-- Container with white background -->
                        <table width="100%" max-width="600" cellpadding="20" cellspacing="0" style="background: linear-gradient(135deg, #5c3a21 0%, #d67a39 100%); border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                            <tr>
                                <td>
                                    <div style="font-family: \'Poppins\', Arial, sans-serif; font-size: 14px; color: rgb(255, 249, 244);">
                                        <div style="text-align: center; margin-bottom: 20px;">
                                            <h1 style="color: #ffe9c1; margin: 0;">Locinder</h1>
                                            <p style="color: #d3bb8e; margin: 5px 0 0 0;">Your Local Food Finder Buddy</p>
                                        </div>

                                        <p style="color: #d3bb8e ; margin-top: 20px;"><strong>Recovery Code</strong></p>
                                        <h3 style="color: #ffe9c1; margin: 3px 0;">' . htmlspecialchars($this->recovery_code) . '</h3>

                                        <p style="color: #ffe9c1; font-size: 12px; font-style: italic; margin-top: 30px;">
                                            Note: This is a system-generated email. Please do not reply!
                                        </p>

                                        <div style="border-top: 1px solid #eee; margin-top: 30px; padding-top: 20px; text-align: center; color: #ddddddff; font-size: 12px;">
                                            <p style="margin: 0;">© ' . date('Y') . ' Locinder. All rights reserved.</p>
                                            <p style="margin: 5px 0;">Sagay City 6122 Negros Island, Philippines</p>
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
            ->subject('Locinder Change Password');
    }
}
