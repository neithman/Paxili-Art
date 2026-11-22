@php
    $user = auth()->user();
    $cartCount = $user ? $user->getCart()->items()->count() : 0;
    $unread = $user ? $user->unreadNotificationsCount() : 0;
@endphp

<header class="site-header">
    <div class="container nav">
        <a href="{{ route('home') }}" class="nav-logo">
            <img src="{{ asset('images/logo.png') }}" alt="PaxiliArt">
        </a>

        <form action="{{ route('marketplace') }}" method="GET" class="nav-search">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search artwork, tags…" aria-label="Search">
        </form>

        <button class="nav-toggle" aria-label="Menu"><span></span><span></span><span></span></button>

        <nav class="nav-links">
            <a href="{{ route('marketplace') }}" class="{{ request()->routeIs('marketplace') ? 'active' : '' }}">Marketplace</a>

            @guest
                <a href="{{ route('login') }}">Log in</a>
                <a href="{{ route('register') }}" class="btn btn-sm">Join free</a>
            @else
                <a href="{{ route('cart.index') }}" class="icon-btn" title="Cart">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
                    <span class="hide-desktop">Cart</span>
                    @if($cartCount)<span class="count">{{ $cartCount }}</span>@endif
                </a>

                <a href="{{ route('notifications.index') }}" class="icon-btn" title="Notifications">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                    <span class="hide-desktop">Notifications</span>
                    @if($unread)<span class="count">{{ $unread > 99 ? '99+' : $unread }}</span>@endif
                </a>

                <div class="dropdown">
                    <button type="button">{{ \Illuminate\Support\Str::limit($user->name, 14) }} ▾</button>
                    <div class="dropdown-menu">
                        <div class="menu-label">Buying</div>
                        <a href="{{ route('purchases.index') }}">My Purchases & Downloads</a>
                        <a href="{{ route('orders.index') }}">My Orders</a>
                        <hr>
                        <div class="menu-label">Selling</div>
                        @if($user->store)
                            <a href="{{ route('seller.dashboard') }}">Seller Dashboard</a>
                            <a href="{{ route('seller.artworks.create') }}">Upload Artwork</a>
                            <a href="{{ route('stores.show', $user->store) }}">View My Store</a>
                        @else
                            <a href="{{ route('store.create') }}">✨ Open your store</a>
                        @endif
                        @if($user->isAdmin())
                            <hr>
                            <a href="{{ route('admin.dashboard') }}"><strong>Admin Dashboard</strong></a>
                        @endif
                        <hr>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit">Log out</button>
                        </form>
                    </div>
                </div>
            @endguest
        </nav>
    </div>
</header>

<style>
    /* Text labels next to icons only show in the mobile menu */
    @media (min-width: 769px) { .hide-desktop { display: none; } }
</style>
