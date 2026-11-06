@extends('layouts.shop')

@section('content')
    <section class="trust-shell">
        <div class="trust-hero"><span class="eyebrow">A LITTLE MORE PEACE OF MIND</span><h2>Good shopping<br>should feel <em>good.</em></h2><p>We keep ordering simple and the important details clear, from your first click to delivery.</p></div>
        <div class="trust-grid">
            <article class="trust-card"><span class="trust-number">01</span><span class="trust-symbol">৳</span><h3>Pay when it arrives</h3><p>Choose cash on delivery at checkout. You pay when your order reaches you.</p></article>
            <article class="trust-card"><span class="trust-number">02</span><span class="trust-symbol">✳</span><h3>Clear order details</h3><p>See your selected item and price before you place the order.</p></article>
            <article class="trust-card"><span class="trust-number">03</span><span class="trust-symbol">↗</span><h3>We’ll confirm with you</h3><p>We’ll get in touch to confirm your order and delivery details.</p></article>
        </div>
        <div class="policy-note"><span class="eyebrow">OUR RETURN POLICY</span><p>{{ config('brand.return_policy', 'If your item arrives damaged, contact us within 7 days so we can help.') }}</p></div>
        @if (count(config('brand.reviews', [])))
            <div class="reviews-section"><span class="eyebrow">KIND WORDS</span><h2>From our customers</h2><div class="review-grid">@foreach(config('brand.reviews', []) as $review)<article class="review-card"><span>{{ str_repeat('★', min(5, max(0, (int) ($review['rating'] ?? 0)))) }}</span><p>{{ $review['text'] ?? '' }}</p><strong>{{ $review['name'] ?? 'Customer' }}</strong></article>@endforeach</div></div>
        @endif
    </section>
@endsection
