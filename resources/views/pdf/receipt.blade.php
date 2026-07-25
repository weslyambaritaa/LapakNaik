<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Courier New', monospace; font-size: 12px; color: #1a1a1a; margin: 0; padding: 16px; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .muted { color: #666; font-size: 10px; }
        .row { display: flex; justify-content: space-between; }
        table { width: 100%; border-collapse: collapse; }
        .divider { border-top: 1px dashed #999; margin: 10px 0; }
        .item-name { padding-top: 4px; }
        .item-detail { font-size: 10px; color: #666; }
        .total-row td { padding-top: 6px; font-weight: bold; font-size: 14px; }
    </style>
</head>
<body>
    <div class="center">
        <div class="bold" style="font-size: 14px;">{{ $business->name }}</div>
        @if ($business->address)
            <div class="muted">{{ $business->address }}</div>
        @endif
        @if ($business->phone)
            <div class="muted">{{ $business->phone }}</div>
        @endif
    </div>

    <div class="divider"></div>

    <table>
        <tr><td>Invoice</td><td style="text-align: right;">{{ $transaction->invoice_number }}</td></tr>
        <tr><td>Waktu</td><td style="text-align: right;">{{ $transaction->created_at->translatedFormat('d M Y H:i') }}</td></tr>
        @if ($transaction->cashier)
            <tr><td>Kasir</td><td style="text-align: right;">{{ $transaction->cashier->name }}</td></tr>
        @endif
        @if ($transaction->customer)
            <tr><td>Pelanggan</td><td style="text-align: right;">{{ $transaction->customer->name }}</td></tr>
        @endif
    </table>

    <div class="divider"></div>

    <table>
        @foreach ($transaction->items as $item)
            <tr>
                <td class="item-name" colspan="2">{{ $item->product->name }}</td>
            </tr>
            <tr class="item-detail">
                <td>{{ $item->quantity }} x Rp{{ number_format($item->price, 0, ',', '.') }}</td>
                <td style="text-align: right;">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    <table>
        <tr><td>Subtotal</td><td style="text-align: right;">Rp{{ number_format($transaction->subtotal, 0, ',', '.') }}</td></tr>
        @if ($transaction->discount > 0)
            <tr><td>Diskon</td><td style="text-align: right;">-Rp{{ number_format($transaction->discount, 0, ',', '.') }}</td></tr>
        @endif
        <tr class="total-row"><td>Total</td><td style="text-align: right;">Rp{{ number_format($transaction->total, 0, ',', '.') }}</td></tr>
        <tr class="item-detail"><td>Pembayaran</td><td style="text-align: right;">{{ ['cash' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer'][$transaction->payment?->method] ?? '-' }}</td></tr>
    </table>

    <div class="divider"></div>

    <div class="center muted">Terima kasih telah berbelanja!</div>
</body>
</html>
