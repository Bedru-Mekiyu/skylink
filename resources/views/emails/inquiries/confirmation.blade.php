@php
    /** @var \App\Models\Inquiry $inquiry */
    $typeAm = \App\Models\Inquiry::TYPES_AM[$inquiry->type] ?? '';
@endphp
<!DOCTYPE html>
<html lang="am">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $inquiry->reference }}</title>
</head>
<body style="margin:0;padding:24px;background:#f3f4f6;font-family:Arial,'Noto Sans Ethiopic','Nyala',sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;border:1px solid #e5e7eb;">
    <tr>
        <td style="padding:22px 24px 8px;">
            <div style="font-size:18px;font-weight:bold;color:#1e40af;">Sky Link Technologies</div>
        </td>
    </tr>
    <tr>
        <td lang="am" style="padding:8px 24px;font-size:15px;line-height:1.9;">
            ሰላም፣<br>
            {{ $typeAm }} መልዕክትዎ ደርሶናል። ቡድናችን በቅርቡ ያገኝዎታል።<br>
            የመልዕክትዎ መለያ ቁጥር፦ <strong>{{ $inquiry->reference }}</strong>
        </td>
    </tr>
    <tr>
        <td lang="en" style="padding:8px 24px;font-size:15px;line-height:1.7;border-top:1px solid #f3f4f6;">
            Hello,<br>
            we have received your {{ strtolower($inquiry->type_label) }}. Our team will get back to you soon.<br>
            Your reference number: <strong>{{ $inquiry->reference }}</strong>
        </td>
    </tr>
    <tr>
        <td style="padding:14px 24px 22px;font-size:13px;color:#6b7280;line-height:1.6;">
            ለዚህ ኢሜይል በመመለስ ተጨማሪ መረጃ መላክ ይችላሉ። / You can reply to this email to add anything.<br>
            ይህን መልዕክት ካልላኩ፣ ይህን ኢሜይል ችላ ይበሉ። / If you did not send a message, please ignore this email.
        </td>
    </tr>
</table>
</body>
</html>
