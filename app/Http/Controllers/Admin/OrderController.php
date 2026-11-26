<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Order;
use App\Services\PaymentProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = Order::with(['items', 'latestPayment'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_email', 'like', "%{$term}%")
                    ->orWhere('customer_phone', 'like', "%{$term}%");
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        // Metrics calculations
        $totalOrdersCount = Order::count();
        $paidOrdersCount = Order::where('status', 'paid')->count();
        $paidRevenue = (float) Order::where('status', 'paid')->sum('total');
        $pendingOrdersCount = Order::where('status', 'pending')->count();
        $failedOrdersCount = Order::where('status', 'failed')->count();

        return view('admin.orders.index', compact(
            'orders',
            'totalOrdersCount',
            'paidOrdersCount',
            'paidRevenue',
            'pendingOrdersCount',
            'failedOrdersCount'
        ));
    }

    public function show(Order $order): View
    {
        $order->load(['items', 'payments']);
        return view('admin.orders.show', compact('order'));
    }

    /**
     * Manual staff re-verification with Chapa API
     */
    public function reverify(Order $order, PaymentProcessor $payments): RedirectResponse
    {
        $payment = $order->latestPayment;
        if (!$payment) {
            return back()->with('error', 'No transaction reference found for this order.');
        }

        // Same checks as the callback and webhook: status, reference, amount and currency.
        $outcome = $payments->confirm($payment);
        AdminAuditLog::record('order.manual_reverify', 'Order', $order->id, [
            'order_number' => $order->order_number, 'tx_ref' => $payment->tx_ref, 'result' => $outcome,
        ]);

        return $outcome === 'paid'
            ? back()->with('success', 'Chapa confirms this payment. The order is marked paid.')
            : back()->with('error', "Not marked paid. Chapa check result: {$outcome}.");
    }

    public function renewals(Request $request): View
    {
        $days = (int) $request->input('days', 14);
        $renewals = Order::with(['items', 'latestPayment'])
            ->renewalsDue($days)
            ->paginate(20)
            ->withQueryString();

        $dueWithinCount = Order::renewalsDue(14)->count();
        $dueOverdueCount = Order::where('status', 'paid')
            ->whereNotNull('renewal_due_date')
            ->where('renewal_due_date', '<', now()->toDateString())
            ->count();

        return view('admin.orders.renewals', compact('renewals', 'days', 'dueWithinCount', 'dueOverdueCount'));
    }

    /**
     * Export filtered orders to CSV
     */
    public function export(Request $request): StreamedResponse
    {
        $query = Order::with('items')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $filename = 'orders-' . date('Ymd-His') . '.csv';

        AdminAuditLog::record('orders.export_csv', 'Order', null, [
            'filter_status' => $request->input('status'),
        ]);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Order Number', 'Date', 'Customer Name', 'Email', 'Phone', 'Company', 'Total (ETB)', 'Status', 'Payment Method', 'Items Count']);

            $query->chunk(100, function ($orders) use ($handle) {
                foreach ($orders as $o) {
                    fputcsv($handle, \App\Support\Csv::row([
                        $o->order_number,
                        $o->created_at->format('Y-m-d H:i:s'),
                        $o->customer_name,
                        $o->customer_email,
                        $o->customer_phone,
                        $o->company ?? '',
                        number_format((float) $o->total, 2, '.', ''),
                        $o->status,
                        $o->payment_method,
                        $o->items->count(),
                    ]));
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
