<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>agriAid verification code</title>
</head>
<body style="margin:0;padding:0;background-color:#f0f7f0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f0f7f0;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;background-color:#ffffff;border-radius:24px;overflow:hidden;border:1px solid #d8eedc;box-shadow:0 12px 40px rgba(2,110,0,0.08);">

                    {{-- Header / logo --}}
                    <tr>
                        <td style="background:linear-gradient(135deg,#013000 0%,#026e00 55%,#00a86b 100%);padding:28px 32px;text-align:center;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto;">
                                <tr>
                                    <td style="width:48px;height:48px;border-radius:14px;background-color:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.35);text-align:center;vertical-align:middle;font-size:22px;line-height:48px;">
                                        🌿
                                    </td>
                                    <td style="padding-left:12px;text-align:left;">
                                        <div style="font-size:22px;font-weight:800;letter-spacing:-0.02em;color:#ffffff;line-height:1.1;">agriAid</div>
                                        <div style="font-size:11px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:rgba(255,255,255,0.75);margin-top:4px;">
                                            Verifiable credit for producers
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:36px 32px 24px 32px;">
                            <p style="margin:0 0 8px 0;font-size:12px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:#026e00;">
                                Security verification
                            </p>
                            <h1 style="margin:0 0 16px 0;font-size:24px;line-height:1.25;font-weight:800;color:#0a160a;">
                                Hello {{ $name }},
                            </h1>
                            <p style="margin:0 0 24px 0;font-size:15px;line-height:1.6;color:#3d4a3d;">
                                Your verification code to {{ $action }} is below. Enter it in the agriAid app within
                                <strong style="color:#013000;">10 minutes</strong>.
                            </p>

                            {{-- OTP card --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 28px 0;">
                                <tr>
                                    <td align="center" style="background-color:#f0f7f0;border:1px solid #c6e8c8;border-radius:16px;padding:22px 16px;">
                                        <div style="font-size:11px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#026e00;margin-bottom:10px;">
                                            One-time code
                                        </div>
                                        <div style="font-family:Consolas,'Courier New',monospace;font-size:36px;font-weight:700;letter-spacing:0.28em;color:#013000;line-height:1;">
                                            {{ $code }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px 0;font-size:14px;line-height:1.55;color:#5a675a;">
                                If you did not request this code, you can safely ignore this email. Your account stays secure.
                            </p>
                        </td>
                    </tr>

                    {{-- Team / developers --}}
                    <tr>
                        <td style="padding:0 32px 28px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#fafdfa;border:1px solid #e4f0e6;border-radius:14px;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <p style="margin:0 0 8px 0;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#026e00;">
                                            Built by the agriAid team
                                        </p>
                                        <p style="margin:0 0 4px 0;font-size:13px;line-height:1.5;color:#1a2a1a;font-weight:600;">
                                            AGOUFACK ALAPANI CORANTIN JUNIOR
                                        </p>
                                        <p style="margin:0 0 10px 0;font-size:12px;color:#5a675a;">
                                            Core Operations &amp; Scoring Engine
                                        </p>
                                        <p style="margin:0 0 4px 0;font-size:13px;line-height:1.5;color:#1a2a1a;font-weight:600;">
                                            TSEHOULE NGALOCK BLONDEL KEVIN
                                        </p>
                                        <p style="margin:0;font-size:12px;color:#5a675a;">
                                            Financing Ecosystem &amp; Platform Services
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#0a160a;padding:20px 32px;text-align:center;">
                            <p style="margin:0 0 6px 0;font-size:13px;font-weight:700;color:#ffffff;">agriAid</p>
                            <p style="margin:0 0 10px 0;font-size:11px;line-height:1.5;color:rgba(255,255,255,0.65);">
                                Intelligent agricultural visibility &amp; financing for Cameroon producers
                            </p>
                            <p style="margin:0;font-size:10px;color:rgba(255,255,255,0.45);">
                                &copy; {{ date('Y') }} agriAid. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
