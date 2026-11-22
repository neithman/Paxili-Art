{{-- Used for BOTH "upload artwork" and "edit artwork" --}}
@extends('layouts.app')
@php $editing = $artwork->exists; @endphp
@section('title', $editing ? 'Edit artwork' : 'Upload artwork')
@section('subnav') @include('partials.seller-nav') @endsection

@section('content')
<div class="container container-md page">
    <h1>{{ $editing ? 'Edit artwork' : 'Upload new artwork' }}</h1>

    <div class="alert alert-info" style="margin-bottom:20px">
        <span>
            @if ($editing)
                Saving changes sends this artwork back to <strong>Pending Approval</strong> until an admin reviews it again.
            @else
                New artwork starts as <strong>Pending Approval</strong> and appears in the marketplace after an admin approves it.
            @endif
        </span>
    </div>

    @if ($editing && $artwork->status === 'rejected' && $artwork->rejection_reason)
        <div class="alert alert-error" style="margin:0 0 20px"><span><strong>Rejected:</strong> {{ $artwork->rejection_reason }}</span></div>
    @endif

    <form method="POST" enctype="multipart/form-data" class="card"
          action="{{ $editing ? route('seller.artworks.update', $artwork) : route('seller.artworks.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" value="{{ old('title', $artwork->title) }}" required maxlength="150"
                   class="@error('title') is-invalid @enderror">
            @error('title')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" placeholder="Size, technique, what's included…">{{ old('description', $artwork->description) }}</textarea>
            @error('description')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <option value="">— Choose —</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $artwork->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id')<div class="error-text">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="price">Price *</label>
                <div class="input-prefix">
                    <span>{{ config('app.currency') }}</span>
                    <input type="number" id="price" name="price" step="0.01" min="1" value="{{ old('price', $artwork->price) }}" required
                           class="@error('price') is-invalid @enderror">
                </div>
                @error('price')<div class="error-text">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="form-group">
            <label for="tags">Tags</label>
            <input type="text" id="tags" name="tags" value="{{ old('tags', $artwork->tags) }}" placeholder="landscape, sunset, pastel">
            <div class="hint">Separate tags with commas. Tags help buyers find your work.</div>
        </div>

        <hr>

        <div class="grid-2">
            <div class="form-group">
                <label for="preview">Preview image {{ $editing ? '' : '*' }}</label>
                <input type="file" id="preview" name="preview" accept="image/jpeg,image/png,image/webp" data-preview="#previewImg" @unless($editing) required @endunless>
                <div class="hint">PUBLIC – shown in the marketplace. JPG/PNG/WEBP, max 5 MB. Tip: use a smaller or watermarked version.</div>
                @error('preview')<div class="error-text">{{ $message }}</div>@enderror
                <img id="previewImg" class="img-preview" alt="" src="{{ $editing ? $artwork->previewUrl() : '' }}" @unless($editing) hidden @endunless>
            </div>
            <div class="form-group">
                <label for="file">Original file {{ $editing ? '' : '*' }}</label>
                <input type="file" id="file" name="file" @unless($editing) required @endunless>
                <div class="hint">🔒 PRIVATE – only paying buyers can download it (once). JPG, PNG, WEBP, GIF, TIFF, PSD, PDF or ZIP, max 50 MB.</div>
                @if ($editing)<div class="hint">Current file: <strong>{{ $artwork->file_name }}</strong> (leave empty to keep it)</div>@endif
                @error('file')<div class="error-text">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="flex mt-2">
            <button type="submit" class="btn">{{ $editing ? 'Save & resubmit' : 'Upload & submit for approval' }}</button>
            <a href="{{ route('seller.artworks.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
@endsection
