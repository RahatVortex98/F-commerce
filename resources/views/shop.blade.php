@extends('layouts.shop')

@section('content')
    @php
        $promotions = collect($storeSettings?->promotions ?? []);

        if ($promotions->isEmpty()) {
            $promotions = $products->take(3)->map(function ($product) {
                $image = $product->image
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->image)
                    : null;

                return [
                    'eyebrow' => $product->category?->name ?? 'NEW ARRIVAL',
                    'title' => $product->name,
                    'description' => $product->description ?: 'A new favourite from our collection.',
                    'button_text' => 'Shop this product',
                    'button_link' => route('product.show', $product),
                    'image' => $image,
                    'image_alt' => $product->name,
                    'image_fit' => 'cover',
                    'show_content' => true,
                    'is_custom_banner' => false,
                ];
            });
        } else {
            $promotions = $promotions->map(function (array $promotion): array {
                $promotion['image'] = \Illuminate\Support\Facades\Storage::disk('public')->url($promotion['image']);
                $promotion['is_custom_banner'] = true;
                $promotion['show_content'] = (bool) ($promotion['show_content'] ?? false);

                return $promotion;
            });
        }
        $hasCustomPromotions = $storeSettings?->promotions && count($storeSettings->promotions) > 0;

        $productsByCategoryId = $products->groupBy('category_id');
        $activeCategoryIds = $categories->modelKeys();
        $categorySections = $categories->map(fn ($category) => [
            'id' => $category->id,
            'slug' => \Illuminate\Support\Str::slug($category->name).'-'.$category->id,
            'name' => $category->name,
            'products' => $productsByCategoryId->get($category->id, collect()),
        ]);
        $otherProducts = $products->reject(fn ($product) => in_array($product->category_id, $activeCategoryIds));
        if ($otherProducts->isNotEmpty()) {
            $categorySections->push([
                'id' => 'other',
                'slug' => 'other',
                'name' => 'Other',
                'products' => $otherProducts,
            ]);
        }
        $contactPhone = $storeSettings?->phone ?: config('storefront.phone');
        $contactWhatsapp = $storeSettings?->whatsapp ?: config('storefront.whatsapp');
        $ownerName = $storeSettings?->owner_name ?: config('storefront.owner_name');
        $whatsappNumber = preg_replace('/\D+/', '', $contactWhatsapp ?? '');
        if (str_starts_with($whatsappNumber, '0')) {
            $whatsappNumber = '88'.substr($whatsappNumber, 1);
        }
    @endphp

    <section class="hero-carousel {{ $hasCustomPromotions ? 'hero-carousel-banners' : '' }}" aria-label="Store updates and featured products" data-carousel>
        @forelse ($promotions as $slide)
            @php
                $buttonLink = $slide['button_link'] ?? '';
                $safeButtonLink = \Illuminate\Support\Str::startsWith($buttonLink, ['https://', 'http://', '/', '#']) ? $buttonLink : '';
            @endphp
            <article class="hero-slide {{ $loop->first ? 'is-active' : '' }} {{ !empty($slide['is_custom_banner']) ? 'hero-slide-banner' : '' }} {{ !empty($slide['is_custom_banner']) && !empty($slide['show_content']) ? 'hero-slide-banner-copy' : '' }}" data-slide aria-hidden="{{ $loop->first ? 'false' : 'true' }}">
                @if (!empty($slide['image']))
                    <img class="hero-photo" src="{{ $slide['image'] }}" alt="{{ $slide['image_alt'] ?? $slide['title'] }}" @if (!$loop->first) loading="lazy" @endif>
                @else
                    <div class="hero-no-photo" aria-hidden="true"><span>{{ $slide['eyebrow'] ?? 'NEW ARRIVAL' }}</span></div>
                @endif
                @if (!empty($slide['show_content']))
                    <div class="hero-copy">
                        @if (!empty($slide['eyebrow']))<span class="eyebrow hero-eyebrow"><span class="eyebrow-dot"></span>{{ $slide['eyebrow'] }}</span>@endif
                        <h2>{{ $slide['title'] ?? 'A good find for you.' }}</h2>
                        @if (!empty($slide['description']))<p>{{ \Illuminate\Support\Str::limit($slide['description'], 240) }}</p>@endif
                        @if ($safeButtonLink && !empty($slide['button_text']))
                            <a class="button button-light" href="{{ $safeButtonLink }}">{{ $slide['button_text'] }} <span aria-hidden="true">→</span></a>
                        @endif
                    </div>
                @endif
            </article>
        @empty
            <div class="hero-empty"><div class="hero-empty-copy"><span class="eyebrow hero-eyebrow">WELCOME TO {{ strtoupper($storeSettings?->store_name ?? 'OUR STORE') }}</span><h2>Good things<br>for <em>every day.</em></h2><p>New arrivals and special offers will appear here.</p><a class="button button-light" href="#collection">All products <span aria-hidden="true">→</span></a></div></div>
        @endforelse
        @if ($promotions->count() > 1)
            <div class="carousel-controls" aria-label="Store banner slides">
                <button class="carousel-arrow" type="button" data-previous aria-label="Previous slide">←</button>
                <div class="carousel-dots">
                    @foreach ($promotions as $slide)
                        <button class="carousel-dot {{ $loop->first ? 'is-active' : '' }}" type="button" data-slide-to="{{ $loop->index }}" aria-label="Show slide {{ $loop->iteration }}" aria-current="{{ $loop->first ? 'true' : 'false' }}"></button>
                    @endforeach
                </div>
                <button class="carousel-arrow" type="button" data-next aria-label="Next slide">→</button>
            </div>
        @endif
    </section>

    <section class="promise-strip" aria-label="Shopping highlights">
        @foreach ($storeServiceHighlights as $highlight)
            <div class="promise-item">
                <span class="promise-icon" aria-hidden="true">
                    @switch($highlight['icon'] ?? 'spark')
                        @case('cash')<svg viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h3"/><circle cx="17" cy="14" r="2"/></svg>@break
                        @case('delivery')<svg viewBox="0 0 24 24"><path d="M2.5 6h12v11h-12zM14.5 10h4l3 3.5V17h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>@break
                        @case('heart')<svg viewBox="0 0 24 24"><path d="M20.5 8.8c0 5-8.5 10.2-8.5 10.2S3.5 13.8 3.5 8.8a4.4 4.4 0 0 1 8.5-1.5 4.4 4.4 0 0 1 8.5 1.5Z"/></svg>@break
                        @case('shield')<svg viewBox="0 0 24 24"><path d="M12 3 20 6v5.5c0 5-3.4 8-8 9.5-4.6-1.5-8-4.5-8-9.5V6z"/><path d="m8.5 12 2.2 2.2 4.8-4.8"/></svg>@break
                        @case('package')<svg viewBox="0 0 24 24"><path d="m12 3 9 4.5v9L12 21l-9-4.5v-9zM3 7.5l9 4.7 9-4.7M12 12.2V21M7.5 5.2l9 4.7"/></svg>@break
                        @case('support')<svg viewBox="0 0 24 24"><path d="M4 13v-2a8 8 0 0 1 16 0v2M4 13H2.8v4H6v-4zm16 0h1.2v4H18v-4zm-2 6a7 7 0 0 1-6 2h-1"/></svg>@break
                        @default<svg viewBox="0 0 24 24"><path d="m12 2 2.7 6.8L22 12l-7.3 3.2L12 22l-2.7-6.8L2 12l7.3-3.2z"/></svg>
                    @endswitch
                </span>
                <span>{{ $highlight['label'] ?? '' }}</span>
            </div>
            @if (!$loop->last)<span class="promise-divider" aria-hidden="true"></span>@endif
        @endforeach
    </section>

    <section class="collection" id="collection" aria-labelledby="collection-title">
        <div class="section-heading">
            <h2 id="collection-title">All Products<span class="heading-period">.</span></h2>
            <span class="product-count">{{ str_pad((string) $products->count(), 2, '0', STR_PAD_LEFT) }} PRODUCTS</span>
        </div>

        @if ($categorySections->isNotEmpty())
            <nav class="category-pills" id="category-menu" aria-label="Shop by category">
                @foreach ($categorySections as $categorySection)
                    <a href="#category-{{ $categorySection['slug'] }}">{{ $categorySection['name'] }} <span>{{ str_pad((string) $categorySection['products']->count(), 2, '0', STR_PAD_LEFT) }}</span></a>
                @endforeach
            </nav>
        @endif

        @if ($categorySections->isNotEmpty())
            @foreach ($categorySections as $categorySection)
                <section class="category-section" id="category-{{ $categorySection['slug'] }}" aria-labelledby="category-heading-{{ $categorySection['slug'] }}">
                    <h3 class="category-heading" id="category-heading-{{ $categorySection['slug'] }}">{{ $categorySection['name'] }}</h3>
                    @if ($categorySection['products']->isNotEmpty())
                    <div class="product-grid">
                        @foreach($categorySection['products'] as $product)
                            @php
                                $productPhoto = $product->image
                                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->image)
                                    : null;
                            @endphp
                            <article class="product-card">
                                <a class="product-visual product-visual-{{ $loop->iteration % 3 }}" href="{{ route('product.show', $product) }}" aria-label="View {{ $product->name }}">
                                    @if ($productPhoto)<img src="{{ $productPhoto }}" alt="{{ $product->name }}">@else<span class="product-image-placeholder" aria-hidden="true">{{ $product->category?->name ?? $product->name }}</span>@endif
                                    @if ($product->category)<span class="product-category">{{ $product->category->name }}</span>@endif
                                </a>
                                <div class="product-info">
                                    <div><h3><a href="{{ route('product.show', $product) }}">{{ $product->name }}</a></h3><p class="product-subtitle">{{ \Illuminate\Support\Str::limit($product->description ?: 'An everyday favourite.', 66) }}</p></div>
                                    @if ($product->hasActiveDiscount())
                                        <p class="product-price product-price-sale"><s>৳{{ number_format($product->price, 0) }}</s><strong>৳{{ number_format($product->sale_price, 0) }}</strong></p>
                                    @else
                                        <p class="product-price">৳{{ number_format($product->price, 0) }}</p>
                                    @endif
                                </div>
                                <a class="product-link" href="{{ route('product.show', $product) }}">Discover this piece <span aria-hidden="true">↗</span></a>
                            </article>
                        @endforeach
                    </div>
                    @else
                        <p class="category-empty">New products in {{ $categorySection['name'] }} are coming soon.</p>
                    @endif
                </section>
            @endforeach
        @else
            <div class="empty-collection"><h3>New favourites are on their way.</h3><p>Check back soon to see what’s in the collection.</p></div>
        @endif
    </section>

    @if ($discountedProducts->isNotEmpty())
        <section class="offers-section" id="offers" aria-labelledby="offers-title">
            <div class="section-heading offers-heading"><div><span class="eyebrow">LIMITED TIME</span><h2 id="offers-title">Special offers<span class="heading-period">.</span></h2></div><span class="product-count">{{ str_pad((string) $discountedProducts->count(), 2, '0', STR_PAD_LEFT) }} ON SALE</span></div>
            <div class="product-grid">
                @foreach ($discountedProducts as $product)
                    <article class="product-card offer-product-card">
                        <a class="product-visual" href="{{ route('product.show', $product) }}" aria-label="View {{ $product->name }}">
                            @if ($product->image)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image) }}" alt="{{ $product->name }}">@else<span class="offer-product-placeholder" aria-hidden="true">{{ $product->category?->name ?? $product->name }}</span>@endif
                            <span class="sale-badge">SALE</span>
                        </a>
                        <div class="product-info"><div><h3><a href="{{ route('product.show', $product) }}">{{ $product->name }}</a></h3><p class="product-subtitle">{{ $product->category?->name ?? 'Special offer' }}</p></div><p class="product-price product-price-sale"><s>৳{{ number_format($product->price, 0) }}</s><strong>৳{{ number_format($product->sale_price, 0) }}</strong></p></div>
                        @if ($product->sale_ends_at)<p class="sale-expiry">Offer ends {{ $product->sale_ends_at->format('M j, Y · g:i A') }}</p>@else<p class="sale-expiry">Limited time offer</p>@endif
                        <a class="product-link" href="{{ route('product.show', $product) }}">Shop this offer <span aria-hidden="true">↗</span></a>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="contact-section" id="contact" aria-labelledby="contact-heading">
        <div class="contact-intro">
            <span class="eyebrow">HERE WHEN YOU NEED US</span>
            <h2 id="contact-heading">Contact the store<span class="heading-period">.</span></h2>
            <p>Ask a question, share a suggestion or complaint, or tell us what product you’d like to see next.</p>
            @if ($ownerName)<p class="contact-owner">{{ $ownerName }}<span>Store owner</span></p>@endif
            <div class="contact-direct-links">
                @if ($contactPhone)<a href="tel:{{ preg_replace('/[^+0-9]/', '', $contactPhone) }}">Call {{ $contactPhone }}</a>@endif
                @if ($contactWhatsapp)<a href="https://wa.me/{{ $whatsappNumber }}" target="_blank" rel="noopener">WhatsApp ↗</a>@endif
                @if ($storeSettings?->email)<a href="mailto:{{ $storeSettings->email }}">{{ $storeSettings->email }}</a>@endif
            </div>
        </div>
        <div class="contact-form-wrap">
            @if (session('inquiry_submitted'))<p class="inquiry-success" role="status">{{ session('inquiry_submitted') }}</p>@endif
            @if ($errors->any())
                <div class="form-errors" role="alert"><strong>Please check your message.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form class="inquiry-form" method="POST" action="{{ route('inquiries.store') }}">
                @csrf
                <div class="honeypot" aria-hidden="true"><label for="inquiry_website">Leave this field empty<input id="inquiry_website" name="website" type="text" tabindex="-1" autocomplete="off"></label></div>
                <label for="inquiry_name">Your name<input id="inquiry_name" name="customer_name" value="{{ old('customer_name') }}" autocomplete="name" maxlength="120" required></label>
                <div class="inquiry-fields-row">
                    <label for="inquiry_email">Email<input id="inquiry_email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255"></label>
                    <label for="inquiry_phone">Phone<input id="inquiry_phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" maxlength="30"></label>
                </div>
                <label for="inquiry_type">What is this about?
                    <select id="inquiry_type" name="inquiry_type" required>
                        <option value="general" @selected(old('inquiry_type', 'general') === 'general')>General question</option>
                        <option value="complaint" @selected(old('inquiry_type') === 'complaint')>Complaint</option>
                        <option value="suggestion" @selected(old('inquiry_type') === 'suggestion')>Suggestion</option>
                        <option value="product_request" @selected(old('inquiry_type') === 'product_request')>Request a product</option>
                    </select>
                </label>
                <label for="inquiry_product" id="inquiry-product-field" @if (old('inquiry_type') !== 'product_request') hidden @endif>Product you want to see <span>(for product requests)</span><input id="inquiry_product" name="product_name" value="{{ old('product_name') }}" maxlength="160" placeholder="Product name or a short description"></label>
                <label for="inquiry_message">Your message<textarea id="inquiry_message" name="message" rows="4" maxlength="3000" required>{{ old('message') }}</textarea></label>
                <button class="button button-green inquiry-submit" type="submit">Send message <span aria-hidden="true">→</span></button>
                <p class="inquiry-privacy">Please provide an email or phone so the owner can reply.</p>
            </form>
        </div>
    </section>
@endsection
