@extends('layouts.app')
@section('title', 'Categories')
@section('subnav') @include('partials.admin-nav') @endsection

@section('content')
<div class="container container-md page">
    <h1>Categories</h1>

    <form method="POST" action="{{ route('admin.categories.store') }}" class="card flex" style="flex-wrap:nowrap">
        @csrf
        <input type="text" name="name" placeholder="New category name" required maxlength="60">
        <button type="submit" class="btn">Add</button>
    </form>

    <div class="table-wrap mt-3">
        <table>
            <thead><tr><th>Name</th><th>Artwork</th><th></th></tr></thead>
            <tbody>
            @forelse ($categories as $category)
                <tr>
                    <td><strong>{{ $category->name }}</strong></td>
                    <td>{{ $category->artworks_count }}</td>
                    <td class="text-right">
                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" data-confirm="Delete category {{ $category->name }}? Its artwork becomes uncategorized.">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center muted">No categories yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
