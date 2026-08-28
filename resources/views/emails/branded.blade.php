@php
    $recipientName = $recipientName ?? null;
    $bodyParagraphs = preg_split('/\r\n|\r|\n/', trim($body));
    $bodyParagraphs = array_values(array_filter($bodyParagraphs, fn ($line) => $line !== ''));
    $year = date('Y');
    $today = date('l, F j, Y');

    $logoPath = public_path('images/agriAid-logo.png');
    if (!file_exists($logoPath)) {
        $logoPath = public_path('agriAid-logo.png');
    }
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f0f7f0; font-family:Arial, Helvetica, sans-serif; -webkit-font-smoothing:antialiased;">

    {{-- Outer wrapper --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0f7f0; min-width:100%;">
        <tr>
            <td align="center" style="padding:20px 12px;">

                {{-- Main notification card --}}
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" border="0" style="max-width:480px; width:100%; background-color:#ffffff; border-radius:20px; overflow:hidden; box-shadow:0 8px 32px rgba(2,110,0,0.12);">

                    {{-- Header band with logo --}}
                    <tr>
                        <td style="background:linear-gradient(135deg, #026e00 0%, #019800 100%); padding:24px 28px; text-align:center;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="padding-bottom:12px;">
                                        @if($logoBase64)
                                            <img src="{{ $logoBase64 }}" alt="agriAid" style="width:56px; height:56px; object-fit:contain; border-radius:14px; box-shadow:0 4px 12px rgba(0,0,0,0.2);" />
                                        @else
                                            <div style="width:56px; height:56px; background:#ffffff; border-radius:14px; text-align:center; line-height:56px; font-size:32px; font-weight:900; color:#026e00; font-family:Arial, Helvetica, sans-serif;">A</div>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <p style="margin:0; font-size:22px; font-weight:bold; color:#ffffff; font-family:Arial, Helvetica, sans-serif; letter-spacing:-0.5px;">agriAid</p>
                                        <p style="margin:4px 0 0 0; font-size:11px; color:#bfe6b3; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; letter-spacing:2px;">Notification</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Notification icon + title --}}
                    <tr>
                        <td style="padding:28px 28px 0 28px; text-align:center;">
                            <div style="width:48px; height:48px; background:#f0f7f0; border-radius:50%; margin:0 auto 16px; text-align:center; line-height:48px; font-size:24px;">
                                &#128276;
                            </div>
                            <h1 style="margin:0 0 8px 0; font-size:18px; font-weight:bold; color:#026e00; font-family:Arial, Helvetica, sans-serif; line-height:1.4;">{{ $title }}</h1>
                            <p style="margin:0; font-size:11px; color:#999; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; letter-spacing:1px;">{{ $today }}</p>
                        </td>
                    </tr>

                    {{-- Divider --}}
                    <tr>
                        <td style="padding:20px 28px 0 28px;">
                            <div style="height:1px; background-color:#e8e8e8; border-radius:1px;"></div>
                        </td>
                    </tr>

                    {{-- Content body --}}
                    <tr>
                        <td style="padding:20px 28px 8px 28px;">
                            @if ($recipientName)
                                <p style="margin:0 0 14px 0; font-size:14px; color:#666666; font-family:Arial, Helvetica, sans-serif;">Hello <strong style="color:#1a1a1a;">{{ $recipientName }}</strong>,</p>
                            @endif

                            <div style="font-size:14px; color:#333333; font-family:Arial, Helvetica, sans-serif; line-height:1.7;">
                                @foreach ($bodyParagraphs as $paragraph)
                                    <p style="margin:0 0 12px 0;">{{ $paragraph }}</p>
                                @endforeach
                            </div>
                        </td>
                    </tr>

                    {{-- Action button (if provided) --}}
                    @if (isset($actionUrl) && isset($actionLabel))
                        <tr>
                            <td style="padding:8px 28px 24px 28px; text-align:center;">
                                <a href="{{ $actionUrl }}" style="display:inline-block; background-color:#026e00; color:#ffffff; text-decoration:none; font-size:14px; font-weight:bold; font-family:Arial, Helvetica, sans-serif; padding:14px 36px; border-radius:12px; box-shadow:0 4px 12px rgba(2,110,0,0.25);">{{ $actionLabel }}</a>
                            </td>
                        </tr>
                    @endif

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:0 28px;">
                            <div style="height:2px; background:linear-gradient(90deg, #026e00, #00e600, #026e00); border-radius:1px; margin-bottom:16px;"></div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px 24px 28px; text-align:center;">
                            <p style="margin:0 0 4px 0; font-size:12px; color:#026e00; font-family:Arial, Helvetica, sans-serif; font-weight:bold;">agriAid Platform</p>
                            <p style="margin:0 0 4px 0; font-size:11px; color:#888; font-family:Arial, Helvetica, sans-serif;">Empowering Cameroon&rsquo;s Agricultural Future</p>
                            <p style="margin:0; font-size:10px; color:#aaa; font-family:Arial, Helvetica, sans-serif;">&copy; {{ $year }} agriAid. All rights reserved.</p>
                        </td>
                    </tr>

                </table>

                {{-- Footer note --}}
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" border="0" style="max-width:480px; width:100%;">
                    <tr>
                        <td style="padding:12px 0; text-align:center;">
                            <p style="margin:0; font-size:10px; color:#bbb; font-family:Arial, Helvetica, sans-serif;">This is an automated message from agriAid. Please do not reply to this email.</p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>
</html>
