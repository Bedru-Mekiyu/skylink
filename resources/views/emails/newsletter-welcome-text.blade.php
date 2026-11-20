@php $am = ($language ?? 'en') === 'am'; @endphp
Sky Link Technologies

@if ($am)
እናመሰግናለን፣
ወደ ስካይሊንክ ጋዜጣ ተመዝግበዋል። አዳዲስ ዜናዎችን፣ ፈጠራዎችንና ዝግጅቶችን እናደርስዎታለን።

ይህን ካልጠየቁ ይህን ኢሜይል ችላ ይበሉ፤ ወይም ከዝርዝሩ ለመውጣት፦ {{ $unsubscribeUrl }}
@else
Thank you,
you are now on the Sky Link newsletter. We will send you our latest news, innovations and events.

If you did not ask for this, ignore this email or unsubscribe: {{ $unsubscribeUrl }}
@endif
