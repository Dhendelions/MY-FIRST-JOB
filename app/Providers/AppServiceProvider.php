<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\FisheriesGroup;
use App\Models\SpreadsheetSheet;
use App\Policies\WorkspacePolicy;
use App\Policies\SpreadsheetPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(FisheriesGroup::class, WorkspacePolicy::class);
        Gate::policy(SpreadsheetSheet::class, SpreadsheetPolicy::class);
    }
}
