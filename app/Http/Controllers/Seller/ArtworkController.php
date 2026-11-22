<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Artwork;
use App\Models\Category;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Seller product management (CRUD). A seller can ONLY touch artwork of their own store.
 *
 * Where files go:
 *   preview image  -> public/storage/previews        (public, shown in the shop)
 *   original file  -> storage/app/private/artworks  (PRIVATE, only sent after payment)
 */
class ArtworkController extends Controller
{
    public function index(Request $request)
    {
        $artworks = $request->user()->store->artworks()
            ->with('category')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate(10)->withQueryString();

        return view('seller.artworks.index', compact('artworks'));
    }

    public function create()
    {
        return view('seller.artworks.form', [
            'artwork' => new Artwork(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateArtwork($request, true);
        $store = $request->user()->store;

        $artwork = $store->artworks()->create($data + [
            'slug' => Str::slug($data['title']) . '-' . Str::lower(Str::random(6)),
            'preview_path' => $request->file('preview')->store('previews', 'public'),
            'file_path' => $request->file('file')->store('artworks', 'local'),
            'file_name' => $request->file('file')->getClientOriginalName(),
            'status' => Artwork::PENDING, // always starts as pending
        ]);

        Notification::toAdmins('new_artwork', 'New artwork to review',
            "{$store->name} submitted \"{$artwork->title}\".",
            route('admin.artworks.show', $artwork));

        return redirect()->route('seller.artworks.index')
            ->with('success', 'Artwork uploaded! It will appear in the marketplace once an admin approves it.');
    }

    public function edit(Request $request, Artwork $artwork)
    {
        $this->ensureOwner($request, $artwork);

        return view('seller.artworks.form', [
            'artwork' => $artwork,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Artwork $artwork)
    {
        $this->ensureOwner($request, $artwork);
        $data = $this->validateArtwork($request, false);

        if ($request->hasFile('preview')) {
            Storage::disk('public')->delete($artwork->preview_path);
            $data['preview_path'] = $request->file('preview')->store('previews', 'public');
        }
        if ($request->hasFile('file')) {
            Storage::disk('local')->delete($artwork->file_path);
            $data['file_path'] = $request->file('file')->store('artworks', 'local');
            $data['file_name'] = $request->file('file')->getClientOriginalName();
        }

        // Any change must be checked again by an admin.
        $data['status'] = Artwork::PENDING;
        $data['rejection_reason'] = null;
        $artwork->update($data);

        Notification::toAdmins('new_artwork', 'Artwork updated – needs review',
            "{$artwork->store->name} updated \"{$artwork->title}\".",
            route('admin.artworks.show', $artwork));

        return redirect()->route('seller.artworks.index')
            ->with('success', 'Artwork updated and sent for approval again.');
    }

    public function destroy(Request $request, Artwork $artwork)
    {
        $this->ensureOwner($request, $artwork);

        if ($artwork->orderItems()->exists()) {
            // Someone bought it: hide it (soft delete) but KEEP the file for the buyers.
            $artwork->delete();
        } else {
            // Never sold: remove everything.
            Storage::disk('public')->delete($artwork->preview_path);
            Storage::disk('local')->delete($artwork->file_path);
            $artwork->forceDelete();
        }

        return redirect()->route('seller.artworks.index')->with('success', 'Artwork deleted.');
    }

    /** Rule: sellers can only manage their OWN products. */
    private function ensureOwner(Request $request, Artwork $artwork): void
    {
        abort_unless($artwork->store_id === $request->user()->store->id, 403, 'This is not your artwork.');
        abort_if($artwork->status === Artwork::REMOVED, 403, 'This artwork was removed by an admin.');
    }

    private function validateArtwork(Request $request, bool $creating): array
    {
        $required = $creating ? 'required' : 'nullable';

        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tags' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:1', 'max:999999'],
            'preview' => [$required, 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],               // 5 MB
            'file' => [$required, 'file', 'mimes:jpg,jpeg,png,webp,gif,tif,tiff,psd,pdf,zip', 'max:51200'], // 50 MB
        ], [
            'preview.required' => 'Please upload a preview image (this is what buyers see).',
            'file.required' => 'Please upload the original artwork file (buyers get this after paying).',
        ]);
    }
}
