{!! $inquiry->type_label !!} from {!! $inquiry->name !!} ({!! $inquiry->reference !!})

Name:    {!! $inquiry->name !!}
Email:   {!! $inquiry->email !!}
@if (filled($inquiry->phone))
Phone:   {!! $inquiry->phone !!}
@endif
@if (filled($inquiry->company))
Company: {!! $inquiry->company !!}
@endif
@if (filled($inquiry->service))
Service / Item: {!! $inquiry->service !!}
@endif
@if (filled($inquiry->plan))
Plan:    {!! $inquiry->plan !!}
@endif
@if (filled($inquiry->billing_term))
Billing Term: {!! ucfirst(str_replace('-', ' ', $inquiry->billing_term)) !!}
@endif
@if (filled($inquiry->estimated_price))
Price:   {!! $inquiry->estimated_price !!}
@endif
@if ($inquiry->items && $inquiry->items->isNotEmpty())

Requested Items:
@foreach ($inquiry->items as $item)
- {!! $item->item_name !!} ({!! $item->option_name ?? 'Standard' !!}) | Qty: {!! $item->quantity !!} | Term: {!! $item->billing_term ? ucfirst($item->billing_term) : 'N/A' !!} | Price: {!! $item->is_price_visible && $item->unit_price_amount !== null ? ($item->currency . ' ' . number_format($item->unit_price_amount * $item->quantity, 2)) : 'Quote' !!}
@endforeach
@endif
Page:    {!! $inquiry->source_page !!}
Received: {{ $inquiry->created_at?->copy()->setTimezone(config('news.display_timezone', 'Africa/Addis_Ababa'))->format('M j, Y H:i') }} (Addis Ababa)
@if (filled($inquiry->message))

Message:
{!! $inquiry->message !!}
@endif

--
Reply to this email to answer {!! $inquiry->name !!} directly.
Admin inbox: {{ route('admin.inbox.show', $inquiry) }}
