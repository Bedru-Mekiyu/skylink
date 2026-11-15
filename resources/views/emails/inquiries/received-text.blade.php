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
Service: {!! $inquiry->service !!}
@endif
@if (filled($inquiry->plan))
Plan:    {!! $inquiry->plan !!}
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
