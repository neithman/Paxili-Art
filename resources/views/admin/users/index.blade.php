@extends('layouts.app')
@section('title', 'Users')
@section('subnav') @include('partials.admin-nav') @endsection

@section('content')
<div class="container page">
    <div class="page-head">
        <h1>Users</h1>
        <form method="GET" class="flex">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name or email" style="width:240px">
            <select name="role" style="width:auto">
                <option value="">All roles</option>
                <option value="seller" @selected(request('role') === 'seller')>Sellers</option>
                <option value="admin" @selected(request('role') === 'admin')>Admins</option>
            </select>
            <button class="btn btn-sm" type="submit">Search</button>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Roles</th><th>Store</th><th>Paid orders</th><th>Joined</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            @forelse ($users as $user)
                <tr>
                    <td><strong>{{ $user->name }}</strong></td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @if ($user->is_admin)<span class="badge badge-admin">Admin</span>@endif
                        <span class="badge">Buyer</span>
                        @if ($user->store)<span class="badge badge-seller">Seller</span>@endif
                    </td>
                    <td>
                        @if ($user->store)
                            <a href="{{ route('stores.show', $user->store) }}" target="_blank">{{ $user->store->name }}</a>
                        @else — @endif
                    </td>
                    <td>{{ $user->orders_count }}</td>
                    <td>{{ $user->created_at->format('M d, Y') }}</td>
                    <td class="text-right">
                        <div class="flex" style="justify-content:flex-end">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-dark btn-sm">Edit</a>
                            @if ($user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Delete {{ $user->name }}? Their store, artwork and orders will be deleted too.">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center muted">No users found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
</div>
@endsection
