@php
    $recipientName = $recipientName ?? null;
    $bodyParagraphs = preg_split('/\r\n|\r|\n/', trim($body));
    $bodyParagraphs = array_values(array_filter($bodyParagraphs, fn ($line) => $line !== ''));
    $year = date('Y');

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

    {{-- Outer wrapper table --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0f7f0; min-width:100%;">
        <tr>
            <td align="center" style="padding:24px 12px;">

                {{-- Main container --}}
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(2,110,0,0.10);">

                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#026e00; padding:28px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="vertical-align:middle; width:56px;">
                                        @if($logoBase64)
                                            <img src="{{ $logoBase64 }}" alt="agriAid" style="width:48px; height:48px; object-fit:contain; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.15);" />
                                        @else
                                            <div style="width:48px; height:48px; background:linear-gradient(135deg, #ffffff 0%, #e8f5e9 100%); border-radius:14px; text-align:center; line-height:48px; font-size:28px; font-weight:900; color:#026e00; font-family:Arial, Helvetica, sans-serif; box-shadow:0 2px 8px rgba(0,0,0,0.15);">A</div>
                                        @endif
                                    </td>
                                    <td style="vertical-align:middle; padding-left:16px;">
                                        <p style="margin:0; font-size:24px; font-weight:bold; color:#ffffff; font-family:Arial, Helvetica, sans-serif; letter-spacing:-0.5px;">agriAid</p>
                                        <p style="margin:4px 0 0 0; font-size:13px; color:#bfe6b3; font-family:Arial, Helvetica, sans-serif;">Empowering Cameroon&rsquo;s Agricultural Future</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="padding:32px;">
                            @if ($recipientName)
                                <p style="margin:0 0 16px 0; font-size:15px; color:#666666; font-family:Arial, Helvetica, sans-serif;">Hello {{ $recipientName }},</p>
                            @endif

                            <h1 style="margin:0 0 20px 0; font-size:22px; font-weight:bold; color:#026e00; font-family:Arial, Helvetica, sans-serif; line-height:1.3;">{{ $title }}</h1>

                            <div style="font-size:15px; color:#1a1a1a; font-family:Arial, Helvetica, sans-serif; line-height:1.6;">
                                @foreach ($bodyParagraphs as $paragraph)
                                    <p style="margin:0 0 14px 0;">{{ $paragraph }}</p>
                                @endforeach
                            </div>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:0 32px 8px 32px;">
                            <div style="height:3px; background-color:#026e00; border-radius:2px; margin-bottom:20px;"></div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 28px 32px;">
                            <p style="margin:0 0 6px 0; font-size:13px; color:#666666; font-family:Arial, Helvetica, sans-serif;">This message was sent by the agriAid Platform</p>
                            <p style="margin:0 0 12px 0; font-size:13px; color:#026e00; font-family:Arial, Helvetica, sans-serif; font-weight:bold;">Empowering Cameroon&rsquo;s Agricultural Future</p>
                            <p style="margin:0; font-size:12px; color:#999999; font-family:Arial, Helvetica, sans-serif;">&copy; {{ $year }} agriAid. All rights reserved.</p>
                        </td>
                    </tr>

                </table>

                {{-- Spacer below container --}}
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%;">
                    <tr>
                        <td style="padding:16px 0; text-align:center;">
                            <p style="margin:0; font-size:11px; color:#999999; font-family:Arial, Helvetica, sans-serif;">This is an automated message, please do not reply.</p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>
</html>
