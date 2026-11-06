<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f8f5ee">
    <title>{{ $storeSettings?->store_name ?? 'F-commerce' }} — Good things for every day</title>
    <x-metapixel-head />
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}?v={{ filemtime(public_path('css/storefront.css')) }}">
</head>
<body class="storefront font-theme-{{ in_array($storeSettings?->theme_font, ['editorial', 'modern', 'classic', 'friendly', 'system'], true) ? $storeSettings->theme_font : 'editorial' }}" style="--green: {{ $storeSettings?->theme_primary ?? '#294f3f' }}; --green-dark: {{ $storeSettings?->theme_dark ?? '#1f3c31' }}; --peach: {{ $storeSettings?->theme_accent ?? '#edb298' }}; --cream: {{ $storeSettings?->theme_background ?? '#f8f5ee' }}; --paper: {{ $storeSettings?->theme_surface ?? '#fffdf9' }}; --ink: {{ $storeSettings?->theme_text ?? '#24382f' }}">
    @php
        $storeLogoUrl = $storeSettings?->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($storeSettings->logo_path) : null;
        $footerWhatsapp = preg_replace('/\D+/', '', $storeSettings?->whatsapp ?? '');
        if (str_starts_with($footerWhatsapp, '0')) {
            $footerWhatsapp = '88'.substr($footerWhatsapp, 1);
        }
        $footerSocialLinks = [
            ['name' => 'Instagram', 'url' => $storeSettings?->instagram_url, 'icon' => 'instagram'],
            ['name' => 'TikTok', 'url' => $storeSettings?->tiktok_url, 'icon' => 'tiktok'],
            ['name' => 'WhatsApp', 'url' => $footerWhatsapp ? 'https://wa.me/'.$footerWhatsapp : null, 'icon' => 'whatsapp'],
            ['name' => 'Email', 'url' => $storeSettings?->email ? 'mailto:'.$storeSettings->email : null, 'icon' => 'email'],
        ];
    @endphp
    <x-metapixel-body />
    <div class="announcement" aria-label="Store announcements">
        <div class="announcement-track">
            @foreach (array_merge($storeAnnouncements, $storeAnnouncements) as $announcement)
                <span class="announcement-item">{{ $announcement }}</span><span class="announcement-star" aria-hidden="true">✳</span>
            @endforeach
        </div>
    </div>
    <header class="site-header">
        <div class="header-inner">
            <a class="wordmark" href="{{ route('shop') }}" aria-label="{{ $storeSettings?->store_name ?? 'F-commerce' }} home">
                @if ($storeLogoUrl)
                    <img class="store-logo" src="{{ $storeLogoUrl }}" alt="{{ $storeSettings?->store_name ?? 'Store' }}">
                @else
                    <span class="wordmark-icon">f</span><span>{{ $storeSettings?->store_name ?? 'f-commerce' }}<span class="wordmark-period">.</span></span>
                @endif
            </a>
            <nav class="main-nav" aria-label="Main navigation">
                <a class="nav-active" href="{{ route('shop') }}">Home</a>
                @if ($storeProducts->count() === 1)
                    <a href="{{ route('product.show', $storeProducts->first()) }}">Products</a>
                @elseif ($storeProducts->count() > 1)
                    <details class="product-nav-dropdown">
                        <summary>Products</summary>
                        <div class="product-nav-menu" aria-label="Product categories">
                            <a class="product-nav-all" href="{{ route('shop') }}#collection"><span>Browse the collection</span><span aria-hidden="true">↗</span></a>
                            <span class="product-nav-menu-heading">Shop by category</span>
                            @forelse ($storeCategories as $storeCategory)
                                <a class="product-nav-category" href="{{ route('shop') }}#category-{{ \Illuminate\Support\Str::slug($storeCategory->name) }}-{{ $storeCategory->id }}"><span>{{ $storeCategory->name }}</span><span aria-hidden="true">→</span></a>
                            @empty
                                @foreach ($storeProducts as $storeProduct)
                                    <a class="product-nav-category" href="{{ route('product.show', $storeProduct) }}"><span>{{ $storeProduct->name }}</span><span aria-hidden="true">→</span></a>
                                @endforeach
                            @endforelse
                        </div>
                    </details>
                @endif
                <a href="{{ route('shop') }}#contact">Contact us</a>
                @auth
                    <a href="{{ route('account.dashboard') }}">My account</a>
                    <form class="nav-logout" method="POST" action="{{ route('customer.logout') }}">
                        @csrf
                        <button type="submit">Log out</button>
                    </form>
                @else
                    <a href="{{ route('customer.login') }}">Login</a>
                    <a href="{{ route('customer.register') }}">Register</a>
                @endauth
                <a href="{{ route('filament.bikrify.auth.login') }}">Admin</a>
            </nav>
        </div>
    </header>
    <main>
        @yield('content')
    </main>
    <footer class="site-footer">
        <a class="footer-brand" href="{{ route('shop') }}" aria-label="{{ $storeSettings?->store_name ?? 'F-commerce' }} home">
            @if ($storeLogoUrl)
                <img class="footer-logo" src="{{ $storeLogoUrl }}" alt="{{ $storeSettings?->store_name ?? 'Store' }}">
            @else
                <span class="footer-logo-fallback"><span class="wordmark-icon">f</span><span>{{ $storeSettings?->store_name ?? 'f-commerce' }}<span>.</span></span></span>
            @endif
        </a>
        <nav class="footer-socials" aria-label="Store social media">
            @foreach ($footerSocialLinks as $socialLink)
                @if ($socialLink['url'])<a href="{{ $socialLink['url'] }}" @if (in_array($socialLink['icon'], ['instagram', 'tiktok', 'whatsapp'])) target="_blank" rel="noopener noreferrer" @endif aria-label="{{ $socialLink['name'] }}">@else<span class="social-link-inactive" title="Add the store {{ $socialLink['name'] }} details in Store Settings">@endif
                @switch($socialLink['icon'])
                    @case('instagram')<svg class="social-icon-instagram" viewBox="0 0 24 24" aria-hidden="true"><defs><linearGradient id="instagramGradient" x1="0" y1="1" x2="1" y2="0"><stop stop-color="#ffd76a"/><stop offset=".48" stop-color="#f04467"/><stop offset="1" stop-color="#9146c8"/></linearGradient></defs><rect x="1.5" y="1.5" width="21" height="21" rx="6.2" fill="url(#instagramGradient)"/><rect x="5.5" y="5.5" width="13" height="13" rx="4" fill="none" stroke="white" stroke-width="1.8"/><circle cx="12" cy="12" r="3.1" fill="none" stroke="white" stroke-width="1.8"/><circle cx="17.1" cy="6.9" r="1.1" fill="white"/></svg>@break
                    @case('tiktok')<svg class="social-icon-tiktok" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3v11.2a4.2 4.2 0 1 1-3.4-4.1M14 3c.5 3.2 2.5 5.1 5.5 5.4" fill="none" stroke="#25f4ee" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" transform="translate(-1 0)"/><path d="M14 3v11.2a4.2 4.2 0 1 1-3.4-4.1M14 3c.5 3.2 2.5 5.1 5.5 5.4" fill="none" stroke="#fe2c55" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" transform="translate(1 0)"/><path d="M14 3v11.2a4.2 4.2 0 1 1-3.4-4.1M14 3c.5 3.2 2.5 5.1 5.5 5.4" fill="none" stroke="white" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/></svg>@break
                    @case('whatsapp')<svg class="social-icon-whatsapp" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.2a9.5 9.5 0 0 0-8.1 14.5L2.7 22l5.5-1.2A9.5 9.5 0 1 0 12 2.2Z" fill="#25d366"/><path d="M8.2 7.3c.3-.6.6-.6 1-.6h.5c.2 0 .4.1.5.4l.8 1.9c.1.3.1.5-.1.7l-.6.8c-.2.2-.2.4 0 .7.4.7 1.1 1.5 2 2 .3.2.5.2.7-.1l.9-1c.2-.2.4-.3.7-.2l1.8.9c.3.1.4.3.4.5 0 .3-.2 1.3-.8 1.8-.6.5-1.3.7-2.1.5-1.3-.3-2.8-1.1-4.2-2.4-1.2-1.1-2.2-2.6-2.5-3.8-.3-1.2.2-1.9.7-2.1Z" fill="white"/></svg>@break
                    @default<svg class="social-icon-email" viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="4.5" width="20" height="15" rx="2"/><path d="m3 6 9 7 9-7"/></svg>
                @endswitch
                {{ $socialLink['name'] }}
                @if ($socialLink['url'])</a>@else</span>@endif
            @endforeach
        </nav>
        <span class="footer-copyright">© {{ date('Y') }} {{ config('storefront.copyright', 'Bikrify') }}. All rights reserved.</span>
    </footer>
    <script defer src="{{ asset('js/storefront.js') }}?v={{ filemtime(public_path('js/storefront.js')) }}"></script>
</body>
</html>
