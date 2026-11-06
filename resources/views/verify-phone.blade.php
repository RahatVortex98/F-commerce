@extends('layouts.shop')

@section('content')
    <section class="success-shell verify-shell">
        <div class="success-icon" aria-hidden="true">✳</div>
        <span class="eyebrow">QUICK PHONE CHECK</span>
        <h2>Verify your number<span class="heading-period">.</span></h2>
        <p class="success-lede">Enter the six-digit code sent to {{ $order->phone }}. It expires in 10 minutes.</p>

        @if (session('status'))
            <p class="form-success" role="status">{{ session('status') }}</p>
        @endif
        @if (session('local_verification_code'))
            <p class="form-success" role="status">Demo code: <strong>{{ session('local_verification_code') }}</strong></p>
        @endif
        @if ($errors->any())
            <div class="form-errors" role="alert"><strong>We couldn’t verify that yet.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form class="checkout-form verify-form" method="POST" action="{{ route('order.verify.store', $order->public_token) }}">
            @csrf
            <label for="code">Verification code<input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="000000" required autofocus></label>
            <button class="button button-green checkout-submit" type="submit">Verify & confirm order <span aria-hidden="true">→</span></button>
        </form>
        <form method="POST" action="{{ route('order.verify.resend', $order->public_token) }}" class="verify-resend">@csrf<button class="back-link" type="submit">Send a new code</button></form>
        <p class="checkout-pay-note"><span>✳</span> Your COD order is not sent to the courier until this number is verified.</p>
    </section>
@endsection
