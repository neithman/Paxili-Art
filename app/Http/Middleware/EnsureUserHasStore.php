<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Seller pages need a store. If the user has none yet, send them to "Create store".
 * Registered as "seller" in bootstrap/app.php.
 */
class EnsureUserHasStore
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()->store) {
            return redirect()->route('store.create')
                ->with('info', 'Create your store first to start selling.');
        }

        return $next($request);
    }
}
