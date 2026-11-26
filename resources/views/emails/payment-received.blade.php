<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Payment received</title></head>
<body style="font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #222;">
    <h2 style="color: #2b4c9d;">Sky Link Technologies</h2>
    <p>We received the payment for order <strong>{{ $order->order_number }}</strong>.</p>
    <table cellpadding="6" style="border-collapse: collapse;">
        @foreach ($order->items as $item)
            <tr><td>{{ $item->title }} ({{ $item->option_name }}, {{ $item->billing_term }}) × {{ $item->quantity }}</td>
                <td align="right">{{ $item->currency }} {{ number_format((float) $item->line_total, 2) }}</td></tr>
        @endforeach
        <tr><td><strong>Total paid</strong></td><td align="right"><strong>{{ $order->currency }} {{ number_format((float) $order->total, 2) }}</strong></td></tr>
    </table>
    <p>This message confirms the payment. It is not a tax invoice.</p>
</body>
</html>
