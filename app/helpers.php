<?php

/*
|--------------------------------------------------------------------------
| Small helper functions you can use anywhere (controllers AND Blade views).
| Loaded automatically through "autoload.files" in composer.json.
|--------------------------------------------------------------------------
*/

if (! function_exists('money')) {
    /** money(1500) -> "₱1,500.00"  (change CURRENCY_SYMBOL in .env) */
    function money($amount): string
    {
        return config('app.currency', '₱') . number_format((float) $amount, 2);
    }
}

if (! function_exists('status_badge')) {
    /** Returns a colored <span> for any status: pending, approved, paid, failed... */
    function status_badge(?string $status): string
    {
        $status = $status ?: 'unknown';

        return '<span class="badge badge-' . e($status) . '">' . e(ucfirst($status)) . '</span>';
    }
}
