@extends('layouts.shop')

@section('content')
    <section class="success-shell">
        <div class="success-icon" aria-hidden="true">✓</div>
        <span class="eyebrow">ORDER RECEIVED</span>
        <h2>Thank you, {{ $order->customer_name }}<span class="heading-period">.</span></h2>
        <p class="success-lede">@if ($order->owner_rejected_at) The shop could not accept this order. Your stock has been returned to availability. @elseif (!$order->owner_confirmed_at) Your phone number is verified. The shop has your order and will confirm it before dispatch. @elseif ($order->courier_booking_status === 'queued' || $order->courier_booking_status === 'booking') The shop confirmed your order. Pathao booking is in progress. @elseif ($order->courier_booking_status === 'booked') Your order is confirmed and booked with Pathao. @else The shop confirmed your order and will arrange delivery shortly. @endif</p>
        <div class="success-card">
            <div class="success-order-number"><span>ORDER NUMBER</span><strong>{{ $order->order_number }}</strong></div>
            @foreach ($order->items as $item)
                <div class="success-item"><span>{{ $item->product_name }}@if ($item->variant_value) — {{ $item->variant_value }}@endif <small>× {{ $item->quantity }}</small></span><strong>৳{{ number_format($item->line_total, 0) }}</strong></div>
            @endforeach
            <div class="success-item"><span>Delivery</span><strong>৳{{ number_format($order->delivery_charge, 0) }}</strong></div>
            <div class="success-item"><span>Discount</span><strong>−৳{{ number_format($order->discount_total, 0) }}</strong></div>
            <div class="success-item"><span>Total</span><strong>৳{{ number_format($order->total, 0) }}</strong></div>
            <div class="success-payment"><span>Payment method</span><strong>Cash on delivery</strong></div>
            @if ($order->courier_tracking_id)
                <div class="success-payment"><span>Pathao tracking ID</span><strong>{{ $order->courier_tracking_id }}</strong></div>
            @endif
        </div>
        @if ($order->user_id && auth('customer')->id() === $order->user_id)
            <a class="button button-green success-button" href="{{ route('account.orders.show', $order) }}">Track this order <span aria-hidden="true">→</span></a>
        @else
            <a class="button button-green success-button" href="{{ route('shop') }}">Back to the collection <span aria-hidden="true">→</span></a>
        @endif
    </section>
@endsection
