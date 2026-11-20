@php $am = $inquiry->language === 'am'; @endphp
Sky Link Technologies

@if ($am)
እናመሰግናለን፣
{{ \App\Models\Inquiry::TYPES_AM[$inquiry->type] ?? '' }} መልዕክትዎ ደርሶናል። ቡድናችን በቅርቡ ያገኝዎታል።
የመልዕክትዎ መለያ ቁጥር፦ {{ $inquiry->reference }}

ለዚህ ኢሜይል በመመለስ ተጨማሪ መረጃ መላክ ይችላሉ።
ይህን መልዕክት ካልላኩ፣ ይህን ኢሜይል ችላ ይበሉ።
@else
Thank you,
we have received your {{ strtolower($inquiry->type_label) }}. Our team will get back to you soon.
Your reference number: {{ $inquiry->reference }}

You can reply to this email to add anything.
If you did not send a message, please ignore this email.
@endif
