@extends('layouts.shop')

@section('content')
    @php
        $galleryPaths = array_values(array_unique(array_filter(array_merge(
            $product->image ? [$product->image] : [],
            array_map(fn ($view) => is_array($view) ? ($view['image'] ?? null) : $view, $product->gallery ?? []),
        ))));
        $galleryPhotos = collect($galleryPaths)
            ->map(fn ($path) => \Illuminate\Support\Facades\Storage::disk('public')->url($path))
            ->values();
    @endphp
    <section class="content-shell">
        <a class="back-link" href="{{ route('shop') }}">← Back to the collection</a>
        <article class="detail-layout">
            <div class="product-gallery" data-product-gallery aria-label="{{ $product->name }} photos">
                <div class="detail-visual">
                    @if ($galleryPhotos->isNotEmpty())
                        <img src="{{ $galleryPhotos->first() }}" alt="{{ $product->name }} view 1" data-gallery-image>
                        @if ($galleryPhotos->count() > 1)
                            <button class="gallery-arrow gallery-previous" type="button" data-gallery-previous aria-label="Previous product photo">←</button>
                            <button class="gallery-arrow gallery-next" type="button" data-gallery-next aria-label="Next product photo">→</button>
                            <span class="gallery-counter" data-gallery-counter aria-live="polite">1 / {{ $galleryPhotos->count() }}</span>
                        @endif
                    @else
                        <span class="product-image-placeholder detail-placeholder" aria-hidden="true">{{ $product->category?->name ?? $product->name }}</span>
                    @endif
                </div>
                @if ($galleryPhotos->count() > 1)
                    <div class="gallery-thumbnails" role="group" aria-label="Choose a product photo">
                        @foreach ($galleryPhotos as $galleryPhoto)
                            <button class="gallery-thumbnail {{ $loop->first ? 'is-active' : '' }}" type="button" data-gallery-thumb data-gallery-src="{{ $galleryPhoto }}" data-gallery-alt="{{ $product->name }} view {{ $loop->iteration }}" aria-label="Show {{ $product->name }} photo {{ $loop->iteration }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                                <img src="{{ $galleryPhoto }}" alt="">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="detail-copy">
                <span class="eyebrow">{{ $product->category?->name ?? 'THE EVERYDAY EDIT' }}</span>
                <h2>{{ $product->name }}</h2>
                @if ($product->hasActiveDiscount())
                    <p class="detail-price detail-price-sale"><s>৳{{ number_format($product->price, 0) }}</s><strong>৳{{ number_format($product->sale_price, 0) }}</strong></p>
                    @if ($product->sale_ends_at)<p class="sale-expiry">Offer ends {{ $product->sale_ends_at->format('M j, Y · g:i A') }}</p>@endif
                @else
                    <p class="detail-price">৳{{ number_format($product->price, 0) }}</p>
                @endif
                <div class="detail-rule"></div>
                <p class="detail-description">{{ $product->description ?: 'A thoughtfully picked everyday favourite, ready to make its way to you.' }}</p>
                @if (!$product->variants && $product->stock !== null)
                    <div class="product-availability">
                        <h3>QTY (available)</h3>
                        <p class="total-stock" role="status">{{ $product->stock }}</p>
                    </div>
                @endif
                @if ($product->variants)
                    @php
                        $variantLabel = $product->product_kind === 'watch' ? 'Color' : (in_array($product->product_kind, ['shirt', 'pant'], true) ? 'Size' : 'Option');
                        $hasAvailableVariant = collect($product->variants)->contains(function ($variant) use ($product): bool {
                            $inventory = $product->productVariants->firstWhere('value', $variant['value'] ?? '');
                            $stock = $inventory?->stock ?? $product->stock;

                            return $stock === null || $stock > 0;
                        });
                        $measurementColumns = match ($product->product_kind) {
                            'shirt' => ['chest_cm' => 'Chest (cm)', 'body_length_cm' => 'Length (cm)', 'shoulder_cm' => 'Shoulder (cm)', 'sleeve_cm' => 'Sleeve (cm)'],
                            'pant' => ['waist_cm' => 'Waist (cm)', 'hip_cm' => 'Hip (cm)', 'outseam_cm' => 'Pant length (cm)', 'inseam_cm' => 'Inseam (cm)'],
                            default => [],
                        };
                        $chartVariants = collect($product->variants)->filter(fn ($variant) => collect($measurementColumns)->keys()->contains(fn ($key) => filled($variant[$key] ?? null)));
                    @endphp
                    <div class="product-availability">
                        <h3>AVAILABLE {{ strtoupper($variantLabel).'S' }}</h3>
                        @foreach ($product->variants as $variant)
                            @php($inventory = $product->productVariants->firstWhere('value', $variant['value'] ?? ''))
                            @php($availableStock = $inventory?->stock ?? $product->stock)
                            <p>{{ $variant['value'] }} · {{ $availableStock === null ? 'Stock not tracked' : number_format($availableStock).' available'.($inventory?->stock === null && $product->stock !== null ? ' across options' : '') }}</p>
                        @endforeach
                    </div>
                    @if ($chartVariants->isNotEmpty())
                        <div class="size-chart">
                            <h3>{{ $product->product_kind === 'shirt' ? 'Shirt size chart' : 'Pant size chart' }}</h3>
                            <div class="size-chart-scroll"><table><thead><tr><th>Size</th>@foreach ($measurementColumns as $key => $label)<th>{{ $label }}</th>@endforeach</tr></thead><tbody>
                                @foreach ($chartVariants as $variant)
                                    <tr><th>{{ $variant['value'] ?? '' }}</th>@foreach ($measurementColumns as $key => $label)<td>{{ filled($variant[$key] ?? null) ? $variant[$key] : '—' }}</td>@endforeach</tr>
                                @endforeach
                            </tbody></table></div>
                        </div>
                    @endif
                @endif
                @if (!$product->variants || $hasAvailableVariant)
                    <a class="button button-green" href="{{ route('checkout', $product) }}">Order with cash on delivery <span aria-hidden="true">→</span></a>
                @else
                    <p class="product-sold-out" role="status">Currently sold out</p>
                @endif
                <p class="detail-reassurance"><span>✳</span> Pay when your order arrives.</p>
            </div>
        </article>
    </section>
@endsection
