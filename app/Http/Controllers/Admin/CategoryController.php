<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('artworks')->orderBy('name')->get();

        return view('admin.categories', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => ['required', 'string', 'max:60', 'unique:categories,name']]);

        Category::create(['name' => $request->name, 'slug' => Str::slug($request->name)]);

        return back()->with('success', 'Category added.');
    }

    public function destroy(Category $category)
    {
        $category->delete(); // artworks in it simply become "uncategorized"

        return back()->with('success', 'Category deleted.');
    }
}
