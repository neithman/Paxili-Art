{{-- Used for BOTH "create store" and "edit store" --}}
@extends('layouts.app')
@php $editing = $store->exists; @endphp
@section('title', $editing ? 'Store settings' : 'Open your store')

@section('subnav')
    @if ($editing) @include('partials.seller-nav') @endif
@endsection

@section('content')
<div class="container container-md page">
    <h1>{{ $editing ? 'Store settings' : 'Open your store ✨' }}</h1>
    @unless ($editing)
        <p class="muted">Opening a store unlocks seller features on your current account. You can still buy art as usual.</p>
    @endunless

    <form method="POST" action="{{ $editing ? route('store.update') : route('store.store') }}" enctype="multipart/form-data" class="card">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="form-group">
            <label for="name">Store name *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $store->name) }}" required maxlength="100"
                   class="@error('name') is-invalid @enderror">
            @error('name')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="slug">Store URL</label>
            <div class="input-prefix">
                <span>{{ url('/store') }}/</span>
                <input type="text" id="slug" name="slug" value="{{ old('slug', $store->slug) }}" placeholder="my-art-studio"
                       class="@error('slug') is-invalid @enderror">
            </div>
            <div class="hint">Letters, numbers and dashes only. Leave empty to create it from your store name.</div>
            @error('slug')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" placeholder="Tell buyers about you and your art…">{{ old('description', $store->description) }}</textarea>
            @error('description')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label for="logo">Logo</label>
                <input type="file" id="logo" name="logo" accept="image/*" data-preview="#logoPreview">
                <div class="hint">Square image, max 2 MB.</div>
                @error('logo')<div class="error-text">{{ $message }}</div>@enderror
                <img id="logoPreview" class="img-preview" src="{{ $store->logoUrl() }}" alt="" @unless($store->logo) hidden @endunless>
            </div>
            <div class="form-group">
                <label for="banner">Banner</label>
                <input type="file" id="banner" name="banner" accept="image/*" data-preview="#bannerPreview">
                <div class="hint">Wide image (e.g. 1600×400), max 4 MB.</div>
                @error('banner')<div class="error-text">{{ $message }}</div>@enderror
                <img id="bannerPreview" class="img-preview" src="{{ $store->bannerUrl() }}" alt="" @unless($store->banner) hidden @endunless>
            </div>
        </div>

        <button type="submit" class="btn">{{ $editing ? 'Save changes' : 'Create my store' }}</button>
    </form>
</div>
@endsection
