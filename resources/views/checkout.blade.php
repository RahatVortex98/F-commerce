@extends('layouts.shop')

@section('content')
    @php
        $productPhoto = $product->image ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->image) : null;
    @endphp
    <section class="checkout-shell">
        <a class="back-link" href="{{ route('product.show', $product) }}">← Back to product</a>
        <div class="checkout-layout">
            <div class="checkout-main">
                <span class="eyebrow">JUST A FEW DETAILS</span>
                <h2>Make it yours<span class="heading-period">.</span></h2>
                <p class="checkout-intro">We’ll confirm your order before it heads your way.</p>
                <p class="checkout-intro">We’ll text a one-time code to verify your number before dispatch.</p>
                <p class="checkout-intro">Ordering as {{ auth('customer')->user()->name }}.</p>

                @if ($errors->any())
                    <div class="form-errors" role="alert"><strong>Please check your details.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                <form class="checkout-form" method="POST" action="{{ route('checkout.store', $product) }}">
                    @csrf
                    <input type="hidden" name="checkout_token" value="{{ old('checkout_token', (string) \Illuminate\Support\Str::uuid()) }}">
                    <div class="honeypot" aria-hidden="true"><label for="website">Leave this field empty<input id="website" name="website" type="text" tabindex="-1" autocomplete="off"></label></div>
                    <label for="phone">Phone number<input id="phone" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" placeholder="01XXXXXXXXX" required></label>
                    @if (config('courierhub.couriers.pathao.enabled'))
                        <label for="city_id">City<select id="city_id" name="city_id" required><option value="">Choose city</option></select></label>
                        <label for="zone_id">Zone<select id="zone_id" name="zone_id" required disabled><option value="">Choose zone</option></select></label>
                        <label for="area_id">Area<select id="area_id" name="area_id" required disabled><option value="">Choose area</option></select></label>
                    @endif
                    <label for="address">Delivery address<textarea id="address" name="address" rows="3" autocomplete="street-address" placeholder="House, road, area and city" required>{{ old('address') }}</textarea></label>
                    @if ($product->variants)
                        @php($variantLabel = $product->product_kind === 'watch' ? 'Color' : (in_array($product->product_kind, ['shirt', 'pant'], true) ? 'Size' : 'Option'))
                        <label for="variant_value">{{ $variantLabel }}<select id="variant_value" name="variant_value" required><option value="">Choose {{ strtolower($variantLabel) }}</option>@foreach ($product->variants as $variant)@php($variantInventory = $product->productVariants->firstWhere('value', $variant['value'] ?? ''))@php($availableStock = $variantInventory?->stock ?? $product->stock)<option value="{{ $variant['value'] }}" @selected(old('variant_value') === ($variant['value'] ?? null))>{{ $variant['value'] }} — {{ $availableStock === null ? 'stock not tracked' : number_format($availableStock).' available'.($variantInventory?->stock === null && $product->stock !== null ? ' across options' : '') }}</option>@endforeach</select></label>
                        <p class="checkout-pay-note">Availability is checked when you place your order.</p>
                    @endif
                    <label for="quantity">Quantity you want to buy<input id="quantity" type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" step="1" inputmode="numeric" required><small>Stock is reserved when your order is placed.</small></label>
                    <button class="button button-green checkout-submit" type="submit">Place my order <span aria-hidden="true">→</span></button>
                    <p class="checkout-pay-note"><span>✳</span> Cash on delivery — pay when it arrives.</p>
                </form>
            </div>
            <aside class="order-summary">
                <span class="eyebrow">YOUR PICK</span>
                <div class="summary-product">
                    <div class="summary-art">@if ($productPhoto)<img src="{{ $productPhoto }}" alt="">@else<span class="product-image-placeholder" aria-hidden="true">{{ $product->category?->name ?? $product->name }}</span>@endif</div>
                    <div><h3>{{ $product->name }}</h3><p>{{ $product->category?->name ?? 'The everyday edit' }}</p></div>
                </div>
                @if ($product->hasActiveDiscount())
                    <div class="summary-line"><span>Price</span><strong><s>৳{{ number_format($product->price, 0) }}</s> <span class="sale-summary-price">৳{{ number_format($product->sale_price, 0) }}</span></strong></div>
                @else
                    <div class="summary-line"><span>Price</span><strong>৳{{ number_format($product->price, 0) }}</strong></div>
                @endif
                <div class="summary-line"><span>Quantity</span><strong>As selected</strong></div>
                <div class="summary-total"><span>Payment</span><strong>Cash on delivery</strong></div>
                <p class="summary-footnote">Your total is based on the quantity you choose above.</p>
            </aside>
        </div>
    </section>
    @if (config('courierhub.couriers.pathao.enabled'))
        <script src="{{ asset('js/pathao-locations.js') }}" defer></script>
    @endif
@endsection
