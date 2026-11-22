<footer class="site-footer">
    <div class="container">
        <div class="cols">
            <div>
                <img src="{{ asset('images/logo.png') }}" alt="PaxiliArt">
                <p>A marketplace for original digital art. Buy from independent artists, or open your own store with the same account.</p>
            </div>
            <div>
                <h4>Explore</h4>
                <a href="{{ route('marketplace') }}">Marketplace</a>
                <a href="{{ route('marketplace', ['sort' => 'newest']) }}">New arrivals</a>
                @auth
                    <a href="{{ route('purchases.index') }}">My purchases</a>
                @endauth
            </div>
            <div>
                <h4>Sell</h4>
                @auth
                    <a href="{{ auth()->user()->store ? route('seller.dashboard') : route('store.create') }}">
                        {{ auth()->user()->store ? 'Seller dashboard' : 'Open a store' }}
                    </a>
                @else
                    <a href="{{ route('register') }}">Become a seller</a>
                @endauth
            </div>
        </div>
        <div class="copy">&copy; {{ date('Y') }} PaxiliArt. All artwork belongs to its creators.</div>
    </div>
</footer>
