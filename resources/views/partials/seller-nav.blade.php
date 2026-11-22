<div class="subnav">
    <div class="container">
        <span class="subnav-title">Seller</span>
        <a href="{{ route('seller.dashboard') }}" class="{{ request()->routeIs('seller.dashboard') ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('seller.artworks.index') }}" class="{{ request()->routeIs('seller.artworks.index', 'seller.artworks.edit') ? 'active' : '' }}">My Artwork</a>
        <a href="{{ route('seller.artworks.create') }}" class="{{ request()->routeIs('seller.artworks.create') ? 'active' : '' }}">+ Upload</a>
        <a href="{{ route('seller.sales') }}" class="{{ request()->routeIs('seller.sales') ? 'active' : '' }}">Sales & Downloads</a>
        <a href="{{ route('store.edit') }}" class="{{ request()->routeIs('store.edit') ? 'active' : '' }}">Store Settings</a>
        @if(auth()->user()->store)
            <a href="{{ route('stores.show', auth()->user()->store) }}">View Store ↗</a>
        @endif
    </div>
</div>
