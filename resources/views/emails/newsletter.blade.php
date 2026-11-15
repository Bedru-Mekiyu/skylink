<h1>Latest News</h1>
<p>{{ $content }}</p>
@if (! empty($unsubscribeUrl))
    <p style="font-size:12px;color:#6b7280;margin-top:32px;">
        You receive this email because you subscribed on our website.
        <a href="{{ $unsubscribeUrl }}" style="color:#6b7280;">Unsubscribe</a>
    </p>
@endif
