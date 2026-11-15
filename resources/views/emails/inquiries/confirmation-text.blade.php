Sky Link Technologies

ሰላም፣
{{ \App\Models\Inquiry::TYPES_AM[$inquiry->type] ?? '' }} መልዕክትዎ ደርሶናል። ቡድናችን በቅርቡ ያገኝዎታል።
የመልዕክትዎ መለያ ቁጥር፦ {{ $inquiry->reference }}

Hello,
we have received your {{ strtolower($inquiry->type_label) }}. Our team will get back to you soon.
Your reference number: {{ $inquiry->reference }}

ለዚህ ኢሜይል በመመለስ ተጨማሪ መረጃ መላክ ይችላሉ። / You can reply to this email to add anything.
ይህን መልዕክት ካልላኩ፣ ይህን ኢሜይል ችላ ይበሉ። / If you did not send a message, please ignore this email.
