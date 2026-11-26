@php
    /** @var \App\Models\Inquiry $inquiry */
    $tz = config('news.display_timezone', 'Africa/Addis_Ababa');
    $rows = array_filter([
        'Type' => $inquiry->type_label,
        'Name' => $inquiry->name,
        'Email' => $inquiry->email,
        'Phone' => $inquiry->phone,
        'Company' => $inquiry->company,
        'Service / Item' => $inquiry->service,
        'Item Type' => $inquiry->item_type ? ucfirst($inquiry->item_type) : null,
        'Plan' => $inquiry->plan,
        'Billing Term' => $inquiry->billing_term ? ucfirst(str_replace('-', ' ', $inquiry->billing_term)) : null,
        'Estimated Price' => $inquiry->estimated_price,
        'Sent from page' => $inquiry->source_page,
        'Received' => $inquiry->created_at?->copy()->setTimezone($tz)->format('M j, Y H:i').' (Addis Ababa)',
    ], fn ($v) => filled($v));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $inquiry->type_label }} {{ $inquiry->reference }}</title>
</head>
<body style="margin:0;padding:24px;background:#f3f4f6;font-family:Arial,'Noto Sans Ethiopic','Nyala',sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:10px;border:1px solid #e5e7eb;">
    <tr>
        <td style="padding:20px 24px;border-bottom:1px solid #e5e7eb;">
            <div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">New website message · {{ $inquiry->reference }}</div>
            <h1 style="margin:6px 0 0;font-size:20px;color:#2b4c9d;">{{ $inquiry->type_label }} from {{ $inquiry->name }}</h1>
        </td>
    </tr>
    <tr>
        <td style="padding:16px 24px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                @foreach ($rows as $label => $value)
                    <tr>
                        <td style="padding:6px 12px 6px 0;color:#6b7280;white-space:nowrap;vertical-align:top;width:140px;">{{ $label }}</td>
                        <td style="padding:6px 0;vertical-align:top;">
                            @if ($label === 'Email')
                                <a href="mailto:{{ $value }}" style="color:#2b4c9d;">{{ $value }}</a>
                            @elseif ($label === 'Phone')
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $value) }}" style="color:#2b4c9d;">{{ $value }}</a>
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
    @if ($inquiry->items && $inquiry->items->isNotEmpty())
        <tr>
            <td style="padding:0 24px 20px;">
                <div style="font-size:12px;color:#6b7280;margin-bottom:6px;text-transform:uppercase;font-weight:bold;">Requested Items ({{ $inquiry->items->count() }})</div>
                <table role="presentation" width="100%" cellpadding="6" cellspacing="0" style="font-size:13px;border-collapse:collapse;border:1px solid #e5e7eb;">
                    <tr style="background:#f9fafb;">
                        <th align="left" style="border:1px solid #e5e7eb;">Item</th>
                        <th align="left" style="border:1px solid #e5e7eb;">Option</th>
                        <th align="left" style="border:1px solid #e5e7eb;">Term</th>
                        <th align="center" style="border:1px solid #e5e7eb;">Qty</th>
                        <th align="right" style="border:1px solid #e5e7eb;">Price</th>
                    </tr>
                    @foreach ($inquiry->items as $item)
                        <tr>
                            <td style="border:1px solid #e5e7eb;"><strong>{{ $item->item_name }}</strong></td>
                            <td style="border:1px solid #e5e7eb;">{{ $item->option_name ?? '—' }}</td>
                            <td style="border:1px solid #e5e7eb;">{{ $item->billing_term ? ucfirst($item->billing_term) : '—' }}</td>
                            <td align="center" style="border:1px solid #e5e7eb;">{{ $item->quantity }}</td>
                            <td align="right" style="border:1px solid #e5e7eb;">
                                @if ($item->is_price_visible && $item->unit_price_amount !== null)
                                    {{ $item->currency }} {{ number_format($item->unit_price_amount * $item->quantity, 2) }}
                                @else
                                    Quote
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    @endif
    @if (filled($inquiry->message))
        <tr>
            <td style="padding:0 24px 20px;">
                <div style="font-size:12px;color:#6b7280;margin-bottom:6px;">Message</div>
                <div lang="{{ $inquiry->language }}" style="font-size:15px;line-height:1.7;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px 16px;white-space:pre-wrap;">{{ $inquiry->message }}</div>
            </td>
        </tr>
    @endif
    <tr>
        <td style="padding:14px 24px;border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280;">
            Reply to this email to answer {{ $inquiry->name }} directly.
            Also in the admin inbox: <a href="{{ route('admin.inbox.show', $inquiry) }}" style="color:#2b4c9d;">{{ $inquiry->reference }}</a>
        </td>
    </tr>
</table>
</body>
</html>
