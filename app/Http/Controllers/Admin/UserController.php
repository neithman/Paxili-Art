<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('store')
            ->withCount(['orders' => fn ($q) => $q->where('status', 'paid')])
            ->when($request->q, fn ($q, $search) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($request->role === 'admin', fn ($q) => $q->where('is_admin', true))
            ->when($request->role === 'seller', fn ($q) => $q->has('store'))
            ->latest()
            ->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function edit(User $user)
    {
        $user->load('store')->loadCount(['orders', 'downloads', 'reviews']);

        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        $makeAdmin = $request->boolean('is_admin');
        if ($user->id === $request->user()->id && ! $makeAdmin) {
            return back()->with('error', 'You cannot remove your own admin access.');
        }

        $user->fill(['name' => $data['name'], 'email' => $data['email']]);
        $user->is_admin = $makeAdmin; // not mass-assignable on purpose
        $user->save();

        return back()->with('success', 'User updated.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate(['password' => ['required', 'confirmed', 'min:8']]);

        $user->password = $request->password; // hashed automatically
        $user->save();

        Notification::send($user, 'password_reset', 'Password changed',
            'An administrator reset your password. Contact support if you did not request this.');

        return back()->with('success', "Password for {$user->name} has been reset.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete(); // related store, cart, orders… are removed by the foreign keys

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
