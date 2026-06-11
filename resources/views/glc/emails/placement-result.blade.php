<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Your Placement Test Result</title>
</head>
<body style="margin: 0; padding: 24px; background-color: #f8fafc; font-family: Arial, Helvetica, sans-serif; color: #0f172a;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 32px;">
        <div style="font-size: 14px; font-weight: bold; color: #059669; margin-bottom: 16px;">Greats Language Center</div>

        <h1 style="font-size: 20px; margin: 0 0 16px;">Your Placement Test Result is ready</h1>

        <p style="font-size: 14px; line-height: 1.6;">Dear {{ $candidateName }},</p>

        <p style="font-size: 14px; line-height: 1.6;">
            Thank you for completing the GLC placement test. Your result has been reviewed and approved
            by our academic team. You can view it and download the PDF using the secure link below.
        </p>

        <p style="text-align: center; margin: 28px 0;">
            <a href="{{ $url }}" style="display: inline-block; background-color: #059669; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-size: 14px; font-weight: bold;">
                View my Placement Test Result
            </a>
        </p>

        <p style="font-size: 13px; line-height: 1.6; color: #475569;">
            This link is valid until <strong>{{ $expiresAt }}</strong>. If the link has expired,
            please contact Greats Language Center to request a new one.
        </p>

        <p style="font-size: 13px; line-height: 1.6; color: #475569;">
            If the button does not work, copy and paste this address into your browser:<br>
            <a href="{{ $url }}" style="color: #059669; word-break: break-all;">{{ $url }}</a>
        </p>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;">

        <p style="font-size: 12px; color: #94a3b8; margin: 0;">
            Greats Language Center — Kuala Lumpur<br>
            [Contact details placeholder — pending GLC branding pack]
        </p>
    </div>
</body>
</html>
