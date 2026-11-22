<div class="subnav">
    <div class="container">
        <span class="subnav-title">Admin</span>
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Analytics</a>
        <a href="{{ route('admin.artworks.index') }}" class="{{ request()->routeIs('admin.artworks.*') ? 'active' : '' }}">Moderation</a>
        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Users</a>
        <a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">Orders & Payments</a>
        <a href="{{ route('admin.downloads.index') }}" class="{{ request()->routeIs('admin.downloads.*') ? 'active' : '' }}">Downloads</a>
        <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">Categories</a>
    </div>
</div>
