@extends('layouts.shop')

@section('content')
    @php
        $steps = [
            'pending' => 'Order placed',
            'confirmed' => 'Confirmed by the store',
            'shipped' => 'On the way',
            'delivered' => 'Delivered',
        ];
        $currentStep = array_search($order->status, array_keys($steps), true);
    @endphp

    <section class="content-shell account-shell">
        <a class="back-link" href="{{ route('account.dashboard') }}">← Back to your account</a>

        <header class="account-heading account-order-heading">
            <span class="eyebrow">ORDER {{ $order->order_number }}</span>
            <h2>Delivery updates<span class="heading-period">.</span></h2>
            <p>Placed {{ $order->created_at->format('M j, Y · g:i A') }}</p>
        </header>

        @if (session('status'))
            <div class="account-notice" role="status">{{ session('status') }}</div>
        @endif

        @if (in_array($order->status, ['cancelled', 'returned'], true))
            <div class="account-notice account-notice-muted" role="status">
                This order was {{ $order->status }}. Contact the store if you need help.
            </div>
        @else
            <ol class="order-progress" aria-label="Order progress">
                @foreach ($steps as $stepStatus => $stepLabel)
                    @php($stepIndex = array_search($stepStatus, array_keys($steps), true))
                    <li class="order-progress-step {{ $stepIndex <= $currentStep ? 'is-complete' : '' }}" @if ($stepStatus === $order->status) aria-current="step" @endif>
                        <span class="order-progress-dot" aria-hidden="true"></span>
                        <span>{{ $stepLabel }}</span>
                    </li>
                @endforeach
            </ol>
        @endif

        <div class="account-detail-grid">
            <article class="account-detail-card">
                <span class="eyebrow">YOUR ITEM</span>
                <h3>{{ $order->product->name }}@if ($order->variant_value) <span>· {{ $order->variant_value }}</span>@endif</h3>
                <dl class="account-detail-list">
                    <div><dt>Quantity</dt><dd>{{ $order->quantity }}</dd></div>
                    <div><dt>Order total</dt><dd>৳{{ number_format($order->total, 2) }}</dd></div>
                    <div><dt>Payment</dt><dd>Cash on delivery</dd></div>
                    <div><dt>Order status</dt><dd><span class="account-status account-status-{{ $order->status }}">{{ str($order->status)->headline() }}</span></dd></div>
                </dl>
            </article>

            <article class="account-detail-card">
                <span class="eyebrow">DELIVERY DETAILS</span>
                <h3>{{ $order->customer_name }}</h3>
                <dl class="account-detail-list">
                    <div><dt>Phone</dt><dd>{{ $order->phone }}</dd></div>
                    <div><dt>Address</dt><dd>{{ $order->address }}</dd></div>
                    @if ($order->courier_tracking_id)
                        <div><dt>{{ $order->courier_name ?: 'Courier' }} tracking ID</dt><dd>{{ $order->courier_tracking_id }}</dd></div>
                    @endif
                </dl>
            </article>
        </div>

        @if ($order->status === 'shipped' && $order->courier_tracking_id)
            <p class="account-delivery-note">Your order is on its way. Use the tracking ID above for courier updates.</p>
        @elseif ($order->status === 'shipped')
            <p class="account-delivery-note">Your order is on its way. The store will share tracking details when available.</p>
        @endif

        @if ($order->status === 'delivered' && $order->customer_received_at === null)
            <form class="account-received-form" method="POST" action="{{ route('account.orders.received', $order) }}">
                @csrf
                <div>
                    <strong>Have you received this order?</strong>
                    <p>Confirm only after the package has reached you.</p>
                </div>
                <button class="button button-green" type="submit">Confirm received <span aria-hidden="true">✓</span></button>
            </form>
        @elseif ($order->customer_received_at)
            <div class="account-notice" role="status">You confirmed receipt on {{ $order->customer_received_at->format('M j, Y') }}. Thank you!</div>
        @endif
    </section>
@endsection
