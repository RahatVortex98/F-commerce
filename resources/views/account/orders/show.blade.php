@extends('layouts.shop')

@section('content')
    @php
        $steps = [
            'pending' => 'Order placed',
            'hold' => 'On hold',
            'confirmed' => 'Confirmed by the store',
            'shipped' => 'On the way',
            'delivered' => 'Delivered',
        ];
        $statusValue = $order->status->value;
        $currentStep = array_search($statusValue, array_keys($steps), true);
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

        @if (in_array($statusValue, ['cancelled', 'returned'], true))
            <div class="account-notice account-notice-muted" role="status">
                This order was {{ $order->status->label() }}. Contact the store if you need help.
            </div>
        @else
            <ol class="order-progress" aria-label="Order progress">
                @foreach ($steps as $stepStatus => $stepLabel)
                    @php($stepIndex = array_search($stepStatus, array_keys($steps), true))
                    <li class="order-progress-step {{ $stepIndex <= $currentStep ? 'is-complete' : '' }}" @if ($stepStatus === $statusValue) aria-current="step" @endif>
                        <span class="order-progress-dot" aria-hidden="true"></span>
                        <span>{{ $stepLabel }}</span>
                    </li>
                @endforeach
            </ol>
        @endif

        <div class="account-detail-grid">
            <article class="account-detail-card">
                <span class="eyebrow">YOUR ITEMS</span>
                <dl class="account-detail-list">
                    @foreach ($order->items as $item)
                        <div>
                            <dt>{{ $item->product_name }}@if ($item->variant_value) · {{ $item->variant_value }}@endif</dt>
                            <dd>{{ $item->quantity }} × ৳{{ number_format($item->unit_price, 2) }}</dd>
                        </div>
                    @endforeach
                    <div><dt>Order total</dt><dd>৳{{ number_format($order->total, 2) }}</dd></div>
                    <div><dt>Payment</dt><dd>Cash on delivery</dd></div>
                    <div><dt>Order status</dt><dd><span class="account-status account-status-{{ $statusValue }}">{{ $order->status->label() }}</span></dd></div>
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

        @if ($order->status === \App\Enums\OrderStatus::Shipped && $order->courier_tracking_id)
            <p class="account-delivery-note">Your order is on its way. Use the tracking ID above for courier updates.</p>
        @elseif ($order->status === \App\Enums\OrderStatus::Shipped)
            <p class="account-delivery-note">Your order is on its way. The store will share tracking details when available.</p>
        @endif

        @if ($order->status === \App\Enums\OrderStatus::Delivered && $order->customer_received_at === null)
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
