@php $am = ($language ?? 'en') === 'am'; @endphp
<!DOCTYPE html>
<html lang="{{ $am ? 'am' : 'en' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sky Link Technologies</title>
</head>
<body style="margin:0;padding:24px;background:#f3f4f6;font-family:Arial,'Noto Sans Ethiopic','Nyala',sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;border:1px solid #e5e7eb;">
    <tr>
        <td style="padding:22px 24px 8px;">
            <div style="font-size:18px;font-weight:bold;color:#1e40af;">Sky Link Technologies</div>
        </td>
    </tr>
    <tr>
        <td lang="{{ $am ? 'am' : 'en' }}" style="padding:8px 24px;font-size:15px;line-height:1.8;">
            @if ($am)
                እናመሰግናለን፣<br>
                ወደ ስካይሊንክ ጋዜጣ ተመዝግበዋል። አዳዲስ ዜናዎችን፣ ፈጠራዎችንና ዝግጅቶችን እናደርስዎታለን።
            @else
                Thank you,<br>
                you are now on the Sky Link newsletter. We will send you our latest news, innovations and events.
            @endif
        </td>
    </tr>
    <tr>
        <td lang="{{ $am ? 'am' : 'en' }}" style="padding:14px 24px 22px;font-size:13px;color:#6b7280;line-height:1.7;">
            @if ($am)
                ይህን ካልጠየቁ ይህን ኢሜይል ችላ ይበሉ፤ ወይም <a href="{{ $unsubscribeUrl }}" style="color:#6b7280;">ከዝርዝሩ ይውጡ</a>።
            @else
                If you did not ask for this, ignore this email or <a href="{{ $unsubscribeUrl }}" style="color:#6b7280;">unsubscribe</a>.
            @endif
        </td>
    </tr>
</table>
</body>
</html>
