<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Public store page + "create / edit my store".
 * Creating a store is what turns a normal user into a seller.
 */
class StoreController extends Controller
{
    /** Public store page: /store/{slug} */
    public function show(Store $store)
    {
        $artworks = $store->artworks()->approved()->latest()->paginate(12);

        return view('stores.show', compact('store', 'artworks'));
    }

    public function create(Request $request)
    {
        if ($request->user()->store) {
            return redirect()->route('store.edit');
        }

        return view('stores.form', ['store' => new Store()]);
    }

    public function store(Request $request)
    {
        abort_if($request->user()->store, 403, 'You already have a store.');

        $data = $this->validateStore($request);
        $data['user_id'] = $request->user()->id;
        $data['logo'] = $request->file('logo')?->store('stores', 'public');
        $data['banner'] = $request->file('banner')?->store('stores', 'public');

        Store::create($data);

        return redirect()->route('seller.dashboard')->with('success', 'Your store is open! Upload your first artwork.');
    }

    public function edit(Request $request)
    {
        $store = $request->user()->store;
        if (! $store) {
            return redirect()->route('store.create');
        }

        return view('stores.form', compact('store'));
    }

    public function update(Request $request)
    {
        $store = $request->user()->store;
        abort_unless($store, 404);

        $data = $this->validateStore($request, $store);

        // Replace images only if new ones were uploaded
        foreach (['logo', 'banner'] as $field) {
            if ($request->hasFile($field)) {
                if ($store->$field) {
                    Storage::disk('public')->delete($store->$field);
                }
                $data[$field] = $request->file($field)->store('stores', 'public');
            } else {
                unset($data[$field]);
            }
        }

        $store->update($data);

        return redirect()->route('store.edit')->with('success', 'Store updated.');
    }

    private function validateStore(Request $request, ?Store $store = null): array
    {
        // If no URL name was typed, build one from the store name.
        if (! $request->filled('slug')) {
            $request->merge(['slug' => Str::slug($request->name)]);
        } else {
            $request->merge(['slug' => Str::slug($request->slug)]);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'alpha_dash', 'max:60', Rule::unique('stores', 'slug')->ignore($store?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'slug.unique' => 'That store URL is already taken. Try another one.',
        ]);
    }
}
