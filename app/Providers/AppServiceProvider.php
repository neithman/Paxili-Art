<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Use our own pagination design (resources/views/partials/pagination.blade.php)
        Paginator::defaultView('partials.pagination');

        // Windows fix for "php artisan serve": Laravel only passes a few env vars to the
        // server process and matches them case-sensitively. PowerShell spells it "SystemRoot"
        // (needed for networking), and TEMP/TMP are needed for file uploads.
        if ($this->app->runningInConsole() && PHP_OS_FAMILY === 'Windows') {
            array_push(\Illuminate\Foundation\Console\ServeCommand::$passthroughVariables,
                'SystemRoot', 'TEMP', 'TMP');
        }
    }
}
