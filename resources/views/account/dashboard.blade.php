@extends('layouts.shop')

@section('content')
    <section class="content-shell account-shell">
        <header class="account-heading">
            <span class="eyebrow">YOUR ACCOUNT</span>
            <h2>Good to see you, {{ auth()->user()->name }}<span class="heading-period">.</span></h2>
            <p>Keep an eye on your orders from confirmation all the way to delivery.</p>
        </header>

        <div class="account-summary" aria-label="Order summary">
            <div class="account-summary-card"><span>All orders</span><strong>{{ $orderCount }}</strong></div>
            <div class="account-summary-card"><span>On the way or in progress</span><strong>{{ $activeOrderCount }}</strong></div>
        </div>

        <div class="account-section-heading">
            <div>
                <span class="eyebrow">ORDER HISTORY</span>
                <h3>Your orders</h3>
            </div>
            <a class="button button-green account-shop-link" href="{{ route('shop') }}">Browse products <span aria-hidden="true">→</span></a>
        </div>

        @if (session('status'))
            <div class="account-notice" role="status">{{ session('status') }}</div>
        @endif

        <div class="account-order-list">
            @forelse ($orders as $order)
                <a class="account-order-card" href="{{ route('account.orders.show', $order) }}">
                    <div class="account-order-topline">
                        <span class="account-order-number">{{ $order->order_number }}</span>
                        <span class="account-status account-status-{{ $order->status }}">{{ str($order->status)->headline() }}</span>
                    </div>
                    <div class="account-order-content">
                        <div>
                            <h4>{{ $order->product->name }}@if ($order->variant_value) <span>· {{ $order->variant_value }}</span>@endif</h4>
                            <p>Quantity {{ $order->quantity }} · Placed {{ $order->created_at->format('M j, Y') }}</p>
                        </div>
                        <strong>৳{{ number_format($order->total, 2) }}</strong>
                    </div>
                    <span class="account-order-link">View delivery updates <span aria-hidden="true">→</span></span>
                </a>
            @empty
                <div class="account-empty">
                    <span class="eyebrow">NOTHING HERE YET</span>
                    <h4>Your first order is waiting.</h4>
                    <p>Choose something you love and we’ll keep its delivery progress here.</p>
                    <a class="button button-green" href="{{ route('shop') }}">Explore the collection <span aria-hidden="true">→</span></a>
                </div>
            @endforelse
        </div>

        {{ $orders->links() }}
    </section>
@endsection
