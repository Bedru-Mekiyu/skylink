<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Subscription Renewal Notice</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 24px; background-color: #f8fafc;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 32px; border: 1px solid #e2e8f0;">
        <div style="border-bottom: 2px solid #1a6fb0; padding-bottom: 16px; margin-bottom: 24px;">
            <h2 style="color: #1a6fb0; margin: 0; font-size: 20px;">Sky Link Technologies</h2>
            <div style="color: #64748b; font-size: 13px;">Subscription Service & Renewal Notice</div>
        </div>

        <p>Dear {{ $order->customer_name }},</p>

        <p>This is a friendly reminder that your subscription for <strong>Order #{{ $order->order_number }}</strong> is due for renewal on <strong>{{ $dueDate?->format('F d, Y') ?? 'the end of the current period' }}</strong>.</p>

        <div style="background-color: #f1f5f9; border-radius: 8px; padding: 16px; margin: 20px 0;">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <tr>
                    <td style="padding: 6px 0; color: #64748b;">Billing Cycle:</td>
                    <td style="padding: 6px 0; font-weight: bold; text-align: right; text-transform: capitalize;">{{ $order->billing_term }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #64748b;">Amount:</td>
                    <td style="padding: 6px 0; font-weight: bold; text-align: right; color: #1a6fb0;">{{ $order->currency }} {{ number_format($order->total, 2) }}</td>
                </tr>
                @if($dueDate)
                <tr>
                    <td style="padding: 6px 0; color: #64748b;">Due Date:</td>
                    <td style="padding: 6px 0; font-weight: bold; text-align: right; color: #b45309;">{{ $dueDate->format('M d, Y') }}</td>
                </tr>
                @endif
            </table>
        </div>

        <p style="text-align: center; margin: 24px 0;">
            <a href="{{ $payUrl }}" style="background: #2b4c9d; color: #ffffff; padding: 12px 28px; border-radius: 50px; text-decoration: none; font-weight: bold;">Review and pay this renewal</a>
        </p>

        <p style="font-size: 14px; color: #475569;">Nothing is charged automatically. You pay each period yourself from this link.</p>

        <p style="font-size: 14px; color: #475569;">If you have any questions or require an invoice update, please reply directly to this email or reach us at <a href="mailto:info@skylinkict.com" style="color: #1a6fb0;">info@skylinkict.com</a>.</p>

        <div style="margin-top: 32px; padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; text-align: center;">
            Sky Link Technologies PLC &bull; Addis Ababa, Ethiopia &bull; <a href="https://skylinkict.com" style="color: #64748b; text-decoration: none;">skylinkict.com</a>
        </div>
    </div>
</body>
</html>
