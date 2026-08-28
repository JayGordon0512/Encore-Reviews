<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Share your review</title>
</head>
<body style="margin:0;background:#f4f1eb;color:#17202a;font-family:Arial,sans-serif;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f1eb;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:16px;overflow:hidden;">
          <tr>
            <td style="padding:30px 34px;background:#12263a;color:#ffffff;">
              <div style="font-size:24px;font-weight:700;">Encore Reviews</div>
              <div style="margin-top:6px;color:#dce6ef;">Audience reviews for live events</div>
            </td>
          </tr>
          <tr>
            <td style="padding:34px;">
              <p style="margin:0 0 18px;">Hello {{ $displayName }},</p>
              <h1 style="margin:0 0 18px;font-size:28px;line-height:1.25;">How was {{ $showTitle }}?</h1>
              <p style="margin:0 0 24px;line-height:1.6;">Your verified attendance means you can share an audience review. Your review will be checked before it appears publicly.</p>
              <p style="margin:0 0 26px;">
                <a href="{{ $reviewUrl }}" style="display:inline-block;padding:14px 22px;border-radius:10px;background:#e6572c;color:#ffffff;text-decoration:none;font-weight:700;">Share your review</a>
              </p>
              <p style="margin:0 0 12px;line-height:1.6;color:#5f6b76;">This personal link expires on {{ $expiresAt->format('j F Y \a\t H:i T') }} and can be used once.</p>
              <p style="margin:0;line-height:1.6;color:#5f6b76;">If you did not expect this email, you can ignore it.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
